# Image Cropper Implementation Guide

## Overview
The image cropper allows members to manually crop their profile photos before uploading. It uses Cropper.js library to provide a professional image cropping experience.

## Features
✅ **Manual Cropping** - Users can crop images to their preferred size  
✅ **Square Aspect Ratio** - Enforces 1:1 ratio for passport photos  
✅ **Zoom Controls** - Zoom in/out for better positioning  
✅ **Rotation** - Rotate images left or right  
✅ **Flip** - Flip images horizontally or vertically  
✅ **Live Preview** - See cropped result in real-time  
✅ **High Quality** - Outputs 600x600px images with high quality  
✅ **File Validation** - Checks file type and size  
✅ **Responsive** - Works on all devices  

## Files Created

### 1. `includes/image_cropper.php`
The main cropper component that includes:
- Modal dialog with cropper interface
- Cropper.js library integration
- Control buttons (zoom, rotate, flip, reset)
- Preview panel
- JavaScript functionality

### 2. `IMAGE_CROPPER_README.md`
This documentation file

## How to Use

### For Developers

#### Step 1: Include the Cropper
Add this line before the footer in your PHP file:
```php
<?php include 'includes/image_cropper.php'; ?>
```

#### Step 2: Update File Input
Add these attributes to your file input:
```html
<input type="file" 
       class="form-control" 
       name="photo" 
       id="photoInput" 
       accept="image/*" 
       data-preview="photoPreview" 
       onchange="initImageCropper(this)">
```

#### Step 3: Add Preview Element
Add an image element for preview:
```html
<img id="photoPreview" 
     src="" 
     alt="Photo Preview" 
     style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd; display: none;">
```

### For Users

1. **Select Image** - Click the file input to select an image
2. **Crop Modal Opens** - The cropper modal will appear automatically
3. **Adjust Image** - Use the controls to adjust your image:
   - **Drag** - Move the image around
   - **Scroll** - Zoom in/out
   - **Handles** - Resize the crop box
   - **Buttons** - Use toolbar buttons for more controls
4. **Preview** - Check the preview panel on the right
5. **Crop & Continue** - Click the button to apply the crop
6. **Submit Form** - The cropped image will be uploaded when you submit the form

## Controls

### Toolbar Buttons

| Button | Icon | Function |
|--------|------|----------|
| Zoom In | 🔍+ | Zoom into the image |
| Zoom Out | 🔍- | Zoom out of the image |
| Rotate Left | ↶ | Rotate 90° counter-clockwise |
| Rotate Right | ↷ | Rotate 90° clockwise |
| Flip Horizontal | ↔ | Flip image horizontally |
| Flip Vertical | ↕ | Flip image vertically |
| Reset | ⟲ | Reset to original state |

### Mouse/Touch Controls

- **Drag** - Move the image
- **Scroll/Pinch** - Zoom in/out
- **Drag Handles** - Resize crop box
- **Drag Crop Box** - Move crop area

## Technical Details

### Cropper Configuration
```javascript
{
    aspectRatio: 1,           // Square crop (1:1)
    viewMode: 2,              // Restrict crop box to canvas
    dragMode: 'move',         // Drag to move image
    autoCropArea: 0.8,        // 80% initial crop area
    cropBoxMovable: true,     // Allow moving crop box
    cropBoxResizable: true,   // Allow resizing crop box
    minCropBoxWidth: 100,     // Minimum 100px width
    minCropBoxHeight: 100     // Minimum 100px height
}
```

### Output Specifications
- **Format**: JPEG
- **Dimensions**: 600x600 pixels
- **Quality**: 90%
- **Smoothing**: High quality
- **File Type**: image/jpeg

### File Validation
- **Allowed Types**: All image formats (image/*)
- **Max Size**: 5MB
- **Validation**: Client-side before cropping

## Integration Examples

### Example 1: Profile Edit (Already Implemented)
```php
// profile_edit.php
<input type="file" name="photo" id="photoInput" accept="image/*" 
       data-preview="photoPreview" onchange="initImageCropper(this)">
<img id="photoPreview" src="" alt="Preview">

<?php include 'includes/image_cropper.php'; ?>
```

### Example 2: Member Add
```php
// member_add.php
<input type="file" name="photo" id="memberPhoto" accept="image/*" 
       data-preview="memberPhotoPreview" onchange="initImageCropper(this)">
<img id="memberPhotoPreview" src="" alt="Preview">

<?php include 'includes/image_cropper.php'; ?>
```

### Example 3: Signup
```php
// signup.php
<input type="file" name="photo" id="signupPhoto" accept="image/*" 
       data-preview="signupPhotoPreview" onchange="initImageCropper(this)">
<img id="signupPhotoPreview" src="" alt="Preview">

<?php include 'includes/image_cropper.php'; ?>
```

## Files to Update

To add cropping to other upload forms, update these files:

1. ✅ **profile_edit.php** - Already updated
2. **signup.php** - Member registration
3. **member_add.php** - Admin adding members
4. **member_edit.php** - Admin editing members
5. **patron_add.php** - Adding patrons

## Dependencies

### External Libraries
- **Cropper.js** v1.6.1
  - CSS: `https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css`
  - JS: `https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js`

### Required
- CoreUI Modal component (already in project)
- Modern browser with Canvas support
- JavaScript enabled

## Browser Support
- ✅ Chrome 60+
- ✅ Firefox 55+
- ✅ Safari 11+
- ✅ Edge 79+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Issue: Modal doesn't open
**Solution**: Ensure CoreUI JavaScript is loaded before the cropper script

### Issue: Preview not updating
**Solution**: Check that the `data-preview` attribute matches the preview image ID

### Issue: Cropped image not uploading
**Solution**: Ensure the form has `enctype="multipart/form-data"`

### Issue: Image quality is poor
**Solution**: Adjust the quality parameter in `canvas.toBlob()` (currently 0.9)

## Customization

### Change Output Size
```javascript
const canvas = cropper.getCroppedCanvas({
    width: 800,    // Change from 600
    height: 800    // Change from 600
});
```

### Change Aspect Ratio
```javascript
cropper = new Cropper(image, {
    aspectRatio: 4/3,  // Change from 1 (square)
    // ... other options
});
```

### Change Quality
```javascript
canvas.toBlob(function(blob) {
    // ...
}, 'image/jpeg', 0.95);  // Change from 0.9
```

## Security Notes

1. **Client-side validation** - File type and size checked before cropping
2. **Server-side validation** - Still validate on server (already implemented)
3. **File size limit** - 5MB max to prevent memory issues
4. **MIME type check** - Only image files accepted

## Performance

- **Lightweight** - Cropper.js is ~50KB minified
- **Fast** - Canvas operations are hardware-accelerated
- **Efficient** - Only loads when needed
- **Memory** - Cleans up on modal close

## Future Enhancements

Possible improvements:
- [ ] Multiple aspect ratios (square, 4:3, 16:9)
- [ ] Filters and effects
- [ ] Brightness/contrast adjustment
- [ ] Face detection for auto-centering
- [ ] Batch upload and crop
- [ ] Save multiple crop presets

## Support

For issues or questions:
1. Check this documentation
2. Review the browser console for errors
3. Verify all dependencies are loaded
4. Test with a different image file

## Credits

- **Cropper.js** - https://github.com/fengyuanchen/cropperjs
- **CoreUI** - Modal component
- **Implementation** - TESCON GH System

---

**Last Updated**: November 2, 2025  
**Version**: 1.0.0  
**Status**: ✅ Production Ready
