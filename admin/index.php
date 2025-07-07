<?php
require_once '../config.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Get dashboard statistics
$stats = [];

// User statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK) THEN 1 END) as new_users_week,
        COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_users
    FROM users WHERE role_id = 1
");
$stats['users'] = $stmt->fetch();

// Question statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_questions,
        COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_questions,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK) THEN 1 END) as new_questions_week
    FROM questions
");
$stats['questions'] = $stmt->fetch();

// Session statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_sessions,
        COUNT(CASE WHEN is_completed = 1 THEN 1 END) as completed_sessions,
        COUNT(CASE WHEN session_start >= DATE_SUB(NOW(), INTERVAL 1 WEEK) THEN 1 END) as sessions_week
    FROM user_sessions
");
$stats['sessions'] = $stmt->fetch();

// Forum statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT ft.id) as total_threads,
        COUNT(DISTINCT fp.id) as total_posts,
        COUNT(CASE WHEN ft.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK) THEN ft.id END) as new_threads_week
    FROM forum_threads ft
    LEFT JOIN forum_posts fp ON ft.id = fp.thread_id
");
$stats['forum'] = $stmt->fetch();

// Recent activities
$stmt = $pdo->query("
    SELECT 'user_registration' as type, u.full_name as title, u.created_at as created_at
    FROM users u WHERE u.role_id = 1 AND u.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
    UNION ALL
    SELECT 'thread_created' as type, ft.title as title, ft.created_at as created_at
    FROM forum_threads ft WHERE ft.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
    UNION ALL
    SELECT 'session_completed' as type, CONCAT(u.full_name, ' - ', qc.name) as title, us.session_end as created_at
    FROM user_sessions us
    JOIN users u ON us.user_id = u.id
    JOIN question_categories qc ON us.category_id = qc.id
    WHERE us.is_completed = 1 AND us.session_end >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
    ORDER BY created_at DESC
    LIMIT 10
");
$recent_activities = $stmt->fetchAll();

// Top performers
$stmt = $pdo->query("
    SELECT u.full_name, u.points, u.total_questions_answered,
           ROUND((SELECT COUNT(*) FROM user_answers ua WHERE ua.user_id = u.id AND ua.is_correct = 1) * 100.0 / u.total_questions_answered, 1) as accuracy
    FROM users u
    WHERE u.role_id = 1 AND u.total_questions_answered > 0
    ORDER BY u.points DESC
    LIMIT 5
");
$top_performers = $stmt->fetchAll();

// Category usage
$stmt = $pdo->query("
    SELECT qc.name, qc.color, COUNT(us.id) as session_count
    FROM question_categories qc
    LEFT JOIN user_sessions us ON qc.id = us.category_id
    GROUP BY qc.id, qc.name, qc.color
    ORDER BY session_count DESC
");
$category_usage = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin/admin_layout.css">
    <link rel="stylesheet" href="../assets/css/admin/admin_dashboard.css">
    <link rel="icon" href="../assets/img/logo.png" type="image/png">
</head>

<body>
    <div class="admin-layout">
        <?php include '../includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-container">
                <div class="admin-header">
                    <div>
                        <h1>Dashboard</h1>
                        <p>Overview statistik dan aktivitas platform</p>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon users">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" data-stat="total_users"><?php echo number_format($stats['users']['total_users']); ?></div>
                            <div class="stat-label">Total Users</div>
                            <div class="stat-change">+<?php echo $stats['users']['new_users_week']; ?> minggu ini</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon questions">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3" />
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" data-stat="total_questions"><?php echo number_format($stats['questions']['total_questions']); ?></div>
                            <div class="stat-label">Total Questions</div>
                            <div class="stat-change">+<?php echo $stats['questions']['new_questions_week']; ?> minggu ini</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon sessions">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12,6 12,12 16,14" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" data-stat="completed_sessions"><?php echo number_format($stats['sessions']['completed_sessions']); ?></div>
                            <div class="stat-label">Completed Sessions</div>
                            <div class="stat-change">+<?php echo $stats['sessions']['sessions_week']; ?> minggu ini</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon forum">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" data-stat="total_threads"><?php echo number_format($stats['forum']['total_threads']); ?></div>
                            <div class="stat-label">Forum Threads</div>
                            <div class="stat-change">+<?php echo $stats['forum']['new_threads_week']; ?> minggu ini</div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <!-- Recent Activities -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Recent Activities</h3>
                            <p class="card-subtitle">Aktivitas 7 hari terakhir</p>
                        </div>

                        <div class="activity-list">
                            <?php if (empty($recent_activities)): ?>
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 6v6l4 2" />
                                    </svg>
                                    <h3>No Recent Activities</h3>
                                    <p>Belum ada aktivitas dalam 7 hari terakhir</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon <?php echo $activity['type']; ?>">
                                            <?php if ($activity['type'] == 'user_registration'): ?>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                                    <circle cx="8.5" cy="7" r="4" />
                                                    <line x1="20" y1="8" x2="20" y2="14" />
                                                    <line x1="23" y1="11" x2="17" y2="11" />
                                                </svg>
                                            <?php elseif ($activity['type'] == 'thread_created'): ?>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                                </svg>
                                            <?php else: ?>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="20,6 9,17 4,12" />
                                                </svg>
                                            <?php endif; ?>
                                        </div>

                                        <div class="activity-content">
                                            <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                            <div class="activity-time" data-timestamp="<?php echo $activity['created_at']; ?>">
                                                <?php echo date('d M, H:i', strtotime($activity['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Top Performers -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Top Performers</h3>
                            <p class="card-subtitle">User dengan poin tertinggi</p>
                        </div>

                        <div class="performers-list">
                            <?php if (empty($top_performers)): ?>
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                    </svg>
                                    <h3>No Data Available</h3>
                                    <p>Belum ada user yang menyelesaikan sesi</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($top_performers as $index => $performer): ?>
                                    <div class="performer-item">
                                        <div class="performer-rank">#<?php echo $index + 1; ?></div>
                                        <div class="performer-info">
                                            <div class="performer-name"><?php echo htmlspecialchars($performer['full_name']); ?></div>
                                            <div class="performer-stats">
                                                <?php echo number_format($performer['points']); ?> poin •
                                                <?php echo $performer['accuracy']; ?>% akurasi
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Category Usage -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Category Usage</h3>
                            <p class="card-subtitle">Penggunaan kategori soal</p>
                        </div>

                        <div class="category-list">
                            <?php if (empty($category_usage)): ?>
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="7" height="7" />
                                        <rect x="14" y="3" width="7" height="7" />
                                        <rect x="14" y="14" width="7" height="7" />
                                        <rect x="3" y="14" width="7" height="7" />
                                    </svg>
                                    <h3>No Categories</h3>
                                    <p>Belum ada kategori soal yang tersedia</p>
                                </div>
                            <?php else: ?>
                                <?php
                                $max_sessions = $category_usage[0]['session_count'];
                                foreach ($category_usage as $category):
                                    $percentage = $max_sessions > 0 ? ($category['session_count'] / $max_sessions) * 100 : 0;
                                ?>
                                    <div class="category-item">
                                        <div class="category-info">
                                            <div class="category-name" style="border-left-color: <?php echo $category['color']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </div>
                                            <div class="category-count"><?php echo number_format($category['session_count']); ?> sesi</div>
                                        </div>
                                        <div class="category-bar">
                                            <div class="category-fill" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $category['color']; ?>"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Quick Actions</h3>
                            <p class="card-subtitle">Aksi cepat admin</p>
                        </div>

                        <div class="quick-actions">
                            <a href="questions.php?action=add" class="action-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19" />
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                </svg>
                                Add Question
                            </a>

                            <a href="users.php" class="action-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                                Manage Users
                            </a>

                            <a href="forum.php" class="action-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                </svg>
                                Moderate Forum
                            </a>

                            <a href="../index.php" class="action-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                    <polyline points="9,22 9,12 15,12 15,22" />
                                </svg>
                                View Site
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin/admin_layout.js"></script>
    <script src="../assets/js/admin/admin_dashboard.js"></script>
</body>

</html>