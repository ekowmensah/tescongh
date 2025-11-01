<?php
/**
 * Common utility functions
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
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
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user has any of the specified roles
 */
function hasAnyRole($roles) {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
}

/**
 * Redirect to a page
 */
function redirect($page) {
    // Prevent caching of redirects
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Location: " . APP_URL . "/" . $page);
    exit();
}

/**
 * Set flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Format date
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

/**
 * Format currency (Ghana Cedis)
 */
function formatCurrency($amount) {
    return 'GH₵ ' . number_format($amount, 2);
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number (Ghana format)
 */
function isValidPhone($phone) {
    // Ghana phone format: +233XXXXXXXXX or 0XXXXXXXXX
    $pattern = '/^(\+233|0)[2-9][0-9]{8}$/';
    return preg_match($pattern, $phone);
}

/**
 * Format phone number to international format
 */
function formatPhoneNumber($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        $phone = '+233' . substr($phone, 1);
    }
    return $phone;
}

/**
 * Upload file
 */
function uploadFile($file, $directory = 'uploads/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error'];
    }

    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds limit'];
    }

    $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
    $uploadPath = $directory . $fileName;

    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'filename' => $fileName, 'path' => $uploadPath];
    }

    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

/**
 * Delete file
 */
function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

/**
 * Get user initials
 */
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return substr($initials, 0, 2);
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'Active' => 'success',
        'Inactive' => 'secondary',
        'Suspended' => 'danger',
        'Graduated' => 'info',
        'pending' => 'warning',
        'completed' => 'success',
        'failed' => 'danger',
        'cancelled' => 'secondary'
    ];
    return $classes[$status] ?? 'secondary';
}

/**
 * Paginate results
 */
function paginate($totalRecords, $currentPage = 1, $recordsPerPage = RECORDS_PER_PAGE) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $recordsPerPage;

    return [
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'records_per_page' => $recordsPerPage,
        'offset' => $offset
    ];
}

/**
 * Generate pagination HTML
 */
function generatePaginationHTML($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) return '';

    $html = '<nav><ul class="pagination">';
    
    // Previous button
    if ($pagination['current_page'] > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($pagination['current_page'] - 1) . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }

    // Page numbers
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }

    // Next button
    if ($pagination['current_page'] < $pagination['total_pages']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($pagination['current_page'] + 1) . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}
