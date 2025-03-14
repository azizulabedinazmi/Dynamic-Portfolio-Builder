<?php
// create_admin.php - UNSECURED ADMIN CREATION (FOR DEMONSTRATION ONLY)
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Lab2_test2/db/db.php'; // Database connection

$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $permissions = json_encode(['all']); // Grant all permissions

    // Basic validation
    if (empty($username) || empty($password)) {
        $error = "Username and password are required!";
    } else {
        // Check if username exists
        $check = $conn->prepare("SELECT id FROM admin WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Username already exists!";
        } else {
            // Create admin account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admin (username, password, role, permissions) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $hashed_password, $role, $permissions);

            if ($stmt->execute()) {
                $success = "Admin account created successfully!";
            } else {
                $error = "Error: " . $conn->error;
            }
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Admin Account</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 20px auto; padding: 20px; }
        .alert { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .error { background-color: #ffd4d4; color: #a00000; }
        .success { background-color: #d4ffd4; color: #00a000; }
        .form-group { margin: 15px 0; }
        input, select { width: 100%; padding: 8px; margin-top: 5px; }
    </style>
</head>
<body>
    <h1>Create Admin Account</h1>

    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>Role:</label>
            <select name="role" required>
                <option value="super_admin">Super Admin</option>
                <option value="moderator">Moderator</option>
            </select>
        </div>

        <button type="submit">Create Admin</button>
    </form>

    <!-- SECURITY WARNING -->
    <div style="color: red; margin-top: 30px; border-top: 1px solid #ccc; padding-top: 20px;">
        <strong>Security Warning:</strong>
        <ul>
            <li>This page has no authentication or authorization checks</li>
            <li>Anyone can create admin accounts with full privileges</li>
            <li>Should only be used for initial setup in controlled environments</li>
            <li>Delete this file immediately after use</li>
        </ul>
    </div>
</body>
</html>