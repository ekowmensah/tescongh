<?php
/**
 * Hubtel SMS API Examples
 * 
 * This file contains practical examples of how to use the Hubtel SMS API
 * for various SMS operations.
 * 
 * IMPORTANT: This is a demonstration file. Do not run in production without proper authentication.
 */

require_once '../config/config.php';
require_once '../classes/HubtelSMS.php';

// Check if running from command line or with proper authentication
$isCommandLine = php_sapi_name() === 'cli';
$isAuthenticated = isset($_SESSION['user_id']) && isset($_SESSION['role']);

if (!$isCommandLine && !$isAuthenticated) {
    die('Access denied. This file is for demonstration purposes only.');
}

// Initialize Hubtel SMS
$clientId = defined('HUBTEL_CLIENT_ID') ? HUBTEL_CLIENT_ID : 'YOUR_CLIENT_ID';
$clientSecret = defined('HUBTEL_CLIENT_SECRET') ? HUBTEL_CLIENT_SECRET : 'YOUR_CLIENT_SECRET';
$senderId = defined('SMS_SENDER_ID') ? SMS_SENDER_ID : 'TESCON-GH';

if (empty($clientId) || empty($clientSecret) || $clientId === 'YOUR_CLIENT_ID') {
    die("ERROR: Please configure your Hubtel credentials in config.php\n");
}

$hubtel = new HubtelSMS($clientId, $clientSecret, $senderId);

echo "=== HUBTEL SMS API EXAMPLES ===\n\n";

// ============================================================================
// EXAMPLE 1: Send Simple SMS using GET method
// ============================================================================
echo "1. SEND SIMPLE SMS (GET METHOD)\n";
echo str_repeat("-", 50) . "\n";

$testPhone = '0244123456'; // Replace with a test number
$testMessage = 'Hello from TESCON! This is a test message.';

echo "Sending to: $testPhone\n";
echo "Message: $testMessage\n\n";

// Uncomment to actually send
// $result = $hubtel->sendSimpleGET($testPhone, $testMessage);
// print_r($result);

echo "Example code:\n";
echo '$result = $hubtel->sendSimpleGET("0244123456", "Your message here");' . "\n";
echo "if (\$result['success']) {\n";
echo "    echo 'Message sent! ID: ' . \$result['data']['messageId'];\n";
echo "}\n\n";

// ============================================================================
// EXAMPLE 2: Send Simple SMS using POST method (Recommended)
// ============================================================================
echo "2. SEND SIMPLE SMS (POST METHOD - RECOMMENDED)\n";
echo str_repeat("-", 50) . "\n";

echo "Example code:\n";
echo '$result = $hubtel->sendSimplePOST("0244123456", "Your message here");' . "\n\n";

// Uncomment to actually send
// $result = $hubtel->sendSimplePOST($testPhone, $testMessage);
// print_r($result);

// ============================================================================
// EXAMPLE 3: Send Batch Simple Messages
// ============================================================================
echo "3. SEND BATCH SIMPLE MESSAGES\n";
echo str_repeat("-", 50) . "\n";

$recipients = [
    '0244123456',
    '0501234567',
    '0551234567'
];

$batchMessage = 'Dear TESCON member, this is a batch message to all recipients.';

echo "Sending to " . count($recipients) . " recipients\n";
echo "Message: $batchMessage\n\n";

echo "Example code:\n";
echo '$recipients = ["0244123456", "0501234567", "0551234567"];' . "\n";
echo '$result = $hubtel->sendBatchSimple($recipients, "Your message here");' . "\n";
echo "if (\$result['success']) {\n";
echo "    echo 'Batch sent! Batch ID: ' . \$result['data']['batchId'];\n";
echo "    foreach (\$result['data']['data'] as \$msg) {\n";
echo "        echo 'Message to ' . \$msg['recipient'] . ': ' . \$msg['messageId'];\n";
echo "    }\n";
echo "}\n\n";

// Uncomment to actually send
// $result = $hubtel->sendBatchSimple($recipients, $batchMessage);
// print_r($result);

// ============================================================================
// EXAMPLE 4: Send Batch Personalized Messages
// ============================================================================
echo "4. SEND BATCH PERSONALIZED MESSAGES\n";
echo str_repeat("-", 50) . "\n";

$personalizedRecipients = [
    [
        'To' => '0244123456',
        'Content' => 'Hi John, thank you for being an active TESCON member!'
    ],
    [
        'To' => '0501234567',
        'Content' => 'Hi Mary, your leadership in TESCON is appreciated!'
    ],
    [
        'To' => '0551234567',
        'Content' => 'Hi David, keep up the great work in TESCON!'
    ]
];

