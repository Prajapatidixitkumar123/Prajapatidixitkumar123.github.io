<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'User not logged in']));
}

if (!isset($_GET['game_id'])) {
    die(json_encode(['error' => 'Game ID not provided']));
}

$game_id = intval($_GET['game_id']);
$user_id = intval($_SESSION['user_id']);

try {
    $stmt = $conn->prepare("
        SELECT 
            gf.*,
            DATE_FORMAT(gf.created_at, '%M %d, %Y') as formatted_date
        FROM game_feedback gf
        WHERE gf.game_id = ? AND gf.user_id = ?
        ORDER BY gf.created_at DESC
        LIMIT 1
    ");
    
    $stmt->bind_param("ii", $game_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $feedback = $result->fetch_assoc();
    
    if ($feedback) {
        echo json_encode([
            'success' => true,
            'feedback' => $feedback
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'feedback' => null
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch feedback'
    ]);
}

$conn->close();