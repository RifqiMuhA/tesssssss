<?php
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

// Get current page for active nav link
$current_page = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), ".php");
?>

<!-- Navigation -->
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="index.php" style="display: flex; align-items: center; gap: 0.5rem;">
                <img src="assets/img/logo.png" alt="DrillPTN Logo" style="height: 32px; width: auto;">
                <h1 style="margin: 0;">DrillPTN</h1>
            </a>
        </div>

        <div class="nav-menu">
            <div class="nav-links">
                <a href="drilling.php" class="nav-link <?php echo ($current_page == 'drilling' || $current_page == 'start-drilling' || $current_page == 'result') ? 'active' : ''; ?>">Drilling Soal</a>
                <a href="forum.php" class="nav-link <?php echo ($current_page == 'forum' || $current_page == 'thread' || $current_page == 'new-thread') ? 'active' : ''; ?>">Forum</a>
                <a href="leaderboard.php" class="nav-link <?php echo ($current_page == 'leaderboard') ? 'active' : ''; ?>">Leaderboard</a>
                <a href="games.php" class="nav-link <?php echo ($current_page == 'games') ? 'active' : ''; ?>">Games</a>

            </div>

            <!-- Profile Dropdown -->
            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-trigger" onclick="toggleDropdown()">
                    <div class="profile-avatar">
                        <?php
                        $avatar = $user['avatar'] ?? null;
                        if ($avatar && file_exists($avatar)) {
                            echo '<img src="' . htmlspecialchars($avatar) . '" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">';
                        } else {
                            echo strtoupper(substr($user['full_name'], 0, 2));
                        }
                        ?>
                    </div>
                    <div class="profile-info">
                        <div class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        <div class="profile-role"><?php echo isAdmin() ? 'Admin' : 'Siswa'; ?></div>
                    </div>
                    <svg class="dropdown-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6,9 12,15 18,9"></polyline>
                    </svg>
                </div>

                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Profile Saya
                    </a>
                    <?php if (isAdmin()): ?>
                        <a href="admin/" class="dropdown-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="9" y1="9" x2="15" y2="9"></line>
                                <line x1="9" y1="12" x2="15" y2="12"></line>
                                <line x1="9" y1="15" x2="15" y2="15"></line>
                            </svg>
                            Go to Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="dropdown-item logout">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16,17 21,12 16,7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Logout
                    </a>
                </div>
            </div>

            <!-- Mobile Menu Toggle -->
            <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Navigation Overlay -->
<div class="mobile-nav-overlay" id="mobileNavOverlay" onclick="closeMobileMenu()"></div>

<!-- Mobile Navigation Menu -->
<div class="mobile-nav-menu" id="mobileNavMenu">
    <div class="mobile-nav-header">
        <h3 style="color: #2196F3; font-weight: 700;">DrillPTN</h3>
        <button class="mobile-close-btn" onclick="closeMobileMenu()">×</button>
    </div>

    <div class="mobile-nav-links">
        <a href="drilling.php" class="nav-link <?php echo ($current_page == 'drilling') ? 'active' : ''; ?>">Drilling Soal</a>
        <a href="forum.php" class="nav-link <?php echo ($current_page == 'forum' || $current_page == 'thread') ? 'active' : ''; ?>">Forum</a>
        <a href="leaderboard.php" class="nav-link <?php echo ($current_page == 'leaderboard') ? 'active' : ''; ?>">Leaderboard</a>
        <a href="Games.php" class="nav-link <?php echo ($current_page == 'games') ? 'active' : ''; ?>">Games</a>
    </div>

    <div style="border-top: 1px solid #e2e8f0; padding-top: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: #f8fafc; border-radius: 8px; margin-bottom: 1rem;">
            <div class="profile-avatar" style="width: 32px; height: 32px; font-size: 0.75rem;">
                <?php
                if ($avatar && file_exists("assets/img/avatars/" . $avatar)) {
                    echo '<img src="assets/img/avatars/' . htmlspecialchars($avatar) . '" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">';
                } else {
                    echo strtoupper(substr($user['full_name'], 0, 2));
                }
                ?>
            </div>
            <div>
                <div style="font-weight: 600; font-size: 0.875rem; color: #1e293b;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div style="font-size: 0.75rem; color: #64748b;"><?php echo isAdmin() ? 'Admin' : 'Siswa'; ?></div>
            </div>
        </div>
        <a href="logout.php" class="nav-link" style="color: #dc2626;">Logout</a>
    </div>
</div>