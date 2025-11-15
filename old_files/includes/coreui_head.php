<!DOCTYPE html>
<html lang="en">
<head>
    <base href="./">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="description" content="UEW-TESCON - Tertiary Students Confederacy of the New Patriotic Party">
    <meta name="author" content="UEW-TESCON">
    
    <title><?php echo $pageTitle ?? 'UEW-TESCON'; ?></title>
    
    <!-- CoreUI CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/coreui@4.2.0/dist/css/coreui.min.css">
    
    <!-- CoreUI Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/icons@3.0.0/css/all.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS (if needed) -->
    <?php if (isset($useDataTables) && $useDataTables): ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <?php endif; ?>
    
    <!-- Chart.js (if needed) -->
    <?php if (isset($useCharts) && $useCharts): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php endif; ?>
    
    <!-- Custom CSS -->
    <style>
        :root {
            --cui-primary: #0d6efd;
            --cui-secondary: #6c757d;
            --cui-success: #198754;
            --cui-danger: #dc3545;
            --cui-warning: #ffc107;
            --cui-info: #0dcaf0;
        }
        
        .sidebar {
            --cui-sidebar-bg: #2c3e50;
            --cui-sidebar-nav-link-color: rgba(255, 255, 255, 0.8);
            --cui-sidebar-nav-link-hover-color: #fff;
            --cui-sidebar-nav-link-hover-bg: rgba(255, 255, 255, 0.1);
            --cui-sidebar-nav-link-active-color: #fff;
            --cui-sidebar-nav-link-active-bg: rgba(255, 255, 255, 0.2);
        }
        
        .sidebar-brand {
            background: rgba(0, 0, 0, 0.2);
            padding: 1rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff !important;
        }
        
        .sidebar-brand:hover {
            color: #fff !important;
            text-decoration: none;
        }
        
        .sidebar-nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
        }
        
        .sidebar-nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .card {
            border: none;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 2px solid var(--cui-primary);
            font-weight: 600;
            padding: 1rem 1.25rem;
        }
        
        .badge {
            padding: 0.35em 0.65em;
            font-weight: 500;
        }
        
        .btn {
            border-radius: 0.375rem;
        }
        
        .table {
            background-color: #fff;
        }
        
        .header-nav {
            margin-left: auto;
        }
        
        .avatar {
            width: 36px;
            height: 36px;
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 1rem;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .required::after {
            content: " *";
            color: var(--cui-danger);
        }
        
        /* Custom scrollbar for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
    
    <!-- Additional page-specific CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
<?php
// Start secure session if not already started
if (!function_exists('startSecureSession')) {
    require_once __DIR__ . '/security.php';
}
startSecureSession();
?>
