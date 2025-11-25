<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'classes/HubtelSMS.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = isset($_POST['student_id']) ? sanitize($_POST['student_id']) : '';
    
    if (empty($student_id)) {
        $response['message'] = 'Student ID is required';
        echo json_encode($response);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        $response['message'] = 'Database connection failed';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Find member with unverified account
        $query = "SELECT m.id as member_id, m.fullname, m.phone, m.student_id, u.id as user_id
                  FROM members m
                  JOIN users u ON m.user_id = u.id
                  WHERE m.student_id = :student_id
                  AND NOT EXISTS (
                      SELECT 1 FROM verification_tokens vt 
                      WHERE vt.member_id = m.id 
                      AND vt.type = 'signup' 
                      AND vt.is_used = 1
                  )
                  LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            $response['message'] = 'No unverified account found with this Student ID. You may already be verified or the Student ID is incorrect.';
            echo json_encode($response);
            exit;
        }
        
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if there's a recent verification SMS sent (within last 5 minutes)
        $recentQuery = "SELECT id FROM verification_tokens 
                       WHERE member_id = :member_id 
                       AND type = 'signup'
                       AND is_used = 0
                       AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                       LIMIT 1";
        $recentStmt = $db->prepare($recentQuery);
        $recentStmt->bindParam(':member_id', $member['member_id']);
        $recentStmt->execute();
        
        if ($recentStmt->rowCount() > 0) {
            $response['message'] = 'A verification link was recently sent. Please check your phone or wait 5 minutes before requesting another.';
            echo json_encode($response);
            exit;
        }
        
        // Mark old tokens as expired (optional cleanup)
        $expireQuery = "UPDATE verification_tokens 
                       SET is_used = 1 
                       WHERE member_id = :member_id 
                       AND type = 'signup' 
                       AND is_used = 0";
        $expireStmt = $db->prepare($expireQuery);
        $expireStmt->bindParam(':member_id', $member['member_id']);
        $expireStmt->execute();
        
        // Generate new verification token (shorter for SMS)
        $token = bin2hex(random_bytes(16)); // 32 characters instead of 64
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Insert new verification token
        $insertQuery = "INSERT INTO verification_tokens (member_id, token, student_id, phone, type, expires_at) 
                       VALUES (:member_id, :token, :student_id, :phone, 'signup', :expires_at)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':member_id', $member['member_id']);
        $insertStmt->bindParam(':token', $token);
        $insertStmt->bindParam(':student_id', $member['student_id']);
        $insertStmt->bindParam(':phone', $member['phone']);
        $insertStmt->bindParam(':expires_at', $expires_at);
        $insertStmt->execute();
        
        // Send SMS
        $sms = new HubtelSMS(HUBTEL_SMS_CLIENT_ID, HUBTEL_SMS_CLIENT_SECRET, SMS_SENDER_ID);
        $verification_url = APP_URL . '/verify_account.php?token=' . $token;
        
        // Extract first name
        $firstName = explode(' ', $member['fullname'])[0];
        $message = "Hi {$firstName}, Your UEW-TESCON verification link: {$verification_url}";
        
        $smsResult = $sms->sendSimplePOST($member['phone'], $message);
        
        if ($smsResult['success']) {
            // Log SMS
            $message_id = isset($smsResult['data']['messageId']) ? $smsResult['data']['messageId'] : null;
            $logQuery = "INSERT INTO sms_logs (sender_id, recipient_phone, message, message_id, status) 
                        VALUES (:sender_id, :phone, :message, :message_id, 'sent')";
            $logStmt = $db->prepare($logQuery);
            $logStmt->bindParam(':sender_id', $member['user_id']);
            $logStmt->bindParam(':phone', $member['phone']);
            $logStmt->bindParam(':message', $message);
            $logStmt->bindParam(':message_id', $message_id);
            $logStmt->execute();
            
            $response['success'] = true;
            $response['message'] = "Verification link sent to {$member['phone']}. Please check your phone.";
        } else {
            $response['message'] = 'Failed to send SMS. Please try again or contact support.';
        }
        
    } catch (Exception $e) {
        $response['message'] = 'An error occurred. Please try again later.';
        error_log("Resend verification error: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
