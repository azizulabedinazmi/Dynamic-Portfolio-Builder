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

$portfolio_id = $_POST['id'];
$user_id = $_SESSION['user_id'];
$full_name = $_POST['full_name'];
$contact_info = $_POST['contact_info'];
$bio = $_POST['bio'];
$soft_skills = $_POST['soft_skills'];
$technical_skills = $_POST['technical_skills'];
$academic_background = $_POST['academic_background'];
$work_experience = $_POST['work_experience'];
$projects_publications = $_POST['projects_publications'];

$sql = "UPDATE portfolio SET full_name='$full_name', contact_info='$contact_info', bio='$bio', soft_skills='$soft_skills', technical_skills='$technical_skills', academic_background='$academic_background', work_experience='$work_experience', projects_publications='$projects_publications' WHERE id='$portfolio_id' AND user_id='$user_id'";

if ($conn->query($sql) === TRUE) {
    echo "Portfolio updated successfully! <a href='history.php'>Go back to history</a>.";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>