<?php
session_start();
include 'db.php';
include 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Enhanced error logging
function logError($message, $error = null) {
    $errorMsg = date('Y-m-d H:i:s') . " - " . $message;
    if ($error) {
        $errorMsg .= " - " . $error;
    }
    error_log($errorMsg);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    
    try {
        // Debug log
        error_log("POST request received: " . print_r($_POST, true));
        
        if (isset($_POST['verify_otp']) && isset($_POST['new_password'])) {
            // Verify OTP and update password
            if (!isset($_SESSION['reset_user_id'])) {
                throw new Exception("Reset session expired. Please start over.");
            }

            $user_id = $_SESSION['reset_user_id'];
            $otp = trim($_POST['verify_otp']);
            $new_password = $_POST['new_password'];

            // Validate password
            if (strlen($new_password) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }

            // Check OTP
            $check_otp = $conn->prepare("
                SELECT id FROM password_resets 
                WHERE user_id = ? 
                AND otp = ? 
                AND used = 0 
                AND expires_at > NOW()
                ORDER BY created_at DESC 
                LIMIT 1
            ");

            if (!$check_otp) {
                throw new Exception("Database error while checking OTP");
            }

            $check_otp->bind_param("is", $user_id, $otp);
            $check_otp->execute();
            $result = $check_otp->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Invalid or expired OTP");
            }

            $reset_id = $result->fetch_assoc()['id'];

            // Start transaction
            $conn->begin_transaction();

            try {
                // Mark OTP as used
                $update_otp = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
                $update_otp->bind_param("i", $reset_id);
                $update_otp->execute();

                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_pass->bind_param("si", $hashed_password, $user_id);
                $update_pass->execute();

                $conn->commit();

                // Clear session
                unset($_SESSION['reset_user_id']);

                echo json_encode([
                    "success" => true,
                    "message" => "Password has been reset successfully"
                ]);
            } catch (Exception $e) {
                $conn->rollback();
                throw new Exception("Failed to reset password: " . $e->getMessage());
            }

        } else {
            $identifier = trim($_POST['identifier']);
            
            // Debug log
            error_log("Processing identifier: " . $identifier);
            
            if (empty($identifier)) {
                throw new Exception("Email or phone number is required");
            }
            
            // Check if user exists
            $sql = "SELECT id, email, phone FROM users WHERE email = ? OR phone = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("ss", $identifier, $identifier);
            
            // Debug log
            error_log("Executing SQL query for user lookup");
            
            if (!$stmt->execute()) {
                throw new Exception("Database execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("No account found with this " . 
                    (filter_var($identifier, FILTER_VALIDATE_EMAIL) ? "email" : "phone number"));
            }
            
            $user = $result->fetch_assoc();
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Debug log
            error_log("Generated OTP: " . $otp . " for user ID: " . $user['id']);
            
            // Store OTP in database
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // First, invalidate any existing OTPs for this user
            $invalidate_sql = "UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0";
            $invalidate_stmt = $conn->prepare($invalidate_sql);
            if ($invalidate_stmt) {
                $invalidate_stmt->bind_param("i", $user['id']);
                $invalidate_stmt->execute();
                $invalidate_stmt->close();
            }
            
            // Insert new OTP
            $sql = "INSERT INTO password_resets (user_id, otp, expires_at) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("iss", $user['id'], $otp, $expiry);
            
            // Debug log
            error_log("Storing OTP in database");
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to store OTP: " . $stmt->error);
            }
            
            $_SESSION['reset_user_id'] = $user['id'];
            
            // Debug log
            error_log("Attempting to send OTP");
            
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                // Send email
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->SMTPDebug = 0;                      // Disable debug output
                    $mail->isSMTP();                           
                    $mail->Host       = 'smtp.gmail.com';      
                    $mail->SMTPAuth   = true;                  
                    $mail->Username   = 'prajapatidixit321@gmail.com'; // Replace with your Gmail
                    $mail->Password   = 'loqv dbpp cyxp hgyw';    // Replace with your App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    
                    // Recipients
                    $mail->setFrom('prajapatidixit321@gmail.com', 'DK Games');
                    $mail->addAddress($identifier);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset OTP - DK Games';
                    $mail->Body    = '
                        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                            <h2 style="color: #333;">Password Reset OTP</h2>
                            <p>Your One-Time Password (OTP) for password reset is:</p>
                            <h1 style="color: #007bff; font-size: 32px; letter-spacing: 5px; padding: 10px; background: #f8f9fa; text-align: center; margin: 20px 0;">'.$otp.'</h1>
                            <p>This OTP will expire in 15 minutes.</p>
                            <p style="color: #666; font-size: 12px;">If you did not request this password reset, please ignore this email.</p>
                        </div>
                    ';
                    $mail->AltBody = "Your OTP for password reset is: $otp";
                    
                    $mail->send();
                    error_log("Email sent successfully to: " . $identifier);
                    
                } catch (Exception $e) {
                    error_log("Email sending failed. Mailer Error: {$mail->ErrorInfo}");
                    throw new Exception("Failed to send OTP email. Please try again later.");
                }
            } else {
                throw new Exception("SMS sending not implemented yet");
            }
            
            echo json_encode([
                "success" => true,
                "message" => "OTP has been sent to your " . 
                    (filter_var($identifier, FILTER_VALIDATE_EMAIL) ? "email" : "phone")
            ]);
        }
    } catch (Exception $e) {
        error_log("Error in forgot_password.php: " . $e->getMessage());
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
    exit();
}
?>

