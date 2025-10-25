<div class="sidebar sidebar-dark sidebar-fixed" id="sidebar">
    <div class="sidebar-brand d-none d-md-flex">
        <i class="fas fa-graduation-cap me-2"></i>
        TESCON Ghana
    </div>
    
    <ul class="sidebar-nav" data-coreui="navigation" data-simplebar="">
        <!-- Dashboard -->
        <li class="nav-item">
            <a class="nav-link" href="index.php">
                <i class="nav-icon fas fa-home"></i> Dashboard
            </a>
        </li>
        
        <li class="nav-title">Member Area</li>
        
        <!-- Members -->
        <li class="nav-item">
            <a class="nav-link" href="members.php">
                <i class="nav-icon fas fa-users"></i> Members Directory
            </a>
        </li>
        
        <!-- Pay Dues -->
        <?php if (isLoggedIn()): ?>
        <li class="nav-item">
            <a class="nav-link" href="pay_dues.php">
                <i class="nav-icon fas fa-credit-card"></i> Pay Dues
            </a>
        </li>
        <?php endif; ?>
        
        <!-- Admin Section -->
        <?php if (isLoggedIn() && hasRole(['Admin', 'Executive', 'Patron'])): ?>
        <li class="nav-title">Administration</li>
        
        <!-- Campus Management -->
        <li class="nav-item">
            <a class="nav-link" href="campus_management.php">
                <i class="nav-icon fas fa-building"></i> Campus Management
            </a>
        </li>
        
        <!-- Location Management -->
        <li class="nav-item">
            <a class="nav-link" href="location_management.php">
                <i class="nav-icon fas fa-map-marker-alt"></i> Locations
            </a>
        </li>
        
        <!-- Dues Management -->
        <li class="nav-item">
            <a class="nav-link" href="dues_management.php">
                <i class="nav-icon fas fa-money-bill-wave"></i> Dues Management
            </a>
        </li>
        
        <!-- SMS Management -->
        <li class="nav-item">
            <a class="nav-link" href="sms_management.php">
                <i class="nav-icon fas fa-sms"></i> SMS Management
            </a>
        </li>
        
        <li class="nav-divider"></li>
        
        <!-- Reports -->
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="nav-icon fas fa-chart-bar"></i> Reports
            </a>
        </li>
        <?php endif; ?>
        
        <li class="nav-divider"></li>
        
        <!-- Account -->
        <?php if (!isLoggedIn()): ?>
        <li class="nav-title">Account</li>
        <li class="nav-item">
            <a class="nav-link" href="login.php">
                <i class="nav-icon fas fa-sign-in-alt"></i> Member Login
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admin_login.php">
                <i class="nav-icon fas fa-shield-alt"></i> Admin Login
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="register.php">
                <i class="nav-icon fas fa-user-plus"></i> Register
            </a>
        </li>
        <?php endif; ?>
    </ul>
    
    <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
</div>
