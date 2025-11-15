<?php
/**
 * mNotify SMS Configuration
 *
 * NOTE: Replace the placeholder values with your real mNotify credentials
 * and keep this file out of public version control if it will contain secrets.
 */

// Base URL for mNotify API
if (!defined('MNOTIFY_API_BASE_URL')) {
    define('MNOTIFY_API_BASE_URL', 'https://api.mnotify.com/api');
}

// Your mNotify API key (from BMS dashboard)
if (!defined('MNOTIFY_API_KEY')) {
    define('MNOTIFY_API_KEY', 'ZEBPGxradnxDI8Zhp3dZqAOOW');
}

// Default sender ID for SMS (must be registered and approved, max 11 chars)
if (!defined('MNOTIFY_SENDER_ID')) {
    define('MNOTIFY_SENDER_ID', 'TESCON- UEW');
}
