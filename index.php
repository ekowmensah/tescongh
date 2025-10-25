<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Redirect to dashboard if logged in, otherwise to login
if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    redirect('home.php');
}
