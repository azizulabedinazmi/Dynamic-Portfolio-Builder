<?php
session_start();
include '../db/db.php';

$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE email='$email'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (password_verify($password, $row['password'])) {
        $_SESSION['user_id'] = $row['id'];
        
        // Audit logging
        $user_id = $row['id'];
        $action_type = 'login';
        $description = "User logged in";
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $log_sql = "INSERT INTO audit_logs (user_id, action_type, description, ip_address)
                    VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($log_sql);
        $stmt->bind_param("isss", $user_id, $action_type, $description, $ip_address);
        $stmt->execute();
        
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Invalid password!";
    }
} else {
    echo "User not found!";
}

$conn->close();
?>