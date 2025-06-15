<?php
// Database connection using MySQLi (not PDO)
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'bakeease';

// Create connection
$conn = mysqli_connect($host, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>