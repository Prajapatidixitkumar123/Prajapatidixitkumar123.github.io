<?php
session_start();
require_once 'google_config.php';
require_once 'db.php';

if (isset($_GET['code'])) {
    $token = $google_client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (!isset($token['error'])) {
        $google_client->setAccessToken($token['access_token']);
        
        // Get user data
        $google_service = new Google_Service_Oauth2($google_client);
        $user_data = $google_service->userinfo->get();
        
        // Extract user details
        $email = $user_data->email;
        $name = $user_data->name;
        $google_id = $user_data->id;
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? OR google_id = ?");
        $stmt->bind_param("ss", $email, $google_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // User exists - log them in
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $email;
            
            // Update Google ID if not set
            if (empty($user['google_id'])) {
                $update = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $update->bind_param("si", $google_id, $user['id']);
                $update->execute();
            }
        } else {
            // New user - register them
            $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999);
            $random_password = bin2hex(random_bytes(8));
            $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, google_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $google_id);
            
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
            }
        }
        
        header("Location: game.php");
        exit();
    }
}

header("Location: index.html?error=google_login_failed");
exit();
?>