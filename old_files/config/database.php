<?php
/**
 * Database Configuration
 * Uses environment variables for security
 */

// Load environment variables
require_once __DIR__ . '/env.php';

// Database configuration from environment
$db_host = env('DB_HOST', 'localhost');
$db_name = env('DB_NAME', 'tescon_ghana');
$db_user = env('DB_USER', 'root');
$db_pass = env('DB_PASS', '');

try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Disable emulated prepares for better security
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch(PDOException $e) {
    // Log error instead of displaying it
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Show generic error to user
    if (env('APP_DEBUG', false)) {
        die("Connection failed: " . $e->getMessage());
    } else {
        die("Database connection error. Please contact support.");
    }
}
?>
