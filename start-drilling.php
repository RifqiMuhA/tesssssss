<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

if (!$category_id) {
    redirect('drilling.php');
}

// Get category details
$stmt = $pdo->prepare("SELECT * FROM question_categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    redirect('drilling.php');
}

// Get topics for this category
$stmt = $pdo->prepare("
    SELECT qt.*, COUNT(q.id) as question_count
    FROM question_topics qt
    LEFT JOIN questions q ON qt.id = q.topic_id AND q.is_active = 1
    WHERE qt.category_id = ?
    GROUP BY qt.id
    HAVING question_count > 0
    ORDER BY qt.name
");
$stmt->execute([$category_id]);
$topics = $stmt->fetchAll();

// Check for existing active session
$stmt = $pdo->prepare("
    SELECT * FROM user_sessions 
    WHERE user_id = ? AND category_id = ? AND is_active = 1 AND is_completed = 0
");
$stmt->execute([$_SESSION['user_id'], $category_id]);
$active_session = $stmt->fetch();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'start_new') {
        $topic_id = isset($_POST['topic_id']) ? (int)$_POST['topic_id'] : null;
        $question_count = (int)($_POST['question_count'] ?? 20);
        
        // Validate topic selection
        if (!$topic_id) {
            $error = 'Harap pilih topik terlebih dahulu';
        } 
        // Validate question count
        elseif ($question_count < 5 || $question_count > 50) {
            $error = 'Jumlah soal harus antara 5-50';
        } else {
            // Get available questions for the selected topic
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as available_count
                FROM questions q
                WHERE q.topic_id = ? AND q.is_active = 1
            ");
            $stmt->execute([$topic_id]);
            $available_count = $stmt->fetch()['available_count'];
            
            if ($available_count < $question_count) {
                $error = "Hanya tersedia $available_count soal untuk topik ini";
            } else {
                // Deactivate any existing active sessions
                $stmt = $pdo->prepare("
                    UPDATE user_sessions 
                    SET is_active = 0 
                    WHERE user_id = ? AND category_id = ? AND is_active = 1
                ");
                $stmt->execute([$_SESSION['user_id'], $category_id]);
                
                // Create new session
                $stmt = $pdo->prepare("
                    INSERT INTO user_sessions (user_id, category_id, topic_id, total_questions)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $category_id, $topic_id, $question_count]);
                $session_id = $pdo->lastInsertId();
                
                redirect("question.php?session_id=$session_id");
            }
        }
    } elseif ($action == 'resume') {
        redirect("question.php?session_id=" . $active_session['id']);
    }
}

// Calculate progress percentage for active session
$progress_percentage = 0;
if ($active_session) {
    $progress_percentage = ($active_session['questions_answered'] / $active_session['total_questions']) * 100;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mulai Drilling - <?php echo htmlspecialchars($category['name']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/pages/start-drilling.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="page-header">
            <div class="breadcrumb">
                <a href="drilling.php">Drilling Soal</a>
                <span class="separator">›</span>
                <span class="current"><?php echo htmlspecialchars($category['name']); ?></span>
            </div>
            <h1>Mulai Drilling: <?php echo htmlspecialchars($category['name']); ?></h1>
            <p><?php echo htmlspecialchars($category['description']); ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="drilling-setup">
            <!-- Resume Session Card -->
            <?php if ($active_session): ?>
            <div class="session-card resume-card">
                <div class="session-header">
                    <h3>Lanjutkan Sesi Sebelumnya</h3>
                    <div class="session-badge">Aktif</div>
                </div>
                
                <div class="session-stats">
                    <!-- Progress Section -->
                    <div class="progress-stat">
                        <div class="progress-header">
                            <span class="progress-label">Progress Drilling</span>
                            <span class="progress-text">
                                <?php echo $active_session['questions_answered']; ?>/<?php echo $active_session['total_questions']; ?> soal
                            </span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                        </div>
                    </div>
                    
                    <!-- Other Stats -->
                    <div class="other-stats">
                        <div class="stat">
                            <span class="stat-label">Dimulai</span>
                            <span class="stat-value"><?php echo date('d M, H:i', strtotime($active_session['session_start'])); ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Sisa Soal</span>
                            <span class="stat-value"><?php echo $active_session['total_questions'] - $active_session['questions_answered']; ?></span>
                        </div>
                    </div>
                </div>
                
                <form method="POST" class="session-form">
                    <input type="hidden" name="action" value="resume">
                    <button type="submit" class="btn btn-primary btn-full">Lanjutkan Sesi</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- New Session Card -->
            <div class="session-card new-card">
                <div class="session-header">
                    <h3>Mulai Sesi Baru</h3>
                </div>
                
                <form method="POST" class="drilling-form">
                    <input type="hidden" name="action" value="start_new">
                    
                    <div class="form-group">
                        <label for="topic_id">Pilih Topik <span style="color: red;">*</span></label>
                        <select id="topic_id" name="topic_id" required>
                            <option value="">-- Pilih Topik --</option>
                            <?php foreach ($topics as $topic): ?>
                            <option value="<?php echo $topic['id']; ?>">
                                <?php echo htmlspecialchars($topic['name']); ?> 
                                (<?php echo $topic['question_count']; ?> soal)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="question_count">Jumlah Soal</label>
                        <select id="question_count" name="question_count">
                            <option value="10">10 Soal</option>
                            <option value="20" selected>20 Soal</option>
                            <option value="30">30 Soal</option>
                            <option value="50">50 Soal</option>
                        </select>
                    </div>
                    
                    <div class="drilling-info">
                        <div class="info-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12,6 12,12 16,14"/>
                            </svg>
                            <span>Tidak ada batas waktu</span>
                        </div>
                        <div class="info-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-4"/>
                                <path d="M9 11V7a3 3 0 0 1 6 0v4"/>
                            </svg>
                            <span>Sesi dapat dilanjutkan kapan saja</span>
                        </div>
                        <div class="info-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                            </svg>
                            <span>Fokus pada topik spesifik untuk hasil maksimal</span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">
                        <?php echo $active_session ? 'Mulai Sesi Baru' : 'Mulai Drilling'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Available Topics -->
        <?php if (count($topics) > 0): ?>
        <div class="topics-section">
            <h3>Topik Tersedia</h3>
            <div class="topics-grid">
                <?php foreach ($topics as $topic): ?>
                <div class="topic-card">
                    <h4 class="topic-name"><?php echo htmlspecialchars($topic['name']); ?></h4>
                    <p class="topic-description"><?php echo htmlspecialchars($topic['description']); ?></p>
                    <div class="topic-stats">
                        <span class="question-count"><?php echo $topic['question_count']; ?> soal</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/global.js"></script>
    <script src="assets/pages/start-drilling.js"></script>
</body>
</html>