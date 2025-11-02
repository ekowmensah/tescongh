<?php
/**
 * Script to crop all existing uploaded photos to passport size
 * Run this once to process all old photos
 * 
 * Usage: Access this file via browser or run via CLI: php crop_existing_photos.php
 */

require_once 'config/config.php';
require_once 'config/Database.php';

// Set execution time limit for large batches
set_time_limit(300); // 5 minutes

// Create backup directory
$backupDir = 'uploads/backup_' . date('Y-m-d_His') . '/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$database = new Database();
$db = $database->getConnection();

// Get all members with photos
$query = "SELECT id, fullname, photo FROM members WHERE photo IS NOT NULL AND photo != ''";
$stmt = $db->query($query);
$members = $stmt->fetchAll();

$totalMembers = count($members);
$processed = 0;
$skipped = 0;
$errors = 0;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Crop Existing Photos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #1e40af; }
        .progress { background: #e0e0e0; border-radius: 4px; height: 30px; margin: 20px 0; overflow: hidden; }
        .progress-bar { background: linear-gradient(90deg, #1e40af, #3b82f6); height: 100%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .log { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 15px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; margin: 20px 0; }
        .success { color: #10b981; }
        .error { color: #dc2626; }
        .warning { color: #f59e0b; }
        .info { color: #3b82f6; }
        .summary { background: #dbeafe; border-left: 4px solid #1e40af; padding: 15px; margin: 20px 0; }
        .summary h3 { margin-top: 0; color: #1e40af; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🖼️ Crop Existing Photos to Passport Size</h1>
    <p>Processing <strong>{$totalMembers}</strong> member photos...</p>
    <div class='progress'>
        <div class='progress-bar' id='progressBar' style='width: 0%'>0%</div>
    </div>
    <div class='log' id='log'>";

// Flush output buffer to show progress in real-time
if (ob_get_level() == 0) ob_start();

foreach ($members as $index => $member) {
    $memberId = $member['id'];
    $memberName = $member['fullname'];
    $photoFilename = $member['photo'];
    
    // Calculate progress
    $progress = round((($index + 1) / $totalMembers) * 100);
    
    echo "<div class='info'>[" . date('H:i:s') . "] Processing: {$memberName} (ID: {$memberId})</div>";
    ob_flush();
    flush();
    
    // Construct file paths
    $originalPath = 'uploads/' . $photoFilename;
    
    // Check if file exists
    if (!file_exists($originalPath)) {
        echo "<div class='warning'>⚠️ File not found: {$photoFilename}</div>";
        $skipped++;
        continue;
    }
    
    // Get file extension
    $fileExtension = strtolower(pathinfo($photoFilename, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($fileExtension, $allowedTypes)) {
        echo "<div class='warning'>⚠️ Unsupported file type: {$fileExtension}</div>";
        $skipped++;
        continue;
    }
    
    // Backup original file
    $backupPath = $backupDir . $photoFilename;
    if (!copy($originalPath, $backupPath)) {
        echo "<div class='error'>❌ Failed to backup: {$photoFilename}</div>";
        $errors++;
        continue;
    }
    
    // Load the image
    $sourceImage = null;
    try {
        switch ($fileExtension) {
            case 'jpg':
            case 'jpeg':
                $sourceImage = @imagecreatefromjpeg($originalPath);
                break;
            case 'png':
                $sourceImage = @imagecreatefrompng($originalPath);
                break;
            case 'gif':
                $sourceImage = @imagecreatefromgif($originalPath);
                break;
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Failed to load image: {$e->getMessage()}</div>";
        $errors++;
        continue;
    }
    
    if (!$sourceImage) {
        echo "<div class='error'>❌ Failed to process image: {$photoFilename}</div>";
        $errors++;
        continue;
    }
    
    // Get original dimensions
    $originalWidth = imagesx($sourceImage);
    $originalHeight = imagesy($sourceImage);
    
    echo "<div class='info'>   Original size: {$originalWidth}x{$originalHeight}px</div>";
    
    // Check if already passport size
    if ($originalWidth == 600 && $originalHeight == 600) {
        echo "<div class='warning'>⏭️ Already passport size, skipping...</div>";
        imagedestroy($sourceImage);
        $skipped++;
        continue;
    }
    
    // Passport photo dimensions
    $passportSize = 600;
    
    // Calculate crop dimensions (center crop to square)
    $cropSize = min($originalWidth, $originalHeight);
    $cropX = ($originalWidth - $cropSize) / 2;
    $cropY = ($originalHeight - $cropSize) / 2;
    
    // Create a new square image
    $croppedImage = imagecreatetruecolor($cropSize, $cropSize);
    
    // Preserve transparency for PNG images
    if ($fileExtension === 'png') {
        imagealphablending($croppedImage, false);
        imagesavealpha($croppedImage, true);
        $transparent = imagecolorallocatealpha($croppedImage, 255, 255, 255, 127);
        imagefilledrectangle($croppedImage, 0, 0, $cropSize, $cropSize, $transparent);
    }
    
    // Copy and crop to square
    imagecopyresampled($croppedImage, $sourceImage, 0, 0, $cropX, $cropY, $cropSize, $cropSize, $cropSize, $cropSize);
    
    // Create final passport-sized image
    $passportImage = imagecreatetruecolor($passportSize, $passportSize);
    
    // Preserve transparency for PNG images
    if ($fileExtension === 'png') {
        imagealphablending($passportImage, false);
        imagesavealpha($passportImage, true);
        $transparent = imagecolorallocatealpha($passportImage, 255, 255, 255, 127);
        imagefilledrectangle($passportImage, 0, 0, $passportSize, $passportSize, $transparent);
    }
    
    // Resize to passport size
    imagecopyresampled($passportImage, $croppedImage, 0, 0, 0, 0, $passportSize, $passportSize, $cropSize, $cropSize);
    
    // Save the processed image (overwrite original)
    $saved = false;
    try {
        switch ($fileExtension) {
            case 'jpg':
            case 'jpeg':
                $saved = imagejpeg($passportImage, $originalPath, 90);
                break;
            case 'png':
                $saved = imagepng($passportImage, $originalPath, 8);
                break;
            case 'gif':
                $saved = imagegif($passportImage, $originalPath);
                break;
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Failed to save: {$e->getMessage()}</div>";
        $errors++;
    }
    
    // Free memory
    imagedestroy($sourceImage);
    imagedestroy($croppedImage);
    imagedestroy($passportImage);
    
    if ($saved) {
        echo "<div class='success'>✅ Successfully cropped to 600x600px</div>";
        $processed++;
    } else {
        echo "<div class='error'>❌ Failed to save processed image</div>";
        $errors++;
    }
    
    echo "<br>";
    
    // Update progress bar
    echo "<script>
        document.getElementById('progressBar').style.width = '{$progress}%';
        document.getElementById('progressBar').textContent = '{$progress}%';
        document.getElementById('log').scrollTop = document.getElementById('log').scrollHeight;
    </script>";
    
    ob_flush();
    flush();
    
    // Small delay to prevent server overload
    usleep(100000); // 0.1 second
}

echo "</div>";

// Summary
echo "<div class='summary'>
    <h3>📊 Processing Complete!</h3>
    <p><strong>Total members:</strong> {$totalMembers}</p>
    <p><strong>Successfully processed:</strong> <span class='success'>{$processed}</span></p>
    <p><strong>Skipped:</strong> <span class='warning'>{$skipped}</span></p>
    <p><strong>Errors:</strong> <span class='error'>{$errors}</span></p>
    <p><strong>Backup location:</strong> <code>{$backupDir}</code></p>
</div>";

echo "<div class='info'>
    <strong>Note:</strong> Original photos have been backed up to <code>{$backupDir}</code><br>
    You can delete the backup folder after verifying all photos look correct.
</div>";

echo "</div>
</body>
</html>";

ob_end_flush();
?>
