<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Access denied. Please <a href='../index.html'>login</a> first.");
}

include '../db/db.php'; // Include the database connection

$user_id = $_SESSION['user_id'];

// Fetch user data
$sql = "SELECT * FROM users WHERE id='$user_id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard</title>
        <link rel="stylesheet" href="../css/styles.css">
    </head>
    <body>
        <div class="container">
            <h1>Dashboard</h1>
            <p>Welcome, <?php echo $row['email']; ?>!</p>
            <a href="../portfolio.html"><button>Create New Portfolio</button></a>
            <a href="history.php"><button>View History</button></a>
            <a href="logout.php"><button>Logout</button></a>
            
            <!-- Feedback Section -->
            <div class="feedback-section">
                <h2>Submit Feedback</h2>
                <form action="submit_feedback.php" method="POST">
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
    <?php
} else {
    echo "User not found.";
}

$conn->close();
?>