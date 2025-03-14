<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.html");
    exit();
}

include '../../db/db.php';

// User growth data
$userGrowth = $conn->query("SELECT DATE(created_at) AS date, COUNT(*) AS count 
                           FROM users GROUP BY DATE(created_at) ORDER BY date");
$labels = [];
$data = [];
while ($row = $userGrowth->fetch_assoc()) {
    $labels[] = $row['date'];
    $data[] = $row['count'];
}

// Portfolio growth data
$portfolioGrowth = $conn->query("SELECT DATE(created_at) AS date, COUNT(*) AS count 
                                FROM portfolio GROUP BY DATE(created_at) ORDER BY date");
$portfolioLabels = [];
$portfolioData = [];
while ($row = $portfolioGrowth->fetch_assoc()) {
    $portfolioLabels[] = $row['date'];
    $portfolioData[] = $row['count'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Analytics Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="main-content">
        <h1>System Analytics</h1>
        
        <div class="chart-container">
            <canvas id="userGrowthChart"></canvas>
        </div>
        
        <div class="chart-container">
            <canvas id="portfolioGrowthChart"></canvas>
        </div>

        <script>
            // User Growth Chart
            new Chart(document.getElementById('userGrowthChart'), {
                type: 'line',
                data: {
                    labels: <?= json_encode($labels) ?>,
                    datasets: [{
                        label: 'User Growth',
                        data: <?= json_encode($data) ?>,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                }
            });

            // Portfolio Growth Chart
            new Chart(document.getElementById('portfolioGrowthChart'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode($portfolioLabels) ?>,
                    datasets: [{
                        label: 'Portfolios Created',
                        data: <?= json_encode($portfolioData) ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 1
                    }]
                }
            });
        </script>
    </div>
</body>
</html>