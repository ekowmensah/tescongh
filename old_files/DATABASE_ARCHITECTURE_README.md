# TESCON Ghana Database Architecture Update

## Overview
The TESCON Ghana membership system has been updated to implement proper separation of concerns with dedicated `users` and `members` tables. This follows database design best practices and enables better scalability and security.

## Architecture Changes

### Before (Single Table Approach)
- **members table**: Contained both authentication data (email, password) and membership data
- **Issues**: Tight coupling, limited extensibility, authentication mixed with business logic

### After (Separated Tables Approach)
- **users table**: Authentication and user account management
- **members table**: TESCON-specific membership data linked to users

## Database Schema

### Users Table (`users`)
```sql
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Member','Executive','Patron','Admin') NOT NULL DEFAULT 'Member',
  `status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `phone_verified` tinyint(1) NOT NULL DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `status` (`status`),
  KEY `role` (`role`)
)
```

### Members Table (`members`)
```sql
CREATE TABLE `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `institution` varchar(100) NOT NULL,
  `program` varchar(100) NOT NULL,
  `year` varchar(20) NOT NULL,
  `position` enum('Member','Executive','Patron') NOT NULL DEFAULT 'Member',
  `constituency` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `campus_id` int(11) DEFAULT NULL,
  `membership_status` enum('Active','Inactive','Suspended','Graduated') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `campus_id` (`campus_id`),
  KEY `membership_status` (`membership_status`),
  CONSTRAINT `members_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `members_ibfk_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL
)
```

## Benefits of New Architecture

### 1. **Separation of Concerns**
- **Authentication**: Handled by `users` table
- **Membership Data**: Isolated in `members` table
- **Clear Boundaries**: Each table has a single responsibility

### 2. **Enhanced Security**
- **Role-Based Access**: Users can have roles independent of membership status
- **Account Management**: User accounts can be suspended without affecting membership records
- **Audit Trail**: Better tracking of user activities

### 3. **Improved Scalability**
- **Future Extensions**: Easy to add non-member users (admins, staff)
- **Multiple Memberships**: Users could potentially have multiple membership types
- **Flexible Roles**: Role system can be extended without database changes

### 4. **Better Data Integrity**
- **Referential Integrity**: Foreign key constraints ensure data consistency
- **Cascade Deletes**: Removing a user automatically cleans up related records
- **Status Management**: Separate user and membership status tracking

## Migration Process

### Step 1: Update Database Schema
Run the updated `database/schema.sql` to create the new table structure.

### Step 2: Run Migration Script
Execute `migrate_database.php` to migrate existing data:
```bash
php migrate_database.php
```

This script will:
- Create user accounts from existing member data
- Link members to their corresponding users
- Preserve all existing data and relationships

### Step 3: Update Application Code
All PHP files have been updated to work with the new structure:
- `login.php`: Now authenticates against `users` table
- `register.php`: Creates both user and member records
- `members.php`: Joins users and members tables
- All admin interfaces updated for role-based access

## Data Relationships

### User Account Creation Flow
1. **Registration**: User provides email/password
2. **User Record**: Created in `users` table with role and status
3. **Member Record**: Created in `members` table linked to user
4. **Verification**: Email/phone verification can be added later

### Authentication Flow
1. **Login**: User enters email/password
2. **User Lookup**: Query `users` table for credentials
3. **Member Data**: Join with `members` table for profile info
4. **Session**: Store user ID, role, and member data

## Role-Based Access Control

### User Roles
- **Member**: Basic membership access
- **Executive**: Campus leadership, can access management features
- **Patron**: Senior leadership, full system access
- **Admin**: System administrators (future use)

### Permission Matrix
| Feature | Member | Executive | Patron | Admin |
|---------|--------|-----------|--------|-------|
| View Members | ✓ | ✓ | ✓ | ✓ |
| Pay Dues | ✓ | ✓ | ✓ | ✓ |
| Campus Management | ✗ | ✓ | ✓ | ✓ |
| Dues Management | ✗ | ✓ | ✓ | ✓ |
| SMS Management | ✗ | ✓ | ✓ | ✓ |

## API Changes

### Session Variables Updated
```php
$_SESSION['user_id']     // User ID (from users table)
$_SESSION['role']        // User role (Member, Executive, etc.)
$_SESSION['fullname']    // From members table
$_SESSION['email']       // From users table
$_SESSION['photo']       // From members table
```

### Query Examples

#### Get User with Member Data
```php
SELECT u.*, m.fullname, m.photo
FROM users u
LEFT JOIN members m ON u.id = m.user_id
WHERE u.email = ?
```

#### Get Members with User Status
```php
SELECT m.*, u.email, u.role, u.status as user_status
FROM members m
JOIN users u ON m.user_id = u.id
```

## Future Enhancements

### 1. **User Management**
- User profile editing
- Password reset functionality
- Email verification system
- Two-factor authentication

### 2. **Advanced Roles**
- Custom permission system
- Role hierarchies
- Group-based access control

### 3. **Audit System**
- User activity logging
- Login attempt monitoring
- Data change tracking

## Migration Checklist

- [x] Database schema updated
- [x] Migration script created
- [x] PHP files updated
- [x] Session handling updated
- [x] Role-based access implemented
- [x] Foreign key constraints added
- [x] Sample data updated
- [ ] **Run migration script**
- [ ] **Test user login**
- [ ] **Verify all features work**
- [ ] **Remove migration script**

## Troubleshooting

### Common Issues
1. **Login fails**: Check if migration ran successfully
2. **Missing data**: Ensure foreign key relationships are correct
3. **Permission errors**: Verify user roles are set correctly
4. **Session issues**: Check session variable names

### Rollback Plan
If migration fails:
1. Restore database backup
2. Revert PHP files to previous version
3. Contact system administrator

## Best Practices

### Development
- Always join users and members tables for user data
- Use role-based checks for permissions
- Validate data integrity across related tables

### Security
- Hash passwords properly
- Use prepared statements
- Implement proper session management
- Log security events

### Performance
- Index foreign key columns
- Use appropriate JOIN operations
- Cache frequently accessed user data
- Monitor query performance

This architecture provides a solid foundation for the TESCON Ghana system with proper separation of authentication and business logic, enabling future scalability and feature development.
