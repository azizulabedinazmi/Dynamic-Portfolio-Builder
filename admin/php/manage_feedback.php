<?php
session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../db/db.php';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token!");
    }

    // Process close/reopen actions
    if (isset($_POST['feedback_id'])) {
        $feedback_id = intval($_POST['feedback_id']);
        $new_status = isset($_POST['close']) ? 'closed' : 'open';
        
        $stmt = $conn->prepare("UPDATE feedback SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $feedback_id);
        
        if ($stmt->execute()) {
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            die("Update failed: " . $stmt->error);
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check permissions
if (!isset($_SESSION['admin_id']) || 
    !has_permission($_SESSION['admin_id'], 'view_feedback')) {
    die("Permission denied!");
}

// Initialize filter parameters
$search = trim($_GET['search'] ?? '');
$user_filter = intval($_GET['user_id'] ?? 0);
$status_filter = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Validate status filter
$allowed_statuses = ['all', 'open', 'closed'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = 'all';
}

// Base query
$query = "SELECT f.*, u.email 
          FROM feedback f
          JOIN users u ON f.user_id = u.id
          WHERE 1=1";

$count_query = "SELECT COUNT(*) AS total 
                FROM feedback f
                JOIN users u ON f.user_id = u.id
                WHERE 1=1";

$params = [];
$types = '';

// Add filters
if (!empty($search)) {
    $query .= " AND f.message LIKE ?";
    $count_query .= " AND f.message LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if ($user_filter > 0) {
    $query .= " AND f.user_id = ?";
    $count_query .= " AND f.user_id = ?";
    $params[] = $user_filter;
    $types .= 'i';
}

if ($status_filter !== 'all') {
    $query .= " AND f.status = ?";
    $count_query .= " AND f.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Prepare and execute count query
$count_stmt = $conn->prepare($count_query);
if (!$count_stmt) {
    die("Count query failed: " . $conn->error);
}

if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// Add pagination to main query (only for main query)
$query .= " ORDER BY f.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// Execute main query
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Main query failed: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$feedback = $stmt->get_result();

// Get users for filter dropdown
$users = $conn->query("SELECT id, email FROM users ORDER BY email");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Feedback Management</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body>
    <div class="admin-container">
        <h1>User Feedback</h1>
        <a href="../dashboard.php" class="btn-primary">← Dashboard</a>

        <!-- Filters -->
        <form method="GET" class="filters">
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search messages" value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="filter-group">
                <select name="user_id">
                    <option value="0">All Users</option>
                    <?php while($user = $users->fetch_assoc()): ?>
                    <option value="<?= $user['id'] ?>" <?= $user['id'] == $user_filter ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['email']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filter-group">
                <select name="status">
                    <option value="all" <?= ($status_filter === 'all') ? 'selected' : '' ?>>All Statuses</option>
                    <option value="open" <?= ($status_filter === 'open') ? 'selected' : '' ?>>Open</option>
                    <option value="closed" <?= ($status_filter === 'closed') ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>

            <button type="submit" class="btn-primary">Filter</button>
        </form>

        <!-- Feedback Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Message</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($feedback->num_rows > 0): ?>
                <?php while($entry = $feedback->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($entry['email']) ?></td>
                    <td><?= nl2br(htmlspecialchars($entry['message'])) ?></td>
                    <td><?= date('M j, Y g:i a', strtotime($entry['created_at'])) ?></td>
                    <td>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="feedback_id" value="<?= htmlspecialchars($entry['id']) ?>">
                            <?php if ($entry['status'] === 'open'): ?>
                            <button type="submit" name="close" class="btn-danger">Close</button>
                            <?php else: ?>
                            <button type="submit" name="reopen" class="btn-warning">Re-open</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="4">No feedback found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                class="<?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
</body>

</html>