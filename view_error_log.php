<?php
// Script to view error logs and PHP configuration

echo "<h2>PHP Error Log Configuration</h2>";

// Get error log location from php.ini
$error_log = ini_get('error_log');
echo "<p><strong>Error Log Path:</strong> " . ($error_log ? $error_log : "Not set (using default)") . "</p>";

// Common XAMPP locations
$possible_logs = [
    'C:\xampp\apache\logs\error.log',
    'C:\xampp\php\logs\php_error_log',
    'C:\xampp\php\logs\error.log',
    dirname(__FILE__) . '\error.log',
    dirname(__FILE__) . '\php_errors.log'
];

echo "<h3>Checking Common Log Locations:</h3>";
echo "<ul>";
foreach ($possible_logs as $log_path) {
    if (file_exists($log_path)) {
        $size = filesize($log_path);
        echo "<li><strong style='color:green;'>✓ FOUND:</strong> $log_path (Size: " . number_format($size) . " bytes)</li>";
    } else {
        echo "<li><strong style='color:red;'>✗ Not found:</strong> $log_path</li>";
    }
}
echo "</ul>";

// Test error logging
error_log("TEST: Error logging is working! Time: " . date('Y-m-d H:i:s'));
echo "<p><em>Test error logged. Check the log file for: 'TEST: Error logging is working!'</em></p>";

// Try to read Apache error log
$apache_log = 'C:\xampp\apache\logs\error.log';
if (file_exists($apache_log)) {
    echo "<h3>Last 50 Lines of Apache Error Log:</h3>";
    echo "<pre style='background:#f5f5f5; padding:10px; max-height:400px; overflow-y:scroll;'>";
    $lines = file($apache_log);
    $last_lines = array_slice($lines, -50);
    echo htmlspecialchars(implode('', $last_lines));
    echo "</pre>";
}

// Display PHP info about logging
echo "<h3>PHP Error Logging Settings:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>error_reporting</td><td>" . error_reporting() . "</td></tr>";
echo "<tr><td>display_errors</td><td>" . ini_get('display_errors') . "</td></tr>";
echo "<tr><td>log_errors</td><td>" . ini_get('log_errors') . "</td></tr>";
echo "<tr><td>error_log</td><td>" . ini_get('error_log') . "</td></tr>";
echo "</table>";

echo "<hr>";
echo "<p><a href='campuses.php'>Go to Campuses Page</a> (This will generate logs)</p>";
?>
