# Hubtel SMS API - Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Configure Credentials (2 minutes)

1. Get your Hubtel credentials from [https://unity.hubtel.com/account/api-accounts-credentials](https://unity.hubtel.com/account/api-accounts-credentials)

2. Open `config/config.php` and add:

```php
define('HUBTEL_CLIENT_ID', 'your_client_id');
define('HUBTEL_CLIENT_SECRET', 'your_client_secret');
define('SMS_SENDER_ID', 'TESCON-GH');
```

### Step 2: Send Your First SMS (1 minute)

```php
require_once 'classes/HubtelSMS.php';

$hubtel = new HubtelSMS(
    HUBTEL_CLIENT_ID,
    HUBTEL_CLIENT_SECRET,
    SMS_SENDER_ID
);

$result = $hubtel->sendSimplePOST('0244123456', 'Hello from TESCON!');

if ($result['success']) {
    echo "✅ Message sent! ID: " . $result['data']['messageId'];
} else {
    echo "❌ Error: " . $result['error'];
}
```

### Step 3: Check Message Status (1 minute)

```php
$messageId = 'your-message-id-here';
$status = $hubtel->getMessageStatus($messageId);

echo "Status: " . $status['data']['status']; // Delivered, Sent, Pending, etc.
```

---

## 📋 Common Use Cases

### Send to Multiple Recipients (Same Message)

```php
$recipients = ['0244123456', '0501234567', '0551234567'];
$message = 'Important TESCON announcement';

$result = $hubtel->sendBatchSimple($recipients, $message);
```

### Send Personalized Messages

```php
$messages = [
    ['To' => '0244123456', 'Content' => 'Hi John, your dues are GHS 50.00'],
    ['To' => '0501234567', 'Content' => 'Hi Mary, your dues are GHS 30.00']
];

$result = $hubtel->sendBatchPersonalized($messages);
```

### Validate Phone Number

```php
if (HubtelSMS::validatePhoneNumber('0244123456')) {
    // Valid Ghanaian number
}
```

### Estimate Cost

```php
$estimate = HubtelSMS::estimateCost('Your message', 100);
echo "Total cost: GHS " . $estimate['estimated_cost'];
```

---

## 🎯 Using the Web Interface

### Send SMS via Web Interface

1. Navigate to **SMS** → **Send SMS** in the dashboard
2. Select recipient type (All Members, By Region, Individual, etc.)
3. Choose **Hubtel** as the provider
4. Type your message
5. Click **Send SMS**

### Check Message Status

1. Navigate to **SMS** → **SMS Status Check**
2. Select **Message ID** or **Batch ID**
3. Enter the ID
4. Click **Check Status**

### View SMS Logs

1. Navigate to **SMS** → **View SMS Logs**
2. See all sent messages with status and costs

---

## 📊 Response Structure

### Successful Response

```php
[
    'success' => true,
    'status' => 0,
    'data' => [
        'messageId' => 'fab43849-6c5b-4334-a88b-d06520b1ace8',
        'rate' => 0.0246,
        'networkId' => '62002',
        'statusDescription' => 'request submitted successfully'
    ],
    'error' => null,
    'http_code' => 201
]
```

### Error Response

```php
[
    'success' => false,
    'status' => null,
    'data' => [...],
    'error' => 'Unauthorized - Invalid credentials',
    'http_code' => 401
]
```

---

## ⚠️ Important Notes

### Phone Number Format
- ✅ `0244123456` (local format)
- ✅ `233244123456` (international without +)
- ✅ `+233244123456` (international with +)
- ❌ `024412345` (too short)

### Sender ID Rules
- Maximum 11 characters
- Alphanumeric only (A-Z, 0-9)
- No spaces or special characters
- Example: `TESCON`, `TESCON-GH`, `MyCompany`

### Message Length
- 1 SMS = 160 characters
- Longer messages are split and charged per SMS
- Use `estimateCost()` to check before sending

### SMS Statuses
- **Delivered** ✅ - Successfully delivered
- **Sent** 📤 - Sent to network, pending delivery
- **Pending** ⏳ - Queued, awaiting dispatch
- **Failed** ❌ - Delivery failed

---

## 🔧 Troubleshooting

### "Invalid credentials" error
→ Check `HUBTEL_CLIENT_ID` and `HUBTEL_CLIENT_SECRET` in `config.php`

### "Insufficient credit" error
→ Top up your Hubtel account at https://unity.hubtel.com

### Messages not delivering
→ Check status with `getMessageStatus()` after 30 seconds

### "Invalid Source Address" error
→ Sender ID exceeds 11 characters or contains special characters

---

## 📚 More Information

- **Full Documentation:** See `HUBTEL_SMS_INTEGRATION.md`
- **Code Examples:** See `api/hubtel_sms_examples.php`
- **Hubtel Docs:** https://developers.hubtel.com/documentations/sms-api

---

## 🆘 Support

**System Issues:**
- Check SMS logs at `sms_logs.php`
- Review examples in `api/hubtel_sms_examples.php`

**Hubtel API Issues:**
- Email: support@hubtel.com
- Phone: +233 30 281 0100
- Dashboard: https://unity.hubtel.com

---

**Quick Reference Card**

| Action | Method | Example |
|--------|--------|---------|
| Send single SMS | `sendSimplePOST()` | `$hubtel->sendSimplePOST('0244123456', 'Hi')` |
| Send batch | `sendBatchSimple()` | `$hubtel->sendBatchSimple(['0244...'], 'Hi')` |
| Send personalized | `sendBatchPersonalized()` | `$hubtel->sendBatchPersonalized([...])` |
| Check status | `getMessageStatus()` | `$hubtel->getMessageStatus($messageId)` |
| Validate phone | `validatePhoneNumber()` | `HubtelSMS::validatePhoneNumber($phone)` |
| Estimate cost | `estimateCost()` | `HubtelSMS::estimateCost($msg, 100)` |

---

**Last Updated:** November 24, 2024
