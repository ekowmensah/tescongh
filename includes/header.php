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
        .sidebar {
            --cui-sidebar-bg: #2c3e50;
            --cui-sidebar-nav-link-color: rgba(255, 255, 255, 0.8);
            --cui-sidebar-nav-link-hover-color: #fff;
            --cui-sidebar-nav-link-hover-bg: rgba(255, 255, 255, 0.1);
            --cui-sidebar-nav-link-active-color: #fff;
            --cui-sidebar-nav-link-active-bg: #667eea;
        }
        
        .sidebar-brand {
            background: #1a252f;
            padding: 1rem;
            font-size: 1.25rem;
            font-weight: bold;
            color: white;
        }
        
        .header {
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        
        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            border-left: 4px solid;
        }
        
        .stat-card.primary {
            border-left-color: #667eea;
        }
        
        .stat-card.success {
            border-left-color: #2eb85c;
        }
        
        .stat-card.warning {
            border-left-color: #f9b115;
        }
        
        .stat-card.danger {
            border-left-color: #e55353;
        }
        
        .stat-card.info {
            border-left-color: #39f;
        }
        
        .avatar-initials {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
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
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="nav-icon cil-chart-line"></i> Reports
                </a>
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
