<?php
session_start();
include '../../db/db.php';

// Only super admins can create new admins
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'super_admin') {
    die("Permission denied!");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $permissions = json_encode($_POST['permissions'] ?? []);

    // Check if username exists
    $check = $conn->query("SELECT * FROM admin WHERE username = '$username'");
    if ($check->num_rows > 0) {
        die("Username already exists!");
    }

    // Create new admin
    $stmt = $conn->prepare("INSERT INTO admin (username, password, role, permissions) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $role, $permissions);
    
    if ($stmt->execute()) {
        header("Location: manage_users.php?success=Admin+created");
    } else {
        header("Location: manage_users.php?error=Creation+failed");
    }
    exit();
}