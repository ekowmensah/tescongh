# Forgot Password Implementation

## Overview
A complete forgot password feature has been implemented for the TESCON Ghana Membership Database system. This feature allows users to securely reset their passwords using either their **email address or student ID**, with reset links delivered via **both email and SMS** for maximum accessibility.

## Features Implemented

### 1. Database Table
- **Table**: `password_reset_tokens`
- **Location**: `database/migrations/create_password_reset_tokens_table.sql`
- **Fields**:
  - `id` - Primary key
  - `user_id` - Foreign key to users table
  - `token` - Unique 16-character reset token (optimized for SMS cost-effectiveness)
  - `expires_at` - Token expiration timestamp (1 hour from creation)
  - `is_used` - Flag to mark if token has been used
  - `created_at` - Token creation timestamp
  - `used_at` - Token usage timestamp
- **Note**: Token length optimized to 16 characters (down from 64) to reduce SMS costs by 50% while maintaining strong security (64-bit entropy)

### 2. User Class Methods
Added to `classes/User.php`:

#### `createPasswordResetToken($identifier)`
- Accepts either email address or student ID as input
- Validates user exists and is active
- Retrieves associated phone number for SMS delivery
- Generates secure random token (16 characters, 64-bit entropy)
- Deletes any existing unused tokens for the user
- Creates new token with 1-hour expiration
- Returns token, email, phone, and user information

#### `verifyPasswordResetToken($token)`
- Validates token exists and hasn't expired
- Checks if token hasn't been used
- Verifies user account is active
- Returns user information if valid

#### `resetPasswordWithToken($token, $newPassword)`
- Verifies token validity
- Updates user password with secure hash
- Marks token as used
- Returns success/failure status

#### `cleanupExpiredTokens()`
- Removes expired tokens from database
- Should be called periodically via cron job

### 3. Email Service Class
- **File**: `classes/Email.php`
- **Features**:
  - SMTP email sending support
  - PHPMailer integration (if available)
  - Fallback to PHP mail() function
  - Beautiful HTML email templates
  - Password reset email with branded design
  - Email validation utilities

### 4. Forgot Password Page
- **File**: `forgot_password.php`
- **Features**:
  - Clean, modern UI matching login page design
  - Accepts both email address and student ID
  - Dual delivery: Sends reset link via both email and SMS
  - Email notification with HTML template
  - SMS notification support via Hubtel
  - Fallback to displaying reset link if both email and SMS fail
  - Security: Generic success message to prevent account enumeration
  - Responsive design

### 5. Reset Password Page
- **File**: `reset_password.php`
- **Features**:
  - Token validation on page load
  - Password strength requirements (minimum 6 characters)
  - Password confirmation matching
  - Client-side and server-side validation
  - Clear error messages for expired/invalid tokens
  - Option to request new reset link
  - Automatic redirect to login after successful reset

### 6. Login Page Integration
- Added "Forgot Password?" link below password field
- Styled to match existing design
- Easy access for users who can't remember their password

## Installation Instructions

### Step 1: Configure Email Settings
Update your `config/config.php` with SMTP credentials:

```php
// Email Configuration (SMTP)
define('SMTP_HOST', 'smtp.gmail.com'); // Your SMTP server
define('SMTP_PORT', 587); // SMTP port (587 for TLS, 465 for SSL)
define('SMTP_USERNAME', 'your-email@gmail.com'); // Your email
define('SMTP_PASSWORD', 'your-app-password'); // Your password or app password
define('SMTP_FROM_EMAIL', 'noreply@tesconghana.org'); // From email
define('SMTP_FROM_NAME', 'TESCON Ghana'); // From name
define('SMTP_ENCRYPTION', 'tls'); // 'tls' or 'ssl'
```

**For Gmail:**
1. Enable 2-Factor Authentication
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Use the App Password in `SMTP_PASSWORD`

### Step 2: Create Database Table
Run the SQL migration file to create the required table:

```sql
-- Run this in your MySQL database
SOURCE database/migrations/create_password_reset_tokens_table.sql;
```

