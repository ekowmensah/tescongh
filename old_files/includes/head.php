<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="TESCON Ghana - Tertiary Students Confederacy of the New Patriotic Party">
    <meta name="author" content="TESCON Ghana">
    
    <title><?php echo $pageTitle ?? 'TESCON Ghana'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS (if needed) -->
    <?php if (isset($useDataTables) && $useDataTables): ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <?php endif; ?>
    
    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
    
    <!-- Additional page-specific CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 2px solid var(--primary-color);
            font-weight: 600;
        }
        
        .btn {
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
        }
        
        .table {
            background-color: #fff;
        }
        
        .badge {
            padding: 0.35em 0.65em;
            font-weight: 500;
        }
        
        footer {
            background-color: #343a40;
            color: #fff;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .required::after {
            content: " *";
            color: var(--danger-color);
        }
    </style>
</head>
<body>
<?php
// Start secure session if not already started
if (!function_exists('startSecureSession')) {
    require_once __DIR__ . '/security.php';
}
startSecureSession();
?>
