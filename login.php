<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];
    
    // Determine if identifier is email, phone, or username
    $sql = "SELECT * FROM users WHERE (email = ? OR phone = ? OR username = ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Database error"
        ]);
        exit();
    }

    $stmt->bind_param("sss", $identifier, $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Store session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'];
            
            echo json_encode([
                "success" => true,
                "message" => "Login successful"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Invalid password"
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Account not found"
        ]);
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>
