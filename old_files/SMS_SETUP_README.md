# TESCON Ghana SMS Integration Setup

## Overview
The TESCON Ghana membership system now includes comprehensive SMS functionality using Hubtel's SMS API for sending notifications to members.

## Features Added
- ✅ **Hubtel SMS Integration**: Send SMS via Hubtel's reliable Ghanaian network
- ✅ **Bulk SMS**: Send messages to groups (all members, paid/unpaid, executives)
- ✅ **SMS Templates**: Pre-defined templates for common messages
- ✅ **Automated Notifications**: Welcome SMS, payment confirmations, executive appointments
- ✅ **SMS History**: Track all sent messages and delivery status
- ✅ **Cost Tracking**: Monitor SMS expenses
- ✅ **Admin SMS Management**: Full SMS control interface

## SMS Configuration
1. **Get Hubtel SMS Credentials:**
   - Use the same Hubtel account as payments, or create a separate SMS account
   - Get SMS API credentials (Client ID, Client Secret)
   - Register a Sender ID (your brand name, max 11 characters)

2. **Update Configuration:**
   Edit `config/hubtel.php` and add your SMS credentials:
   ```php
   define('HUBTEL_SMS_CLIENT_ID', 'your_sms_client_id_here');
   define('HUBTEL_SMS_CLIENT_SECRET', 'your_sms_client_secret_here');
   define('HUBTEL_SMS_SENDER_ID', 'TESCON'); // Your registered sender ID
   ```

## Database Setup
Run the updated `database/schema.sql` to create SMS-related tables:
- `sms_templates` - Pre-defined message templates
- `sms_logs` - SMS sending history and tracking

## SMS Templates Included
The system includes 5 default SMS templates:
1. **Dues Reminder**: Annual membership dues notifications
2. **Payment Confirmation**: Automatic confirmation after successful payment
3. **Event Notification**: Event announcements and updates
4. **Welcome Message**: New member registration confirmation
5. **Executive Appointment**: Campus executive role assignments

## Automated SMS Triggers

### 1. Registration Welcome SMS
- **Trigger**: When a new member registers
- **Recipient**: New member
- **Purpose**: Welcome message and next steps

### 2. Payment Confirmation SMS
- **Trigger**: After successful dues payment
- **Recipient**: Paying member
- **Purpose**: Confirm payment receipt and amount

### 3. Executive Appointment SMS
- **Trigger**: When a member is assigned as campus executive
- **Recipient**: New executive
- **Purpose**: Official notification of appointment

## Manual SMS Sending

### Access SMS Management
- Login as Executive or Patron
- Navigate to "SMS Management" in the menu
- Choose recipient groups and compose messages

### Recipient Groups
- **All Members**: Send to entire membership
- **Paid Members**: Only those who have paid current dues
- **Unpaid Members**: Those yet to pay current dues
- **Campus Executives**: All appointed executives
- **Custom Recipients**: Manual phone number entry

### SMS Best Practices
- **Length**: Keep under 160 characters for single SMS
- **Timing**: Send during business hours (8 AM - 8 PM)
- **Frequency**: Avoid spamming - max 2-3 SMS per member per month
- **Personalization**: Use member names where possible
- **Call-to-Action**: Include clear next steps

## Cost Management
- **Per SMS Cost**: ~GH₵0.03 (adjust in config based on Hubtel pricing)
- **Bulk Estimation**: System calculates estimated cost before sending
- **Usage Tracking**: Monitor daily/monthly SMS usage
- **Budget Alerts**: Set up monitoring for high usage

## SMS Delivery & Status
- **Delivery Time**: Usually within 30 seconds
- **Status Tracking**: Sent, Delivered, Failed status updates
- **Network Support**: MTN, Vodafone, AirtelTigo
- **International**: Ghana phone numbers only (233xxxxxxxxx format)

## Technical Implementation

