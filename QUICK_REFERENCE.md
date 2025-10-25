# TESCON Ghana Membership Database - Quick Reference Guide

## 🚀 Quick Start (5 Minutes)

1. Extract to `c:\xampp\htdocs\tescongh\`
2. Start XAMPP (Apache + MySQL)
3. Import `schema.sql` in phpMyAdmin
4. Create `uploads` folder
5. Visit `http://localhost/tescongh`
6. Login: `ekowme@gmail.com` / `password`

## 🔑 Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | ekowme@gmail.com | password |
| Member | john.doe@ug.edu.gh | password |

**⚠️ Change passwords immediately after first login!**

## 📁 File Structure

```
tescongh/
├── classes/         # PHP classes
├── config/          # Configuration
├── includes/        # Shared files
├── uploads/         # File uploads
├── *.php           # Main pages
└── schema.sql      # Database
```

## 🎯 Main Features & Pages

| Feature | URL | Access Level |
|---------|-----|--------------|
| Dashboard | `/dashboard.php` | All |
| Members | `/members.php` | All |
| Regions | `/regions.php` | Admin, Executive |
| Institutions | `/institutions.php` | Admin, Executive |
| Campuses | `/campuses.php` | Admin, Executive |
| Dues | `/dues.php` | All |
| Payments | `/payments.php` | All |
| Events | `/events.php` | All |
| Profile | `/profile.php` | All |

## 👥 User Roles & Permissions

| Feature | Admin | Executive | Patron | Member |
|---------|-------|-----------|--------|--------|
| View Dashboard | ✅ | ✅ | ✅ | ✅ |
| Manage Members | ✅ | ✅ | ✅ | ❌ |
| Manage Regions | ✅ | ✅ | ❌ | ❌ |
| Manage Institutions | ✅ | ✅ | ❌ | ❌ |
| Send SMS | ✅ | ✅ | ❌ | ❌ |
| Manage Users | ✅ | ❌ | ❌ | ❌ |
| View Reports | ✅ | ✅ | ✅ | ❌ |
| Edit Own Profile | ✅ | ✅ | ✅ | ✅ |

## 🗄️ Database Tables

| Table | Purpose |
|-------|---------|
| users | Authentication |
| members | Member profiles |
| regions | Ghana regions |
| constituencies | Electoral areas |
| institutions | Universities/Colleges |
| campuses | Campus locations |
| dues | Annual fees |
| payments | Payment records |
| events | Event information |
| sms_logs | SMS history |

## ⚙️ Configuration Files

### `config/config.php`
```php
// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tescon_ghana');

// SMS API (Hubtel)
define('SMS_API_KEY', '');
define('SMS_API_SECRET', '');

// Payment API (Hubtel)
define('HUBTEL_CLIENT_ID', '');
define('HUBTEL_CLIENT_SECRET', '');
```

## 🔧 Common Tasks

### Add New Member
1. Go to Members → Add Member
2. Fill in personal details
3. Add academic information
4. Upload photo (optional)
5. Save

### Record Payment
1. Go to Payments → Record Payment
2. Select member
3. Select dues year
4. Enter amount and method
5. Add transaction ID
6. Save

### Create Event
1. Go to Events → Create Event
2. Enter title and description
3. Set date, time, location
4. Save

### Send SMS
1. Go to SMS
2. Select recipients
3. Choose template or write message
4. Send

### Add Region
1. Go to Regions → Add Region
2. Enter name and code
3. Save

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| Can't login | Check MySQL is running, verify credentials |
| Database error | Import schema.sql, check config.php |
| 404 errors | Verify files in correct directory |
| Upload fails | Create uploads folder, check permissions |
| Blank page | Enable error reporting in config.php |

## 📊 Statistics & Reports

### Dashboard Metrics
- Total Members
- Active Members
- Executives Count
- Patrons Count
- Members by Status
- Top Regions

### Payment Statistics
- Total Collected
- Pending Payments
- Failed Payments
- Payment Methods

## 🔐 Security Checklist

