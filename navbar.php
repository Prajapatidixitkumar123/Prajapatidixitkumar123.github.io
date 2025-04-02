<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar">
    <div class="nav-content">
        <a href="game.php" class="logo">
            <i class="fas fa-gamepad"></i>
            GameVerse
        </a>
        <div class="nav-buttons">
            <a href="profile.php" class="logout-btn" style="background: var(--primary-color);">
                <i class="fas fa-user"></i>
                Profile
            </a>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
</nav>

<style>
.navbar {
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--border-color);
    padding: 1rem 2rem;
    position: fixed;
    width: 100%;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    top: 0;
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
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
    -webkit-background-clip: text;
    color: transparent;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.nav-buttons {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.logout-btn {
    background: var(--accent-color);
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.logout-btn:hover {
    background: #ff6b6b;
    transform: translateY(-2px);
}
</style>