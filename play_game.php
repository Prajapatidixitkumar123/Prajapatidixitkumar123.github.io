<?php
// Add this at the top of play_game.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Include database connection (adjusted path)
include 'db.php'; // Use this if db.php is in C:\xampp\htdocs\gaming\

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $game_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id']; // Make sure you store user_id in session during login
    
    // Record game play
    $stmt = $conn->prepare("
        INSERT INTO user_game_history (user_id, game_id)
        VALUES (?, ?)
    ");
    $stmt->bind_param("ii", $user_id, $game_id);
    $stmt->execute();
    
    // Update play duration when game ends (you'll need to add this via AJAX when user leaves the game)
    // Add this JavaScript to your game page:
    echo "
    <script>
        let startTime = Date.now();
        
        window.addEventListener('beforeunload', function() {
            let duration = Math.floor((Date.now() - startTime) / 1000);
            fetch('update_play_time.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'game_id=' + $game_id + '&duration=' + duration
            });
        });
    </script>
    ";
}

$game_id = intval($_GET['id']);
$game_stmt = $conn->prepare("SELECT name, genre, cover_image FROM games WHERE id = ?");
$game_stmt->bind_param("i", $game_id);
$game_stmt->execute();
$game = $game_stmt->get_result()->fetch_assoc();
$game_stmt->close();

$code_stmt = $conn->prepare("SELECT language, code FROM game_codes WHERE game_id = ?");
$code_stmt->bind_param("i", $game_id);
$code_stmt->execute();
$codes_result = $code_stmt->get_result();
$codes = [];
while ($row = $codes_result->fetch_assoc()) {
    $codes[$row['language']] = $row['code'];
}
$code_stmt->close();

// Add this after fetching game codes
error_log("Game ID: " . $game_id);
error_log("Found codes: " . print_r($codes, true));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($game['name']) ?> | Gaming Hub</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #1f2937;
            color: #f3f4f6;
            margin: 0;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }
        .game-container {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 800px;
        }
        h1 {
            font-size: 2rem;
            color: #1e90ff;
            margin-bottom: 1rem;
        }
        .back-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #1e90ff;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="game-container">
        <h1><?= htmlspecialchars($game['name']) ?></h1>
        <?php if (isset($codes['html'])): ?>
            <?= $codes['html'] ?>
        <?php endif; ?>
    </div>
    <a href="game.php" class="back-btn">Back to Games</a>

    <?php if (isset($codes['css'])): ?>
    <style>
        <?= $codes['css'] ?>
    </style>
    <?php endif; ?>

    <?php if (isset($codes['javascript'])): ?>
    <script>
        <?= $codes['javascript'] ?>
    </script>
    <?php endif; ?>
</body>
</html>
