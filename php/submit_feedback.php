<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Access denied");
}

include '../db/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $message = $conn->real_escape_string($_POST['feedback']);

    $sql = "INSERT INTO feedback (user_id, message) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $message);
    
    if ($stmt->execute()) {
        header("Location: dashboard.php?feedback=success");
    } else {
        header("Location: dashboard.php?feedback=error");
    }
    exit();
}