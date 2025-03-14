<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "portfolio_db";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Debugging: Print the token from the form
    echo "Token from form: $token<br>";

    // Check if the token is valid and not expired
    $sql = "SELECT * FROM users WHERE reset_token='$token' AND reset_token_expiry > NOW()";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "Token is valid.<br>";
        echo "Expiry time: " . $row['reset_token_expiry'] . "<br>";

        // Update the password and clear the token
        $sql = "UPDATE users SET password='$new_password', reset_token=NULL, reset_token_expiry=NULL WHERE reset_token='$token'";
        if ($conn->query($sql) === TRUE) {
            echo "Password reset successfully! <a href='../index.html'>Login here</a>.";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } else {
        echo "Invalid or expired token.<br>";
        echo "SQL Query: $sql<br>";
    }

    $conn->close();
} else {
    $token = $_GET['token'];
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reset Password</title>
        <link rel="stylesheet" href="../css/styles.css">
    </head>
    <body>
        <div class="container">
            <h1>Reset Password</h1>
            <form action="reset_password.php" method="POST">
                <input type="hidden" name="token" value="<?php echo $token; ?>">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>
                <button type="submit">Reset Password</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}
?>