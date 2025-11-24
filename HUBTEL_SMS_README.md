# Hubtel SMS API Integration

> Complete SMS integration for TESCON Ghana Membership Database

[![Status](https://img.shields.io/badge/status-production%20ready-brightgreen)]()
[![Version](https://img.shields.io/badge/version-1.0.0-blue)]()
[![API](https://img.shields.io/badge/API-Hubtel%20SMS%20v1-orange)]()

---

## 🚀 Quick Links

| Resource | Description | Link |
|----------|-------------|------|
| **Quick Start** | Get started in 5 minutes | [HUBTEL_SMS_QUICK_START.md](HUBTEL_SMS_QUICK_START.md) |
| **Complete Guide** | Full documentation | [HUBTEL_SMS_INTEGRATION.md](HUBTEL_SMS_INTEGRATION.md) |
| **Features** | Feature overview | [HUBTEL_SMS_FEATURES.md](HUBTEL_SMS_FEATURES.md) |
| **Implementation** | Technical details | [HUBTEL_SMS_IMPLEMENTATION_SUMMARY.md](HUBTEL_SMS_IMPLEMENTATION_SUMMARY.md) |
| **Examples** | Code examples | [api/hubtel_sms_examples.php](api/hubtel_sms_examples.php) |

---

## 📋 What's Included

### ✅ Complete API Implementation

All Hubtel SMS API features are fully implemented:

- **Simple Messaging** - Send SMS to individuals (GET and POST methods)
- **Batch Simple** - Send same message to multiple recipients
- **Batch Personalized** - Send different messages to multiple recipients
- **Status Checking** - Check delivery status by message ID or batch ID
- **Phone Validation** - Validate Ghanaian phone numbers
- **Cost Estimation** - Calculate SMS costs before sending
- **Web Interface** - User-friendly web interface for sending and tracking
- **Database Logging** - Automatic logging of all SMS transactions

### 📁 Files Created

```
classes/
├── HubtelSMS.php                    # Main API wrapper class
└── SMSClient.php                    # Updated with Hubtel integration

sms_status_check.php                 # Status check web interface

api/
├── hubtel_sms_examples.php          # Comprehensive code examples
└── README.md                        # API directory guide

Documentation/
├── HUBTEL_SMS_README.md             # This file
├── HUBTEL_SMS_QUICK_START.md        # Quick start guide
├── HUBTEL_SMS_INTEGRATION.md        # Complete documentation
├── HUBTEL_SMS_FEATURES.md           # Feature overview
└── HUBTEL_SMS_IMPLEMENTATION_SUMMARY.md  # Implementation details
```

---

## ⚡ Quick Start

### 1. Configure (2 minutes)

Get credentials from [Hubtel Unity Dashboard](https://unity.hubtel.com/account/api-accounts-credentials)

Edit `config/config.php`:

```php
define('HUBTEL_CLIENT_ID', 'your_client_id');
define('HUBTEL_CLIENT_SECRET', 'your_client_secret');
define('SMS_SENDER_ID', 'TESCON-GH');
```

### 2. Send SMS (1 minute)

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
}
```

### 3. Check Status (1 minute)

```php
$status = $hubtel->getMessageStatus($messageId);
echo "Status: " . $status['data']['status'];
```

**Or use the web interface:**
1. Navigate to **SMS** → **Send SMS**
2. Select **Hubtel** as provider
3. Choose recipients and type message
4. Click **Send SMS**

---

## 🎯 Common Use Cases

### Send Announcement to All Members

```php
$members = getAllMembers(); // Get from database
$phones = array_column($members, 'phone');
$message = 'Important TESCON announcement: Meeting tomorrow at 3pm';

$result = $hubtel->sendBatchSimple($phones, $message);
```

### Send Personalized Dues Reminders

```php
$members = getMembersWithUnpaidDues();
$messages = [];

foreach ($members as $member) {
    $messages[] = [
        'To' => $member['phone'],
        'Content' => "Hi {$member['name']}, your TESCON dues for 2024 (GHS {$member['amount']}) are due."
    ];
}

$result = $hubtel->sendBatchPersonalized($messages);
```

### Validate Phone Before Sending

```php
if (HubtelSMS::validatePhoneNumber($phone)) {
    $result = $hubtel->sendSimplePOST($phone, $message);
} else {
    echo "Invalid phone number";
}
```

### Estimate Cost Before Sending

```php
$estimate = HubtelSMS::estimateCost($message, 100);
echo "This will cost approximately GHS {$estimate['estimated_cost']}";
```

---

## 📊 API Methods Overview

| Method | Purpose | Example |
|--------|---------|---------|
| `sendSimplePOST()` | Send single SMS | `$hubtel->sendSimplePOST('0244123456', 'Hi')` |
| `sendBatchSimple()` | Batch same message | `$hubtel->sendBatchSimple($phones, 'Hi')` |
| `sendBatchPersonalized()` | Batch different messages | `$hubtel->sendBatchPersonalized($messages)` |
| `getMessageStatus()` | Check message status | `$hubtel->getMessageStatus($messageId)` |
| `getBatchStatus()` | Check batch status | `$hubtel->getBatchStatus($batchId)` |
| `validatePhoneNumber()` | Validate phone | `HubtelSMS::validatePhoneNumber($phone)` |
| `estimateCost()` | Estimate cost | `HubtelSMS::estimateCost($msg, 100)` |

---

## 🎨 Web Interface

### Send SMS Page (`sms.php`)

- Select provider (mNotify or Hubtel)
- Choose recipient type (All, By Region, Individual, etc.)
- Type message with character counter
- Send to single or multiple recipients
- Support for templates and custom lists

### Status Check Page (`sms_status_check.php`)

- Check status by Message ID or Batch ID
- View recent messages
- Quick status check buttons
- Detailed delivery information
- Cost tracking

### SMS Logs Page (`sms_logs.php`)

- View all sent messages
- Filter by status, date, recipient
- Track costs and delivery rates
- Export capabilities

---

## 📈 Response Structure

All API methods return a standardized response:

```php
[
    'success' => true/false,         // Whether API call succeeded
    'status' => 0,                   // Status code
    'data' => [                      // Response data
        'messageId' => 'xxx',        // Unique message ID
        'rate' => 0.03,              // Cost per SMS
        'networkId' => '62002',      // Network ID
        // ... more fields
    ],
    'error' => null,                 // Error message if failed
    'http_code' => 201,              // HTTP status code
    'raw_response' => '{...}'        // Raw JSON response
]
```

---

## 🔐 Security

- ✅ Credentials stored in config file (not in code)
- ✅ Basic authentication for API calls
- ✅ Input validation (phone numbers, sender ID)
- ✅ SQL injection prevention
- ✅ Error logging and monitoring
- ✅ Secure credential validation

---

## 💰 Cost Management

### Message Length

- 1 SMS = 160 characters
- Longer messages are split (each charged separately)
- Use `estimateCost()` before sending large batches

### Example Costs

```
Message: "Hello" (5 chars) = 1 SMS × GHS 0.03 = GHS 0.03
Message: 200 chars = 2 SMS × GHS 0.03 = GHS 0.06

Batch to 100 members:
- Short message (1 SMS): 100 × GHS 0.03 = GHS 3.00
- Long message (2 SMS): 200 × GHS 0.03 = GHS 6.00
```

*Actual rates depend on your Hubtel plan*

---

## 📚 Documentation Guide

### For Quick Setup
→ Start with [HUBTEL_SMS_QUICK_START.md](HUBTEL_SMS_QUICK_START.md)

### For Complete Reference
→ Read [HUBTEL_SMS_INTEGRATION.md](HUBTEL_SMS_INTEGRATION.md)

### For Feature Overview
→ Check [HUBTEL_SMS_FEATURES.md](HUBTEL_SMS_FEATURES.md)

### For Code Examples
→ See [api/hubtel_sms_examples.php](api/hubtel_sms_examples.php)

### For Implementation Details
→ Review [HUBTEL_SMS_IMPLEMENTATION_SUMMARY.md](HUBTEL_SMS_IMPLEMENTATION_SUMMARY.md)

---

## 🧪 Testing

### Run Examples

```bash
# From command line
php api/hubtel_sms_examples.php

# Or via web browser
http://localhost/tescongh/api/hubtel_sms_examples.php
```

### Test Checklist

- [ ] Configure credentials in `config.php`
- [ ] Send test SMS to your number
- [ ] Test batch sending (small batch)
- [ ] Check message status
- [ ] Verify database logging
- [ ] Test web interface
- [ ] Test error handling

---

## ⚠️ Important Notes

### Before Production

1. **Add Real Credentials** - Replace placeholder credentials in `config.php`
2. **Test Thoroughly** - Send test messages to your own numbers first
3. **Check Balance** - Ensure sufficient SMS credit in Hubtel account
4. **Monitor Costs** - Set up alerts for low balance
5. **Review Logs** - Check `sms_logs.php` regularly

### Limitations

- Sender ID: Maximum 11 characters, alphanumeric only
- Message Length: 160 characters per SMS (longer = multiple SMS)
- Delivery: Depends on recipient's network and phone status
- Rate Limits: Check with Hubtel for your account limits

---

## 🆘 Troubleshooting

| Issue | Solution |
|-------|----------|
| "Invalid credentials" | Check `HUBTEL_CLIENT_ID` and `HUBTEL_CLIENT_SECRET` in `config.php` |
| "Insufficient credit" | Top up account at https://unity.hubtel.com |
| Messages not delivering | Check status with `getMessageStatus()` after 30 seconds |
| "Invalid Source Address" | Sender ID exceeds 11 characters or has special characters |
| High costs | Keep messages under 160 characters, use `estimateCost()` |

---

## 📞 Support

### System Support
- **Documentation:** See files listed above
- **Examples:** `api/hubtel_sms_examples.php`
- **Logs:** `sms_logs.php` in web interface

### Hubtel Support
- **Email:** support@hubtel.com
- **Phone:** +233 30 281 0100
- **Dashboard:** https://unity.hubtel.com
- **API Docs:** https://developers.hubtel.com/documentations/sms-api

---

## ✅ Feature Checklist

### Implemented Features

- [x] Simple messaging (GET method)
- [x] Simple messaging (POST method) ⭐ Recommended
- [x] Batch simple messaging
- [x] Batch personalized messaging
- [x] Message status check (by message ID)
- [x] Batch status check (by batch ID)
- [x] Phone number validation
- [x] Phone number formatting
- [x] Sender ID validation
- [x] Cost estimation
- [x] Status descriptions
- [x] Web interface for sending
- [x] Web interface for status checking
- [x] Database logging
- [x] Error handling
- [x] Comprehensive documentation
- [x] Code examples

---

## 🎉 Summary

The Hubtel SMS API integration is **complete and production-ready**. All features from the official Hubtel SMS API documentation have been implemented with:

- ✅ Full API coverage
- ✅ User-friendly web interface
- ✅ Comprehensive documentation
- ✅ Code examples
- ✅ Database logging
- ✅ Error handling
- ✅ Security features

**Next Steps:**
1. Configure your Hubtel credentials
2. Test with your phone number
3. Review the documentation
4. Start sending SMS!

---

## 📖 Version History

**v1.0.0** (November 24, 2024)
- Initial release
- Complete Hubtel SMS API integration
- All features implemented
- Documentation complete
- Production ready

---

**Implementation Date:** November 24, 2024  
**Status:** ✅ Production Ready  
**Maintained By:** TESCON Ghana Development Team

---

For detailed information, please refer to the specific documentation files listed above.
