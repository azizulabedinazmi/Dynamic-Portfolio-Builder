<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "portfolio_db";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if the email exists
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $token = bin2hex(random_bytes(32)); // Generate a random token
        $expiry = date("Y-m-d H:i:s", strtotime("+1440 minutes")); // Token expires in 30 minutes

        // Store the token in the database
        $sql = "UPDATE users SET reset_token='$token', reset_token_expiry='$expiry' WHERE email='$email'";
        if ($conn->query($sql) === TRUE) {
            echo "Token stored successfully.<br>";
            echo "Token: $token<br>";
            echo "Expiry: $expiry<br>";

            // Send the reset link via email (for simplicity, we'll just display it)
            $reset_link = "./reset_password.php?token=$token";
            echo "Reset link: <a href='$reset_link'>$reset_link</a>";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } else {
        echo "Email not found.";
    }

    $conn->close();
} else {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Forgot Password</title>
        <link rel="stylesheet" href="../css/styles.css">
    </head>
    <body>
        <div class="container">
            <h1>Forgot Password</h1>
            <form action="forgot_password.php" method="POST">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                <button type="submit">Reset Password</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}
?>