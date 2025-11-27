# Quick Setup Guide - Forgot Password Feature

## Prerequisites
- MySQL database access
- SMTP email account (Gmail, Outlook, etc.)
- Hubtel SMS API credentials (already configured)

## Step-by-Step Setup

### 1. Create Database Table (Required)

#### For New Installations
Run this SQL command in your MySQL database:

```sql
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(16) NOT NULL UNIQUE,
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

#### For Existing Installations (Already have the table)
If you already created the table with 64-character tokens, update it:

```sql
TRUNCATE TABLE password_reset_tokens;
ALTER TABLE password_reset_tokens MODIFY COLUMN token VARCHAR(16) NOT NULL UNIQUE;
```

**Note**: Token length is now 16 characters (down from 64) for SMS cost optimization. This reduces SMS costs by 50% while maintaining strong security.

**Via phpMyAdmin:**
1. Open phpMyAdmin
2. Select `tescon_ghana` database
3. Click "SQL" tab
4. Paste the SQL above
5. Click "Go"

**Via Command Line:**
```bash
mysql -u root -p tescon_ghana < database/migrations/create_password_reset_tokens_table.sql
```

### 2. Configure Email Settings (Required)

Edit `config/config.php` and update these lines:

```php
// Email Configuration (SMTP)
define('SMTP_HOST', 'smtp.gmail.com'); // Change if not using Gmail
define('SMTP_PORT', 587); // Keep as 587 for TLS
define('SMTP_USERNAME', 'your-email@gmail.com'); // YOUR EMAIL HERE
define('SMTP_PASSWORD', 'your-app-password'); // YOUR APP PASSWORD HERE
define('SMTP_FROM_EMAIL', 'noreply@tesconghana.org'); // From address
define('SMTP_FROM_NAME', 'TESCON Ghana'); // From name
define('SMTP_ENCRYPTION', 'tls'); // Keep as 'tls'
```

#### For Gmail Users:
1. Go to https://myaccount.google.com/security
2. Enable "2-Step Verification"
3. Go to https://myaccount.google.com/apppasswords
4. Select "Mail" and "Other (Custom name)"
5. Enter "TESCON Ghana" as the name
6. Click "Generate"
7. Copy the 16-character password
8. Use this password in `SMTP_PASSWORD`

#### For Other Email Providers:
- **Outlook/Hotmail**: 
  - SMTP_HOST: `smtp.office365.com`
  - SMTP_PORT: `587`
  
- **Yahoo Mail**:
  - SMTP_HOST: `smtp.mail.yahoo.com`
  - SMTP_PORT: `587`

### 3. Test the Feature

#### Test 1: With Email Address
1. Go to http://localhost/tescongh/login.php
2. Click "Forgot Password?"
3. Enter a valid email address (e.g., admin email)
4. Check your email inbox
5. Click the reset link in the email
6. Enter new password
7. Login with new password

#### Test 2: With Student ID
1. Go to http://localhost/tescongh/login.php
2. Click "Forgot Password?"
3. Enter a valid student ID
4. Check email AND phone for SMS
5. Click the reset link
6. Enter new password
7. Login with new password

### 4. Verify Everything Works

✅ **Email Delivery**: Check if email arrives within 1-2 minutes
✅ **SMS Delivery**: Check if SMS arrives (if phone number exists)
✅ **Reset Link**: Click link and verify it opens reset page
✅ **Password Reset**: Successfully change password
✅ **Login**: Login with new password works

## Common Issues & Solutions

### Issue: Email not sending
**Solution:**
- Check SMTP credentials are correct
- Verify email/password in config.php
- Check PHP error logs: `tail -f /xampp/apache/logs/error.log`
- Test SMTP connection with a simple script

### Issue: SMS not sending
**Solution:**
- Verify Hubtel credentials
- Check SMS credits in Hubtel account
- Ensure phone number exists in members table
- Check PHP error logs for API errors

### Issue: "Database connection failed"
**Solution:**
- Verify database credentials in config.php
- Ensure MySQL is running
- Check database name is correct

### Issue: "Invalid or expired reset token"
**Solution:**
- Token expires after 1 hour - request new one
- Token can only be used once
- Clear browser cache and try again

### Issue: Reset link not clickable in email
**Solution:**
- Copy and paste the link manually
- Check email client settings (some block links)
- Try viewing email in web browser

## Testing Checklist

- [ ] Database table created successfully
- [ ] Email configuration updated
- [ ] Test forgot password with email address
- [ ] Test forgot password with student ID
- [ ] Receive email with reset link
- [ ] Receive SMS with reset link (if phone available)
- [ ] Reset link opens correctly
- [ ] Password successfully changed
- [ ] Can login with new password
- [ ] Old password no longer works
- [ ] Expired token shows error message
- [ ] Used token shows error message

## Security Notes

⚠️ **Important Security Practices:**
1. Never share your SMTP password
2. Use App Passwords for Gmail (not your main password)
3. Tokens expire after 1 hour for security
4. Tokens can only be used once
5. Generic success messages prevent account enumeration
6. All passwords are securely hashed with bcrypt

## Need Help?

If you encounter issues:
1. Check the detailed documentation: `FORGOT_PASSWORD_IMPLEMENTATION.md`
2. Review PHP error logs
3. Test with a simple email first
4. Verify all configuration settings
5. Contact the development team

## Quick Reference

**Files Created:**
- `classes/Email.php` - Email service
- `forgot_password.php` - Request reset page
- `reset_password.php` - Reset password page
- `database/migrations/create_password_reset_tokens_table.sql` - DB migration

**Files Modified:**
- `config/config.php` - Email settings
- `classes/User.php` - Reset methods
- `login.php` - Forgot password link

**Configuration Required:**
- SMTP email settings
- Database table creation

**Optional:**
- Cron job for token cleanup (recommended for production)

---
**Setup Time**: ~10 minutes  
**Difficulty**: Easy  
**Status**: Ready to Use
