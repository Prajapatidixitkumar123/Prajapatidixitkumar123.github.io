<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Fetch user details
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch user's gaming statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT game_id) as total_games_played,
        SUM(play_duration) as total_play_time,
        MAX(played_at) as last_played
    FROM user_game_history 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Fetch recent games played
$stmt = $conn->prepare("
    SELECT g.name, g.genre, g.cover_image, h.played_at, h.play_duration
    FROM user_game_history h
    JOIN games g ON h.game_id = g.id
    WHERE h.user_id = ?
    ORDER BY h.played_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$recent_games = $stmt->get_result();

// Fetch user's achievements
$stmt = $conn->prepare("
    SELECT a.title, a.description, a.icon, ua.achieved_at
    FROM user_achievements ua
    JOIN achievements a ON ua.achievement_id = a.id
    WHERE ua.user_id = ?
    ORDER BY ua.achieved_at DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$achievements = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameVerse | Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #6C63FF;
            --secondary-color: #4CAF50;
            --accent-color: #FF4D4D;
            --background: #0F172A;
            --card-bg: #1E293B;
            --text-primary: #F8FAFC;
            --text-secondary: #94A3B8;
            --border-color: rgba(148, 163, 184, 0.1);
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        body {
            background: var(--background);
            color: var(--text-primary);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
        }

        .profile-container {
            max-width: 1200px;
            margin: 80px auto 0;
            padding: 2rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .profile-email {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--card-shadow);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
        }

        .recent-games {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .game-history {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .game-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .game-card:hover {
            transform: translateY(-5px);
        }

        .game-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .game-details {
            padding: 1rem;
        }

        .game-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .game-meta {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .achievements {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
        }

        .achievement-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .achievement-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }

        .achievement-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .edit-profile-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .edit-profile-btn:hover {
            background: #5651cc;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h1>
                <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                <a href="edit_profile.php" class="edit-profile-btn">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
        </div>

        <div class="profile-stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_games_played'] ?? 0; ?></div>
                <div class="stat-label">Games Played</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo floor(($stats['total_play_time'] ?? 0) / 3600); ?></div>
                <div class="stat-label">Hours Played</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php 
                        $achievements_count = $achievements ? $achievements->num_rows : 0;
                        echo $achievements_count;
                    ?>
                </div>
                <div class="stat-label">Achievements</div>
            </div>
        </div>

        <div class="recent-games">
            <h2 class="section-title">
                <i class="fas fa-gamepad"></i>
                Recent Games
            </h2>
            <div class="game-history">
                <?php while ($game = $recent_games->fetch_assoc()): ?>
                    <div class="game-card">
                        <img src="<?php echo htmlspecialchars($game['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($game['name']); ?>"
                             class="game-image">
                        <div class="game-details">
                            <div class="game-title"><?php echo htmlspecialchars($game['name']); ?></div>
                            <div class="game-meta">
                                <div>Genre: <?php echo htmlspecialchars($game['genre']); ?></div>
                                <div>Last played: <?php echo date('M d, Y', strtotime($game['played_at'])); ?></div>
                                <div>Play time: <?php echo floor($game['play_duration'] / 3600); ?>h <?php echo floor(($game['play_duration'] % 3600) / 60); ?>m</div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="achievements">
            <h2 class="section-title">
                <i class="fas fa-trophy"></i>
                Achievements
            </h2>
            <div class="achievement-grid">
                <?php while ($achievement = $achievements->fetch_assoc()): ?>
                    <div class="achievement-card">
                        <div class="achievement-icon">
                            <i class="<?php echo htmlspecialchars($achievement['icon']); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($achievement['title']); ?></h3>
                        <p><?php echo htmlspecialchars($achievement['description']); ?></p>
                        <small>Achieved: <?php echo date('M d, Y', strtotime($achievement['achieved_at'])); ?></small>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>
</html>
