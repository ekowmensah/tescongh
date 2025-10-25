<?php
/**
 * Authentication middleware
 */

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    redirect('login.php?timeout=1');
}

// Update last activity time
$_SESSION['last_activity'] = time();