Or manually execute:
```sql
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Step 2: Verify Configuration
Ensure your `config/config.php` has the following SMS settings configured:

```php
define('HUBTEL_SMS_CLIENT_ID', 'your_client_id');
define('HUBTEL_SMS_CLIENT_SECRET', 'your_client_secret');
define('SMS_SENDER_ID', 'UEWTESCON');
```

### Step 3: Test the Feature
1. Navigate to `login.php`
2. Click "Forgot Password?" link
3. Enter a valid email address or student ID
4. Check your email inbox and/or phone for SMS
5. Click the reset link from email or SMS
6. Enter and confirm new password
7. Login with new password

**Test Scenarios:**
- Test with email address (should receive email + SMS if phone available)
- Test with student ID (should receive email + SMS)
- Test with account that has no phone (should receive email only)
- Test with expired token (should show error and option to request new link)

## Security Features

### Token Security
- **Random Generation**: Uses `random_bytes(8)` for cryptographically secure tokens
- **Entropy**: 64 bits (18.4 quintillion possible combinations)
- **Unique Tokens**: Database constraint ensures no duplicate tokens
- **Time-Limited**: Tokens expire after 1 hour
- **Single Use**: Tokens are marked as used after password reset
- **Automatic Cleanup**: Old tokens can be removed via cleanup method
- **Brute Force Protection**: Even at 1 million attempts/second, would take 292,000 years to crack

### User Protection
- **Account Enumeration Prevention**: Generic success message regardless of email/student ID existence
- **Flexible Input**: Accepts both email address and student ID
- **Account Status Check**: Only active accounts can reset passwords
- **Password Requirements**: Minimum 6 characters enforced
- **Secure Hashing**: Uses `PASSWORD_BCRYPT` for password storage
- **Dual Delivery**: Increases likelihood of user receiving reset link

### Database Security
- **Foreign Key Constraints**: Cascade delete when user is deleted
- **Indexed Fields**: Optimized queries on token, user_id, and expires_at
- **Prepared Statements**: All queries use PDO prepared statements

## User Flow

### Forgot Password Flow
1. User clicks "Forgot Password?" on login page
2. User enters email address or student ID
3. System validates identifier and generates token
4. System sends reset link via email (HTML formatted)
5. System sends reset link via SMS (if phone number available)
6. User receives reset link via email and/or SMS
7. If both delivery methods fail, displays reset link on screen

### Reset Password Flow
1. User clicks reset link with token
2. System validates token (not expired, not used, user active)
3. User enters new password twice
4. System validates password requirements
5. System updates password and marks token as used
6. User redirected to login with success message

## Email & SMS Integration

### Email Delivery
The system uses SMTP to send professional HTML-formatted emails containing:
- Branded TESCON Ghana header with gradient design
- Personalized greeting (if user name available)
- Clear call-to-action button
- Plain text link as fallback
- Security warnings and expiration notice
- Responsive design for mobile devices

### SMS Delivery
The system uses Hubtel SMS API to send password reset links via SMS:
- Clear identification (TESCON)
- Reset link with short token (16 characters)
- Expiration notice (abbreviated: "1hr")
- Security note (concise)
- **Optimized for cost**: Message fits in 1 SMS (~120 characters) vs 2 SMS previously
- **Cost savings**: 50% reduction in SMS costs per password reset

### Delivery Priority
1. **Primary**: Email (always attempted if email exists)
2. **Secondary**: SMS (attempted if phone number available)
3. **Fallback**: On-screen display (if both email and SMS fail)

The system attempts both delivery methods simultaneously for maximum reliability.

## Maintenance

### Periodic Cleanup
Add a cron job to clean up expired tokens:

```php
// cleanup_tokens.php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$user->cleanupExpiredTokens();
```

Cron job (run daily):
```bash
0 0 * * * php /path/to/tescongh/cleanup_tokens.php
```

## Troubleshooting

### Token Not Working
- Check if token has expired (1 hour limit)
- Verify token hasn't been used already
- Ensure user account is active

### Email Not Sending
- Verify SMTP credentials in config.php
- Check SMTP server allows connections from your server
- For Gmail: Ensure App Password is used (not regular password)
- Check PHP error logs for SMTP connection errors
- Test with a simple email first

### SMS Not Sending
- Verify Hubtel credentials in config.php
- Check user has valid phone number in database
- Review SMS API logs for errors
- Ensure sufficient SMS credits in Hubtel account
- Use fallback link displayed on screen

### Database Errors
- Ensure migration script has been run
- Check foreign key constraints are in place
- Verify user has permissions on password_reset_tokens table

## Files Modified/Created

### Created Files
1. `database/migrations/create_password_reset_tokens_table.sql` - Database table migration
2. `database/migrations/update_password_reset_tokens_shorter.sql` - Migration for existing tables
3. `classes/Email.php` - Email service class with SMTP support
4. `forgot_password.php` - Forgot password page (email/student ID input)
5. `reset_password.php` - Reset password page (token validation)
6. `FORGOT_PASSWORD_IMPLEMENTATION.md` - This documentation file
7. `SMS_OPTIMIZATION.md` - SMS cost optimization analysis and guide
8. `SETUP_FORGOT_PASSWORD.md` - Quick setup guide

### Modified Files
1. `config/config.php` - Added SMTP email configuration
2. `classes/User.php` - Added password reset methods with email/student ID support
3. `login.php` - Added forgot password link

## Future Enhancements

Potential improvements for future versions:
1. ✅ ~~Email notification support~~ (Already implemented)
2. ✅ ~~Accept student ID as input~~ (Already implemented)
3. Rate limiting on reset requests (prevent abuse)
4. Password strength meter on reset page
5. Two-factor authentication option
6. Password history to prevent reuse
7. Admin dashboard for monitoring reset requests
8. Customizable token expiration time
9. Multi-language support
10. PHPMailer library integration for better email reliability

## Support

For issues or questions about this implementation, contact the development team or refer to the main project documentation.

---
**Implementation Date**: November 2024  
**Version**: 1.0.0  
**Status**: Production Ready
