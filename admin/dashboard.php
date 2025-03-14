<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.html");
    exit();
}

include '../db/db.php';

// Fetch stats
$users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$portfolios = $conn->query("SELECT COUNT(*) FROM portfolio")->fetch_row()[0];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="/Lab2_test2/admin/php/manage_users.php">User Management</a>
        <a href="/Lab2_test2/admin/php/manage_portfolios.php">Portfolio Management</a>
        <a href="/Lab2_test2/admin/php/manage_feedback.php">Feedback Management</a>
        <a href="../php/logout.php">Logout</a>
    </div>

    <div class="main-content">
        <h1>System Overview</h1>
        <div class="stats">
            <div class="stat-box">
                <h3>Total Users</h3>
                <p><?= $users ?></p>
            </div>
            <div class="stat-box">
                <h3>Total Portfolios</h3>
                <p><?= $portfolios ?></p>
            </div>
        </div>

        <h2>Recent Activity</h2>
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $logs = $conn->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10");
                while ($log = $logs->fetch_assoc()):
                ?>
                <tr>
                    <td><?= $log['created_at'] ?></td>
                    <td><?= $log['user_id'] ?></td>
                    <td><?= $log['description'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Feedback Section -->
        <div class="feedback-section">
            <h2>Submit Feedback</h2>
            <form action="php/submit_feedback.php" method="POST">
                <textarea name="feedback" rows="4" required 
                          placeholder="Enter your feedback here..."></textarea>
                <button type="submit" class="btn-primary">Submit Feedback</button>
            </form>
            
            <?php if (isset($_GET['feedback'])): ?>
                <p class="feedback-msg">
                    <?php 
                    echo $_GET['feedback'] === 'success' 
                        ? "Thank you for your feedback!" 
                        : "Error submitting feedback. Please try again.";
                    ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>