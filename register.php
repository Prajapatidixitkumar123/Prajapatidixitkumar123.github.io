<?php
header('Content-Type: application/json');

try {
    include 'db.php';

    // Validate input
    if (!isset($_POST['username']) || !isset($_POST['email']) || !isset($_POST['phone']) || !isset($_POST['password'])) {
        throw new Exception('Missing required fields');
    }

    // Sanitize inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check for existing user
        $check_sql = "SELECT username, email, phone FROM users WHERE username = ? OR email = ? OR phone = ? LIMIT 1";
        $check_stmt = $conn->prepare($check_sql);
        
        if (!$check_stmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }

        $check_stmt->bind_param("sss", $username, $email, $phone);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $existing_user = $result->fetch_assoc();
            if ($existing_user['email'] == $email) {
                throw new Exception('This email is already registered');
            } elseif ($existing_user['phone'] == $phone) {
                throw new Exception('This phone number is already registered');
            } else {
                throw new Exception('This username is already taken');
            }
        }

        // Insert new user
        $insert_sql = "INSERT INTO users (username, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        
        if (!$insert_stmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }

        $insert_stmt->bind_param("ssss", $username, $email, $phone, $password);
        
        if (!$insert_stmt->execute()) {
            throw new Exception('Failed to create account: ' . $insert_stmt->error);
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! Please log in.'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    // Log error
    error_log('Registration error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Close connection
if (isset($conn)) {
    $conn->close();
}

// Important: Exit after sending response
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - GameVerse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="register-container">
        <h2>Create Account</h2>
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
?>
