<?php
session_start();
include 'db.php';

if (isset($_SESSION['user_id']) && isset($_POST['game_id']) && isset($_POST['duration'])) {
    $user_id = $_SESSION['user_id'];
    $game_id = intval($_POST['game_id']);
    $duration = intval($_POST['duration']);
    
    $stmt = $conn->prepare("
        UPDATE user_game_history 
        SET play_duration = play_duration + ?
        WHERE user_id = ? AND game_id = ?
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->bind_param("iii", $duration, $user_id, $game_id);
    $stmt->execute();
}