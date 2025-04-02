<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'User not logged in']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if rating is set
    if (!isset($_POST['rating'])) {
        die(json_encode(['error' => 'Please select a rating']));
    }
    
    $game_id = intval($_POST['game_id']);
    $user_id = intval($_SESSION['user_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    // Validate input
    if ($rating < 1 || $rating > 5) {
        die(json_encode(['error' => 'Invalid rating']));
    }

    // Check if user already submitted feedback for this game
    $check_stmt = $conn->prepare("SELECT id FROM game_feedback WHERE game_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $game_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing feedback
        $stmt = $conn->prepare("UPDATE game_feedback SET rating = ?, comment = ?, status = 'pending' WHERE game_id = ? AND user_id = ?");
        $stmt->bind_param("isii", $rating, $comment, $game_id, $user_id);
    } else {
        // Insert new feedback
        $stmt = $conn->prepare("INSERT INTO game_feedback (game_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $game_id, $user_id, $rating, $comment);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to submit feedback']);
    }

    $stmt->close();
}
