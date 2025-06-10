<?php
// Database connection configuration
$host = 'localhost'; // or your database server address
$dbname = 'bakeease';
$username = 'root'; // default XAMPP username
$password = ''; // default XAMPP password

try {
    // Create a PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optional: Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Uncomment the following line to verify connection (for testing purposes only)
    // echo "Connected successfully to database: $dbname";
    
} catch(PDOException $e) {
    // Handle connection errors
    die("Connection failed: " . $e->getMessage());
}
?>