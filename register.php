<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prevent any output before headers
ob_start();

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Content-Type: application/json; charset=UTF-8');

// Function to send JSON response
function sendJsonResponse($success, $message, $statusCode = 200) {
    http_response_code($statusCode);
    $response = json_encode([
        'success' => $success,
        'message' => $message
    ]);
    
    if ($response === false) {
        // Log JSON encoding error
        error_log("JSON encode error: " . json_last_error_msg());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error'
        ]);
        exit;
    }
    
    echo $response;
    exit;
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Method not allowed. Please use POST request.', 405);
}

try {
    // Log incoming request data
    error_log("Received registration request: " . print_r($_POST, true));

    // Include database connection
    require_once 'db.php';
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Validate required fields
    $required_fields = ['username', 'email', 'phone', 'password'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("$field is required");
        }
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
        $check_stmt = $conn->prepare("SELECT username, email, phone FROM users WHERE username = ? OR email = ? OR phone = ? LIMIT 1");
        if (!$check_stmt) {
            throw new Exception($conn->error);
        }

        $check_stmt->bind_param("sss", $username, $email, $phone);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $existing_user = $result->fetch_assoc();
            if ($existing_user['email'] === $email) {
                throw new Exception('This email is already registered');
            } elseif ($existing_user['phone'] === $phone) {
                throw new Exception('This phone number is already registered');
            } else {
                throw new Exception('This username is already taken');
            }
        }

        // Insert new user
        $insert_stmt = $conn->prepare("INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)");
        if (!$insert_stmt) {
            throw new Exception($conn->error);
        }

        $insert_stmt->bind_param("ssss", $username, $email, $phone, $password);
        
        if (!$insert_stmt->execute()) {
            throw new Exception($insert_stmt->error);
        }

        // Commit transaction
        $conn->commit();
        
        // Log successful registration
        error_log("User successfully registered: $username");
        
        sendJsonResponse(true, 'Registration successful! Please log in.');

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Registration error: ' . $e->getMessage());
    sendJsonResponse(false, $e->getMessage(), 400);
}

// Close connection
if (isset($conn)) {
    $conn->close();
}
?>
