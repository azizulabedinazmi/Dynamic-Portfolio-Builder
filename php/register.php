<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "portfolio_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$sql = "INSERT INTO users (email, password) VALUES ('$email', '$password')";

if ($conn->query($sql) === TRUE) {
    echo "<div style='font-family: Arial, sans-serif; color: green; animation: fadeIn 3s; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;'>
            Registration successful! <a href='../index.html' style='color: blue;'>Login here</a>.
          </div>
          <style>
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            body {
                background: url('../gif/J59.gif') no-repeat center center fixed;
                background-size: cover;
            }
          </style>";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>