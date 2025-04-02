<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

include 'db.php';

// Function to validate and sanitize image paths
function getValidImagePath($imagePath) {
    // Default image path
    $defaultImage = 'uploads/default_cover.jpg';
    
    // If image path is empty, return default
    if (empty($imagePath)) {
        return $defaultImage;
    }
    
    // Clean the path and ensure it starts with uploads/
    $cleanPath = 'uploads/' . basename($imagePath);
    
    // Check if file exists
    if (file_exists($cleanPath)) {
        return $cleanPath;
    }
    
    // If file doesn't exist, log error and return default
    error_log("Image not found: " . $cleanPath);
    return $defaultImage;
}

// Function to get previous feedback for a user and game
function getPreviousFeedback($conn, $game_id, $user_id) {
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
    return $result->fetch_assoc();
}

// Initialize previous_feedback variable
$previous_feedback = null;

// Fetch games with error handling
try {
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
    $sortColumn = 'name';
    $sortOrder = 'ASC';

    if ($sort === 'name_desc') {
        $sortOrder = 'DESC';
    } elseif ($sort === 'genre_asc') {
        $sortColumn = 'genre';
    } elseif ($sort === 'genre_desc') {
        $sortColumn = 'genre';
        $sortOrder = 'DESC';
    }

    // Modified query to ensure we're getting all necessary fields
    $query = "SELECT id, name, genre, cover_image FROM games ORDER BY $sortColumn $sortOrder";
    $games = $conn->query($query);
    
    if (!$games) {
        throw new Exception("Error loading games: " . $conn->error);
    }

    // Debug logging
    error_log("Games query executed: " . $query);
    error_log("Number of games found: " . $games->num_rows);

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while loading games. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameVerse | Ultimate Gaming Experience</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #4F46E5;
            --primary-dark: #4338CA;
            --secondary-color: #10B981;
            --accent-color: #F43F5E;
            --background: #0F172A;
            --card-bg: #1E293B;
            --text-primary: #F8FAFC;
            --text-secondary: #94A3B8;
            --border-color: rgba(148, 163, 184, 0.1);
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --hover-transform: translateY(-5px);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background: var(--background);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }

        .navbar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            position: fixed;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .nav-content {
            max-width: 1440px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            color: transparent;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-btn {
            background: transparent;
            color: var(--text-primary);
            padding: 0.75rem 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .nav-btn.primary {
            background: var(--primary-color);
            border: none;
        }

        .nav-btn.primary:hover {
            background: var(--primary-dark);
        }

        .main-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 7rem 2rem 2rem;
        }

        .search-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .search-input {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem 1.5rem;
            color: var(--text-primary);
            font-size: 1rem;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .sort-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem 1.5rem;
            color: var(--text-primary);
            min-width: 200px;
            cursor: pointer;
            transition: var(--transition);
        }

        .sort-select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            padding: 1rem;
        }

        .game-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: var(--transition);
            position: relative;
        }

        .game-card:hover {
            transform: var(--hover-transform);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .game-banner {
            position: relative;
            padding-top: 56.25%;
            overflow: hidden;
        }

        .game-banner img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .game-card:hover .game-banner img {
            transform: scale(1.05);
        }

        .game-content {
            padding: 1.5rem;
        }

        .game-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }

        .game-tags {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .tag {
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .game-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.2rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .play-button {
            background: var(--secondary-color);
            color: white;
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .play-button:hover {
            background: #0EA5E9;
            transform: translateY(-2px);
        }

        .feedback-btn {
            background: transparent;
            color: var(--primary-color);
            padding: 0.75rem 1rem;
            border: 1px solid var(--primary-color);
            border-radius: 8px;
            cursor: pointer;
            margin-top: 1rem;
            transition: var(--transition);
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .feedback-btn:hover {
            background: rgba(79, 70, 229, 0.1);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
        }

        .modal-content {
            background: var(--bg-gradient);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            position: relative;
            margin: 50px auto;
            backdrop-filter: blur(20px);
        }

        .close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
        }

        .rating-container {
            margin-bottom: 1.5rem;
        }

        .stars {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .stars input {
            display: none;
        }

        .stars label {
            cursor: pointer;
            padding: 0.2rem;
            color: var(--text-secondary);
        }

        .stars label:hover,
        .stars label:hover ~ label,
        .stars input:checked ~ label {
            color: #FFD700;
        }

        .comment-container textarea {
            width: 100%;
            height: 150px;
            padding: 1rem;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text);
            margin-bottom: 1rem;
        }

        .submit-feedback {
            background: var(--accent);
            color: white;
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-feedback:hover {
            background: var(--accent-hover);
        }

        @media (max-width: 768px) {
            .search-bar {
                flex-direction: column;
            }

            .games-grid {
                grid-template-columns: 1fr;
                padding: 0.5rem;
            }

            .nav-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-buttons {
                width: 100%;
                justify-content: center;
            }
        }

        .animate-in {
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
            opacity: 0;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .previous-feedback {
            margin: 20px 0;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid var(--card-border);
        }

        .previous-feedback h3 {
            color: var(--text);
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .feedback-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .rating {
            display: flex;
            gap: 5px;
        }

        .rating .fa-star.active {
            color: #FFD700;
        }

        .date {
            color: var(--text-secondary);
            font-size: 0.9em;
        }

        .feedback-content {
            margin-top: 10px;
        }

        .comment {
            color: var(--text);
            margin-bottom: 15px;
        }

        .admin-response {
            margin-top: 15px;
            padding: 15px;
            background: rgba(var(--accent-rgb), 0.1);
            border-left: 4px solid var(--accent);
            border-radius: 4px;
        }

        .admin-response h4 {
            color: var(--accent);
            margin: 0 0 10px 0;
            font-size: 1em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-response p {
            color: var(--text);
            margin: 0;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <a href="game.php" class="logo">
                <i class="fas fa-gamepad"></i>
                GameVerse
            </a>
            <div class="nav-buttons">
                <a href="profile.php" class="nav-btn primary" style="background: var(--primary-color);">
                    <i class="fas fa-user"></i>
                    Profile
                </a>
                <a href="logout.php" class="nav-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <main class="main-container">
        <div class="search-bar">
            <input type="text" class="search-input" placeholder="Search your favorite games..." oninput="filterGames(this.value)">
            <select class="sort-select" onchange="sortGames(this.value)">
                <option value="name_asc">Name (A-Z)</option>
                <option value="name_desc">Name (Z-A)</option>
                <option value="genre_asc">Genre (A-Z)</option>
                <option value="genre_desc">Genre (Z-A)</option>
            </select>
        </div>

        <div class="games-grid">
            <?php if ($games && $games->num_rows > 0): ?>
                <?php while ($row = $games->fetch_assoc()): ?>
                    <?php $imagePath = getValidImagePath($row['cover_image']); ?>
                    <div class="game-card animate-in" data-name="<?php echo strtolower(htmlspecialchars($row['name'])); ?>" 
                         data-genre="<?php echo strtolower(htmlspecialchars($row['genre'])); ?>">
                        <div class="game-banner">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                 alt="<?php echo htmlspecialchars($row['name']); ?>"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='uploads/default_cover.jpg';">
                        </div>
                        <div class="game-content">
                            <h3 class="game-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <div class="game-tags">
                                <span class="tag">
                                    <i class="fas fa-gamepad"></i>
                                    <?php echo htmlspecialchars($row['genre']); ?>
                                </span>
                                <span class="tag">Multiplayer</span>
                                <span class="tag">HD</span>
                            </div>
                            <div class="game-stats">
                                <div class="stat">
                                    <div class="stat-value">4.8</div>
                                    <div class="stat-label">Rating</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value">2.5K</div>
                                    <div class="stat-label">Players</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value">New</div>
                                    <div class="stat-label">Status</div>
                                </div>
                            </div>
                            <a href="play_game.php?id=<?php echo $row['id']; ?>" class="play-button">
                                <i class="fas fa-play"></i>
                                Play Now
                            </a>
                            <div class="feedback-section">
                                <button class="feedback-btn" onclick="openFeedback(<?php echo $row['id']; ?>)">
                                    <i class="fas fa-comment"></i> Give Feedback
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-games">No games found. Check back later!</p>
            <?php endif; ?>
        </div>
    </main>

    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><?php echo (isset($previous_feedback) ? 'Update' : 'Game') . ' Feedback'; ?></h2>
            
            <?php if (isset($previous_feedback)): ?>
                <div class="previous-feedback">
                    <h3>Your Previous Feedback</h3>
                    <div class="feedback-card">
                        <div class="feedback-header">
                            <div class="rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $previous_feedback['rating'] ? 'active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="date"><?php echo $previous_feedback['formatted_date']; ?></span>
                        </div>
                        <div class="feedback-content">
                            <p class="comment"><?php echo htmlspecialchars($previous_feedback['comment']); ?></p>
                            
                            <?php if (!empty($previous_feedback['admin_response'])): ?>
                                <div class="admin-response">
                                    <h4><i class="fas fa-reply"></i> Admin Response:</h4>
                                    <p><?php echo htmlspecialchars($previous_feedback['admin_response']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form id="feedbackForm" action="submit_feedback.php" method="POST">
                <input type="hidden" name="game_id" id="feedback_game_id">
                <div class="rating-container">
                    <p>Rating:</p>
                    <div class="stars">
                        <?php for($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>"
                                <?php echo (isset($previous_feedback) && $previous_feedback['rating'] == $i) ? 'checked' : ''; ?>>
                            <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="comment-container">
                    <textarea name="comment" placeholder="Share your thoughts about this game..." required><?php 
                        echo (isset($previous_feedback) ? htmlspecialchars($previous_feedback['comment']) : ''); 
                    ?></textarea>
                </div>
                <button type="submit" class="submit-feedback">
                    <?php echo isset($previous_feedback) ? 'Update Feedback' : 'Submit Feedback'; ?>
                </button>
            </form>
        </div>
    </div>

    <script>
        let debounceTimer;
        function filterGames(searchTerm) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const games = document.querySelectorAll('.game-card');
                searchTerm = searchTerm.toLowerCase().trim();
                games.forEach(game => {
                    const gameName = game.dataset.name;
                    const gameGenre = game.dataset.genre;
                    const shouldShow = gameName.includes(searchTerm) || gameGenre.includes(searchTerm);
                    game.style.display = shouldShow ? 'block' : 'none';
                });
            }, 300);
        }

        function sortGames(sortOption) {
            window.location.href = `?sort=${sortOption}`;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const games = document.querySelectorAll('.game-card');
            games.forEach((game, index) => {
                game.style.animationDelay = `${index * 0.1}s`;
            });
        });

        function openFeedback(gameId) {
            document.getElementById('feedback_game_id').value = gameId;
            
            // Make an AJAX call to get previous feedback
            fetch(`get_previous_feedback.php?game_id=${gameId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.feedback) {
                        // Update the modal with previous feedback
                        updateModalWithPreviousFeedback(data.feedback);
                    }
                    document.getElementById('feedbackModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('feedbackModal').style.display = 'block';
                });
        }

        function updateModalWithPreviousFeedback(feedback) {
            // Update rating
            if (feedback.rating) {
                document.querySelector(`input[name="rating"][value="${feedback.rating}"]`).checked = true;
            }
            
            // Update comment
            if (feedback.comment) {
                document.querySelector('textarea[name="comment"]').value = feedback.comment;
            }
            
            // Show previous feedback section
            const previousFeedbackHtml = `
                <div class="previous-feedback">
                    <h3>Your Previous Feedback</h3>
                    <div class="feedback-card">
                        <div class="feedback-header">
                            <div class="rating">
                                ${[1,2,3,4,5].map(i => `
                                    <i class="fas fa-star ${i <= feedback.rating ? 'active' : ''}"></i>
                                `).join('')}
                            </div>
                            <span class="date">${feedback.formatted_date}</span>
                        </div>
                        <div class="feedback-content">
                            <p class="comment">${feedback.comment}</p>
                            ${feedback.admin_response ? `
                                <div class="admin-response">
                                    <h4><i class="fas fa-reply"></i> Admin Response:</h4>
                                    <p>${feedback.admin_response}</p>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            // Insert the previous feedback HTML before the form
            const form = document.getElementById('feedbackForm');
            form.insertAdjacentHTML('beforebegin', previousFeedbackHtml);
        }

        document.querySelector('.close').onclick = function() {
            document.getElementById('feedbackModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('feedbackModal')) {
                document.getElementById('feedbackModal').style.display = 'none';
            }
        }

        document.getElementById('feedbackForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Check if rating is selected
            const rating = document.querySelector('input[name="rating"]:checked');
            if (!rating) {
                alert('Please select a rating');
                return;
            }
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('submit_feedback.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('feedbackModal').style.display = 'none';
                    alert('Thank you for your feedback!');
                    // Optional: reload the page to show updated feedback
                    location.reload();
                } else {
                    alert(result.error || 'Failed to submit feedback');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to submit feedback');
            }
        });
    </script>
</body>
</html>