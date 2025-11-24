# Hubtel SMS API Integration Guide

## Overview

This document provides comprehensive information about the Hubtel SMS API integration in the TESCON Ghana Membership Database system.

## Table of Contents

1. [Features](#features)
2. [Setup & Configuration](#setup--configuration)
3. [API Methods](#api-methods)
4. [Usage Examples](#usage-examples)
5. [Status Codes](#status-codes)
6. [Error Handling](#error-handling)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)

---

## Features

The Hubtel SMS integration supports all major SMS functionalities:

- ✅ **Simple Messaging** - Send SMS to individuals using GET or POST
- ✅ **Batch Simple Messaging** - Send the same message to multiple recipients
- ✅ **Batch Personalized Messaging** - Send personalized messages to groups
- ✅ **Message Status Check** - Check delivery status by messageId
- ✅ **Batch Status Check** - Check status of batch messages by batchId
- ✅ **Phone Number Validation** - Validate Ghanaian phone numbers
- ✅ **Sender ID Validation** - Validate sender IDs (max 11 characters)
- ✅ **Cost Estimation** - Estimate SMS costs before sending
- ✅ **Database Logging** - Automatic logging of all SMS transactions

---

## Setup & Configuration

### Step 1: Get Hubtel Credentials

1. Visit [Hubtel Unity Dashboard](https://unity.hubtel.com/account/api-accounts-credentials)
2. Log in to your account
3. Navigate to **API Accounts & Credentials**
4. Copy your **Client ID** and **Client Secret**

### Step 2: Configure Credentials

Open `config/config.php` and add your credentials:

```php
// SMS Configuration (Hubtel)
define('HUBTEL_CLIENT_ID', 'your_client_id_here');
define('HUBTEL_CLIENT_SECRET', 'your_client_secret_here');
define('SMS_SENDER_ID', 'TESCON-GH'); // Max 11 characters, alphanumeric only
```

### Step 3: Verify Installation

The following files should be present:

```
classes/
  ├── HubtelSMS.php          # Main Hubtel SMS API class
  ├── SMSClient.php          # SMS client interface and factory
  └── SMSTemplateRenderer.php # Template rendering

sms.php                      # Send SMS interface
sms_status_check.php         # Check message status
sms_logs.php                 # View SMS logs
api/
  └── hubtel_sms_examples.php # Usage examples
```

---

## API Methods

### HubtelSMS Class Methods

#### 1. `sendSimpleGET($to, $content, $from = null)`

Send a simple SMS using GET method.

**Parameters:**
- `$to` (string) - Recipient phone number (e.g., '0244123456' or '233244123456')
- `$content` (string) - Message content
- `$from` (string|null) - Optional sender ID (defaults to configured sender ID)

**Returns:** Array with keys: `success`, `status`, `data`, `error`, `http_code`, `raw_response`

**Example:**
```php
$hubtel = new HubtelSMS($clientId, $clientSecret, 'TESCON-GH');
$result = $hubtel->sendSimpleGET('0244123456', 'Hello from TESCON!');

if ($result['success']) {
    echo "Message sent! ID: " . $result['data']['messageId'];
}
```

---

#### 2. `sendSimplePOST($to, $content, $from = null, $clientReference = null)`

Send a simple SMS using POST method (recommended).

**Parameters:**
- `$to` (string) - Recipient phone number
- `$content` (string) - Message content
- `$from` (string|null) - Optional sender ID
- `$clientReference` (string|null) - Optional client reference for tracking

**Returns:** Array with response data

**Example:**
```php
$result = $hubtel->sendSimplePOST('0244123456', 'Hello from TESCON!');

if ($result['success']) {
    $messageId = $result['data']['messageId'];
    $cost = $result['data']['rate'];
    echo "Sent! ID: $messageId, Cost: GHS $cost";
}
```

---

#### 3. `sendBatchSimple(array $recipients, $content, $from = null)`

Send the same message to multiple recipients.

**Parameters:**
- `$recipients` (array) - Array of phone numbers
- `$content` (string) - Message content (same for all)
- `$from` (string|null) - Optional sender ID

**Returns:** Array with batchId and individual message details

**Example:**
```php
$recipients = ['0244123456', '0501234567', '0551234567'];
$message = 'Important TESCON announcement for all members.';

$result = $hubtel->sendBatchSimple($recipients, $message);

if ($result['success']) {
    echo "Batch ID: " . $result['data']['batchId'];
    foreach ($result['data']['data'] as $msg) {
        echo "Sent to " . $msg['recipient'] . " - ID: " . $msg['messageId'];
    }
}
```

---

#### 4. `sendBatchPersonalized(array $personalizedRecipients, $from = null)`

Send personalized messages to multiple recipients.

**Parameters:**
- `$personalizedRecipients` (array) - Array of arrays with 'To' and 'Content' keys
- `$from` (string|null) - Optional sender ID

**Example:**
```php
$personalizedRecipients = [
    ['To' => '0244123456', 'Content' => 'Hi John, your dues are due.'],
    ['To' => '0501234567', 'Content' => 'Hi Mary, thank you for your payment.'],
    ['To' => '0551234567', 'Content' => 'Hi David, welcome to TESCON!']
];

$result = $hubtel->sendBatchPersonalized($personalizedRecipients);

if ($result['success']) {
    echo "Batch ID: " . $result['data']['batchId'];
}
```

---

#### 5. `getMessageStatus($messageId)`

Check the delivery status of a single message.

**Parameters:**
- `$messageId` (string) - The unique message ID returned when sending

**Returns:** Array with message status details

**Example:**
```php
$messageId = 'fab43849-6c5b-4334-a88b-d06520b1ace8';
$result = $hubtel->getMessageStatus($messageId);

if ($result['success']) {
    $status = $result['data']['status']; // 'Delivered', 'Sent', 'Pending', etc.
    $updateTime = $result['data']['updateTime'];
    echo "Status: $status (Updated: $updateTime)";
}
```

---

#### 6. `getBatchStatus($batchId)`

Check the status of all messages in a batch.

**Parameters:**
- `$batchId` (string) - The batch ID returned when sending batch messages

**Returns:** Array with batch status details

**Example:**
```php
$batchId = '18ee6a2a-29ac-41c6-b70f-de809b040584';
$result = $hubtel->getBatchStatus($batchId);

if ($result['success']) {
    foreach ($result['data']['data'] as $msg) {
        echo "To: " . $msg['to'] . " - Status: " . $msg['status'];
    }
}
```

---

### Static Helper Methods

#### `HubtelSMS::validatePhoneNumber($phone)`

Validate if a phone number is in correct Ghanaian format.

**Example:**
```php
$isValid = HubtelSMS::validatePhoneNumber('0244123456'); // true
$isValid = HubtelSMS::validatePhoneNumber('233244123456'); // true
$isValid = HubtelSMS::validatePhoneNumber('invalid'); // false
```

---

#### `HubtelSMS::validateSenderId($senderId)`

Validate if a sender ID meets Hubtel requirements (max 11 chars, alphanumeric).

**Example:**
```php
$isValid = HubtelSMS::validateSenderId('TESCON'); // true
$isValid = HubtelSMS::validateSenderId('TooLongSenderId'); // false
```

---

#### `HubtelSMS::estimateCost($message, $recipientCount = 1)`

Estimate the cost of sending SMS.

**Returns:** Array with message count and estimated cost

**Example:**
```php
$estimate = HubtelSMS::estimateCost('Your message here', 100);

echo "Message length: " . $estimate['message_length'];
echo "SMS per recipient: " . $estimate['messages_per_recipient'];
echo "Total messages: " . $estimate['total_messages'];
echo "Estimated cost: GHS " . $estimate['estimated_cost'];
```

---

#### `HubtelSMS::getStatusDescription($status)`

Get human-readable description of SMS status.

**Example:**
```php
$description = HubtelSMS::getStatusDescription('Delivered');
// Returns: "Message successfully delivered to recipient"
```

---

## Usage Examples

### Example 1: Send SMS from Application

```php
require_once 'classes/HubtelSMS.php';

// Initialize
$hubtel = new HubtelSMS(
    HUBTEL_CLIENT_ID,
    HUBTEL_CLIENT_SECRET,
    SMS_SENDER_ID
);

// Send message
$result = $hubtel->sendSimplePOST('0244123456', 'Hello from TESCON!');

// Handle response
if ($result['success']) {
    // Log to database
    $query = "INSERT INTO sms_logs (sender_id, recipient_phone, message, status, message_id, cost, sent_at)
              VALUES (:sender_id, :phone, :message, :status, :message_id, :cost, NOW())";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':sender_id' => $_SESSION['user_id'],
        ':phone' => '0244123456',
        ':message' => 'Hello from TESCON!',
        ':status' => 'sent',
        ':message_id' => $result['data']['messageId'],
        ':cost' => $result['data']['rate']
    ]);
    
    echo "Message sent successfully!";
} else {
    echo "Error: " . $result['error'];
}
```

---

### Example 2: Send Dues Reminder to All Members

```php
// Get all members with unpaid dues
$query = "SELECT m.* FROM members m
          LEFT JOIN payments p ON m.id = p.member_id AND p.year = 2024
          WHERE p.id IS NULL";
$stmt = $db->query($query);
$members = $stmt->fetchAll();

// Prepare personalized messages
$personalizedRecipients = [];
foreach ($members as $member) {
    $personalizedRecipients[] = [
        'To' => $member['phone'],
        'Content' => "Hi {$member['fullname']}, your TESCON dues for 2024 (GHS 50.00) are due. Please pay at your earliest convenience."
    ];
}

// Send batch
$result = $hubtel->sendBatchPersonalized($personalizedRecipients);

if ($result['success']) {
    echo "Sent to " . count($personalizedRecipients) . " members";
    echo "Batch ID: " . $result['data']['batchId'];
}
```

---

### Example 3: Check Status of Recent Messages

```php
// Get recent messages from database
$query = "SELECT * FROM sms_logs WHERE message_id IS NOT NULL ORDER BY sent_at DESC LIMIT 10";
$stmt = $db->query($query);
$logs = $stmt->fetchAll();

foreach ($logs as $log) {
    $result = $hubtel->getMessageStatus($log['message_id']);
    
    if ($result['success']) {
        $status = $result['data']['status'];
        
        // Update status in database
        $updateQuery = "UPDATE sms_logs SET status = :status WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([
            ':status' => strtolower($status),
            ':id' => $log['id']
        ]);
        
        echo "Message {$log['message_id']}: $status\n";
    }
}
```

---

## Status Codes

### HTTP Response Codes

| Code | Status | Description |
|------|--------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Message sent successfully |
| 400 | Bad Request | Invalid parameters |
| 401 | Unauthorized | Invalid credentials |
| 402 | Payment Required | Insufficient credit |
| 403 | Forbidden | Recipient has not given approval |
| 404 | Not Found | Message not found |
| 500 | Internal Server Error | Server error |
| 502 | Bad Gateway | Gateway error |

### SMS Delivery Statuses

| Status | Description |
|--------|-------------|
| **Delivered** | Message successfully delivered to recipient's phone |
| **Sent** | Message sent to network operator, pending delivery |
| **Pending** | Message queued, awaiting dispatch by Hubtel |
| **Blacklisted** | Recipient has opted out of bulk messages |
| **Undeliverable** | Message could not be delivered (phone off, no network, etc.) |
| **Failed** | Message delivery failed |
| **Unrouteable** | Message could not be routed |
| **Error** | An error occurred during delivery |
| **Rejected** | Message was rejected by the network |

### Error Status Codes

| Status | Description |
|--------|-------------|
| **NACK/0x0000000b/Invalid Destination Address** | Invalid recipient phone number |
| **NACK/0x0000000a/Invalid Source Address** | Invalid sender ID (exceeds 11 characters) |

---

## Error Handling

### Proper Error Handling Pattern

```php
try {
    $result = $hubtel->sendSimplePOST('0244123456', 'Test message');
    
    if ($result['success']) {
        // Success - message sent
        $messageId = $result['data']['messageId'];
        $cost = $result['data']['rate'];
        
        // Log success
        logSMS($messageId, 'sent', $cost);
        
    } else {
        // API returned an error
        $error = $result['error'];
        $httpCode = $result['http_code'];
        
        // Log failure
        logSMS(null, 'failed', 0, $error);
        
        // Handle specific errors
        if ($httpCode === 401) {
            // Invalid credentials
            notifyAdmin('Invalid Hubtel credentials');
        } elseif ($httpCode === 402) {
            // Insufficient credit
            notifyAdmin('Hubtel account needs top-up');
        }
    }
    
} catch (Exception $e) {
    // Exception occurred (network error, etc.)
    logError('SMS Exception: ' . $e->getMessage());
}
```

---

## Best Practices

### 1. Phone Number Formatting

Always validate and format phone numbers before sending:

```php
// Validate first
if (!HubtelSMS::validatePhoneNumber($phone)) {
    throw new Exception('Invalid phone number');
}

// The HubtelSMS class automatically formats numbers
// 0244123456 → 233244123456
// +233244123456 → 233244123456
```

### 2. Sender ID Guidelines

- Maximum 11 characters
- Alphanumeric only (no special characters)
- Use your organization name for brand recognition
- Register your sender ID with Hubtel for better delivery rates

```php
// Good sender IDs
'TESCON'
'TESCON-GH'
'MyCompany'

// Bad sender IDs
'TooLongSenderId' // > 11 characters
'Test@123'        // Special characters
```

### 3. Message Length

- Standard SMS: 160 characters
- Messages > 160 chars are split into multiple SMS
- Each SMS is charged separately
- Use `estimateCost()` to calculate costs before sending

```php
$message = "Your very long message...";
$estimate = HubtelSMS::estimateCost($message, 100);

if ($estimate['total_messages'] > 200) {
    // Warning: This will send more than 200 SMS
    confirmBeforeSending();
}
```

### 4. Batch Sending

For large batches, use batch methods instead of loops:

```php
// ❌ Bad: Loop with individual sends
foreach ($recipients as $phone) {
    $hubtel->sendSimplePOST($phone, $message);
}

// ✅ Good: Use batch method
$hubtel->sendBatchSimple($recipients, $message);
```

### 5. Status Checking

- Don't check status immediately after sending
- Wait at least 30 seconds for delivery
- Use batch status check for batch messages
- Store message IDs for later status checks

```php
// Send message
$result = $hubtel->sendSimplePOST($phone, $message);
$messageId = $result['data']['messageId'];

// Wait before checking (in a cron job or delayed task)
sleep(30);

// Check status
$status = $hubtel->getMessageStatus($messageId);
```

### 6. Database Logging

Always log SMS transactions for auditing and tracking:

```php
$logData = [
    'sender_id' => $_SESSION['user_id'],
    'recipient_phone' => $phone,
    'message' => $message,
    'status' => $result['success'] ? 'sent' : 'failed',
    'message_id' => $result['data']['messageId'] ?? null,
    'cost' => $result['data']['rate'] ?? null,
    'error_message' => $result['error'] ?? null,
    'sent_at' => date('Y-m-d H:i:s')
];

// Insert into sms_logs table
```

### 7. Error Notifications

Set up alerts for critical errors:

```php
if ($httpCode === 402) {
    // Insufficient credit - notify admin immediately
    sendEmailToAdmin('Hubtel SMS credit low');
}

if ($httpCode === 401) {
    // Invalid credentials - critical error
    sendEmailToAdmin('Hubtel SMS credentials invalid');
}
```

---

## Troubleshooting

### Issue: "Invalid credentials" error

**Solution:**
1. Verify `HUBTEL_CLIENT_ID` and `HUBTEL_CLIENT_SECRET` in `config.php`
2. Check credentials at https://unity.hubtel.com/account/api-accounts-credentials
3. Ensure no extra spaces in credentials

### Issue: "Insufficient credit" error

**Solution:**
1. Log in to Hubtel Unity Dashboard
2. Check your SMS credit balance
3. Top up your account if needed

### Issue: Messages not delivering

**Possible causes:**
- Recipient phone is off or out of network
- Invalid phone number format
- Recipient has blacklisted bulk SMS
- Network issues

**Solution:**
1. Check message status using `getMessageStatus()`
2. Verify phone number format
3. Try sending to a different number to test

### Issue: "Invalid Source Address" error

**Solution:**
- Sender ID exceeds 11 characters
- Sender ID contains special characters
- Use `validateSenderId()` to check before sending

### Issue: Messages showing as "Sent" but not "Delivered"

**Explanation:**
- "Sent" means message was sent to network operator
- Delivery depends on recipient's phone status
- Check status again after a few minutes
- Some networks take longer to deliver

### Issue: High SMS costs

**Solution:**
1. Use `estimateCost()` before sending
2. Keep messages under 160 characters
3. Use batch methods for multiple recipients
4. Consider message templates to reduce length

---

## Additional Resources

- **Hubtel Developer Documentation:** https://developers.hubtel.com/documentations/sms-api
- **Hubtel Unity Dashboard:** https://unity.hubtel.com
- **Hubtel Support:** support@hubtel.com
- **API Status Page:** https://status.hubtel.com

---

## Support

For issues with this integration:
1. Check the troubleshooting section above
2. Review the examples in `api/hubtel_sms_examples.php`
3. Check SMS logs at `sms_logs.php`
4. Contact system administrator

For Hubtel API issues:
- Email: support@hubtel.com
- Phone: +233 30 281 0100

---

## Version History

- **v1.0.0** (2024-11-24) - Initial Hubtel SMS API integration
  - Simple messaging (GET and POST)
  - Batch simple messaging
  - Batch personalized messaging
  - Message status checking
  - Batch status checking
  - Phone number validation
  - Cost estimation
  - Database logging

---

**Last Updated:** November 24, 2024
