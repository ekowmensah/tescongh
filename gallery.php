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

$pageTitle = 'Photo Gallery';

$database = new Database();
$db = $database->getConnection();

$gallery = new Gallery($db);

// Handle delete
if (isset($_GET['delete']) && hasRole('Admin')) {
    $id = (int)$_GET['delete'];
    $image = $gallery->getById($id);
    
    if ($image) {
        // Delete physical file
        $filePath = 'assets/images/gallery/' . $image['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        if ($gallery->delete($id)) {
            setFlashMessage('success', 'Image deleted successfully');
        } else {
            setFlashMessage('danger', 'Failed to delete image');
        }
    }
    redirect('gallery.php');
}

// Handle filters
$filters = [];
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $filters['category'] = sanitize($_GET['category']);
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 12;
$offset = ($page - 1) * $recordsPerPage;

// Get gallery images
$images = $gallery->getAll($recordsPerPage, $offset, $filters);
$totalImages = $gallery->count($filters);
$pagination = paginate($totalImages, $page, $recordsPerPage);

// Get categories
$categories = $gallery->getCategories();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Photo Gallery</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
            <a href="gallery_add.php" class="btn btn-primary">
                <i class="cil-plus"></i> Upload Photo
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-header">
        <strong>Filter Gallery</strong>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-10">
                <select class="form-select" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" 
                                <?php echo (isset($filters['category']) && $filters['category'] == $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Gallery Grid -->
<div class="row">
    <?php if (empty($images)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="cil-image" style="font-size: 4rem; opacity: 0.3;"></i>
                    <h4 class="mt-3 text-muted">No Images Found</h4>
                    <p class="text-muted">Upload your first photo to get started.</p>
                    <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
                        <a href="gallery_add.php" class="btn btn-primary mt-2">Upload Photo</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($images as $img): ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card h-100 gallery-card">
                    <div class="gallery-image-container">
                        <img src="assets/images/gallery/<?php echo htmlspecialchars($img['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($img['title']); ?>"
                             class="card-img-top gallery-image"
                             data-bs-toggle="modal"
                             data-bs-target="#imageModal<?php echo $img['id']; ?>">
                        <?php if ($img['is_featured']): ?>
                            <span class="badge bg-warning position-absolute top-0 end-0 m-2">
                                <i class="cil-star"></i> Featured
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($img['title']); ?></h6>
                        <p class="card-text small text-muted">
                            <?php echo htmlspecialchars(substr($img['description'], 0, 60)); ?>
                            <?php echo strlen($img['description']) > 60 ? '...' : ''; ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <span class="badge bg-info"><?php echo htmlspecialchars($img['category']); ?></span>
                            </small>
                            <?php if (hasRole('Admin')): ?>
                                <div class="btn-group btn-group-sm">
                                    <a href="gallery_edit.php?id=<?php echo $img['id']; ?>" 
                                       class="btn btn-warning" title="Edit">
                                        <i class="cil-pencil"></i>
                                    </a>
                                    <a href="gallery.php?delete=<?php echo $img['id']; ?>" 
                                       class="btn btn-danger" 
                                       title="Delete"
                                       onclick="return confirmDelete('Are you sure you want to delete this image?')">
                                        <i class="cil-trash"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer text-muted small">
                        <?php echo formatDate($img['created_at'], 'd M Y'); ?>
                    </div>
                </div>
            </div>

            <!-- Image Modal -->
            <div class="modal fade" id="imageModal<?php echo $img['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?php echo htmlspecialchars($img['title']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <img src="assets/images/gallery/<?php echo htmlspecialchars($img['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($img['title']); ?>"
                                 class="img-fluid">
                            <?php if ($img['description']): ?>
                                <p class="mt-3"><?php echo nl2br(htmlspecialchars($img['description'])); ?></p>
                            <?php endif; ?>
                            <div class="mt-3">
                                <span class="badge bg-info"><?php echo htmlspecialchars($img['category']); ?></span>
                                <small class="text-muted ms-2">
                                    Uploaded on <?php echo formatDate($img['created_at'], 'd M Y'); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($pagination['total_pages'] > 1): ?>
    <div class="mt-4">
        <?php echo generatePaginationHTML($pagination, 'gallery.php'); ?>
    </div>
<?php endif; ?>

<style>
.gallery-card {
    transition: transform 0.3s, box-shadow 0.3s;
}

.gallery-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.gallery-image-container {
    position: relative;
    overflow: hidden;
    height: 200px;
}

.gallery-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.3s;
}

.gallery-image:hover {
    transform: scale(1.1);
}
</style>

<?php include 'includes/footer.php'; ?>
