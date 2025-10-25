<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">TESCON Ghana</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="members.php">Members</a>
                </li>
                <?php endif; ?>
                <?php if (!isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="register.php">Register</a>
                </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="pay_dues.php">Pay Dues</a>
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['Executive', 'Patron', 'Admin'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="campus_management.php">Campus Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="location_management.php">Location Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dues_management.php">Dues Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sms_management.php">SMS Management</a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="loginDropdown" role="button" data-bs-toggle="dropdown">
                            Login
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="login.php">Member Login</a></li>
                            <li><a class="dropdown-item" href="admin_login.php">Admin Login</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