### SMS Service Class (`includes/SMSService.php`)
```php
$smsService = new SMSService();

// Send single SMS
$result = $smsService->sendSMS('233241234567', 'Hello World!');

// Send bulk SMS
$phones = ['233241234567', '233501234567'];
$results = $smsService->sendBulkSMS($phones, 'Bulk message');
```

### SMS Notifications (`includes/SMSNotifications.php`)
Automated notification functions:
```php
// Send payment confirmation
sendPaymentConfirmationSMS($memberId, $paymentId);

// Send welcome message
sendWelcomeSMS($memberId);

// Send dues reminders
sendMonthlyDuesReminders(2024);

// Send event notifications
sendEventNotificationSMS($eventId, ['all']);
```

## Admin Features

### SMS Management Interface (`sms_management.php`)
- **Send SMS**: Compose and send messages to groups
- **Templates**: Create and manage reusable templates
- **History**: View sent messages and delivery status
- **Statistics**: Daily/monthly SMS usage reports

### Template Variables
Use these placeholders in templates:
- `{year}` - Current year
- `{amount}` - Payment amount
- `{event_name}` - Event title
- `{event_date}` - Event date
- `{event_location}` - Event venue
- `{position}` - Executive position
- `{campus_name}` - Campus name

## Phone Number Format
All phone numbers must be in Ghana format:
- **Input formats accepted**: 0244123456, 0201234567, 233241234567
- **Internal format**: 233xxxxxxxxx (international format)
- **Validation**: Automatic format conversion and validation

## Security & Compliance
- **Opt-out**: Members can request to stop receiving SMS
- **Content Filtering**: No spam or promotional content
- **Rate Limiting**: Prevent excessive sending
- **Audit Trail**: Complete logging of all SMS activities

## Troubleshooting

### Common Issues
1. **SMS Not Sending**
   - Check Hubtel API credentials
   - Verify Sender ID registration
   - Check account balance

2. **Invalid Phone Numbers**
   - Ensure Ghana format (233xxxxxxxxx)
   - Check for typos in manual entry
   - Verify member phone numbers in database

3. **High Costs**
   - Monitor usage in SMS Management
   - Use templates to reduce character count
   - Send targeted messages instead of bulk

4. **Delivery Failures**
   - Check phone number validity
   - Network issues (temporary)
   - Recipient phone switched off

### Logs & Debugging
- **SMS Logs**: Check `logs/payment_callbacks.log` (shared with payments)
- **Database Logs**: All SMS stored in `sms_logs` table
- **Error Logs**: PHP error logs for debugging
- **Hubtel Dashboard**: Check delivery status in Hubtel portal

## Production Deployment
1. **HTTPS Required**: SMS API requires secure connection
2. **Environment Variables**: Move credentials to server environment
3. **Sender ID Approval**: Register your Sender ID with Hubtel
4. **Rate Limiting**: Implement sending limits to prevent abuse
5. **Backup Systems**: Have fallback SMS providers ready

## Cost Optimization
- **Template Usage**: Reduces character count and costs
- **Bulk Sending**: More cost-effective than individual SMS
- **Timing**: Send during off-peak hours if possible
- **Segmentation**: Target specific groups to reduce volume

## Support & Resources
- **Hubtel SMS API**: https://developers.hubtel.com/docs/sms-api
- **SMS Best Practices**: Hubtel developer documentation
- **Phone Number Formats**: Ghana telephone numbering plan
- **SMS Regulations**: Follow Ghana telecom regulations

## Sample SMS Messages

### Welcome Message
```
Welcome to TESCON Ghana! Complete your registration and pay your dues to access all member benefits. Visit our portal for more information.
```

### Dues Reminder
```
Dear TESCON member, your annual membership dues for 2024 (GH₵50.00) are due. Pay now to avoid penalties. Visit our portal to pay.
```

### Payment Confirmation
```
Thank you for paying your TESCON membership dues for 2024. Your payment of GH₵50.00 has been received successfully.
```

The SMS system is now fully integrated and ready for production use with comprehensive automation and manual control capabilities.
