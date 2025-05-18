<?php
// Start session if needed
session_start();

// Connect to database
$conn = new mysqli("localhost", "root", "", "bakeease"); // Adjust DB name and credentials

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Get values
$name = $_POST['name'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Check if admin already exists
$check = $conn->prepare("SELECT id FROM admins WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
  echo "Admin already registered.";
  exit();
}
$check->close();

// Insert new admin
$stmt = $conn->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $password);

if ($stmt->execute()) {
  echo "Admin registered successfully.";
  header("Location: login.html"); // redirect to login
  exit();
} else {
  echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>