<!-- Image Cropper Modal -->
<div class="modal fade" id="imageCropperModal" tabindex="-1" aria-labelledby="imageCropperModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageCropperModalLabel">
                    <i class="cil-crop"></i> Crop Your Photo
                </h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <div class="img-container">
                            <img id="imageToCrop" src="" alt="Image to crop" style="max-width: 100%; display: block;">
                        </div>
                        <div class="crop-info mt-3 text-center">
                            <small class="text-muted">
                                <i class="cil-info"></i> Drag to reposition &nbsp;|&nbsp; 
                                <i class="cil-zoom-in"></i> Scroll to zoom &nbsp;|&nbsp; 
                                <i class="cil-move"></i> Use handles to resize
                            </small>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-secondary" id="zoomIn" title="Zoom In">
                                <i class="cil-zoom-in"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="zoomOut" title="Zoom Out">
                                <i class="cil-zoom-out"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="rotateLeft" title="Rotate Left">
                                <i class="cil-action-undo"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="rotateRight" title="Rotate Right">
                                <i class="cil-action-redo"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="flipHorizontal" title="Flip Horizontal">
                                <i class="cil-flip-to-back"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="flipVertical" title="Flip Vertical">
                                <i class="cil-flip-to-front"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="reset" title="Reset">
                                <i class="cil-reload"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">
                    <i class="cil-x"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="cropAndUpload">
                    <i class="cil-check"></i> Crop & Continue
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cropper.js CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">

<!-- Cropper.js JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<script>
let cropper = null;
let currentFile = null;
let targetInput = null;

// Initialize image cropper when file is selected
function initImageCropper(inputElement) {
    const file = inputElement.files[0];
    
    if (!file) return;
    
    // Validate file type
    if (!file.type.match('image.*')) {
        alert('Please select an image file');
        inputElement.value = '';
        return;
    }
    
    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('Image size should not exceed 5MB');
        inputElement.value = '';
        return;
    }
    
    currentFile = file;
    targetInput = inputElement;
    
    // Read and display the image
    const reader = new FileReader();
    reader.onload = function(e) {
        const image = document.getElementById('imageToCrop');
        image.src = e.target.result;
        
        // Show modal
        const modal = new coreui.Modal(document.getElementById('imageCropperModal'));
        modal.show();
        
        // Initialize cropper after modal is shown
        document.getElementById('imageCropperModal').addEventListener('shown.coreui.modal', function() {
            if (cropper) {
                cropper.destroy();
            }
            
            cropper = new Cropper(image, {
                aspectRatio: 1, // Square crop for passport photo
                viewMode: 2,
                dragMode: 'move',
                autoCropArea: 0.8,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                minContainerWidth: 200,
                minContainerHeight: 200,
                minCropBoxWidth: 100,
                minCropBoxHeight: 100
            });
        }, { once: true });
    };
    
    reader.readAsDataURL(file);
}

// Zoom controls
document.getElementById('zoomIn')?.addEventListener('click', function() {
    if (cropper) cropper.zoom(0.1);
});

document.getElementById('zoomOut')?.addEventListener('click', function() {
    if (cropper) cropper.zoom(-0.1);
});

// Rotation controls
document.getElementById('rotateLeft')?.addEventListener('click', function() {
    if (cropper) cropper.rotate(-90);
});

document.getElementById('rotateRight')?.addEventListener('click', function() {
    if (cropper) cropper.rotate(90);
});

// Flip controls
document.getElementById('flipHorizontal')?.addEventListener('click', function() {
    if (cropper) {
        const data = cropper.getData();
        cropper.scaleX(data.scaleX === -1 ? 1 : -1);
    }
});

document.getElementById('flipVertical')?.addEventListener('click', function() {
    if (cropper) {
        const data = cropper.getData();
        cropper.scaleY(data.scaleY === -1 ? 1 : -1);
    }
});

// Reset
document.getElementById('reset')?.addEventListener('click', function() {
    if (cropper) cropper.reset();
});

