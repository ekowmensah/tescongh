<?php
/**
 * Hubtel Configuration
 * Add your actual Hubtel API credentials here
 */

// Hubtel SMS Configuration
define('HUBTEL_SMS_CLIENT_ID', 'your_sms_client_id_here'); // Same as payment client ID or separate SMS client ID
define('HUBTEL_SMS_CLIENT_SECRET', 'your_sms_client_secret_here'); // Same as payment client secret or separate SMS client secret
define('HUBTEL_SMS_SENDER_ID', 'TESCON'); // Your registered sender ID (max 11 characters)

// SMS API Configuration
define('HUBTEL_SMS_API_URL', 'https://api.hubtel.com/v2/messages/send');
define('SMS_MAX_LENGTH', 160); // Standard SMS length
define('SMS_COST_PER_MESSAGE', 0.03); // Approximate cost per SMS in GHS (adjust based on your Hubtel pricing)

// Application Configuration
define('APP_NAME', 'TESCON Ghana Membership System');
define('APP_URL', 'http://localhost/tescongh/');

// Payment Configuration
define('PAYMENT_CALLBACK_URL', APP_URL . 'payment_callback.php');
define('PAYMENT_CURRENCY', 'GHS');

// File Upload Configuration
define('UPLOAD_PATH', 'uploads/');
define('PHOTO_UPLOAD_PATH', UPLOAD_PATH . 'photos/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Database Configuration (should be moved to environment variables in production)
define('DB_HOST', 'localhost');
define('DB_NAME', 'tescon_ghana');
define('DB_USER', 'root');
define('DB_PASS', '');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour

// Email Configuration (for future use)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');

// Application Settings
define('CURRENT_YEAR', date('Y'));
define('DEFAULT_TIMEZONE', 'Africa/Accra');

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);
?>
