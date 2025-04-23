<?php
session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../db/db.php';

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check permissions
if (!isset($_SESSION['admin_id']) || 
    !has_permission($_SESSION['admin_id'], 'manage_users')) {
    die("Permission denied!");
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token!");
    }

    // Ban/Unban user
    if (isset($_POST['ban_user'])) {
        $user_id = intval($_POST['user_id']);
        $banned = isset($_POST['banned']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE users SET banned = ? WHERE id = ?");
        $stmt->bind_param("ii", $banned, $user_id);
        $stmt->execute();
    }

    // Delete user
    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        
        // Delete related records in audit_logs first
        $stmt = $conn->prepare("DELETE FROM audit_logs WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Delete related records in portfolio table first
        $stmt = $conn->prepare("DELETE FROM portfolio WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Then delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }

    // Redirect to prevent form resubmission
    header("Location: manage_users.php");
    exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Initialize filter parameters
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Base query
$query = "SELECT id, email, created_at, banned FROM users WHERE 1=1";
$count_query = "SELECT COUNT(*) AS total FROM users WHERE 1=1";

$params = [];
$types = '';

// Add search filter
if (!empty($search)) {
    $query .= " AND email LIKE ?";
    $count_query .= " AND email LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

// Get total count
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// Add pagination to query
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// Execute main query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
    <link rel="stylesheet" href="../css/admin.css">
    <script>
    function confirmDelete(userId) {
        return confirm(`Are you sure you want to delete user #${userId}?`);
    }
    </script>
</head>
<body>
    <div class="admin-container">
        <h1>User Management</h1>
        <a href="../dashboard.php" class="btn-primary">← Dashboard</a>

        <!-- Search Form -->
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search users..." 
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>

        <!-- Users Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users->num_rows > 0): ?>
                <?php while($user = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <?= $user['banned'] ? '🚫 Banned' : '✅ Active' ?>
                    </td>
                    <td>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="banned" value="<?= $user['banned'] ? 0 : 1 ?>">
                            <button type="submit" name="ban_user" class="btn-warning">
                                <?= $user['banned'] ? 'Unban' : 'Ban' ?>
                            </button>
                        </form>
                        
                        <form method="POST" class="inline-form" 
                              onsubmit="return confirmDelete(<?= $user['id'] ?>)">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" name="delete_user" class="btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="5">No users found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
               class="<?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>