// Crop and upload
document.getElementById('cropAndUpload')?.addEventListener('click', function() {
    if (!cropper) {
        console.error('Cropper not initialized');
        return;
    }
    
    console.log('Starting crop process...');
    
    // Get cropped canvas
    const canvas = cropper.getCroppedCanvas({
        width: 600,
        height: 600,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
    });
    
    if (!canvas) {
        console.error('Failed to get cropped canvas');
        alert('Failed to crop image. Please try again.');
        return;
    }
    
    console.log('Canvas created successfully');
    
    // Convert canvas to blob
    canvas.toBlob(function(blob) {
        if (!blob) {
            console.error('Failed to create blob');
            alert('Failed to process image. Please try again.');
            return;
        }
        
        console.log('Blob created:', blob.size, 'bytes');
        
        try {
            // Create a new File object from the blob
            const croppedFile = new File([blob], currentFile.name, {
                type: 'image/jpeg',
                lastModified: Date.now()
            });
            
            console.log('File created:', croppedFile.name, croppedFile.size, 'bytes');
            
            // Try to use DataTransfer API
            try {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(croppedFile);
                targetInput.files = dataTransfer.files;
                console.log('Files replaced using DataTransfer');
            } catch (e) {
                console.warn('DataTransfer not supported, using alternative method');
                // Store the cropped file data in a data attribute
                targetInput.setAttribute('data-cropped-file', canvas.toDataURL('image/jpeg', 0.9));
            }
            
            // Show preview - with robust handling
            const previewId = targetInput.getAttribute('data-preview');
            console.log('Looking for preview element:', previewId);
            
            let previewElement = null;
            
            if (previewId) {
                previewElement = document.getElementById(previewId);
                console.log('Preview element found:', previewElement);
            }
            
            // If preview element doesn't exist, create it
            if (!previewElement) {
                console.log('Creating new preview element');
                previewElement = document.createElement('img');
                previewElement.id = previewId || 'photoPreview';
                previewElement.alt = 'Photo Preview';
                previewElement.style.cssText = 'width: 150px; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd; margin-top: 10px;';
                
                // Insert after the file input
                if (targetInput.nextElementSibling) {
                    targetInput.parentNode.insertBefore(previewElement, targetInput.nextElementSibling.nextSibling);
                } else {
                    targetInput.parentNode.appendChild(previewElement);
                }
            }
            
            // Update preview with cropped image
            if (previewElement) {
                const imageData = canvas.toDataURL('image/jpeg', 0.9);
                previewElement.src = imageData;
                previewElement.style.display = 'block';
                previewElement.style.width = '150px';
                previewElement.style.height = '150px';
                previewElement.style.objectFit = 'cover';
                previewElement.style.borderRadius = '8px';
                previewElement.style.border = '2px solid #ddd';
                
                // Also show parent container if it has an ID like 'photoPreview' or ends with 'Preview'
                const parentContainer = previewElement.parentElement;
                if (parentContainer && parentContainer.id && parentContainer.id.toLowerCase().includes('preview')) {
                    parentContainer.style.display = 'block';
                    console.log('✓ Preview container shown:', parentContainer.id);
                }
                
                console.log('✓ Preview updated successfully with image data');
                console.log('Preview src length:', imageData.substring(0, 50) + '...');
            } else {
                console.error('Failed to create or find preview element');
            }
            
            // Store cropped blob for form submission
            if (!window.croppedImages) {
                window.croppedImages = {};
            }
            window.croppedImages[targetInput.name] = blob;
            console.log('Cropped image stored in window.croppedImages');
            
            // Close modal
            const modal = coreui.Modal.getInstance(document.getElementById('imageCropperModal'));
            if (modal) {
                modal.hide();
                console.log('Modal closed');
            }
            
            // Show success notification
            const fileName = currentFile.name;
            const fileSize = (blob.size / 1024).toFixed(2);
            console.log(`✓ Image cropped successfully: ${fileName} (${fileSize} KB)`);
            
            // Show visual feedback
            if (targetInput.parentElement) {
                const feedback = document.createElement('small');
                feedback.className = 'text-success d-block mt-1';
                feedback.innerHTML = '<i class="cil-check-circle"></i> Image cropped successfully!';
                
                // Remove any existing feedback
                const existingFeedback = targetInput.parentElement.querySelector('.text-success');
                if (existingFeedback) {
                    existingFeedback.remove();
                }
                
                targetInput.parentElement.appendChild(feedback);
                
                // Remove feedback after 3 seconds
                setTimeout(() => {
                    feedback.remove();
                }, 3000);
            }
            
        } catch (error) {
            console.error('Error processing cropped image:', error);
            alert('Error processing image: ' + error.message);
        }
    }, 'image/jpeg', 0.9);
});

// Clean up when modal is hidden
document.getElementById('imageCropperModal')?.addEventListener('hidden.coreui.modal', function() {
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
});

// Handle form submission to include cropped images
document.addEventListener('DOMContentLoaded', function() {
    // Find all forms that might have file inputs
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Check if we have any cropped images stored
            if (window.croppedImages && Object.keys(window.croppedImages).length > 0) {
                console.log('Form submission detected with cropped images');
                
                // For each cropped image, ensure it's in the form data
                Object.keys(window.croppedImages).forEach(inputName => {
                    const fileInput = form.querySelector(`input[name="${inputName}"]`);
                    if (fileInput && fileInput.type === 'file') {
                        const blob = window.croppedImages[inputName];
                        
                        // Create a new File from the blob
                        const file = new File([blob], 'cropped_' + Date.now() + '.jpg', {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        
                        // Try to replace the files
                        try {
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            fileInput.files = dataTransfer.files;
                            console.log('Cropped image added to form:', inputName);
                        } catch (error) {
                            console.warn('Could not replace files, image should still be in input');
                        }
                    }
                });
            }
        });
    });
});
</script>

<style>
.img-container {
    max-height: 500px;
    min-height: 400px;
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.crop-info {
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.crop-info small {
    line-height: 1.8;
}

#imageCropperModal .btn-group {
    display: flex;
}

#imageCropperModal .btn-group .btn {
    flex: 1;
}

#imageCropperModal .modal-dialog {
    max-width: 800px;
}
</style>
