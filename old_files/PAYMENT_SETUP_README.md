# TESCON Ghana Membership System - Payment Integration Setup

## Overview
This system now includes Hubtel payment integration for collecting membership dues from TESCON Ghana members.

## Features Added
- ✅ Hubtel mobile money and card payments
- ✅ Annual dues management
- ✅ Payment tracking and history
- ✅ Admin dues management
- ✅ Member payment status display
- ✅ Automated payment callbacks

## Database Setup
Run the updated `database/schema.sql` to create the new tables:
- `dues` - Annual membership dues configuration
- `payments` - Individual payment records

## Hubtel Configuration
1. **Get Hubtel API Credentials:**
   - Sign up at [Hubtel Developer Portal](https://developers.hubtel.com/)
   - Create a merchant account
   - Get your Client ID, Client Secret, and Merchant Account Number

2. **Update Configuration:**
   Edit `config/hubtel.php` and replace the placeholder values:
   ```php
   define('HUBTEL_CLIENT_ID', 'your_actual_client_id_here');
   define('HUBTEL_CLIENT_SECRET', 'your_actual_client_secret_here');
   define('HUBTEL_MERCHANT_ACCOUNT_NUMBER', 'your_actual_merchant_account_number_here');
   ```

3. **Set Callback URL:**
   In your Hubtel dashboard, set the webhook/callback URL to:
   ```
   http://yourdomain.com/payment_callback.php
   ```

## Payment Flow
1. **Member Login** → Access "Pay Dues" from navigation
2. **Select Payment Method** → Mobile Money or Card
3. **Enter Phone Number** → For mobile money payments
4. **Redirect to Hubtel** → Secure payment processing
5. **Payment Confirmation** → Automatic callback updates status
6. **Receipt** → Member sees payment confirmation

## Admin Features
- **Dues Management** (`dues_management.php`):
  - Set annual dues amounts
  - Configure due dates
  - View payment statistics
  - Record manual payments

- **Campus Management** (existing):
  - Assign campus executives
  - Manage campus memberships

## Supported Payment Methods
- **Mobile Money**: MTN Mobile Money, Vodafone Cash, AirtelTigo Money
- **Cards**: Visa, MasterCard
- **Manual**: Cash and bank transfer recording

## Security Features
- Secure API key storage
- CSRF protection
- Input validation
- File upload restrictions
- Payment verification

## Testing
For testing purposes, you can use Hubtel's sandbox environment:
- Use test API credentials from Hubtel
- Test mobile numbers: various test numbers provided by Hubtel
- Test card numbers: use Hubtel's test cards

## File Structure
```
├── config/
│   ├── database.php
│   └── hubtel.php          # Payment configuration
├── includes/
│   ├── FileUpload.php      # Photo upload handler
│   └── HubtelPayment.php   # Payment gateway integration
├── uploads/photos/         # Member photos
├── logs/                   # Payment callback logs
├── pay_dues.php           # Member payment interface
├── dues_management.php    # Admin dues management
├── payment_callback.php   # Hubtel webhook handler
└── database/schema.sql    # Updated database schema
```

## Usage Instructions

### For Members:
1. Register/Login to the system
2. Click "Pay Dues" in the navigation
3. Select payment method and enter details
4. Complete payment on Hubtel's secure page
5. Receive confirmation of successful payment

### For Administrators:
1. Login as Executive or Patron
2. Access "Dues Management" to configure annual dues
3. Use "Campus Management" for organizational structure
4. View payment reports and statistics

## Troubleshooting
- **Payment not processing**: Check Hubtel API credentials
- **Callback not working**: Verify webhook URL in Hubtel dashboard
- **Logs**: Check `logs/payment_callbacks.log` for callback data
- **Database errors**: Ensure all tables are created from schema.sql

## Production Deployment
1. Use HTTPS for all payment-related pages
2. Store API keys as environment variables
3. Set up proper error logging
4. Configure email notifications
5. Set up database backups
6. Test with small amounts first

## Support
For Hubtel integration issues:
- Hubtel Developer Documentation: https://developers.hubtel.com/
- Hubtel Support: support@hubtel.com

For system issues:
- Check PHP error logs
- Verify database connections
- Test file permissions for uploads/

## Sample Data
The system includes sample dues for 2024 and 2025. Update these values according to your organization's requirements.

Default admin login:
- Email: ekowme@gmail.com
- Password: admin123
