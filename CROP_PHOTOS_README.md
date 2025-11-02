# Photo Cropping Scripts

This folder contains scripts to automatically crop existing member photos to passport size (600x600 pixels).

## 📋 Overview

Two scripts are provided:
1. **Browser Version** - `crop_existing_photos.php` (with visual progress)
2. **CLI Version** - `crop_existing_photos_cli.php` (for command line)

## 🚀 Usage

### Option 1: Browser (Recommended)

1. Open your browser
2. Navigate to: `http://your-domain.com/crop_existing_photos.php`
3. Watch the real-time progress
4. Wait for completion

**Example:**
```
http://localhost/tescongh/crop_existing_photos.php
```

### Option 2: Command Line

1. Open terminal/command prompt
2. Navigate to your project directory
3. Run the script:

**Windows:**
```bash
cd C:\xampp\htdocs\tescongh
php crop_existing_photos_cli.php
```

**Linux/Mac:**
```bash
cd /path/to/tescongh
php crop_existing_photos_cli.php
```

## ⚙️ What the Script Does

1. **Finds all members** with photos in the database
2. **Creates a backup** of all original photos (timestamped folder)
3. **Processes each photo:**
   - Loads the image
   - Crops to square (center crop)
   - Resizes to 600x600 pixels
   - Saves with high quality
4. **Shows progress** in real-time
5. **Provides summary** of processed, skipped, and errors

## 📐 Technical Details

- **Output size:** 600x600 pixels (passport size)
- **Cropping method:** Center crop to square
- **Supported formats:** JPG, JPEG, PNG, GIF
- **Quality:** 90% for JPEG, level 8 for PNG
- **Transparency:** Preserved for PNG images
- **Backup:** Automatic backup before processing

## 🔒 Safety Features

- ✅ **Automatic backup** - Original photos saved before processing
- ✅ **Skip existing** - Photos already 600x600 are skipped
- ✅ **Error handling** - Continues processing even if one fails
- ✅ **Memory management** - Properly frees memory after each photo
- ✅ **Progress tracking** - See exactly what's happening

## 📊 Expected Output

### Browser Version:
```
🖼️ Crop Existing Photos to Passport Size
Processing 150 member photos...

[Progress Bar: 67%]

[10:30:15] Processing: John Doe (ID: 1)
   Original size: 1024x768px
✅ Successfully cropped to 600x600px

[10:30:16] Processing: Jane Smith (ID: 2)
⏭️ Already passport size, skipping...

📊 Processing Complete!
Total members: 150
Successfully processed: 120
Skipped: 25
Errors: 5
```

### CLI Version:
```
╔════════════════════════════════════════════════════════════╗
║     Crop Existing Photos to Passport Size (600x600px)     ║
╚════════════════════════════════════════════════════════════╝

✓ Backup directory created: uploads/backup_2025-11-02_050000/

Found 150 members with photos
────────────────────────────────────────────────────────────

[1/150] Processing: John Doe
  Original size: 1024x768px
  ✓ Successfully cropped to 600x600px
  Progress: [1%]

[2/150] Processing: Jane Smith
  Original size: 600x600px
  ⏭ Already passport size, skipping...
  Progress: [1%]

...

═══════════════════════════════════════════════════════════
                    PROCESSING COMPLETE
═══════════════════════════════════════════════════════════

📊 Summary:
  Total members:         150
  Successfully processed: 120
  Skipped:               25
  Errors:                5
  Backup location:       uploads/backup_2025-11-02_050000/

✓ All done! Original photos have been backed up.
```

## 🗂️ Backup Location

Backups are stored in:
```
uploads/backup_YYYY-MM-DD_HHMMSS/
```

Example:
```
uploads/backup_2025-11-02_050000/
```

## ⚠️ Important Notes

1. **Run once** - Only run this script once to process existing photos
2. **Check backups** - Verify photos look correct before deleting backups
3. **Server resources** - Script includes delays to prevent server overload
4. **Execution time** - May take several minutes for many photos
5. **New uploads** - Future uploads are automatically cropped (no need to run again)

## 🐛 Troubleshooting

### "File not found" errors
- Photo filename exists in database but file is missing
- Check if files were moved or deleted

### "Unsupported file type" errors
- Only JPG, JPEG, PNG, and GIF are supported
- Other formats will be skipped

### "Failed to process image" errors
- Image file may be corrupted
- Try opening the file manually to verify

### Script times out
- Increase PHP execution time in php.ini
- Or process in smaller batches

## 🧹 Cleanup

After verifying all photos look correct:

1. Check a few member profiles to ensure photos display properly
2. Delete the backup folder to free up space:
   ```bash
   # Windows
   rmdir /s uploads\backup_2025-11-02_050000
   
   # Linux/Mac
   rm -rf uploads/backup_2025-11-02_050000
   ```

## 🔐 Security

**Important:** After running the script, consider:
- Deleting or restricting access to these scripts
- They should only be run by administrators
- Add authentication if keeping them accessible

## 📞 Support

If you encounter issues:
1. Check the error messages in the output
2. Verify PHP GD extension is installed
3. Check file permissions on uploads folder
4. Review backup folder for original files

---

**Created:** November 2, 2025  
**Purpose:** One-time migration of existing photos to passport size  
**Status:** Ready to use
