<?php
session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../db/db.php';

// Check admin permissions
if (!isset($_SESSION['admin_id'])) {
    die("Permission denied!");
}

// Initialize variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Base query
$query = "SELECT p.*, u.email 
          FROM portfolio p
          LEFT JOIN users u ON p.user_id = u.id
          WHERE 1=1";

$params = [];
$types = '';

// Add search filter
if (!empty($search)) {
    $query .= " AND (p.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

// Add user filter
if ($user_id > 0) {
    $query .= " AND p.user_id = ?";
    $params[] = $user_id;
    $types .= 'i';
}

// Add date filter
if (!empty($date_filter)) {
    $query .= " AND DATE(p.created_at) = ?";
    $params[] = $date_filter;
    $types .= 's';
}

// Get total count
$count_query = "SELECT COUNT(*) AS total FROM ($query) AS tmp";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// Add pagination
$query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// Fetch filtered portfolios
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$portfolios = $stmt->get_result();

// Fetch all users for filter dropdown
$users = $conn->query("SELECT id, email FROM users ORDER BY email");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Portfolio Management</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
    .filters {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        margin-bottom: 0.5rem;
        font-weight: bold;
    }

    input[type="text"],
    select,
    input[type="date"] {
        padding: 0.5rem;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    </style>
</head>

<body>
    <div class="admin-container">
        <h1>Portfolio Management</h1>
        <div class="navigation-buttons" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <a href="../dashboard.php" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                    class="bi bi-arrow-left" viewBox="0 0 16 16" style="margin-right: 5px;">
                    <path fill-rule="evenodd"
                        d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z" />
                </svg>
                Back to Dashboard
            </a>
        </div>

        <!-- Filters -->
        <form method="GET" class="filters">
            <div class="filter-group">
                <label>Search:</label>
                <input type="text" name="search" placeholder="Name or email" value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="filter-group">
                <label>Filter by User:</label>
                <select name="user_id">
                    <option value="0">All Users</option>
                    <?php while($user = $users->fetch_assoc()): ?>
                    <option value="<?= $user['id'] ?>" <?= $user['id'] == $user_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['email']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Filter by Date:</label>
                <input type="date" name="date_filter" value="<?= htmlspecialchars($date_filter) ?>">
            </div>

            <div class="filter-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn-primary">Apply Filters</button>
            </div>
        </form>

        <!-- Results Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Name</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($portfolios->num_rows > 0): ?>
                <?php while($portfolio = $portfolios->fetch_assoc()): ?>
                <tr>
                    <td><?= $portfolio['id'] ?></td>
                    <td><?= htmlspecialchars($portfolio['email']) ?></td>
                    <td><?= htmlspecialchars($portfolio['full_name']) ?></td>
                    <td><?= date('M d, Y H:i', strtotime($portfolio['created_at'])) ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="portfolio_id" value="<?= $portfolio['id'] ?>">
                            <button type="submit" name="delete_portfolio" class="btn-danger"
                                onclick="return confirm('Delete portfolio #<?= $portfolio['id'] ?>?')">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="5">No portfolios found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&user_id=<?= $user_id ?>&date_filter=<?= $date_filter ?>"
                class="<?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
            <!-- Fixed closing tag -->
        </div>
    </div>
</body>

</html>