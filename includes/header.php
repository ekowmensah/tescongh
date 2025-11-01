<!DOCTYPE html>
<html lang="en">
<head>
    <base href="./">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    
    <!-- CoreUI CSS -->
    <link rel="stylesheet" href="https://unpkg.com/@coreui/coreui@4.2.0/dist/css/coreui.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/icons@3.0.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-blue: #1e40af;
            --secondary-blue: #3b82f6;
            --light-blue: #dbeafe;
            --primary-red: #dc2626;
            --secondary-red: #ef4444;
            --light-red: #fee2e2;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }
        
        .sidebar {
            --cui-sidebar-bg: linear-gradient(180deg, var(--primary-blue) 0%, #1e3a8a 100%);
            --cui-sidebar-nav-link-color: rgba(255, 255, 255, 0.9);
            --cui-sidebar-nav-link-hover-color: #fff;
            --cui-sidebar-nav-link-hover-bg: rgba(220, 38, 38, 0.2);
            --cui-sidebar-nav-link-active-color: #fff;
            --cui-sidebar-nav-link-active-bg: var(--primary-red);
            background: linear-gradient(180deg, var(--primary-blue) 0%, #1e3a8a 100%);
        }
        
        .sidebar-brand {
            background: var(--primary-red);
            padding: 1.25rem;
            font-size: 1.4rem;
            font-weight: 800;
            color: white;
            text-align: center;
            letter-spacing: 1px;
            border-bottom: 3px solid var(--white);
        }
        
        .header {
            background: var(--white);
            box-shadow: 0 2px 8px rgba(30, 64, 175, 0.1);
            border-bottom: 2px solid var(--light-blue);
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            color: white;
            font-weight: 600;
            border-bottom: none;
        }
        
        .stat-card {
            border-left: 4px solid;
            border-radius: 8px;
        }
        
        .stat-card.primary {
            border-left-color: var(--primary-blue);
            background: linear-gradient(135deg, var(--light-blue) 0%, var(--white) 100%);
        }
        
        .stat-card.success {
            border-left-color: #10b981;
            background: linear-gradient(135deg, #d1fae5 0%, var(--white) 100%);
        }
        
        .stat-card.warning {
            border-left-color: #f59e0b;
            background: linear-gradient(135deg, #fef3c7 0%, var(--white) 100%);
        }
        
        .stat-card.danger {
            border-left-color: var(--primary-red);
            background: linear-gradient(135deg, var(--light-red) 0%, var(--white) 100%);
        }
        
        .stat-card.info {
            border-left-color: var(--secondary-blue);
            background: linear-gradient(135deg, var(--light-blue) 0%, var(--white) 100%);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            border: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1e3a8a 0%, var(--primary-blue) 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--primary-red) 0%, var(--secondary-red) 100%);
            border: none;
            font-weight: 600;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #b91c1c 0%, var(--primary-red) 100%);
        }
        
        .avatar-initials {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-red) 0%, var(--secondary-red) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border: 2px solid var(--white);
        }
        
        .table-actions {
            white-space: nowrap;
        }
        
        .badge {
            padding: 0.35em 0.65em;
        }
        
        /* Fix sidebar overlap issue */
        @media (min-width: 768px) {
            .wrapper {
                margin-left: 256px;
            }
        }
        
        .sidebar {
            position: fixed;
            z-index: 1030;
        }
        
        /* Sticky sidebar for forms */
        .sticky-sidebar {
            position: sticky;
            top: 20px;
            z-index: 100;
        }
    </style>
</head>
<body>
    <div class="sidebar sidebar-dark sidebar-fixed" id="sidebar">
        <div class="sidebar-brand">
            <span>TESCON GH</span>
        </div>
        
        <ul class="sidebar-nav" data-coreui="navigation" data-simplebar>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="nav-icon cil-speedometer"></i> Dashboard
                </a>
            </li>
            
            <?php if (hasRole('Executive')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'executive_dashboard.php' ? 'active' : ''; ?>" href="executive_dashboard.php">
                    <i class="nav-icon cil-chart-line"></i> My Executive Team
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-title">Management</li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'members.php' ? 'active' : ''; ?>" href="members.php">
                    <i class="nav-icon cil-people"></i> Members
                </a>
            </li>
            
            <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'campus_executives.php' ? 'active' : ''; ?>" href="campus_executives.php">
                    <i class="nav-icon cil-star"></i> Executives
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'patrons.php' ? 'active' : ''; ?>" href="patrons.php">
                    <i class="nav-icon cil-user-follow"></i> Patrons
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasRole('Admin')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'institutions.php' ? 'active' : ''; ?>" href="institutions.php">
                    <i class="nav-icon cil-building"></i> Institutions
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'campuses.php' ? 'active' : ''; ?>" href="campuses.php">
                    <i class="nav-icon cil-location-pin"></i> Campuses
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'regions.php' ? 'active' : ''; ?>" href="regions.php">
                    <i class="nav-icon cil-map"></i> Regions
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'constituencies.php' ? 'active' : ''; ?>" href="constituencies.php">
                    <i class="nav-icon cil-list"></i> Constituencies
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasRole('Admin')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'positions.php' ? 'active' : ''; ?>" href="positions.php">
                    <i class="nav-icon cil-badge"></i> Positions
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-title">Finance</li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dues.php' ? 'active' : ''; ?>" href="dues.php">
                    <i class="nav-icon cil-dollar"></i> Dues
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>" href="payments.php">
                    <i class="nav-icon cil-credit-card"></i> Payments
                </a>
            </li>
            
            <li class="nav-title">Communication</li>
            
            <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sms.php' ? 'active' : ''; ?>" href="sms.php">
                    <i class="nav-icon cil-comment-square"></i> SMS
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gallery.php' ? 'active' : ''; ?>" href="gallery.php">
                    <i class="nav-icon cil-image"></i> Photo Gallery
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : ''; ?>" href="events.php">
                    <i class="nav-icon cil-calendar"></i> Events
                </a>
            </li>
            
            <?php if (hasRole('Admin')): ?>
            <li class="nav-title">Administration</li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="nav-icon cil-user"></i> Users
                </a>
            </li>
            
            <li class="nav-group">
                <a class="nav-link nav-group-toggle" href="#">
                    <i class="nav-icon cil-chart-line"></i> Reports
                </a>
                <ul class="nav-group-items">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                            <i class="nav-icon cil-chart"></i> Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>" href="register.php">
                            <i class="nav-icon cil-address-book"></i> Members Register
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
        
        <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
    </div>
    
    <div class="wrapper d-flex flex-column min-vh-100 bg-light">
        <header class="header header-sticky mb-4">
            <div class="container-fluid">
                <button class="header-toggler px-md-0 me-md-3" type="button" onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()">
                    <i class="icon cil-menu"></i>
                </button>
                
                <ul class="header-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link py-0" data-coreui-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                            <div class="avatar-initials">
                                <?php echo getInitials($_SESSION['email']); ?>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end pt-0">
                            <div class="dropdown-header bg-light py-2">
                                <div class="fw-semibold">Account</div>
                            </div>
                            <a class="dropdown-item" href="profile.php">
                                <i class="cil-user me-2"></i> Profile
                            </a>
                            <a class="dropdown-item" href="settings.php">
                                <i class="cil-settings me-2"></i> Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="logout.php">
                                <i class="cil-account-logout me-2"></i> Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </header>
        
        <div class="body flex-grow-1 px-3">
            <div class="container-lg">
                <?php
                $flash = getFlashMessage();
                if ($flash):
                ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                </div>
                <?php endif; ?>
