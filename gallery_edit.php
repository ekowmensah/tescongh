<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Gallery.php';

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('gallery.php');
}

$pageTitle = 'Edit Photo';

$database = new Database();
$db = $database->getConnection();

$gallery = new Gallery($db);

// Get image ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Image ID not provided');
    redirect('gallery.php');
}

$imageId = (int)$_GET['id'];
$image = $gallery->getById($imageId);

if (!$image) {
    setFlashMessage('danger', 'Image not found');
    redirect('gallery.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => sanitize($_POST['title']),
        'description' => sanitize($_POST['description']),
        'category' => sanitize($_POST['category']),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'display_order' => (int)$_POST['display_order']
    ];
    
    // Handle image replacement if new file uploaded
    if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['new_image']['name'];
        $filesize = $_FILES['new_image']['size'];
        
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            setFlashMessage('danger', 'Invalid file type. Only JPG, PNG, and GIF are allowed.');
        } elseif ($filesize > 5 * 1024 * 1024) { // 5MB max
            setFlashMessage('danger', 'File size too large. Maximum 5MB allowed.');
        } else {
            $uploadDir = 'assets/images/gallery/';
            $newFilename = uniqid() . '_' . time() . '.' . $ext;
            $uploadPath = $uploadDir . $newFilename;
            
            if (move_uploaded_file($_FILES['new_image']['tmp_name'], $uploadPath)) {
                // Delete old image file
                $oldImagePath = $uploadDir . $image['image_path'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
                
                // Update image path in data
                $data['image_path'] = $newFilename;
            } else {
                setFlashMessage('danger', 'Failed to upload new image');
            }
        }
    }
    
    if ($gallery->update($imageId, $data)) {
        setFlashMessage('success', 'Image updated successfully');
        redirect('gallery.php');
    } else {
        setFlashMessage('danger', 'Failed to update image');
    }
}

// Get categories
$categories = $gallery->getCategories();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Edit Photo</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="gallery.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Gallery
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <strong>Edit Photo Details</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Current Image</label>
                        <div>
                            <img src="assets/images/gallery/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($image['title']); ?>"
                                 class="img-thumbnail" id="currentImage" style="max-width: 400px;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Replace Image (Optional)</label>
                        <input type="file" class="form-control" name="new_image" accept="image/*" 
                               onchange="previewNewImage(this)">
                        <small class="text-muted">Leave empty to keep current image. Max: 5MB</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required 
                               value="<?php echo htmlspecialchars($image['title']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($image['description']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="category" required 
                               list="categoryList" value="<?php echo htmlspecialchars($image['category']); ?>">
                        <datalist id="categoryList">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" class="form-control" name="display_order" 
                               value="<?php echo $image['display_order']; ?>" min="0">
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured"
                               <?php echo $image['is_featured'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_featured">
                            <i class="cil-star text-warning"></i> Feature on Homepage
                        </label>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="cil-save"></i> Update Photo
                        </button>
                        <a href="gallery.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <strong>Image Information</strong>
            </div>
            <div class="card-body">
                <p><strong>Uploaded by:</strong><br><?php echo htmlspecialchars($image['uploaded_by_email']); ?></p>
                <p><strong>Upload date:</strong><br><?php echo formatDate($image['created_at'], 'd M Y H:i'); ?></p>
                <p><strong>Last updated:</strong><br><?php echo formatDate($image['updated_at'], 'd M Y H:i'); ?></p>
                <p><strong>File:</strong><br><small><?php echo htmlspecialchars($image['image_path']); ?></small></p>
            </div>
        </div>
    </div>
</div>

<script>
function previewNewImage(input) {
    const currentImage = document.getElementById('currentImage');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            currentImage.src = e.target.result;
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
