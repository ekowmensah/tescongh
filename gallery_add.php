<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Gallery.php';

if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Upload Photo';

$database = new Database();
$db = $database->getConnection();

$gallery = new Gallery($db);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $category = sanitize($_POST['category']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];
    
    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = $_FILES['image']['type'];
        $filesize = $_FILES['image']['size'];
        
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            setFlashMessage('danger', 'Invalid file type. Only JPG, PNG, and GIF are allowed.');
        } elseif ($filesize > 5 * 1024 * 1024) { // 5MB max
            setFlashMessage('danger', 'File size too large. Maximum 5MB allowed.');
        } else {
            // Create upload directory if it doesn't exist
            $uploadDir = 'assets/images/gallery/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $newFilename = uniqid() . '_' . time() . '.' . $ext;
            $uploadPath = $uploadDir . $newFilename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                // Save to database
                $data = [
                    'title' => $title,
                    'description' => $description,
                    'image_path' => $newFilename,
                    'category' => $category,
                    'is_featured' => $is_featured,
                    'display_order' => $display_order,
                    'uploaded_by' => $_SESSION['user_id']
                ];
                
                $result = $gallery->create($data);
                
                if ($result['success']) {
                    setFlashMessage('success', 'Image uploaded successfully');
                    redirect('gallery.php');
                } else {
                    // Delete uploaded file if database insert fails
                    unlink($uploadPath);
                    setFlashMessage('danger', 'Failed to save image to database');
                }
            } else {
                setFlashMessage('danger', 'Failed to upload image');
            }
        }
    } else {
        setFlashMessage('danger', 'Please select an image to upload');
    }
}

// Get categories for dropdown
$categories = $gallery->getCategories();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Upload Photo</h2>
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
                <strong>Upload New Photo</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required 
                               placeholder="e.g., Annual Conference 2024">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" 
                                  placeholder="Brief description of the photo"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="category" required 
                               list="categoryList" placeholder="e.g., Events, Leadership, Activities">
                        <datalist id="categoryList">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                            <option value="Events">
                            <option value="Leadership">
                            <option value="Activities">
                            <option value="Meetings">
                            <option value="Campaigns">
                        </datalist>
                        <small class="text-muted">Type a new category or select from existing ones</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Image <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="image" accept="image/*" required 
                               onchange="previewImage(this)">
                        <small class="text-muted">Max file size: 5MB. Allowed formats: JPG, PNG, GIF</small>
                    </div>

                    <div class="mb-3">
                        <div id="imagePreview" class="mt-2" style="display: none;">
                            <img id="preview" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" class="form-control" name="display_order" value="0" min="0">
                        <small class="text-muted">Lower numbers appear first (0 = default)</small>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured">
                        <label class="form-check-label" for="is_featured">
                            <i class="cil-star text-warning"></i> Feature on Homepage
                        </label>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="cil-cloud-upload"></i> Upload Photo
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
                <strong>Upload Guidelines</strong>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="cil-check-circle text-success"></i> Use high-quality images
                    </li>
                    <li class="mb-2">
                        <i class="cil-check-circle text-success"></i> Recommended size: 1200x800px
                    </li>
                    <li class="mb-2">
                        <i class="cil-check-circle text-success"></i> Maximum file size: 5MB
                    </li>
                    <li class="mb-2">
                        <i class="cil-check-circle text-success"></i> Formats: JPG, PNG, GIF
                    </li>
                    <li class="mb-2">
                        <i class="cil-check-circle text-success"></i> Add descriptive titles
                    </li>
                    <li class="mb-2">
                        <i class="cil-check-circle text-success"></i> Categorize properly
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('preview');
    const previewDiv = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewDiv.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