echo "Sending personalized messages to " . count($personalizedRecipients) . " recipients\n\n";

echo "Example code:\n";
echo '$personalizedRecipients = [' . "\n";
echo '    ["To" => "0244123456", "Content" => "Hi John, ..."],' . "\n";
echo '    ["To" => "0501234567", "Content" => "Hi Mary, ..."]' . "\n";
echo '];' . "\n";
echo '$result = $hubtel->sendBatchPersonalized($personalizedRecipients);' . "\n\n";

// Uncomment to actually send
// $result = $hubtel->sendBatchPersonalized($personalizedRecipients);
// print_r($result);

// ============================================================================
// EXAMPLE 5: Check Message Status by Message ID
// ============================================================================
echo "5. CHECK MESSAGE STATUS (BY MESSAGE ID)\n";
echo str_repeat("-", 50) . "\n";

$messageId = 'fab43849-6c5b-4334-a88b-d06520b1ace8'; // Replace with actual message ID

echo "Checking status for Message ID: $messageId\n\n";

echo "Example code:\n";
echo '$result = $hubtel->getMessageStatus("fab43849-6c5b-4334-a88b-d06520b1ace8");' . "\n";
echo "if (\$result['success']) {\n";
echo "    echo 'Status: ' . \$result['data']['status'];\n";
echo "    echo 'Recipient: ' . \$result['data']['to'];\n";
echo "    echo 'Delivered: ' . \$result['data']['updateTime'];\n";
echo "}\n\n";

// Uncomment to actually check
// $result = $hubtel->getMessageStatus($messageId);
// print_r($result);

// ============================================================================
// EXAMPLE 6: Check Batch Status by Batch ID
// ============================================================================
echo "6. CHECK BATCH STATUS (BY BATCH ID)\n";
echo str_repeat("-", 50) . "\n";

$batchId = '18ee6a2a-29ac-41c6-b70f-de809b040584'; // Replace with actual batch ID

echo "Checking status for Batch ID: $batchId\n\n";

echo "Example code:\n";
echo '$result = $hubtel->getBatchStatus("18ee6a2a-29ac-41c6-b70f-de809b040584");' . "\n";
echo "if (\$result['success']) {\n";
echo "    echo 'Batch ID: ' . \$result['data']['batchId'];\n";
echo "    foreach (\$result['data']['data'] as \$msg) {\n";
echo "        echo 'Recipient: ' . \$msg['to'] . ' - Status: ' . \$msg['status'];\n";
echo "    }\n";
echo "}\n\n";

// Uncomment to actually check
// $result = $hubtel->getBatchStatus($batchId);
// print_r($result);

// ============================================================================
// EXAMPLE 7: Phone Number Validation
// ============================================================================
echo "7. PHONE NUMBER VALIDATION\n";
echo str_repeat("-", 50) . "\n";

$testNumbers = [
    '0244123456',      // Valid
    '233244123456',    // Valid
    '+233244123456',   // Valid
    '024412345',       // Invalid (too short)
    '02441234567',     // Invalid (too long)
    'invalid'          // Invalid
];

echo "Testing phone number validation:\n";
foreach ($testNumbers as $number) {
    $isValid = HubtelSMS::validatePhoneNumber($number);
    echo sprintf("%-20s => %s\n", $number, $isValid ? 'VALID' : 'INVALID');
}
echo "\n";

// ============================================================================
// EXAMPLE 8: Sender ID Validation
// ============================================================================
echo "8. SENDER ID VALIDATION\n";
echo str_repeat("-", 50) . "\n";

$testSenderIds = [
    'TESCON',          // Valid
    'TESCON-GH',       // Valid
    'MyCompany',       // Valid
    'TooLongSenderId', // Invalid (>11 chars)
    'Test@123',        // Invalid (special chars)
];

echo "Testing sender ID validation:\n";
foreach ($testSenderIds as $senderId) {
    $isValid = HubtelSMS::validateSenderId($senderId);
    echo sprintf("%-20s => %s\n", $senderId, $isValid ? 'VALID' : 'INVALID');
}
echo "\n";

// ============================================================================
// EXAMPLE 9: Cost Estimation
// ============================================================================
echo "9. SMS COST ESTIMATION\n";
echo str_repeat("-", 50) . "\n";

$messages = [
    'Short message',
    'This is a longer message that contains more than 160 characters and will be split into multiple SMS messages. This helps you understand the cost implications.',
    'Very long message: ' . str_repeat('Lorem ipsum dolor sit amet. ', 20)
];

