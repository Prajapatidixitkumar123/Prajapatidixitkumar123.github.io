<?php
session_start();
require_once 'config.php';

$transaction_id = $_POST['transaction_id'];

// In a real implementation, you would verify with UPI/payment gateway
// For demo, we'll just mark it as completed
$stmt = $conn->prepare("UPDATE user_purchases SET payment_status = 'completed' WHERE transaction_id = ?");
$stmt->bind_param("s", $transaction_id);
$stmt->execute();

echo json_encode(['success' => true]);
?>