<?php
// Database configuration
$db_host = 'your_host';  // Usually 'localhost' or provided by your hosting service
$db_user = 'your_username';
$db_pass = 'your_password';
$db_name = 'your_database_name';

// Create connection with error handling
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        throw new Exception("Database connection failed");
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed");
}
?>
