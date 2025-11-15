<?php
session_start();
require_once 'config/database.php';
require_once 'includes/SMSNotifications.php';

// Check if user is logged in and is an executive or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Executive', 'Patron', 'Admin'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Get institutions, regions and constituencies for dropdowns
$institutions = [];
$regions = [];
$constituencies = [];
try {
    $stmt = $pdo->query("SELECT * FROM institutions ORDER BY name");
    $institutions = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT * FROM regions ORDER BY name");
    $regions = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT c.*, r.name as region_name
        FROM constituencies c
        JOIN regions r ON c.region_id = r.id
        ORDER BY r.name, c.name
    ");
    $constituencies = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Handle campus creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_campus'])) {
    $name = trim($_POST['name']);
    $institution_id = $_POST['institution_id'];
    $region_id = $_POST['region_id'];
    $constituency_id = $_POST['constituency_id'];
    $location = trim($_POST['location']);

    if (empty($name) || empty($institution_id) || empty($region_id) || empty($constituency_id)) {
        $error = 'All fields are required';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO campuses (name, institution_id, location, region_id, constituency_id, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $institution_id, $location, $region_id, $constituency_id, $_SESSION['user_id']])) {
                $success = 'Campus created successfully';
            } else {
                $error = 'Failed to create campus';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle executive assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_executive'])) {
    $campus_id = $_POST['campus_id'];
    $member_id = $_POST['member_id'];
    $position = trim($_POST['executive_position']);

    if (empty($campus_id) || empty($member_id) || empty($position)) {
        $error = 'All fields are required for executive assignment';
    } else {
        try {
            // Check if member is already an executive for this campus
            $stmt = $pdo->prepare("SELECT id FROM campus_executives WHERE campus_id = ? AND member_id = ?");
            $stmt->execute([$campus_id, $member_id]);

            if ($stmt->rowCount() > 0) {
                $existing = true;
            } else {
                $existing = false;
            }

            if ($existing) {
                // Update existing position
                $stmt = $pdo->prepare("UPDATE campus_executives SET position = ? WHERE campus_id = ? AND member_id = ?");
                $stmt->execute([$position, $campus_id, $member_id]);
                $success = 'Executive position updated successfully';
            } else {
                // Insert new executive
                $stmt = $pdo->prepare("INSERT INTO campus_executives (campus_id, member_id, position) VALUES (?, ?, ?)");
                if ($stmt->execute([$campus_id, $member_id, $position])) {
                    $executiveId = $pdo->lastInsertId();
                    $success = 'Executive assigned successfully';

                    // Send SMS notification to the new executive
                    $smsResult = sendExecutiveAppointmentSMS($executiveId);
                    if (!$smsResult['success']) {
                        // Don't fail the operation, just log the SMS failure
                        error_log("Failed to send executive appointment SMS: " . ($smsResult['error'] ?? 'Unknown error'));
                    }
                } else {
                    $error = 'Failed to assign executive';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle executive removal
if (isset($_GET['remove_executive'])) {
    $executive_id = $_GET['remove_executive'];
    try {
        $stmt = $pdo->prepare("DELETE FROM campus_executives WHERE id = ?");
        if ($stmt->execute([$executive_id])) {
            $success = 'Executive removed successfully';
        } else {
            $error = 'Failed to remove executive';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Get all campuses
$campuses = [];
try {
    $stmt = $pdo->query("SELECT c.*, i.name as institution_name FROM campuses c JOIN institutions i ON c.institution_id = i.id ORDER BY i.name, c.name");
    $campuses = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Get all members for executive assignment
$members = [];
try {
    $stmt = $pdo->query("SELECT m.id, m.fullname, u.email FROM members m JOIN users u ON m.user_id = u.id ORDER BY m.fullname");
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Get campus executives with member details
$campus_executives = [];
try {
    $stmt = $pdo->query("SELECT ce.*, m.fullname, u.email, c.name as campus_name, i.name as campus_institution
                        FROM campus_executives ce
                        JOIN members m ON ce.member_id = m.id
                        JOIN users u ON m.user_id = u.id
                        JOIN campuses c ON ce.campus_id = c.id
                        JOIN institutions i ON c.institution_id = i.id
                        ORDER BY i.name, c.name, ce.position");
    $campus_executives = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Management - UEW-TESCON</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Campus Management</h2>
            <div>
                <a href="members.php" class="btn btn-secondary me-2">Back to Members</a>
                <span class="me-3">Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Create Campus -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Create New Campus</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="create_campus" value="1">
                            <div class="mb-3">
                                <label for="name" class="form-label">Campus Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="institution_id" class="form-label">Institution *</label>
                                <select class="form-select" id="institution_id" name="institution_id" required>
                                    <option value="">Select Institution</option>
                                    <?php foreach ($institutions as $institution): ?>
                                        <option value="<?php echo $institution['id']; ?>"><?php echo htmlspecialchars($institution['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="region_id" class="form-label">Region *</label>
                                <select class="form-select" id="region_id" name="region_id" required>
                                    <option value="">Select Region</option>
                                    <?php foreach ($regions as $region): ?>
                                        <option value="<?php echo $region['id']; ?>"><?php echo htmlspecialchars($region['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="constituency_id" class="form-label">Constituency *</label>
                                <select class="form-select" id="constituency_id" name="constituency_id" required>
                                    <option value="">Select Constituency</option>
                                    <?php foreach ($constituencies as $constituency): ?>
                                        <option value="<?php echo $constituency['id']; ?>" data-region="<?php echo $constituency['region_name']; ?>"><?php echo htmlspecialchars($constituency['name'] . ' (' . $constituency['region_name'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location">
                            </div>
                            <button type="submit" class="btn btn-primary">Create Campus</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Assign Executive -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Assign Campus Executive</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="assign_executive" value="1">
                            <div class="mb-3">
                                <label for="campus_id" class="form-label">Campus *</label>
                                <select class="form-select" id="campus_id" name="campus_id" required>
                                    <option value="">Select Campus</option>
                                    <?php foreach ($campuses as $campus): ?>
                                        <option value="<?php echo $campus['id']; ?>">
                                            <?php echo htmlspecialchars($campus['institution_name'] . ' - ' . $campus['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="member_id" class="form-label">Member *</label>
                                <select class="form-select" id="member_id" name="member_id" required>
                                    <option value="">Select Member</option>
                                    <?php foreach ($members as $member): ?>
                                        <option value="<?php echo $member['id']; ?>">
                                            <?php echo htmlspecialchars($member['fullname'] . ' (' . $member['email'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="executive_position" class="form-label">Position *</label>
                                <input type="text" class="form-control" id="executive_position" name="executive_position"
                                       placeholder="e.g., President, Secretary, Treasurer" required>
                            </div>
                            <button type="submit" class="btn btn-success">Assign Executive</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Campuses -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Current Campuses</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Institution</th>
                                <th>Location</th>
                                <th>Members Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campuses as $campus): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($campus['name']); ?></td>
                                    <td><?php echo htmlspecialchars($campus['institution_name']); ?></td>
                                    <td><?php echo htmlspecialchars($campus['location'] ?: '-'); ?></td>
                                    <td>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM members WHERE campus_id = ?");
                                        $stmt->execute([$campus['id']]);
                                        $count = $stmt->fetch()['count'];
                                        echo $count;
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Campus Executives -->
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Campus Executives</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Campus</th>
                                <th>Executive Name</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Appointed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campus_executives as $executive): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($executive['campus_institution'] . ' - ' . $executive['campus_name']); ?></td>
                                    <td><?php echo htmlspecialchars($executive['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($executive['email']); ?></td>
                                    <td><?php echo htmlspecialchars($executive['position']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($executive['appointed_at'])); ?></td>
                                    <td>
                                        <a href="?remove_executive=<?php echo $executive['id']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to remove this executive?')">
                                            <i class="fas fa-trash"></i> Remove
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($campus_executives)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No campus executives assigned yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Dynamic constituency loading based on region selection
        document.getElementById('region_id').addEventListener('change', function() {
            const selectedRegionId = this.value;
            const selectedRegionName = this.options[this.selectedIndex].text;
            const constituencySelect = document.getElementById('constituency_id');
            const constituencies = <?php echo json_encode($constituencies); ?>;

            // Clear constituency options
            constituencySelect.innerHTML = '<option value="">Select Constituency</option>';

            // Filter and add constituencies for selected region
            constituencies.forEach(function(constituency) {
                if (selectedRegionId === '' || constituency.region_id == selectedRegionId) {
                    const option = document.createElement('option');
                    option.value = constituency.id;
                    option.textContent = constituency.name + ' (' + constituency.region_name + ')';
                    option.setAttribute('data-region', constituency.region_name);

                    constituencySelect.appendChild(option);
                }
            });
        });
    </script>
</body>
</html>
