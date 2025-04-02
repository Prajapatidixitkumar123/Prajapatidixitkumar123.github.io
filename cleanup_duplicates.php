<?php
include 'db.php';

function cleanupDuplicateGames($conn) {
    // Keep only one copy of each game (the one with the lowest ID)
    $sql = "DELETE g1 FROM games g1
            INNER JOIN games g2
            WHERE g1.name = g2.name
            AND g1.id > g2.id";
    
    try {
        $conn->query($sql);
        echo "Duplicate games cleaned up successfully!";
    } catch (Exception $e) {
        echo "Error cleaning up duplicates: " . $e->getMessage();
    }
}

// Run this function once to clean up existing duplicates
cleanupDuplicateGames($conn);
?>