echo "Estimating costs for different message lengths:\n\n";
foreach ($messages as $msg) {
    $estimate = HubtelSMS::estimateCost($msg, 100); // 100 recipients
    echo "Message length: {$estimate['message_length']} characters\n";
    echo "SMS per recipient: {$estimate['messages_per_recipient']}\n";
    echo "Total recipients: {$estimate['total_recipients']}\n";
    echo "Total SMS: {$estimate['total_messages']}\n";
    echo "Estimated cost: {$estimate['currency']} {$estimate['estimated_cost']}\n";
    echo str_repeat("-", 30) . "\n";
}

// ============================================================================
// EXAMPLE 10: Error Handling
// ============================================================================
echo "\n10. ERROR HANDLING\n";
echo str_repeat("-", 50) . "\n";

echo "Example of proper error handling:\n\n";
echo 'try {' . "\n";
echo '    $result = $hubtel->sendSimplePOST("0244123456", "Test message");' . "\n";
echo '    ' . "\n";
echo '    if ($result["success"]) {' . "\n";
echo '        // Success' . "\n";
echo '        $messageId = $result["data"]["messageId"];' . "\n";
echo '        echo "Message sent successfully! ID: $messageId";' . "\n";
echo '    } else {' . "\n";
echo '        // API returned an error' . "\n";
echo '        $error = $result["error"];' . "\n";
echo '        $httpCode = $result["http_code"];' . "\n";
echo '        echo "Failed to send: $error (HTTP $httpCode)";' . "\n";
echo '    }' . "\n";
echo '} catch (Exception $e) {' . "\n";
echo '    // Exception occurred' . "\n";
echo '    echo "Exception: " . $e->getMessage();' . "\n";
echo '}' . "\n\n";

// ============================================================================
// EXAMPLE 11: Integration with Database Logging
// ============================================================================
echo "11. DATABASE LOGGING INTEGRATION\n";
echo str_repeat("-", 50) . "\n";

echo "Example of logging SMS to database:\n\n";
echo '$result = $hubtel->sendSimplePOST($phone, $message);' . "\n\n";
echo '$query = "INSERT INTO sms_logs (sender_id, recipient_phone, message, status, message_id, cost, sent_at)' . "\n";
echo '         VALUES (:sender_id, :phone, :message, :status, :message_id, :cost, NOW())";' . "\n";
echo '$stmt = $db->prepare($query);' . "\n";
echo '$stmt->execute([' . "\n";
echo '    ":sender_id" => $_SESSION["user_id"],' . "\n";
echo '    ":phone" => $phone,' . "\n";
echo '    ":message" => $message,' . "\n";
echo '    ":status" => $result["success"] ? "sent" : "failed",' . "\n";
echo '    ":message_id" => $result["data"]["messageId"] ?? null,' . "\n";
echo '    ":cost" => $result["data"]["rate"] ?? null' . "\n";
echo ']);' . "\n\n";

// ============================================================================
// SUMMARY
// ============================================================================
echo "\n" . str_repeat("=", 70) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 70) . "\n\n";

echo "Available Methods:\n";
echo "- sendSimpleGET()           : Send single SMS using GET\n";
echo "- sendSimplePOST()          : Send single SMS using POST (recommended)\n";
echo "- sendBatchSimple()         : Send same message to multiple recipients\n";
echo "- sendBatchPersonalized()   : Send personalized messages to multiple recipients\n";
echo "- getMessageStatus()        : Check status of a single message\n";
echo "- getBatchStatus()          : Check status of a batch\n";
echo "- validatePhoneNumber()     : Validate phone number format\n";
echo "- validateSenderId()        : Validate sender ID\n";
echo "- estimateCost()            : Estimate SMS cost\n\n";

echo "Response Codes:\n";
echo "- 200/201 : Success\n";
echo "- 400     : Bad Request\n";
echo "- 401     : Unauthorized (invalid credentials)\n";
echo "- 402     : Payment Required (insufficient credit)\n";
echo "- 403     : Forbidden\n";
echo "- 404     : Not Found\n";
echo "- 500     : Server Error\n\n";

echo "SMS Statuses:\n";
echo "- Delivered      : Successfully delivered\n";
echo "- Sent           : Sent to network, pending delivery\n";
echo "- Pending        : Queued, awaiting dispatch\n";
echo "- Blacklisted    : Recipient opted out\n";
echo "- Undeliverable  : Could not be delivered\n";
echo "- Failed         : Delivery failed\n";
echo "- Rejected       : Rejected by network\n\n";

echo "For more information, visit: https://developers.hubtel.com/documentations/sms-api\n\n";
