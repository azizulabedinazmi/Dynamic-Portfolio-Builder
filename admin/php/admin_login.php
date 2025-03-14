<?php
session_start();
include '../../db/db.php';

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM admin WHERE username='$username'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    if (password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_role'] = $admin['role'];
        header("Location: ../dashboard.php");
    } else {
        echo "Invalid credentials!";
    }
} else {
    echo "Admin not found!";
}
?>