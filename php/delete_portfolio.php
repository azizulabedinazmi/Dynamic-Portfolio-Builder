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

// Fetch the PDF file path before deleting the portfolio
$sql = "SELECT pdf_file FROM portfolio WHERE id='$portfolio_id' AND user_id='$user_id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $pdf_file = $row['pdf_file'];

    // Delete the portfolio entry
    $sql = "DELETE FROM portfolio WHERE id='$portfolio_id' AND user_id='$user_id'";

    if ($conn->query($sql) === TRUE) {
        // Delete the PDF file from the server
        if (file_exists($pdf_file)) {
            unlink($pdf_file); // Delete the file
        }
        echo "Portfolio deleted successfully! <a href='history.php'>Go back to history</a>.";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
} else {
    echo "Portfolio not found.";
}

$conn->close();
?>