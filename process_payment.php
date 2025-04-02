<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$game_id = $_POST['game_id'];
$upi_id = $_POST['upi_id'];

// Fetch game details
$stmt = $conn->prepare("SELECT price, name FROM games WHERE id = ?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$game = $stmt->get_result()->fetch_assoc();

// Generate unique transaction ID
$transaction_id = 'TXN_' . time() . '_' . rand(1000, 9999);

// Insert pending transaction
$stmt = $conn->prepare("INSERT INTO user_purchases (user_id, game_id, amount, transaction_id, upi_id) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iidss", $_SESSION['user_id'], $game_id, $game['price'], $transaction_id, $upi_id);
$stmt->execute();

// Generate UPI payment link
$upi_link = "upi://pay?pa=" . $upi_id . 
            "&pn=GameVerse" . 
            "&tn=Game:" . urlencode($game['name']) .
            "&am=" . $game['price'] .
            "&tr=" . $transaction_id;

echo json_encode([
    'success' => true,
    'upi_link' => $upi_link,
    'transaction_id' => $transaction_id
]);
?>