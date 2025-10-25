# View Files Refactoring Guide

## ✅ COMPLETED

### index.php
- ✅ Added security functions
- ✅ Modern hero section with CTAs
- ✅ Feature cards section
- ✅ Statistics dashboard (for logged-in users)
- ✅ Call-to-action section
- ✅ Uses new template system

## 🔄 REFACTORING PATTERN

All view files should follow this pattern:

```php
<?php
/**
 * Page Title - Description
 */
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();

// Add protection if needed
// requireLogin(); // For member-only pages
// requireRole(['Admin', 'Executive']); // For admin pages

$pageTitle = "Page Title";
$useDataTables = true; // if using DataTables
$useCharts = true; // if using Chart.js

include 'includes/head.php';
include 'includes/header.php';
?>

<!-- Page Content Here -->

<?php
include 'includes/footer.php';
include 'includes/scripts.php';
?>
```

## 📝 REFACTORING CHECKLIST FOR EACH FILE

### login.php
```php
<?php
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$pageTitle = "Member Login";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting
    if (!checkRateLimit('login', 5, 300)) {
        $error = 'Too many login attempts. Please try again in 5 minutes.';
    } elseif (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
        logSecurityEvent('csrf_failure', ['page' => 'login']);
    } else {
        $student_id = sanitizeInput($_POST['student_id']);
        $password = $_POST['password'];
        
        // Existing login logic...
    }
}

include 'includes/head.php';
include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>Member Login</h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="student_id" class="form-label required">Student ID</label>
                            <input type="text" class="form-control" id="student_id" name="student_id" required autofocus>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label required">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p class="mb-1">Don't have an account? <a href="register.php">Register here</a></p>
                        <p class="mb-0"><small class="text-muted">Admin? <a href="admin_login.php">Login here</a></small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
include 'includes/scripts.php';
?>
```

### admin_login.php
Similar to login.php but:
- Change title to "Admin Login"
- Use email instead of student_id
- Add admin-specific styling
- Redirect to appropriate admin page after login

### register.php
- Add CSRF protection
- Use new template system
- Keep existing form structure
- Add better validation feedback
- Improve mobile responsiveness

### members.php
```php
<?php
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();
requireLogin(); // Must be logged in

$pageTitle = "Members Directory";
$useDataTables = true;

// Existing member logic...

include 'includes/head.php';
include 'includes/header.php';
?>

<!-- Keep existing content but improve styling -->

<?php
include 'includes/footer.php';
include 'includes/scripts.php';
?>
```

### Admin Pages (campus_management.php, location_management.php, dues_management.php, sms_management.php)
```php
<?php
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();
requireRole(['Admin', 'Executive', 'Patron']); // Admin only

$pageTitle = "Page Title";
$useDataTables = true; // Most admin pages use tables

// Existing logic...

include 'includes/head.php';
include 'includes/header.php';
?>

<!-- Keep existing content -->

<?php
include 'includes/footer.php';
include 'includes/scripts.php';
?>
```

### pay_dues.php
```php
<?php
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();
requireLogin(); // Must be logged in

$pageTitle = "Pay Membership Dues";

// Existing logic...

include 'includes/head.php';
include 'includes/header.php';
?>

<!-- Keep existing content -->

<?php
include 'includes/footer.php';
include 'includes/scripts.php';
?>
```

## 🎨 UI IMPROVEMENTS TO ADD

### 1. Better Alert Messages
```php
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
```

### 2. Better Form Styling
```html
<div class="mb-3">
    <label for="field" class="form-label required">Field Name</label>
    <input type="text" class="form-control" id="field" name="field" required>
    <div class="form-text">Helper text if needed</div>
</div>
```

### 3. Better Buttons
```html
<!-- Primary Action -->
<button type="submit" class="btn btn-primary">
    <i class="fas fa-save me-2"></i>Save
</button>

<!-- Secondary Action -->
<button type="button" class="btn btn-outline-secondary">
    <i class="fas fa-times me-2"></i>Cancel
</button>

<!-- Danger Action -->
<button type="button" class="btn btn-danger" onclick="return confirmDelete()">
    <i class="fas fa-trash me-2"></i>Delete
</button>
```

### 4. Better Cards
```html
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-icon me-2"></i>Card Title</h5>
    </div>
    <div class="card-body">
        <!-- Content -->
    </div>
</div>
```

### 5. Better Tables
```html
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Table Title</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Column</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Rows -->
                </tbody>
            </table>
        </div>
    </div>
</div>
```

## 🔒 SECURITY ADDITIONS

### 1. Add to ALL Forms
```html
<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
```

### 2. Add to ALL POST Handlers
```php
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid request';
    logSecurityEvent('csrf_failure', ['page' => basename(__FILE__)]);
} else {
    // Process form
}
```

### 3. Add to Login Pages
```php
if (!checkRateLimit('login', 5, 300)) {
    $error = 'Too many attempts. Try again in 5 minutes.';
}
```

### 4. Sanitize ALL Inputs
```php
$input = sanitizeInput($_POST['field']);
```

## 📱 RESPONSIVE IMPROVEMENTS

### 1. Stack on Mobile
```html
<div class="row">
    <div class="col-12 col-md-6">
        <!-- Stacks on mobile, side-by-side on desktop -->
    </div>
    <div class="col-12 col-md-6">
        <!-- Stacks on mobile, side-by-side on desktop -->
    </div>
</div>
```

### 2. Hide on Mobile
```html
<div class="d-none d-md-block">
    <!-- Only shows on medium screens and up -->
</div>
```

### 3. Responsive Tables
```html
<div class="table-responsive">
    <table class="table">
        <!-- Table content -->
    </table>
</div>
```

## ✅ FINAL CHECKLIST FOR EACH FILE

- [ ] Uses `startSecureSession()`
- [ ] Uses `requireLogin()` or `requireRole()` if needed
- [ ] Includes `head.php` and `header.php`
- [ ] Includes `footer.php` and `scripts.php`
- [ ] Has CSRF protection on forms
- [ ] Sanitizes all user inputs
- [ ] Uses modern card/alert styling
- [ ] Responsive on mobile
- [ ] Has proper error handling
- [ ] Uses Font Awesome icons
- [ ] Has proper page title
- [ ] Follows consistent code style

## 🚀 QUICK REFACTOR SCRIPT

To quickly refactor a file:

1. Add security at top
2. Replace old header with new templates
3. Add CSRF to forms
4. Improve styling with cards/alerts
5. Add icons to buttons
6. Test functionality
7. Test on mobile

## 📊 PRIORITY ORDER

1. **High Priority** (Security-critical):
   - login.php
   - admin_login.php
   - register.php

2. **Medium Priority** (User-facing):
   - members.php
   - pay_dues.php

3. **Lower Priority** (Admin-only):
   - campus_management.php
   - location_management.php
   - dues_management.php
   - sms_management.php
