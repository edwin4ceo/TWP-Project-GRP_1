<?php
session_start();

// 1. Connect to the database
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'your_db_username';
$password = 'your_db_password';

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 2. Get user input
$adminUser = $_POST['username'];
$adminPass = $_POST['password'];

// 3. Query the database
$sql = "SELECT * FROM admins WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $adminUser, $adminPass);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $adminUser;
    header("Location: admin-dashboard.html");
} else {
    echo "<script>alert('Invalid credentials'); window.location.href='admin-login.html';</script>";
}
?>
