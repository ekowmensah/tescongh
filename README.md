# TESCON Ghana Membership Database

A comprehensive membership management system for TESCON Ghana built with PHP, CoreUI, and MySQL.

## Features

### Core Functionality
- **User Authentication**: Secure login/logout with role-based access control
- **Member Management**: Complete CRUD operations for member records
- **Institution Management**: Manage universities, polytechnics, and colleges
- **Campus Management**: Track multiple campuses per institution
- **Region & Constituency Management**: Organize members by geographical location

### Financial Management
- **Dues Management**: Set and track annual membership dues
- **Payment Processing**: Record and manage payments with multiple payment methods
- **Hubtel Integration**: Support for Hubtel mobile money and card payments
- **Payment Tracking**: Monitor payment status and generate reports

### Communication
- **SMS System**: Send bulk SMS to members using Hubtel SMS API
- **SMS Templates**: Pre-defined message templates for common communications
- **SMS Logs**: Track all sent messages and delivery status

### Events & Attendance
- **Event Management**: Create and manage TESCON events
- **Attendance Tracking**: Record member attendance at events

### Reporting & Analytics
- **Dashboard**: Overview of key metrics and statistics
- **Member Statistics**: Track active members, executives, and patrons
- **Regional Distribution**: View member distribution by region
- **Payment Reports**: Financial reports and payment tracking

## User Roles

1. **Admin**: Full system access, user management, and configuration
2. **Executive**: Member management, event creation, SMS sending
3. **Patron**: View access and limited member management
4. **Member**: Personal profile management and dues payment

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: CoreUI 4.2 (Bootstrap 5)
- **Icons**: CoreUI Icons
- **DataTables**: jQuery DataTables for enhanced table functionality

## Installation

### Prerequisites
- XAMPP (or any PHP/MySQL environment)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

### Setup Instructions

1. **Clone or Extract Files**
   ```
   Place the project folder in: c:\xampp\htdocs\tescongh
   ```

2. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the `schema.sql` file to create the database and tables
   - The script will create the database `tescon_ghana` with sample data

3. **Configure Application**
   - Open `config/config.php`
   - Update database credentials if needed (default: root with no password)
   - Configure SMS and payment API keys if you have them

4. **Set Permissions**
   - Create an `uploads` folder in the root directory
   - Ensure it has write permissions for photo uploads

5. **Access Application**
   - Open your browser and navigate to: http://localhost/tescongh
   - Default login credentials:
     - Email: ekowme@gmail.com
     - Password: password

## Directory Structure

```
tescongh/
├── classes/           # PHP classes for business logic
│   ├── User.php
│   ├── Member.php
│   ├── Region.php
│   ├── Institution.php
│   ├── Campus.php
│   ├── Dues.php
│   └── Payment.php
├── config/           # Configuration files
│   ├── config.php
│   └── Database.php
├── includes/         # Shared includes
│   ├── header.php
│   ├── footer.php
│   ├── auth.php
│   └── functions.php
├── uploads/          # File uploads directory
├── dashboard.php     # Main dashboard
├── members.php       # Member listing
├── login.php         # Login page
├── logout.php        # Logout handler
├── index.php         # Entry point
├── schema.sql        # Database schema
└── README.md         # This file
```

## Configuration

### Database Configuration
Edit `config/config.php` to update database settings:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tescon_ghana');
```

### SMS Configuration (Hubtel)
Add your Hubtel API credentials in `config/config.php`:
```php
define('SMS_API_KEY', 'your-api-key');
define('SMS_API_SECRET', 'your-api-secret');
define('SMS_SENDER_ID', 'TESCON-GH');
```

### Payment Configuration (Hubtel)
Add your Hubtel payment credentials in `config/config.php`:
```php
define('HUBTEL_CLIENT_ID', 'your-client-id');
define('HUBTEL_CLIENT_SECRET', 'your-client-secret');
define('HUBTEL_MERCHANT_ACCOUNT', 'your-merchant-account');
```

## Usage

### Managing Members
1. Navigate to **Members** from the sidebar
2. Click **Add Member** to create a new member
3. Fill in member details including personal info, institution, and region
4. Upload a photo (optional)
5. Save the member record

### Managing Dues
1. Navigate to **Dues** from the sidebar
2. Create annual dues with year, amount, and due date
3. Members can view and pay their dues
4. Track payment status in the Payments section

### Sending SMS
1. Navigate to **SMS** from the sidebar (Admin/Executive only)
2. Select recipients (all members, by region, or by status)
3. Choose a template or write a custom message
4. Send SMS to selected members

### Viewing Reports
1. Navigate to **Reports** from the sidebar (Admin only)
2. View member statistics, payment reports, and regional distribution
3. Export reports as needed

## Security Features

- Password hashing using bcrypt
- SQL injection prevention using prepared statements
- XSS protection through input sanitization
- Session timeout after inactivity
- Role-based access control
- CSRF protection (recommended to implement)

## API Integration

### Hubtel SMS API
The system supports Hubtel SMS API for sending bulk messages. Configure your API credentials in `config/config.php`.

### Hubtel Payment API
Payment processing through Hubtel mobile money and card payments. Configure your merchant credentials in `config/config.php`.

## Troubleshooting

### Database Connection Issues
- Verify MySQL is running in XAMPP
- Check database credentials in `config/config.php`
- Ensure the database `tescon_ghana` exists

### File Upload Issues
- Create `uploads` folder in root directory
- Check folder permissions (should be writable)
- Verify `MAX_FILE_SIZE` in config

### Login Issues
- Clear browser cache and cookies
- Check if session is enabled in PHP
- Verify user exists in database

## Future Enhancements

- Email notifications
- Advanced reporting with charts
- Member portal for self-service
- Mobile app integration
- Document management
- Online payment gateway integration
- Export to Excel/PDF
- Multi-language support

## Support

For issues or questions, contact the development team or refer to the documentation.

## License

Copyright © 2025 TESCON Ghana. All rights reserved.

## Credits

Developed for TESCON Ghana Membership Management
