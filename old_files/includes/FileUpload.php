<?php
/**
 * Handle file uploads securely
 */
class FileUpload {
    private $uploadDir;
    private $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    private $maxSize = 5 * 1024 * 1024; // 5MB

    public function __construct($uploadDir = 'uploads/photos/') {
        $this->uploadDir = $uploadDir;
    }

    /**
     * Upload a file
     * @param array $file The $_FILES array element
     * @param string $prefix Optional prefix for filename
     * @return array ['success' => bool, 'filename' => string, 'error' => string]
     */
    public function upload($file, $prefix = '') {
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload failed'];
        }

        // Check file size
        if ($file['size'] > $this->maxSize) {
            return ['success' => false, 'error' => 'File size exceeds 5MB limit'];
        }

        // Get file extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Check file type
        if (!in_array($fileExtension, $this->allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed'];
        }

        // Generate unique filename
        $uniqueName = $prefix . uniqid() . '_' . time() . '.' . $fileExtension;
        $targetPath = $this->uploadDir . $uniqueName;

        // Create directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => true, 'filename' => $uniqueName];
        } else {
            return ['success' => false, 'error' => 'Failed to save uploaded file'];
        }
    }

    /**
     * Delete a file
     * @param string $filename
     * @return bool
     */
    public function delete($filename) {
        $filePath = $this->uploadDir . $filename;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
}
?>
