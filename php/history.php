<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Access denied. Please <a href='../index.html'>login</a> first.");
}

include '../db/db.php'; // Include the database connection

$user_id = $_SESSION['user_id'];

// Handle search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'recent';
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : '';
$skills = isset($_GET['skills']) ? $_GET['skills'] : '';

// Build the SQL query
$sql = "SELECT * FROM portfolio WHERE user_id='$user_id'";

// Add search condition
if (!empty($search)) {
    $sql .= " AND full_name LIKE '%$search%'";
}

// Add date condition
if (!empty($search_date)) {
    $sql .= " AND DATE(created_at)='$search_date'";
}

// Add skills condition
if (!empty($skills)) {
    $sql .= " AND technical_skills LIKE '%$skills%'";
}

// Add filter condition
if ($filter === 'recent') {
    $sql .= " ORDER BY created_at DESC";
} elseif ($filter === 'oldest') {
    $sql .= " ORDER BY created_at ASC";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio History</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Portfolio History</h1>
        <a href="../portfolio.html"><button>Create New Portfolio</button></a>
        <a href="logout.php"><button>Logout</button></a>

        <!-- Search Bar -->
        <form action="history.php" method="GET" class="search-filter-form">
            <input type="text" id="search" name="search" placeholder="Search portfolios..." value="<?php echo htmlspecialchars($search); ?>">
            <input type="date" id="search_date" name="search_date" value="<?php echo htmlspecialchars($search_date); ?>">
            <select id="skills" name="skills">
                <option value="">Select Skill</option>
                <option value="PHP" <?php echo ($skills === 'PHP') ? 'selected' : ''; ?>>PHP</option>
                <option value="JavaScript" <?php echo ($skills === 'JavaScript') ? 'selected' : ''; ?>>JavaScript</option>
                <option value="HTML" <?php echo ($skills === 'HTML') ? 'selected' : ''; ?>>HTML</option>
                <option value="CSS" <?php echo ($skills === 'CSS') ? 'selected' : ''; ?>>CSS</option>
            </select>
            <select id="filter" name="filter">
                <option value="recent" <?php echo ($filter === 'recent') ? 'selected' : ''; ?>>Most Recent</option>
                <option value="oldest" <?php echo ($filter === 'oldest') ? 'selected' : ''; ?>>Oldest</option>
            </select>
            <button type="submit">Apply</button>
        </form>

        <!-- Portfolio Table -->
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Full Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['created_at'] . "</td>";
                        echo "<td>" . $row['full_name'] . "</td>";
                        echo "<td>
                                <a href='regenerate_pdf.php?id=" . $row['id'] . "'>Regenerate PDF</a> | 
                                <a href='edit_portfolio.php?id=" . $row['id'] . "'>Edit</a> | 
                                <a href='delete_portfolio.php?id=" . $row['id'] . "' onclick='return confirm(\"Are you sure you want to delete this portfolio?\");'>Delete</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No portfolios found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$conn->close();
?>