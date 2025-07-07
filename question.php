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
    SELECT us.*, qc.name as category_name, qc.color as category_color
    FROM user_sessions us
    JOIN question_categories qc ON us.category_id = qc.id
    WHERE us.id = ? AND us.user_id = ? AND us.is_active = 1
");
$stmt->execute([$session_id, $_SESSION['user_id']]);
$session = $stmt->fetch();

if (!$session) {
    redirect('drilling.php');
}

// Get questions for this session dengan urutan yang konsisten
$where_clause = $session['topic_id'] ? "AND q.topic_id = ?" : "";
$params = [$session['category_id']];
if ($session['topic_id']) {
    $params[] = $session['topic_id'];
}

$total_questions = (int)$session['total_questions'];

// Gunakan seed yang konsisten berdasarkan session_id untuk RAND()
$sql = "
    SELECT q.*, qt.name as topic_name
    FROM questions q
    JOIN question_topics qt ON q.topic_id = qt.id
    WHERE qt.category_id = ? AND q.is_active = 1 $where_clause
    ORDER BY RAND($session_id)
    LIMIT $total_questions
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questions = $stmt->fetchAll();

if (count($questions) == 0) {
    redirect('drilling.php');
}

// Get current question number from session
$current_question_number = $session['current_question_number'] ?? 1;
$current_question_number = max(1, min($current_question_number, count($questions)));

$current_question = $questions[$current_question_number - 1];

// Validasi data question dan set default values
$current_question = array_merge([
    'id' => 0,
    'topic_name' => 'Unknown Topic',
    'question_text' => '',
    'question_image' => '',
    'points' => 0,
    'option_a' => '',
    'option_b' => '',
    'option_c' => '',
    'option_d' => '',
    'option_e' => '',
    'correct_answer' => 'A'
], $current_question ?: []);

