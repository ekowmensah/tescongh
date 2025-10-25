# TESCON Ghana - Membership Management System

## 🎓 About

TESCON Ghana (Tertiary Students Confederacy of the New Patriotic Party) Membership Management System - A modern, secure, and professional web application for managing student members across Ghana's tertiary institutions.

---

## ✨ Features

### **Member Management**
- Member registration with photo upload
- Member directory with search and filtering
- Role-based access control (Member, Executive, Patron, Admin)
- Campus-based member organization
- Membership status tracking

### **Payment System**
- Hubtel payment integration
- Annual dues management
- Payment history tracking
- Multiple payment methods support

### **Location Management**
- 16 Ghana regions
- Constituencies management
- Institutions database
- Campus management

### **Communication**
- SMS notifications via Hubtel
- SMS templates
- Bulk SMS sending
- SMS delivery tracking

### **Security**
- CSRF protection on all forms
- Rate limiting on login attempts
- Security event logging
- Role-based access control
- Secure session management
- Input sanitization
- Environment-based configuration

---

## 🎨 **UI Framework: CoreUI**

The system uses **CoreUI 4.2** - a professional admin dashboard template with:
- ✅ Sidebar navigation
- ✅ Top header with user menu
- ✅ Breadcrumbs
- ✅ Modern card-based layouts
- ✅ Responsive design
- ✅ Professional color scheme
- ✅ Enterprise-grade UI components

---

## 🚀 Installation

### **Prerequisites**
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (optional)

### **Setup Steps**

1. **Clone/Download the repository**
```bash
cd /path/to/webroot
git clone [repository-url] tescongh
cd tescongh
```

2. **Create Database**
```sql
CREATE DATABASE tescon_ghana CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. **Import Database Schema**
```bash
mysql -u root -p tescon_ghana < database/schema.sql
```

4. **Configure Environment**
```bash
cp .env.example .env
nano .env
```

Update `.env` with your credentials:
```env
DB_HOST=localhost
DB_NAME=tescon_ghana
DB_USER=root
DB_PASS=your_password

HUBTEL_CLIENT_ID=your_client_id
HUBTEL_CLIENT_SECRET=your_client_secret
HUBTEL_MERCHANT_NUMBER=your_merchant_number
```

5. **Set Permissions**
```bash
chmod 755 uploads/
chmod 755 logs/
chmod 644 .env
```

6. **Access the Application**
```
http://localhost/tescongh
```

### **Default Login**
- **Admin Email**: ekowme@gmail.com
- **Password**: password
- **Member Student ID**: UGCS12345
- **Password**: password

---

## 📁 Project Structure

```
tescongh/
├── config/
│   ├── database.php          # Database connection
│   ├── env.php               # Environment loader
│   └── hubtel.php            # Hubtel API config
│
├── includes/
│   ├── security.php          # Security functions
│   ├── coreui_head.php       # CoreUI head template
│   ├── coreui_sidebar.php    # Sidebar navigation
│   ├── coreui_header.php     # Top header
│   ├── coreui_footer.php     # Footer
│   ├── coreui_scripts.php    # JavaScript includes
│   ├── coreui_layout_start.php  # Layout wrapper start
│   ├── coreui_layout_end.php    # Layout wrapper end
│   ├── FileUpload.php        # File upload handler
│   ├── HubtelPayment.php     # Payment integration
│   └── SMSNotifications.php  # SMS integration
│
├── database/
│   └── schema.sql            # Database schema
│
├── uploads/                  # User uploads
├── logs/                     # Application logs
│
├── index.php                 # Dashboard (CoreUI)
├── login.php                 # Member login
├── admin_login.php           # Admin login
├── register.php              # Member registration
├── members.php               # Members directory
├── pay_dues.php              # Payment page
├── campus_management.php     # Campus CRUD
├── location_management.php   # Locations CRUD
├── dues_management.php       # Dues configuration
├── sms_management.php        # SMS management
└── logout.php                # Logout handler
```

---

## 🔒 Security Features

### **Implemented**
- ✅ Environment-based configuration
- ✅ CSRF token protection
- ✅ Rate limiting (5 attempts/5 minutes)
- ✅ Security event logging
- ✅ Input sanitization
- ✅ Secure session management
- ✅ Role-based access control
- ✅ Password hashing (bcrypt)
- ✅ SQL injection prevention (prepared statements)

### **Security Functions Available**
```php
startSecureSession()           // Initialize secure session
isLoggedIn()                   // Check if user is logged in
hasRole(['Admin'])             // Check user role
requireLogin()                 // Redirect if not logged in
requireRole(['Admin'])         // Redirect if wrong role
generateCSRFToken()            // Generate CSRF token
verifyCSRFToken($token)        // Verify CSRF token
sanitizeInput($data)           // Sanitize user input
checkRateLimit('action', 5, 300)  // Rate limiting
logSecurityEvent('event', [])  // Log security events
```

---

## 📚 Documentation

- **SECURITY_AUDIT.md** - Security improvements and checklist
- **SYSTEM_IMPROVEMENTS.md** - Architecture recommendations
- **IMPLEMENTATION_GUIDE.md** - Step-by-step guide
- **REFACTORING_GUIDE.md** - Code refactoring patterns
- **COREUI_IMPLEMENTATION.md** - CoreUI integration guide
- **FINAL_REFACTORING_SUMMARY.md** - Complete refactoring summary

---

## 🎯 Key Technologies

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **UI Framework**: CoreUI 4.2
- **CSS Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6.4
- **DataTables**: 1.13.6
- **Payment**: Hubtel API
- **SMS**: Hubtel SMS API

---

## 🔧 Configuration

### **Database Optimization**
Run these SQL commands for better performance:
```sql
ALTER TABLE members ADD INDEX idx_campus_status (campus_id, membership_status);
ALTER TABLE members ADD INDEX idx_student_id (student_id);
ALTER TABLE members ADD INDEX idx_email (email);
ALTER TABLE users ADD INDEX idx_email (email);
ALTER TABLE payments ADD INDEX idx_member_status (member_id, status);
ALTER TABLE sms_logs ADD INDEX idx_sent_at (sent_at);
```

### **PHP Configuration (php.ini)**
```ini
; Production settings
display_errors = Off
log_errors = On
error_log = /path/to/tescongh/logs/php_error.log

; Performance
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000

; Security
expose_php = Off
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

---

## 📱 Mobile Support

The system is fully responsive and works on:
- ✅ Desktop (1920px+)
- ✅ Laptop (1366px+)
- ✅ Tablet (768px+)
- ✅ Mobile (320px+)

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📝 License

This project is proprietary software owned by TESCON Ghana.

---

## 👥 Support

For support, email: info@tesconghana.org

---

## 🎉 Acknowledgments

- TESCON Ghana National Executives
- TESCON Tech Team
- All contributing developers

---

## 📊 System Status

- **Version**: 2.0.0
- **Status**: Production Ready
- **Last Updated**: October 2025
- **Security**: ✅ Fully Secured
- **UI**: ✅ CoreUI Implemented
- **Mobile**: ✅ Fully Responsive
- **Documentation**: ✅ Complete

---

**Built with ❤️ for TESCON Ghana**
