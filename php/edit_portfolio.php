<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Access denied. Please <a href='../index.html'>login</a> first.");
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "portfolio_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$portfolio_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch portfolio data
$sql = "SELECT * FROM portfolio WHERE id='$portfolio_id' AND user_id='$user_id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Portfolio</title>
        <link rel="stylesheet" href="../css/styles.css">
    </head>
    <body>
        <div class="container">
            <h1>Edit Portfolio</h1>
            <form action="update_portfolio.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo $row['full_name']; ?>" required>
                <label for="contact_info">Contact Information:</label>
                <input type="text" id="contact_info" name="contact_info" value="<?php echo $row['contact_info']; ?>" required>
                <label for="bio">Short Bio:</label>
                <textarea id="bio" name="bio" rows="4" required><?php echo $row['bio']; ?></textarea>
                <label for="soft_skills">Soft Skills:</label>
                <textarea id="soft_skills" name="soft_skills" rows="4" required><?php echo $row['soft_skills']; ?></textarea>
                <label for="technical_skills">Technical Skills:</label>
                <textarea id="technical_skills" name="technical_skills" rows="4" required><?php echo $row['technical_skills']; ?></textarea>
                <label for="academic_background">Academic Background:</label>
                <textarea id="academic_background" name="academic_background" rows="4"><?php echo $row['academic_background']; ?></textarea>
                <label for="work_experience">Work Experience:</label>
                <textarea id="work_experience" name="work_experience" rows="4" required><?php echo $row['work_experience']; ?></textarea>
                <label for="projects_publications">Projects/Publications:</label>
                <textarea id="projects_publications" name="projects_publications" rows="4"><?php echo $row['projects_publications']; ?></textarea>
                <button type="submit">Update Portfolio</button>
            </form>
        </div>
    </body>
    </html>
    <?php
} else {
    echo "Portfolio not found.";
}

$conn->close();
?>