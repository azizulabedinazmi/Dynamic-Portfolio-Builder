<?php
session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../db/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check admin authentication and permissions
if (!isset($_SESSION['admin_id']) || !has_permission($_SESSION['admin_id'], 'manage_users')) {
    header("HTTP/1.1 403 Forbidden");
    die("Permission denied!");
}

// Verify database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Initialize variables
$error = '';
$success = '';
$search = '';
$page = 1;
$limit = 10;

// Handle pagination
if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $page = max(1, intval($_GET['page']));
}

// Handle search
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token!");
    }

    if (isset($_POST['ban_user'])) {
        // Ban/unban user logic
        $user_id = intval($_POST['user_id']);
        $banned = isset($_POST['banned']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE users SET banned=? WHERE id=?");
        $stmt->bind_param("ii", $banned, $user_id);
        if (!$stmt->execute()) {
            $error = "Error updating user: " . $stmt->error;
        }
    }

    if (isset($_POST['delete_user'])) {
        // Delete user logic
        $user_id = intval($_POST['user_id']);
        
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            $error = "Error deleting user: " . $stmt->error;
        }
    }
}

// Generate CSRF token
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// Build base query
$base_query = "SELECT id, email, created_at, banned FROM users";
$where = [];
$params = [];
$types = '';

// Add search filter
if (!empty($search)) {
    $where[] = "email LIKE CONCAT('%', ?, '%')";
    $params[] = $search;
    $types .= 's';
}

// Build final query
$query = $base_query;
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Get total users for pagination
$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM ($query) AS tmp");
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_users = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

// Add pagination to query
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = ($page - 1) * $limit;
$types .= 'ii';

// Get users
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body>
    <div class="admin-container">
        <h1>User Management</h1>
        <div class="navigation-buttons" style="margin-bottom: 20px;">
            <a href="../dashboard.php" class="btn-primary">← Dashboard</a>
        </div>

        <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Search Form -->
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search by email" value="<?= htmlspecialchars($search) ?>">
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
                <?php if ($users_result->num_rows > 0): ?>
                <?php while ($user = $users_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                    <td><?= $user['banned'] ? '🚫 Banned' : '✅ Active' ?></td>
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
                            onsubmit="return confirm('Delete user <?= $user['id'] ?> permanently?')">
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
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
</body>

</html>