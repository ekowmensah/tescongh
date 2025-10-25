<header class="header header-sticky mb-4">
    <div class="container-fluid">
        <button class="header-toggler px-md-0 me-md-3" type="button" onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()">
            <i class="fas fa-bars"></i>
        </button>
        
        <a class="header-brand d-md-none" href="index.php">
            <i class="fas fa-graduation-cap me-2"></i>
            TESCON
        </a>
        
        <ul class="header-nav ms-auto">
            <?php if (isLoggedIn()): ?>
            <!-- Notifications -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-coreui-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <span class="badge badge-sm bg-danger ms-auto">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end pt-0">
                    <div class="dropdown-header bg-light">
                        <strong>You have 0 notifications</strong>
                    </div>
                    <a class="dropdown-item" href="#">
                        <div class="text-muted small">No new notifications</div>
                    </a>
                </div>
            </li>
            <?php endif; ?>
        </ul>
        
        <ul class="header-nav ms-3">
            <?php if (isLoggedIn()): ?>
            <!-- User Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link py-0" data-coreui-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <div class="avatar avatar-md">
                        <?php if (isset($_SESSION['photo']) && $_SESSION['photo']): ?>
                            <img class="avatar-img" src="uploads/<?php echo htmlspecialchars($_SESSION['photo']); ?>" alt="User">
                        <?php else: ?>
                            <span class="avatar-initial rounded-circle bg-primary">
                                <?php echo strtoupper(substr($_SESSION['fullname'] ?? 'U', 0, 1)); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end pt-0">
                    <div class="dropdown-header bg-light py-2">
                        <div class="fw-semibold"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'User'); ?></div>
                        <div class="text-muted small"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Member'); ?></div>
                    </div>
                    <a class="dropdown-item" href="#">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                    <a class="dropdown-item" href="#">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </li>
            <?php else: ?>
            <!-- Login Button -->
            <li class="nav-item">
                <a class="btn btn-primary btn-sm" href="login.php">
                    <i class="fas fa-sign-in-alt me-1"></i> Login
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    
    <!-- Breadcrumb (optional) -->
    <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
    <div class="header-divider"></div>
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb my-0 ms-2">
                <li class="breadcrumb-item">
                    <a href="index.php">Home</a>
                </li>
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <?php if ($index === count($breadcrumbs) - 1): ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $crumb['title']; ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item">
                            <a href="<?php echo $crumb['url']; ?>"><?php echo $crumb['title']; ?></a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
    </div>
    <?php endif; ?>
</header>
