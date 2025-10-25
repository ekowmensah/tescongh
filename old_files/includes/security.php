<?php
/**
 * Security Helper Functions
 * Centralized security utilities
 */

/**
 * Start secure session with proper configuration
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Session configuration
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        // Enable secure cookies in production
        if (env('APP_ENV') === 'production') {
            ini_set('session.cookie_secure', 1);
        }
        
        // Set session lifetime
        ini_set('session.gc_maxlifetime', env('SESSION_LIFETIME', 7200));
        
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION['role'] ?? '', $roles);
}

/**
 * Require login - redirect if not authenticated
 */
function requireLogin($redirectTo = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirectTo");
        exit();
    }
}

/**
 * Require specific role - redirect if unauthorized
 */
function requireRole($roles, $redirectTo = 'index.php') {
    requireLogin();
    
    if (!hasRole($roles)) {
        header("Location: $redirectTo");
        exit();
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Ghana format)
 */
function validatePhone($phone) {
    // Remove spaces and dashes
    $phone = preg_replace('/[\s\-]/', '', $phone);
    
    // Check if it matches Ghana phone format
    return preg_match('/^(\+233|0)[2-5][0-9]{8}$/', $phone);
}

/**
 * Rate limiting check
 */
function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
    $key = $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
    }
    
    $rateData = &$_SESSION['rate_limit'][$key];
    
    // Reset if time window has passed
    if (time() - $rateData['first_attempt'] > $timeWindow) {
        $rateData['attempts'] = 0;
        $rateData['first_attempt'] = time();
    }
    
    // Check if limit exceeded
    if ($rateData['attempts'] >= $maxAttempts) {
        return false;
    }
    
    // Increment attempts
    $rateData['attempts']++;
    
    return true;
}

/**
 * Log security event
 */
function logSecurityEvent($event, $details = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'user_id' => $_SESSION['user_id'] ?? 'guest',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details
    ];
    
    $logFile = dirname(__DIR__) . '/logs/security.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    error_log(json_encode($logEntry) . PHP_EOL, 3, $logFile);
}

/**
 * Prevent directory traversal
 */
function sanitizePath($path) {
    // Remove any directory traversal attempts
    $path = str_replace(['../', '..\\'], '', $path);
    return basename($path);
}
?>
