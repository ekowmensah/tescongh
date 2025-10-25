<?php
require_once 'config/hubtel.php';

/**
 * SMS Service using Hubtel SMS API
 * Handles sending SMS notifications to TESCON Ghana members
 */
class SMSService {
    private $clientId;
    private $clientSecret;
    private $senderId;
    private $apiUrl;

    public function __construct() {
        $this->clientId = HUBTEL_SMS_CLIENT_ID;
        $this->clientSecret = HUBTEL_SMS_CLIENT_SECRET;
        $this->senderId = HUBTEL_SMS_SENDER_ID;
        $this->apiUrl = HUBTEL_SMS_API_URL;
    }

    /**
     * Get access token for SMS API
     */
    private function getAccessToken() {
        $url = 'https://api.hubtel.com/v2/auth/token';
        $credentials = base64_encode($this->clientId . ':' . $this->clientSecret);

        $headers = [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['token'] ?? null;
        }

        return null;
    }

    /**
     * Send single SMS message
     * @param string $phoneNumber Recipient phone number (format: 233xxxxxxxxx)
     * @param string $message SMS content
     * @return array ['success' => bool, 'message_id' => string, 'error' => string]
     */
    public function sendSMS($phoneNumber, $message) {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to get access token'];
        }

        // Ensure phone number is in correct format (233xxxxxxxxx)
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        if (!$phoneNumber) {
            return ['success' => false, 'error' => 'Invalid phone number format'];
        }

        // Check message length
        if (strlen($message) > SMS_MAX_LENGTH) {
            return ['success' => false, 'error' => 'Message exceeds maximum length of ' . SMS_MAX_LENGTH . ' characters'];
        }

        $payload = [
            'from' => $this->senderId,
            'to' => $phoneNumber,
            'content' => $message,
            'registeredDelivery' => true
        ];

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 201) {
            $data = json_decode($response, true);
            return [
                'success' => true,
                'message_id' => $data['messageId'] ?? null,
                'status' => $data['status'] ?? 'sent'
            ];
        }

        $error = json_decode($response, true);
        return [
            'success' => false,
            'error' => $error['message'] ?? 'SMS sending failed',
            'response' => $response
        ];
    }

    /**
     * Send bulk SMS messages
     * @param array $recipients Array of ['phone' => string, 'message' => string] or just phone numbers for same message
     * @param string $message Optional message if all recipients get the same message
     * @return array Results for each recipient
     */
    public function sendBulkSMS($recipients, $message = null) {
        $results = [];

        foreach ($recipients as $recipient) {
            if (is_array($recipient) && isset($recipient['phone'])) {
                $phone = $recipient['phone'];
                $msg = $recipient['message'] ?? $message;
            } elseif (is_string($recipient)) {
                $phone = $recipient;
                $msg = $message;
            } else {
                continue;
            }

            if ($msg) {
                $result = $this->sendSMS($phone, $msg);
                $results[] = [
                    'phone' => $phone,
                    'success' => $result['success'],
                    'message_id' => $result['message_id'] ?? null,
                    'error' => $result['error'] ?? null
                ];
            }
        }

        return $results;
    }

    /**
     * Format phone number to Ghana format (233xxxxxxxxx)
     * @param string $phone Phone number in various formats
     * @return string|null Formatted phone number or null if invalid
     */
    private function formatPhoneNumber($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/\D/', '', $phone);

        // Handle different Ghana phone number formats
        if (preg_match('/^233\d{9}$/', $phone)) {
            // Already in correct format: 233xxxxxxxxx
            return $phone;
        } elseif (preg_match('/^0\d{9}$/', $phone)) {
            // Format: 0xxxxxxxxx -> 233xxxxxxxxx
            return '233' . substr($phone, 1);
        } elseif (preg_match('/^233\d{9}$/', $phone)) {
            // Already correct
            return $phone;
        } elseif (preg_match('/^\d{9}$/', $phone)) {
            // Format: xxxxxxxxx -> 233xxxxxxxxx
            return '233' . $phone;
        }

        return null; // Invalid format
    }

    /**
     * Check SMS delivery status
     * @param string $messageId Message ID from sendSMS response
     * @return array Status information
     */
    public function checkDeliveryStatus($messageId) {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to get access token'];
        }

        $url = 'https://api.hubtel.com/v2/messages/' . $messageId;

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return [
                'success' => true,
                'status' => $data['status'] ?? 'unknown',
                'delivered_at' => $data['deliveredAt'] ?? null,
                'network_code' => $data['networkCode'] ?? null
            ];
        }

        return ['success' => false, 'error' => 'Status check failed'];
    }

    /**
     * Calculate SMS cost estimate
     * @param int $messageCount Number of messages
     * @return float Estimated cost in GHS
     */
    public function calculateCost($messageCount) {
        return $messageCount * SMS_COST_PER_MESSAGE;
    }
}
?>
