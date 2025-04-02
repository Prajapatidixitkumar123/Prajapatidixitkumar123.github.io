<?php
$host = 'localhost';     // Your database host
$dbname = 'your_db';     // Your database name
$username = 'your_user'; // Your database username
$password = 'your_pass'; // Your database password

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        throw new Exception("Database connection failed");
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]));
}
?>
