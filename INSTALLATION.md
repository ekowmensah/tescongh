# TESCON Ghana Membership Database - Installation Guide

## Quick Start Guide

Follow these steps to get the TESCON Ghana Membership Database up and running on your local machine.

## Prerequisites

Before you begin, ensure you have the following installed:

- **XAMPP** (or similar PHP/MySQL environment)
  - PHP 7.4 or higher
  - MySQL 5.7 or higher
  - Apache Web Server
- **Web Browser** (Chrome, Firefox, Edge, etc.)

## Step-by-Step Installation

### 1. Download and Extract Files

Extract the project files to your XAMPP htdocs directory:
```
c:\xampp\htdocs\tescongh\
```

### 2. Start XAMPP Services

1. Open XAMPP Control Panel
2. Start **Apache** service
3. Start **MySQL** service

### 3. Create the Database

**Option A: Using phpMyAdmin (Recommended)**

1. Open your browser and go to: `http://localhost/phpmyadmin`
2. Click on "Import" tab
3. Click "Choose File" and select `schema.sql` from the project folder
4. Click "Go" to import the database
5. The database `tescon_ghana` will be created with all tables and sample data

**Option B: Using MySQL Command Line**

```bash
mysql -u root -p < c:\xampp\htdocs\tescongh\schema.sql
```

### 4. Configure the Application

The default configuration should work out of the box. If you need to change database settings:

1. Open `config/config.php`
2. Update these constants if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your MySQL password if you set one
define('DB_NAME', 'tescon_ghana');
```

### 5. Create Uploads Directory

Create a folder for file uploads:
```
c:\xampp\htdocs\tescongh\uploads\
```

Make sure this folder has write permissions.

### 6. Access the Application

Open your web browser and navigate to:
```
http://localhost/tescongh
```

You will be redirected to the login page.

### 7. Login with Default Credentials

Use these credentials to login as an administrator:

- **Email:** `ekowme@gmail.com`
- **Password:** `password`

**IMPORTANT:** Change this password immediately after first login!

## Post-Installation Steps

### 1. Change Admin Password

1. Login with default credentials
2. Go to Profile → Change Password
3. Set a strong, secure password

### 2. Configure API Keys (Optional)

If you plan to use SMS and payment features:

1. Open `config/config.php`
2. Add your Hubtel API credentials:

```php
// SMS Configuration
define('SMS_API_KEY', 'your-hubtel-api-key');
define('SMS_API_SECRET', 'your-hubtel-api-secret');

// Payment Configuration
define('HUBTEL_CLIENT_ID', 'your-client-id');
define('HUBTEL_CLIENT_SECRET', 'your-client-secret');
define('HUBTEL_MERCHANT_ACCOUNT', 'your-merchant-account');
```

### 3. Test the System

1. Navigate through different sections
2. Create a test member
3. Add dues for the current year
4. Test the payment recording feature

## Troubleshooting

### Database Connection Error

**Problem:** "Database connection failed" message

**Solution:**
1. Verify MySQL is running in XAMPP
2. Check database credentials in `config/config.php`
3. Ensure database `tescon_ghana` exists
4. Check MySQL error logs in XAMPP

### Page Not Found (404 Error)

**Problem:** Pages show 404 errors

**Solution:**
1. Verify Apache is running
2. Check that files are in correct directory: `c:\xampp\htdocs\tescongh\`
3. Access via: `http://localhost/tescongh` (not `http://localhost`)

### File Upload Issues

**Problem:** Cannot upload photos

**Solution:**
1. Create `uploads` folder in project root
2. Check folder permissions (should be writable)
3. Verify `upload_max_filesize` in `php.ini` (should be at least 5MB)

### Session Issues / Automatic Logout

**Problem:** Getting logged out frequently

**Solution:**
1. Check `session.gc_maxlifetime` in `php.ini`
2. Adjust `SESSION_TIMEOUT` in `config/config.php`
3. Clear browser cookies and cache

### Blank White Page

**Problem:** Page shows blank white screen

**Solution:**
1. Enable error reporting in `config/config.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
2. Check Apache error logs in XAMPP
3. Verify all required PHP extensions are enabled

## Default Data

The database comes pre-populated with:

- **Admin User:** ekowme@gmail.com (password: password)
- **Sample Member:** john.doe@ug.edu.gh (password: password)
- **16 Ghana Regions** with codes
- **Sample Constituencies** for major regions
- **3 Universities** (UG, KNUST, UCC)
- **4 Campuses**
- **Dues for 2024 and 2025**
- **SMS Templates** for common communications

## Security Recommendations

### For Production Use

1. **Change Default Passwords**
   - Change admin password immediately
   - Remove or change sample user passwords

2. **Update Configuration**
   - Set `display_errors` to `0` in production
   - Use strong database passwords
   - Enable HTTPS (SSL certificate)

3. **File Permissions**
   - Restrict access to `config/` directory
   - Set proper file permissions (644 for files, 755 for directories)

4. **Database Security**
   - Create a dedicated MySQL user with limited privileges
   - Use strong database password
   - Restrict remote database access

5. **Backup Strategy**
   - Regular database backups
   - Backup uploaded files
   - Store backups securely off-site

## System Requirements

### Minimum Requirements
- PHP 7.4+
- MySQL 5.7+
- Apache 2.4+
- 512MB RAM
- 100MB Disk Space

### Recommended Requirements
- PHP 8.0+
- MySQL 8.0+
- Apache 2.4+
- 1GB RAM
- 500MB Disk Space

## Browser Compatibility

The application is compatible with:
- Google Chrome (latest)
- Mozilla Firefox (latest)
- Microsoft Edge (latest)
- Safari (latest)

## Getting Help

If you encounter issues:

1. Check this installation guide
2. Review the README.md file
3. Check XAMPP error logs
4. Verify all prerequisites are met
5. Contact the development team

## Next Steps

After successful installation:

1. ✅ Login with admin credentials
2. ✅ Change default password
3. ✅ Add your organization's regions (if not Ghana)
4. ✅ Add institutions and campuses
5. ✅ Create member accounts
6. ✅ Set up annual dues
7. ✅ Configure SMS/Payment APIs (optional)
8. ✅ Start managing your membership!

## Uninstallation

To remove the application:

1. Delete the project folder: `c:\xampp\htdocs\tescongh\`
2. Drop the database in phpMyAdmin: `DROP DATABASE tescon_ghana;`
3. Remove any backups you created

---

**Installation Complete!** 🎉

You're now ready to use the TESCON Ghana Membership Database system.
