<?php
/**
 * CLI Script to crop all existing uploaded photos to passport size
 * Run via command line: php crop_existing_photos_cli.php
 */

require_once 'config/config.php';
require_once 'config/Database.php';

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line. Use crop_existing_photos.php for browser access.\n");
}

// Set execution time limit for large batches
set_time_limit(300); // 5 minutes

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     Crop Existing Photos to Passport Size (600x600px)     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Create backup directory
$backupDir = 'uploads/backup_' . date('Y-m-d_His') . '/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}
echo "✓ Backup directory created: {$backupDir}\n\n";

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

echo "Found {$totalMembers} members with photos\n";
echo str_repeat("─", 60) . "\n\n";

foreach ($members as $index => $member) {
    $memberId = $member['id'];
    $memberName = $member['fullname'];
    $photoFilename = $member['photo'];
    
    // Calculate progress
    $progress = round((($index + 1) / $totalMembers) * 100);
    $current = $index + 1;
    
    echo "[{$current}/{$totalMembers}] Processing: {$memberName}\n";
    
    // Construct file paths
    $originalPath = 'uploads/' . $photoFilename;
    
    // Check if file exists
    if (!file_exists($originalPath)) {
        echo "  ⚠ File not found: {$photoFilename}\n\n";
        $skipped++;
        continue;
    }
    
    // Get file extension
    $fileExtension = strtolower(pathinfo($photoFilename, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($fileExtension, $allowedTypes)) {
        echo "  ⚠ Unsupported file type: {$fileExtension}\n\n";
        $skipped++;
        continue;
    }
    
    // Backup original file
    $backupPath = $backupDir . $photoFilename;
    if (!copy($originalPath, $backupPath)) {
        echo "  ✗ Failed to backup: {$photoFilename}\n\n";
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
        echo "  ✗ Failed to load image: {$e->getMessage()}\n\n";
        $errors++;
        continue;
    }
    
    if (!$sourceImage) {
        echo "  ✗ Failed to process image: {$photoFilename}\n\n";
        $errors++;
        continue;
    }
    
    // Get original dimensions
    $originalWidth = imagesx($sourceImage);
    $originalHeight = imagesy($sourceImage);
    
    echo "  Original size: {$originalWidth}x{$originalHeight}px\n";
    
    // Check if already passport size
    if ($originalWidth == 600 && $originalHeight == 600) {
        echo "  ⏭ Already passport size, skipping...\n\n";
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
        echo "  ✗ Failed to save: {$e->getMessage()}\n\n";
        $errors++;
    }
    
    // Free memory
    imagedestroy($sourceImage);
    imagedestroy($croppedImage);
    imagedestroy($passportImage);
    
    if ($saved) {
        echo "  ✓ Successfully cropped to 600x600px\n";
        $processed++;
    } else {
        echo "  ✗ Failed to save processed image\n";
        $errors++;
    }
    
    echo "  Progress: [{$progress}%]\n\n";
    
    // Small delay to prevent server overload
    usleep(100000); // 0.1 second
}

// Summary
echo str_repeat("═", 60) . "\n";
echo "                    PROCESSING COMPLETE                      \n";
echo str_repeat("═", 60) . "\n\n";

echo "📊 Summary:\n";
echo "  Total members:         {$totalMembers}\n";
echo "  Successfully processed: {$processed}\n";
echo "  Skipped:               {$skipped}\n";
echo "  Errors:                {$errors}\n";
echo "  Backup location:       {$backupDir}\n\n";

echo "✓ All done! Original photos have been backed up.\n";
echo "  You can delete the backup folder after verifying all photos look correct.\n\n";

exit(0);
?>