// Get user's previous answers for this session
$stmt = $pdo->prepare("
    SELECT question_id, selected_answer, is_correct, is_ragu
    FROM user_answers
    WHERE session_id = ?
");
$stmt->execute([$session_id]);
$user_answers = [];
foreach ($stmt->fetchAll() as $answer) {
    $user_answers[$answer['question_id']] = $answer;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

    if ($is_ajax) {
        header('Content-Type: application/json');

        $action = $_POST['action'] ?? '';
        $response = ['status' => 'error', 'message' => 'Invalid action'];

        switch ($action) {
            case 'navigate':
                $target_question = isset($_POST['target_question']) ? (int)$_POST['target_question'] : $current_question_number;

                // Validate target question
                if ($target_question < 1 || $target_question > count($questions)) {
                    if ($target_question > count($questions)) {
                        // Session completed
                        $stmt = $pdo->prepare("
                            UPDATE user_sessions 
                            SET is_completed = 1, session_end = NOW(), is_active = 0
                            WHERE id = ?
                        ");
                        $stmt->execute([$session_id]);

                        // Update user stats saat session selesai
                        updateUserStats($_SESSION['user_id'], $pdo);

                        $response = [
                            'status' => 'completed',
                            'redirect_url' => "result.php?session_id=$session_id"
                        ];
                    } else {
                        $response = ['status' => 'error', 'message' => 'Invalid question number'];
                    }
                    break;
                }

                // Update current question in session
                $stmt = $pdo->prepare("
                    UPDATE user_sessions 
                    SET current_question_number = ?, last_activity = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$target_question, $session_id]);

                // Get the target question
                $target_question_data = $questions[$target_question - 1];
                $target_question_data = array_merge([
                    'id' => 0,
                    'topic_name' => 'Unknown Topic',
                    'question_text' => '',
                    'question_image' => '',
                    'points' => 0,
                    'option_a' => '',
                    'option_b' => '',
                    'option_c' => '',
                    'option_d' => '',
                    'option_e' => '',
                    'correct_answer' => 'A'
                ], $target_question_data ?: []);

                // Get selected answer for target question
                $selected_answer = isset($user_answers[$target_question_data['id']]) ?
                    ($user_answers[$target_question_data['id']]['selected_answer'] ?? '') : '';

                $response = [
                    'status' => 'success',
                    'question_number' => $target_question,
                    'total_questions' => count($questions),
                    'question' => $target_question_data,
                    'selected_answer' => $selected_answer,
                    'is_ragu' => isset($user_answers[$target_question_data['id']]) ?
                        ($user_answers[$target_question_data['id']]['is_ragu'] ?? 0) : 0
                ];
                break;

            case 'submit_answer':
            case 'ragu_ragu':
                $question_id = (int)$_POST['question_id'];
                $selected_answer = $_POST['selected_answer'] ?? '';

                if ($question_id > 0) {
                    $is_ragu = ($action == 'ragu_ragu') ? 1 : 0;

                    // Validasi selected_answer
                    $valid_answers = ['A', 'B', 'C', 'D', 'E'];
                    $db_selected_answer = in_array($selected_answer, $valid_answers) ? $selected_answer : NULL;

                    // Check if answer already exists
                    $stmt = $pdo->prepare("
                        SELECT id FROM user_answers 
                        WHERE session_id = ? AND question_id = ?
                    ");
                    $stmt->execute([$session_id, $question_id]);
                    $existing_answer = $stmt->fetch();

                    // Get correct answer
                    $stmt = $pdo->prepare("SELECT correct_answer, points FROM questions WHERE id = ?");
                    $stmt->execute([$question_id]);
                    $question_data = $stmt->fetch();

                    if ($question_data) {
                        $is_correct = 0;
                        $points_earned = 0;

                        if ($db_selected_answer !== NULL && !$is_ragu) {
                            $is_correct = ($db_selected_answer == $question_data['correct_answer']) ? 1 : 0;
                            $points_earned = $is_correct ? (int)$question_data['points'] : 0;
                        }

                        if ($existing_answer) {
                            // Update existing answer
                            $stmt = $pdo->prepare("
                                UPDATE user_answers 
                                SET selected_answer = ?, is_correct = ?, points_earned = ?, is_ragu = ?, answered_at = NOW()
                                WHERE id = ?
                            ");
                            $stmt->execute([$db_selected_answer, $is_correct, $points_earned, $is_ragu, $existing_answer['id']]);
                        } else {
                            // Insert new answer
                            $stmt = $pdo->prepare("
                                INSERT INTO user_answers (user_id, question_id, session_id, selected_answer, is_correct, points_earned, is_ragu)
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([$_SESSION['user_id'], $question_id, $session_id, $db_selected_answer, $is_correct, $points_earned, $is_ragu]);
                        }

                        // Update user stats berdasarkan total aktual dari user_answers
                        updateUserStats($_SESSION['user_id'], $pdo);

                        // Update user_answers array
                        $user_answers[$question_id] = [
                            'question_id' => $question_id,
                            'selected_answer' => $db_selected_answer,
                            'is_correct' => $is_correct,
                            'is_ragu' => $is_ragu
                        ];

                        $response = [
                            'status' => 'success',
                            'action' => $action,
                            'message' => $action == 'ragu_ragu' ? 'Ditandai ragu-ragu' : 'Jawaban tersimpan'
                        ];
                    }
                }
                break;
        }

        echo json_encode($response);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soal <?php echo $current_question_number; ?> - <?php echo htmlspecialchars($session['category_name'] ?? 'Unknown Category'); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/pages/question.css">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
</head>

<body>
    <div class="cbt-container">
        <!-- Header -->
        <div class="cbt-header">
            <div class="session-info">
                <h1><?php echo htmlspecialchars($session['category_name'] ?? 'Unknown Category'); ?></h1>
                <span class="session-progress">
                    Soal <span id="current-question-display"><?php echo $current_question_number; ?></span> dari <?php echo count($questions); ?>
                </span>
            </div>

            <div class="session-actions">
                <a href="drilling.php" class="btn btn-secondary" onclick="return confirm('Yakin ingin keluar? Progress akan tersimpan.')">
                    Keluar
                </a>
            </div>
        </div>

        <div class="cbt-content">
            <!-- Question Navigation Sidebar -->
            <div class="question-nav">
                <div class="question-nav-content">
                    <div class="nav-header">
                        <h3>Navigasi Soal</h3>
                    </div>

                    <div class="question-numbers">
                        <?php for ($i = 1; $i <= count($questions); $i++): ?>
                            <?php
                            $question_id = $questions[$i - 1]['id'] ?? 0;
                            $is_answered = isset($user_answers[$question_id]);
                            $is_current = ($i == $current_question_number);
                            $is_ragu = $is_answered ? ($user_answers[$question_id]['is_ragu'] ?? 0) : 0;

                            $class = 'question-number';
                            if ($is_current) $class .= ' current';
                            if ($is_answered) {
                                if ($is_ragu) {
                                    $class .= ' ragu';
                                } else {
                                    $class .= ' answered';
                                }
                            }
                            ?>
                            <button type="button" data-question="<?php echo $i; ?>" class="<?php echo $class; ?>">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                    </div>

                    <div class="nav-stats">
                        <div class="stat-item">
                            <span class="stat-label">Dijawab</span>
                            <span class="stat-value" id="answered-count"><?php echo count(array_filter($user_answers, function ($ans) {
                                                                                return !($ans['is_ragu'] ?? 0);
                                                                            })); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Ragu-ragu</span>
                            <span class="stat-value" id="ragu-count"><?php echo count(array_filter($user_answers, function ($ans) {
                                                                            return ($ans['is_ragu'] ?? 0);
                                                                        })); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Study Companion - YouTube Player -->
                <div class="study-companion">
                    <div class="companion-header">
                        <h4>Study Companion</h4>
                        <p>Background music untuk fokus belajar</p>
                    </div>

                    <div class="youtube-container">
                        <iframe
                            src="https://www.youtube.com/embed/DXT9dF-WK-I?autoplay=1&loop=1&playlist=DXT9dF-WK-I&controls=1&modestbranding=1&rel=0&showinfo=0&enablejsapi=1&origin=<?php echo $_SERVER['HTTP_HOST']; ?>&widgetid=1"
                            title="Study Music - Focus & Concentration"
                            allow="autoplay; encrypted-media"
                            allowfullscreen>
                        </iframe>
                    </div>
                </div>
            </div>

            <!-- Question Content -->
            <div class="question-content" id="question-content">
                <!-- Dynamic content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Hidden data for JavaScript -->
    <script>
        window.cbtData = {
            sessionId: <?php echo $session_id; ?>,
            totalQuestions: <?php echo count($questions); ?>,
            currentQuestion: <?php echo $current_question_number; ?>,
            initialQuestionData: <?php echo json_encode($current_question); ?>,
            initialSelectedAnswer: <?php echo json_encode(isset($user_answers[$current_question['id']]) ? ($user_answers[$current_question['id']]['selected_answer'] ?? '') : ''); ?>,
            initialIsRagu: <?php echo json_encode(isset($user_answers[$current_question['id']]) ? ($user_answers[$current_question['id']]['is_ragu'] ?? 0) : 0); ?>
        };
    </script>
    <script src="assets/js/pages/question.js"></script>
</body>

</html>