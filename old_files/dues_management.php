<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an executive or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Executive', 'Patron', 'Admin'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Handle dues creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_dues'])) {
    $year = (int)$_POST['year'];
    $amount = (float)$_POST['amount'];
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];

    if (empty($year) || $amount <= 0 || empty($description) || empty($due_date)) {
        $error = 'All fields are required and amount must be greater than 0.';
    } else {
        try {
            // Check if dues already exist for this year
            $stmt = $pdo->prepare("SELECT id FROM dues WHERE year = ?");
            $stmt->execute([$year]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Update existing
                $stmt = $pdo->prepare("UPDATE dues SET amount = ?, description = ?, due_date = ? WHERE year = ?");
                $stmt->execute([$amount, $description, $due_date, $year]);
                $success = 'Dues updated successfully for ' . $year;
            } else {
                // Insert new
                $stmt = $pdo->prepare("INSERT INTO dues (year, amount, description, due_date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$year, $amount, $description, $due_date]);
                $success = 'Dues created successfully for ' . $year;
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle manual payment recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $member_id = $_POST['member_id'];
    $dues_id = $_POST['dues_id'];
    $amount = (float)$_POST['payment_amount'];
    $payment_method = $_POST['payment_method'];
    $notes = trim($_POST['notes']);

    if (empty($member_id) || empty($dues_id) || $amount <= 0) {
        $error = 'All required fields must be filled and amount must be greater than 0.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO payments (member_id, dues_id, amount, payment_method, status, payment_date, notes)
                VALUES (?, ?, ?, ?, 'completed', NOW(), ?)
            ");
            $stmt->execute([$member_id, $dues_id, $amount, $payment_method, $notes]);
            $success = 'Payment recorded successfully.';
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get all dues
$dues = [];
try {
    $stmt = $pdo->query("SELECT * FROM dues ORDER BY year DESC");
    $dues = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Get payment statistics
$stats = [];
try {
    // Total payments
    $stmt = $pdo->query("SELECT COUNT(*) as total_payments, SUM(amount) as total_amount FROM payments WHERE status = 'completed'");
    $stats = $stmt->fetch();

    // Current year payments
    $currentYear = date('Y');
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as year_payments, SUM(p.amount) as year_amount
        FROM payments p
        JOIN dues d ON p.dues_id = d.id
        WHERE d.year = ? AND p.status = 'completed'
    ");
    $stmt->execute([$currentYear]);
    $yearStats = $stmt->fetch();

    $stats['year_payments'] = $yearStats['year_payments'] ?? 0;
    $stats['year_amount'] = $yearStats['year_amount'] ?? 0;

} catch (PDOException $e) {
    // Handle silently
}

// Get members for manual payment recording
$members = [];
try {
    $stmt = $pdo->query("SELECT id, fullname, email FROM members ORDER BY fullname");
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle silently
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dues Management - UEW-TESCON</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Dues Management</h2>
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-credit-card"></i> Total Payments</h5>
                        <h3><?php echo $stats['total_payments'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-money-bill-wave"></i> Total Amount</h5>
                        <h3>GH₵ <?php echo number_format($stats['total_amount'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar"></i> This Year</h5>
                        <h3><?php echo $stats['year_payments'] ?? 0; ?> payments</h3>
                        <small>GH₵ <?php echo number_format($stats['year_amount'] ?? 0, 2); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users"></i> Active Members</h5>
                        <h3><?php echo count($members); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Manage Dues -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Manage Annual Dues</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="save_dues" value="1">
                            <div class="mb-3">
                                <label for="year" class="form-label">Year *</label>
                                <input type="number" class="form-control" id="year" name="year" required
                                       min="2020" max="2030" value="<?php echo date('Y'); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount (GH₵) *</label>
                                <input type="number" class="form-control" id="amount" name="amount" required
                                       step="0.01" min="0" placeholder="50.00">
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <input type="text" class="form-control" id="description" name="description" required
                                       placeholder="Annual TESCON Membership Dues 2024">
                            </div>
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date *</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" required
                                       value="<?php echo date('Y') . '-12-31'; ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Dues</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Record Manual Payment -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Record Manual Payment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="record_payment" value="1">
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
                                <label for="dues_id" class="form-label">Dues Year *</label>
                                <select class="form-select" id="dues_id" name="dues_id" required>
                                    <option value="">Select Year</option>
                                    <?php foreach ($dues as $due): ?>
                                        <option value="<?php echo $due['id']; ?>">
                                            <?php echo $due['year'] . ' - GH₵ ' . number_format($due['amount'], 2); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="payment_amount" class="form-label">Amount Paid *</label>
                                <input type="number" class="form-control" id="payment_amount" name="payment_amount" required
                                       step="0.01" min="0">
                            </div>
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method *</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cash">Cash</option>
                                    <option value="hubtel_mobile">Mobile Money</option>
                                    <option value="hubtel_card">Card Payment</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">Record Payment</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Dues -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Configured Dues</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Due Date</th>
                                <th>Payments Made</th>
                                <th>Total Collected</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dues as $due): ?>
                                <tr>
                                    <td><?php echo $due['year']; ?></td>
                                    <td>GH₵ <?php echo number_format($due['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($due['description']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($due['due_date'])); ?></td>
                                    <td>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payments WHERE dues_id = ? AND status = 'completed'");
                                        $stmt->execute([$due['id']]);
                                        echo $stmt->fetch()['count'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE dues_id = ? AND status = 'completed'");
                                        $stmt->execute([$due['id']]);
                                        $total = $stmt->fetch()['total'];
                                        echo 'GH₵ ' . number_format($total ?? 0, 2);
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($dues)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No dues configured yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Recent Payments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Member</th>
                                <th>Year</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT p.*, m.fullname, d.year
                                    FROM payments p
                                    JOIN members m ON p.member_id = m.id
                                    JOIN dues d ON p.dues_id = d.id
                                    ORDER BY p.created_at DESC LIMIT 20
                                ");
                                $recentPayments = $stmt->fetchAll();

                                foreach ($recentPayments as $payment): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($payment['payment_date'] ?: $payment['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($payment['fullname']); ?></td>
                                        <td><?php echo $payment['year']; ?></td>
                                        <td>GH₵ <?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $payment['status'] == 'completed' ? 'success' : ($payment['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach;

                                if (empty($recentPayments)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No payments recorded yet.</td>
                                    </tr>
                                <?php endif;
                            } catch (PDOException $e) {
                                echo '<tr><td colspan="6" class="text-danger">Error loading payments</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
