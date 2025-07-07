<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if this is an AJAX request
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Get filter parameters
$grade_filter = isset($_GET['grade']) ? sanitize($_GET['grade']) : '';
$sort_by = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'points';

// Build WHERE clause
$where_conditions = ["u.role_id = 1", "u.is_active = 1"];
$params = [];

if ($grade_filter && in_array($grade_filter, ['10', '11', '12'])) {
    $where_conditions[] = "u.grade = ?";
    $params[] = $grade_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Build ORDER BY clause
$order_clause = "ORDER BY ";
switch ($sort_by) {
    case 'questions':
        $order_clause .= "u.total_questions_answered DESC, u.points DESC";
        break;
    case 'accuracy':
        $order_clause .= "accuracy DESC, u.points DESC";
        break;
    default: // points
        $order_clause .= "u.points DESC, u.total_questions_answered DESC";
        break;
}

// Get leaderboard data
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.school_name, u.grade, u.points, u.total_questions_answered,
           CASE 
               WHEN u.total_questions_answered > 0 
               THEN ROUND(
                       (SELECT COUNT(*) 
                        FROM user_answers ua 
                        WHERE ua.user_id = u.id AND ua.is_correct = 1
                       ) * 100.0 / u.total_questions_answered, 1)
               ELSE 0 
           END as accuracy
    FROM users u
    $where_clause
    $order_clause
    LIMIT 50
");
$stmt->execute($params);
$leaderboard = $stmt->fetchAll();

// Get current user's rank
$current_user_rank = null;
foreach ($leaderboard as $index => $user) {
    if ($user['id'] == $_SESSION['user_id']) {
        $current_user_rank = $index + 1;
        break;
    }
}

// If current user not in top 50, get their rank separately
if (!$current_user_rank) {
    $rank_params = [];
    $rank_where_conditions = ["u2.role_id = 1", "u2.is_active = 1"];

    if ($grade_filter && in_array($grade_filter, ['10', '11', '12'])) {
        $rank_where_conditions[] = "u2.grade = ?";
        $rank_params[] = $grade_filter;
    }

    $rank_where_clause = "WHERE " . implode(" AND ", $rank_where_conditions);

    $stmt = $pdo->prepare("
        SELECT COUNT(*) + 1 as user_rank
        FROM users u2
        $rank_where_clause AND u2.points > (
            SELECT points FROM users WHERE id = ?
        )
    ");
    $rank_params[] = $_SESSION['user_id'];
    $stmt->execute($rank_params);
    $result = $stmt->fetch();
    $current_user_rank = $result ? $result['user_rank'] : 'N/A';
}

// Get current user's data
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.school_name, u.grade, u.points, u.total_questions_answered,
           CASE 
               WHEN u.total_questions_answered > 0 
               THEN ROUND(
                       (SELECT COUNT(*) 
                        FROM user_answers ua 
                        WHERE ua.user_id = u.id 
                          AND ua.is_correct = 1
                          AND ua.question_id IN (
                              SELECT question_id 
                              FROM user_answers 
                              WHERE user_id = u.id
                          )
                       ) * 100.0 / u.total_questions_answered, 1)
               ELSE 0 
           END as accuracy
    FROM users u WHERE u.id = ?
");

$stmt->execute([$_SESSION['user_id']]);
$user_data = $stmt->fetch();
$badge = getUserBadge($user_data['points']);

if ($isAjax) {
    ob_start();
    include_once 'ajax/leaderboard_data.php';
    $content = ob_get_clean();
    echo $content;
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/pages/leaderboard.css">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <h1>Leaderboard</h1>
            <p>Ranking siswa berdasarkan poin dan performa drilling</p>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <div class="filter-form" id="filterForm">
                <div class="filter-group">
                    <label for="grade">Filter Kelas</label>
                    <select name="grade" id="grade" class="filter-select">
                        <option value="">Semua Kelas</option>
                        <option value="10" <?php echo ($grade_filter == '10') ? 'selected' : ''; ?>>Kelas 10</option>
                        <option value="11" <?php echo ($grade_filter == '11') ? 'selected' : ''; ?>>Kelas 11</option>
                        <option value="12" <?php echo ($grade_filter == '12') ? 'selected' : ''; ?>>Kelas 12</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="sort">Urutkan berdasarkan</label>
                    <select name="sort" id="sort" class="filter-select">
                        <option value="points" <?php echo ($sort_by == 'points') ? 'selected' : ''; ?>>Poin Tertinggi</option>
                        <option value="questions" <?php echo ($sort_by == 'questions') ? 'selected' : ''; ?>>Soal Terbanyak</option>
                        <option value="accuracy" <?php echo ($sort_by == 'accuracy') ? 'selected' : ''; ?>>Akurasi Tertinggi</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Loading indicator -->
        <div id="loadingIndicator" class="loading-indicator" style="display: none;">
            <div class="loading-spinner"></div>
        </div>

        <!-- Content Container -->
        <div id="leaderboardContent">
            <!-- Current User Rank -->
            <div class="current-rank">
                <div class="rank-card">
                    <div class="rank-content">
                        <h2 class="rank-title">Peringkat Kamu</h2>
                        <div class="rank-info">
                            <div class="rank-number">#<?php echo $current_user_rank; ?></div>
                            <div class="rank-details">
                                <h3><?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>
                                <div class="rank-stats">
                                    <div class="rank-stat">
                                        <span class="rank-stat-value"><?php echo number_format($user_data['points']); ?></span>
                                        <span class="rank-stat-label">Poin</span>
                                    </div>
                                    <div class="rank-stat">
                                        <span class="rank-stat-value"><?php echo number_format($user_data['total_questions_answered']); ?></span>
                                        <span class="rank-stat-label">Soal</span>
                                    </div>
                                    <div class="rank-stat">
                                        <span class="rank-stat-value"><?php echo $user_data['accuracy']; ?>%</span>
                                        <span class="rank-stat-label">Akurasi</span>
                                    </div>
                                    <div class="rank-stat">
                                        <span class="rank-stat-value" style="background-color: <?php echo $badge['color']; ?>; padding: 0.25rem 0.5rem; border-radius: 0.375rem; color: white; font-size: 0.75rem;">
                                            <?php echo $badge['name']; ?>
                                        </span>
                                        <span class="rank-stat-label">Badge</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leaderboard -->
            <div class="leaderboard-container">
                <div class="table-header">
                    <h2>Pengguna Jagoan</h2>
                </div>

                <?php if (count($leaderboard) > 0): ?>
                    <div class="leaderboard-list">
                        <?php foreach ($leaderboard as $index => $user): ?>
                            <?php
                            $rank = $index + 1;
                            $badge = getUserBadge($user['points']);
                            $is_current_user = ($user['id'] == $_SESSION['user_id']);
                            ?>
                            <div class="leaderboard-item <?php echo $is_current_user ? 'current-user' : ''; ?> <?php echo $rank <= 3 ? 'rank-' . $rank : ''; ?>" style="--index: <?php echo $index; ?>">
                                <!-- Rank -->
                                <div class="rank-display">
                                    <?php if ($rank <= 3): ?>
                                        <div class="medal">
                                            <?php if ($rank == 1): ?>🥇<?php elseif ($rank == 2): ?>🥈<?php else: ?>🥉<?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="rank-num"><?php echo $rank; ?></div>
                                </div>

                                <!-- User Info -->
                                <div class="user-info">
                                    <h4>
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                        <?php if ($is_current_user): ?>
                                            <span class="you-badge">Anda</span>
                                        <?php endif; ?>
                                    </h4>
                                    <?php if ($user['school_name']): ?>
                                        <div class="school"><?php echo htmlspecialchars($user['school_name']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($user['grade']): ?>
                                        <div class="grade">Kelas <?php echo $user['grade']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Points -->
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo number_format($user['points']); ?></span>
                                    <span class="stat-label-small">Poin</span>
                                </div>

                                <!-- Questions -->
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo number_format($user['total_questions_answered']); ?></span>
                                    <span class="stat-label-small">Soal</span>
                                </div>

                                <!-- Accuracy -->
                                <div class="accuracy-display">
                                    <div class="accuracy-percentage"><?php echo $user['accuracy']; ?>%</div>
                                    <div class="accuracy-bar">
                                        <div class="accuracy-fill" style="width: <?php echo $user['accuracy']; ?>%"></div>
                                    </div>
                                </div>

                                <!-- Badge -->
                                <div class="badge" style="background-color: <?php echo $badge['color']; ?>">
                                    <?php echo $badge['name']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📊</div>
                        <h3>Belum ada data leaderboard</h3>
                        <p>Mulai drilling soal untuk masuk ke leaderboard</p>
                        <a href="drilling.php" class="btn btn-primary">Mulai Drilling</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/global.js"></script>
    <script src="assets/js/navbar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const gradeFilter = document.getElementById('grade');
            const sortFilter = document.getElementById('sort');
            const loadingIndicator = document.getElementById('loadingIndicator');
            const leaderboardContent = document.getElementById('leaderboardContent');

            // Filter change handlers - auto trigger on change
            [gradeFilter, sortFilter].forEach(filter => {
                filter.addEventListener('change', function() {
                    loadLeaderboard();
                });
            });

            function loadLeaderboard() {
                const params = new URLSearchParams({
                    ajax: '1',
                    grade: gradeFilter.value,
                    sort: sortFilter.value
                });

                // Show loading
                loadingIndicator.style.display = 'flex';
                leaderboardContent.style.opacity = '0.5';

                fetch(`leaderboard.php?${params.toString()}`)
                    .then(response => response.text())
                    .then(html => {
                        leaderboardContent.innerHTML = html;

                        // Hide loading
                        loadingIndicator.style.display = 'none';
                        leaderboardContent.style.opacity = '1';

                        // Re-initialize animations
                        initializeAnimations();

                        // Smooth scroll to top
                        leaderboardContent.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    })
                    .catch(error => {
                        console.error('Error loading leaderboard:', error);
                        loadingIndicator.style.display = 'none';
                        leaderboardContent.style.opacity = '1';
                    });
            }

            function initializeAnimations() {
                // Animate accuracy bars
                document.querySelectorAll('.accuracy-fill').forEach((bar, index) => {
                    setTimeout(() => {
                        const width = bar.style.width;
                        bar.style.width = '0%';
                        setTimeout(() => {
                            bar.style.width = width;
                        }, 50);
                    }, index * 50);
                });

                // Animate stats on scroll
                const observerOptions = {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                };

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }
                    });
                }, observerOptions);

                // Observe leaderboard items
                document.querySelectorAll('.leaderboard-item').forEach((item, index) => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(20px)';
                    item.style.transition = `all 0.5s ease ${index * 0.05}s`;
                    observer.observe(item);
                });
            }

            // Initial animation setup
            initializeAnimations();
        });
    </script>
</body>

</html>