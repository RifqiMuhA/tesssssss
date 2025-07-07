<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

if (!$session_id) {
    redirect('drilling.php');
}

// Get session details
$stmt = $pdo->prepare("
    SELECT us.*, qc.name as category_name, qc.color as category_color,
           qt.name as topic_name
    FROM user_sessions us
    JOIN question_categories qc ON us.category_id = qc.id
    LEFT JOIN question_topics qt ON us.topic_id = qt.id
    WHERE us.id = ? AND us.user_id = ? AND us.is_completed = 1
");
$stmt->execute([$session_id, $_SESSION['user_id']]);
$session = $stmt->fetch();

if (!$session) {
    redirect('drilling.php');
}

// Get detailed answers
$stmt = $pdo->prepare("
    SELECT ua.*, q.question_text, q.correct_answer, q.explanation, q.points,
           qt.name as topic_name
    FROM user_answers ua
    JOIN questions q ON ua.question_id = q.id
    JOIN question_topics qt ON q.topic_id = qt.id
    WHERE ua.session_id = ?
    ORDER BY ua.answered_at
");
$stmt->execute([$session_id]);
$answers = $stmt->fetchAll();

// PERBAIKAN: Hitung statistik langsung dari user_answers, bukan dari session
$total_questions = $session['total_questions'];
$answered_questions = count($answers);

// Hitung benar/salah dari data aktual user_answers
$correct_answers = 0;
$total_points = 0;
$ragu_answers = 0;

foreach ($answers as $answer) {
    if ($answer['is_correct'] == 1) {
        $correct_answers++;
    }
    $total_points += $answer['points_earned'];
    if ($answer['is_ragu'] == 1) {
        $ragu_answers++;
    }
}

$wrong_answers = $answered_questions - $correct_answers - $ragu_answers;
$accuracy = $answered_questions > 0 ? round(($correct_answers / $answered_questions) * 100, 1) : 0;

// Calculate time spent
$session_duration = strtotime($session['session_end']) - strtotime($session['session_start']);
$duration_minutes = round($session_duration / 60);

// Get topic-wise performance
$stmt = $pdo->prepare("
    SELECT qt.name as topic_name,
           COUNT(*) as total_questions,
           SUM(CASE WHEN ua.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
           SUM(ua.points_earned) as points_earned
    FROM user_answers ua
    JOIN questions q ON ua.question_id = q.id
    JOIN question_topics qt ON q.topic_id = qt.id
    WHERE ua.session_id = ?
    GROUP BY qt.id, qt.name
    ORDER BY qt.name
");
$stmt->execute([$session_id]);
$topic_performance = $stmt->fetchAll();

// Get user's overall stats (PERBAIKAN: Hitung dari user_answers yang aktual)
$stmt = $pdo->prepare("
    SELECT u.points,
           COUNT(ua.id) as total_questions_answered,
           SUM(CASE WHEN ua.is_correct = 1 THEN 1 ELSE 0 END) as total_correct
    FROM users u
    LEFT JOIN user_answers ua ON u.id = ua.user_id
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$_SESSION['user_id']]);
$user_stats = $stmt->fetch();

$overall_accuracy = $user_stats['total_questions_answered'] > 0 ?
    round(($user_stats['total_correct'] / $user_stats['total_questions_answered']) * 100, 1) : 0;

// Get badge
$badge = getUserBadge($user_stats['points']);

// TAMBAHAN: Update user_sessions dengan data yang benar (opsional, untuk memperbaiki data)
$stmt = $pdo->prepare("
    UPDATE user_sessions 
    SET correct_answers = ?, 
        questions_answered = ?
    WHERE id = ?
");
$stmt->execute([$correct_answers, $answered_questions, $session_id]);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Drilling - <?php echo htmlspecialchars($session['category_name']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="assets/img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/pages/result.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container" style="margin-top: 2em;">
        <!-- Result Header -->
        <div class="result-header">
            <div class="result-title">
                <h1>Hasil Drilling</h1>
                <p><?php echo htmlspecialchars($session['category_name']); ?>
                    <?php if ($session['topic_name']): ?>
                        - <?php echo htmlspecialchars($session['topic_name']); ?>
                    <?php endif; ?>
                </p>
            </div>

            <div class="result-badge">
                <?php if ($accuracy >= 80): ?>
                    <div class="performance-badge excellent">Kamu Genius!</div>
                <?php elseif ($accuracy >= 60): ?>
                    <div class="performance-badge good">Good Job!</div>
                <?php elseif ($accuracy >= 40): ?>
                    <div class="performance-badge fair">Tingkatkan!</div>
                <?php else: ?>
                    <div class="performance-badge poor">Latihan Lagi</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Score Summary -->
        <div class="score-summary">
            <div class="score-card main-score">
                <div class="score-circle">
                    <svg width="120" height="120" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="50" fill="none" stroke="#e2e8f0" stroke-width="8" />
                        <circle cx="60" cy="60" r="50" fill="none" stroke="var(--primary-color)"
                            stroke-width="8" stroke-linecap="round"
                            stroke-dasharray="<?php echo 2 * pi() * 50; ?>"
                            stroke-dashoffset="<?php echo 2 * pi() * 50 * (1 - $accuracy / 100); ?>"
                            transform="rotate(-90 60 60)" />
                    </svg>
                    <div class="score-text">
                        <div class="score-percentage"><?php echo $accuracy; ?>%</div>
                        <div class="score-label">Akurasi</div>
                    </div>
                </div>
            </div>

            <div class="score-stats">
                <div class="stat-item">
                    <div class="stat-icon correct">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $correct_answers; ?></div>
                        <div class="stat-label">Benar</div>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon wrong">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $wrong_answers; ?></div>
                        <div class="stat-label">Salah</div>
                    </div>
                </div>

                <!-- TAMBAHAN: Tampilkan statistik ragu-ragu jika ada -->
                <?php if ($ragu_answers > 0): ?>
                    <div class="stat-item">
                        <div class="stat-icon ragu" style="color: #f59e0b;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                                <circle cx="12" cy="17" r="1" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $ragu_answers; ?></div>
                            <div class="stat-label">Ragu-ragu</div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="stat-item">
                    <div class="stat-icon points">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo number_format($total_points); ?></div>
                        <div class="stat-label">Poin</div>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon time">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12,6 12,12 16,14" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $duration_minutes; ?></div>
                        <div class="stat-label">Menit</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Topic Performance -->
        <?php if (count($topic_performance) > 1): ?>
            <div class="topic-performance">
                <h3>Performa per Topik</h3>
                <div class="topic-grid">
                    <?php foreach ($topic_performance as $topic): ?>
                        <?php $topic_accuracy = $topic['total_questions'] > 0 ? round(($topic['correct_answers'] / $topic['total_questions']) * 100, 1) : 0; ?>
                        <div class="topic-card">
                            <div class="topic-header">
                                <h4><?php echo htmlspecialchars($topic['topic_name']); ?></h4>
                                <span class="topic-accuracy"><?php echo $topic_accuracy; ?>%</span>
                            </div>
                            <div class="topic-stats">
                                <span><?php echo $topic['correct_answers']; ?>/<?php echo $topic['total_questions']; ?> benar</span>
                                <span><?php echo number_format($topic['points_earned']); ?> poin</span>
                            </div>
                            <div class="topic-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $topic_accuracy; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Overall Progress -->
        <div class="overall-progress">
            <h3>Progress Keseluruhan</h3>
            <div class="progress-cards">
                <div class="progress-card">
                    <div class="progress-header">
                        <h4>Total Poin</h4>
                        <span class="badge" style="background-color: <?php echo $badge['color']; ?>">
                            <?php echo $badge['name']; ?>
                        </span>
                    </div>
                    <div class="progress-number"><?php echo number_format($user_stats['points']); ?></div>
                </div>

                <div class="progress-card">
                    <div class="progress-header">
                        <h4>Total Soal Dijawab</h4>
                    </div>
                    <div class="progress-number"><?php echo number_format($user_stats['total_questions_answered']); ?></div>
                </div>

                <div class="progress-card">
                    <div class="progress-header">
                        <h4>Akurasi Keseluruhan</h4>
                    </div>
                    <div class="progress-number"><?php echo $overall_accuracy; ?>%</div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="result-actions">
            <a href="drilling.php" class="btn btn-secondary">Kembali ke Drilling</a>
            <a href="start-drilling.php?category_id=<?php echo $session['category_id']; ?>" class="btn btn-primary">
                Drilling Lagi
            </a>
            <a href="leaderboard.php" class="btn btn-outline">Lihat Leaderboard</a>
        </div>

        <!-- Detailed Review -->
        <div class="detailed-review">
            <div class="review-header">
                <h3>Review Jawaban</h3>
                <p>Klik pada soal untuk melihat penjelasan</p>
            </div>

            <div class="review-list">
                <?php foreach ($answers as $index => $answer): ?>
                    <div class="review-item <?php echo $answer['is_correct'] ? 'correct' : ($answer['is_ragu'] ? 'ragu' : 'wrong'); ?>">
                        <div class="review-header-item">
                            <div class="question-number">
                                <span><?php echo $index + 1; ?></span>
                                <?php if ($answer['is_ragu']): ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                                        <circle cx="12" cy="17" r="1" />
                                    </svg>
                                <?php elseif ($answer['is_correct']): ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20,6 9,17 4,12" />
                                    </svg>
                                <?php else: ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18" />
                                        <line x1="6" y1="6" x2="18" y2="18" />
                                    </svg>
                                <?php endif; ?>
                            </div>

                            <div class="question-info">
                                <div class="question-topic"><?php echo htmlspecialchars($answer['topic_name']); ?></div>
                                <div class="question-points"><?php echo $answer['points_earned']; ?> poin</div>
                            </div>

                            <button class="expand-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9" />
                                </svg>
                            </button>
                        </div>

                        <div class="review-content" id="review-<?php echo $index; ?>">
                            <div class="question-text">
                                <?php echo nl2br(htmlspecialchars($answer['question_text'])); ?>
                            </div>

                            <div class="answer-comparison">
                                <div class="answer-item">
                                    <span class="answer-label">Jawaban Anda:</span>
                                    <span class="answer-value <?php echo $answer['is_ragu'] ? 'ragu' : ($answer['is_correct'] ? 'correct' : 'wrong'); ?>">
                                        <?php echo $answer['selected_answer'] ? $answer['selected_answer'] : ($answer['is_ragu'] ? 'Ragu-ragu' : 'Tidak dijawab'); ?>
                                    </span>
                                </div>

                                <div class="answer-item">
                                    <span class="answer-label">Jawaban Benar:</span>
                                    <span class="answer-value correct">
                                        <?php echo $answer['correct_answer']; ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($answer['explanation']): ?>
                                <div class="explanation">
                                    <h5>Penjelasan:</h5>
                                    <p><?php echo nl2br(htmlspecialchars($answer['explanation'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/global.js"></script>
    <script src="assets/js/pages/result.js"></script>
    <script src="assets/js/navbar.js"></script>
</body>

</html>