- [ ] Change default admin password
- [ ] Change sample user password
- [ ] Set strong database password
- [ ] Disable error display in production
- [ ] Enable HTTPS
- [ ] Regular backups
- [ ] Update PHP/MySQL
- [ ] Restrict file permissions

## 📱 Payment Methods

| Method | Code | Badge Color |
|--------|------|-------------|
| Mobile Money | hubtel_mobile | Primary (Blue) |
| Card Payment | hubtel_card | Success (Green) |
| Bank Transfer | bank_transfer | Info (Cyan) |
| Cash | cash | Secondary (Gray) |

## 🎨 Status Colors

| Status | Badge Color |
|--------|-------------|
| Active | Success (Green) |
| Inactive | Secondary (Gray) |
| Suspended | Danger (Red) |
| Graduated | Info (Cyan) |
| Pending | Warning (Yellow) |
| Completed | Success (Green) |
| Failed | Danger (Red) |

## 📞 Contact & Support

### Getting Help
1. Check documentation files
2. Review error logs
3. Verify configuration
4. Check XAMPP logs
5. Contact development team

### Documentation Files
- `README.md` - Overview
- `INSTALLATION.md` - Setup guide
- `FEATURES.md` - Feature list
- `PROJECT_SUMMARY.md` - Technical details
- `QUICK_REFERENCE.md` - This file

## 🔄 Backup & Restore

### Backup Database
```sql
-- In phpMyAdmin, select tescon_ghana database
-- Click Export → Go
-- Save SQL file
```

### Backup Files
- Copy entire `tescongh` folder
- Especially backup `uploads` folder

### Restore
1. Import SQL file in phpMyAdmin
2. Copy files back to htdocs
3. Verify configuration

## 📈 Performance Tips

- Keep database optimized
- Regular cleanup of old records
- Optimize images before upload
- Use pagination for large lists
- Enable caching
- Monitor disk space

## 🎓 Ghana Regions (Pre-configured)

1. Greater Accra (GAR)
2. Ashanti (ASR)
3. Central (CR)
4. Western (WR)
5. Eastern (ER)
6. Volta (VR)
7. Northern (NR)
8. Upper East (UER)
9. Upper West (UWR)
10. Oti (OR)
11. Bono (BR)
12. Bono East (BER)
13. Ahafo (AR)
14. North East (NER)
15. Savannah (SR)
16. Western North (WNR)

## 🏫 Sample Institutions

- University of Ghana (UG)
- Kwame Nkrumah University of Science and Technology (KNUST)
- University of Cape Coast (UCC)

## 💡 Tips & Best Practices

### For Administrators
- Regular database backups
- Monitor user activity
- Keep software updated
- Review security logs
- Train executives on system use

### For Members
- Keep profile updated
- Pay dues on time
- Check events regularly
- Update contact information
- Upload clear profile photo

### For Executives
- Verify new member details
- Confirm payments promptly
- Send timely SMS reminders
- Create events in advance
- Track attendance accurately

## 🚨 Emergency Procedures

### Forgot Admin Password
1. Access database via phpMyAdmin
2. Go to `users` table
3. Update password field with:
   ```
   $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
   ```
4. Login with password: `password`
5. Change immediately

### Database Corruption
1. Restore from latest backup
2. If no backup, re-import schema.sql
3. Re-enter data manually

### System Not Accessible
1. Check XAMPP services running
2. Verify Apache on port 80
3. Check MySQL on port 3306
4. Review Apache error logs
5. Check file permissions

## 📋 Keyboard Shortcuts

| Action | Shortcut |
|--------|----------|
| Search | Ctrl + F |
| Save Form | Ctrl + Enter |
| Close Modal | Esc |
| Navigate Back | Alt + ← |

## 🎯 Quick Links

- phpMyAdmin: `http://localhost/phpmyadmin`
- Application: `http://localhost/tescongh`
- XAMPP Control: Start → XAMPP Control Panel

---

**Keep this guide handy for quick reference!** 📚

For detailed information, refer to the full documentation files.
