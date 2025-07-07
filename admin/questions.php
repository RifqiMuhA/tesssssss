<?php
require_once '../config.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Get categories and topics for dropdowns
$stmt = $pdo->query("SELECT * FROM question_categories ORDER BY type, name");
$categories = $stmt->fetchAll();

$stmt = $pdo->query("SELECT qt.*, qc.name as category_name FROM question_topics qt JOIN question_categories qc ON qt.category_id = qc.id ORDER BY qc.name, qt.name");
$topics = $stmt->fetchAll();

$success = '';
$error = '';

// Check for session messages
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'add_question') {
        $topic_id = (int)$_POST['topic_id'];
        $question_text = sanitize($_POST['question_text']);
        $option_a = sanitize($_POST['option_a']);
        $option_b = sanitize($_POST['option_b']);
        $option_c = sanitize($_POST['option_c']);
        $option_d = sanitize($_POST['option_d']);
        $option_e = sanitize($_POST['option_e']);
        $correct_answer = sanitize($_POST['correct_answer']);
        $explanation = sanitize($_POST['explanation']);
        $points = (int)$_POST['points'];

        // Validate required fields first
        if (empty($question_text) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || empty($option_e) || empty($correct_answer)) {
            $_SESSION['error_message'] = 'Semua field wajib harus diisi';
        } elseif (!in_array($correct_answer, ['A', 'B', 'C', 'D', 'E'])) {
            $_SESSION['error_message'] = 'Jawaban benar harus A, B, C, D, atau E';
        } elseif ($topic_id <= 0) {
            $_SESSION['error_message'] = 'Topic harus dipilih';
        } elseif ($points < 1 || $points > 100) {
            $_SESSION['error_message'] = 'Points harus antara 1-100';
        } else {
            // Handle image upload
            $question_image = null;
            $upload_error = '';

            if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] == 0) {
                $upload_result = handleImageUpload($_FILES['question_image']);
                if ($upload_result['success']) {
                    $question_image = $upload_result['filename'];
                } else {
                    $upload_error = $upload_result['error'];
                }
            }

            // Only proceed if no upload error (or no image uploaded)
            if (empty($upload_error)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO questions (topic_id, question_text, question_image, option_a, option_b, option_c, option_d, option_e, correct_answer, explanation, points, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    if ($stmt->execute([$topic_id, $question_text, $question_image, $option_a, $option_b, $option_c, $option_d, $option_e, $correct_answer, $explanation, $points, $_SESSION['user_id']])) {
                        if ($question_image) {
                            $_SESSION['success_message'] = 'Soal berhasil ditambahkan dengan gambar';
                        } else {
                            $_SESSION['success_message'] = 'Soal berhasil ditambahkan';
                        }
                        header("Location: questions.php");
                        exit();
                    } else {
                        $_SESSION['error_message'] = 'Gagal menambahkan soal ke database';
                        if ($question_image && file_exists("../resources/img/questions/" . $question_image)) {
                            unlink("../resources/img/questions/" . $question_image);
                        }
                    }
                } catch (Exception $e) {
                    $_SESSION['error_message'] = 'Error database: ' . $e->getMessage();
                    if ($question_image && file_exists("../resources/img/questions/" . $question_image)) {
                        unlink("../resources/img/questions/" . $question_image);
                    }
                }
            } else {
                $_SESSION['error_message'] = $upload_error;
            }
        }

        // Redirect back to form if there's an error
        if (isset($_SESSION['error_message'])) {
            header("Location: questions.php?action=add");
            exit();
        }
    } elseif ($action == 'toggle_status') {
        $question_id = (int)$_POST['question_id'];

        // Get current status first
        $stmt = $pdo->prepare("SELECT is_active FROM questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $question = $stmt->fetch();

        if ($question) {
            $new_status = $question['is_active'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE questions SET is_active = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $question_id])) {
                $_SESSION['success_message'] = $new_status ? 'Soal berhasil diaktifkan' : 'Soal berhasil dinonaktifkan';
            } else {
                $_SESSION['error_message'] = 'Gagal mengubah status soal';
            }
        } else {
            $_SESSION['error_message'] = 'Soal tidak ditemukan';
        }
        header("Location: questions.php");
        exit();
    }
}

// Function to handle image upload
function handleImageUpload($file)
{
    $upload_dir = '../resources/img/questions/';

    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            return ['success' => false, 'error' => 'Gagal membuat direktori upload'];
        }
    }

    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($file['tmp_name']);

    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP'];
    }

    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'Ukuran file terlalu besar. Maksimal 5MB'];
    }

    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'question_' . time() . '_' . uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Gagal mengupload file'];
    }
}

$show_add_form = isset($_GET['action']) && $_GET['action'] == 'add';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Management - Admin - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin/admin_layout.css">
    <link rel="stylesheet" href="../assets/css/admin/admin_questions.css">
    <link rel="icon" href="../assets/img/logo.png" type="image/png">
</head>

<body>
    <div class="admin-layout">
        <?php include '../includes/admin_sidebar.php'; ?>

        <main class="admin-main">
            <div class="admin-container">
                <div class="admin-header">
                    <div>
                        <h1>Question Management</h1>
                        <p>Kelola bank soal platform DrillPTN</p>
                    </div>
                    <div class="header-actions">
                        <?php if (!$show_add_form): ?>
                            <a href="?action=add" class="btn btn-primary">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19" />
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                </svg>
                                Add Question
                            </a>
                        <?php else: ?>
                            <a href="questions.php" class="btn btn-secondary">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 12H5m7-7l-7 7 7 7" />
                                </svg>
                                Back to List
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="15" y1="9" x2="9" y2="15" />
                            <line x1="9" y1="9" x2="15" y2="15" />
                        </svg>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12" />
                        </svg>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($show_add_form): ?>
                    <!-- Add Question Form -->
                    <div class="admin-form-container">
                        <h2>Add New Question</h2>

                        <form method="POST" enctype="multipart/form-data" class="admin-form" id="addQuestionForm">
                            <input type="hidden" name="action" value="add_question">

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="topic_id">Topic *</label>
                                    <select id="topic_id" name="topic_id" required>
                                        <option value="">Select Topic</option>
                                        <?php foreach ($topics as $topic): ?>
                                            <option value="<?php echo $topic['id']; ?>" <?php echo (isset($_POST['topic_id']) && $_POST['topic_id'] == $topic['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($topic['category_name'] . ' - ' . $topic['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="points">Points</label>
                                    <input type="number" id="points" name="points" value="<?php echo isset($_POST['points']) ? $_POST['points'] : '10'; ?>" min="1" max="100">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="question_text">Question Text *</label>
                                <textarea id="question_text" name="question_text" rows="4" required
                                    placeholder="Enter the question text..."><?php echo isset($_POST['question_text']) ? htmlspecialchars($_POST['question_text']) : ''; ?></textarea>
                            </div>

                            <!-- Image Upload Section -->
                            <div class="form-group">
                                <label for="question_image">Question Image (Optional)</label>
                                <div class="image-upload-container">
                                    <input type="file"
                                        id="question_image"
                                        name="question_image"
                                        accept="image/*"
                                        class="image-input"
                                        onchange="showImagePreview(this)">
                                    <div class="image-preview" id="imagePreview" onclick="document.getElementById('question_image').click()">
                                        <div class="upload-placeholder">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                                                <circle cx="9" cy="9" r="2" />
                                                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
                                            </svg>
                                            <p>Click to upload image</p>
                                            <span>JPG, PNG, GIF, WEBP (Max 5MB)</span>
                                        </div>
                                    </div>
                                    <div class="upload-info" id="uploadInfo" style="display: none;">
                                        <span class="file-selected">✅ File selected: <span id="fileName"></span></span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Answer Options *</label>
                                <div class="options-grid">
                                    <div class="option-item">
                                        <label for="option_a">A.</label>
                                        <input type="text" id="option_a" name="option_a" required placeholder="Option A" value="<?php echo isset($_POST['option_a']) ? htmlspecialchars($_POST['option_a']) : ''; ?>">
                                    </div>
                                    <div class="option-item">
                                        <label for="option_b">B.</label>
                                        <input type="text" id="option_b" name="option_b" required placeholder="Option B" value="<?php echo isset($_POST['option_b']) ? htmlspecialchars($_POST['option_b']) : ''; ?>">
                                    </div>
                                    <div class="option-item">
                                        <label for="option_c">C.</label>
                                        <input type="text" id="option_c" name="option_c" required placeholder="Option C" value="<?php echo isset($_POST['option_c']) ? htmlspecialchars($_POST['option_c']) : ''; ?>">
                                    </div>
                                    <div class="option-item">
                                        <label for="option_d">D.</label>
                                        <input type="text" id="option_d" name="option_d" required placeholder="Option D" value="<?php echo isset($_POST['option_d']) ? htmlspecialchars($_POST['option_d']) : ''; ?>">
                                    </div>
                                    <div class="option-item">
                                        <label for="option_e">E.</label>
                                        <input type="text" id="option_e" name="option_e" required placeholder="Option E" value="<?php echo isset($_POST['option_e']) ? htmlspecialchars($_POST['option_e']) : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="correct_answer">Correct Answer *</label>
                                <select id="correct_answer" name="correct_answer" required>
                                    <option value="">Select Correct Answer</option>
                                    <option value="A" <?php echo (isset($_POST['correct_answer']) && $_POST['correct_answer'] == 'A') ? 'selected' : ''; ?>>A</option>
                                    <option value="B" <?php echo (isset($_POST['correct_answer']) && $_POST['correct_answer'] == 'B') ? 'selected' : ''; ?>>B</option>
                                    <option value="C" <?php echo (isset($_POST['correct_answer']) && $_POST['correct_answer'] == 'C') ? 'selected' : ''; ?>>C</option>
                                    <option value="D" <?php echo (isset($_POST['correct_answer']) && $_POST['correct_answer'] == 'D') ? 'selected' : ''; ?>>D</option>
                                    <option value="E" <?php echo (isset($_POST['correct_answer']) && $_POST['correct_answer'] == 'E') ? 'selected' : ''; ?>>E</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="explanation">Explanation</label>
                                <textarea id="explanation" name="explanation" rows="3"
                                    placeholder="Optional explanation for the answer..."><?php echo isset($_POST['explanation']) ? htmlspecialchars($_POST['explanation']) : ''; ?></textarea>
                            </div>

                            <div class="form-actions">
                                <a href="questions.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Add Question</button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Filters -->
                    <div class="admin-filters">
                        <div class="filter-form">
                            <div class="filter-row">
                                <div class="search-group">
                                    <input type="text" id="searchInput" placeholder="Search questions..." class="search-input">
                                    <button type="button" id="searchBtn" class="search-btn">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="11" cy="11" r="8" />
                                            <path d="m21 21-4.35-4.35" />
                                        </svg>
                                    </button>
                                </div>

                                <select id="categoryFilter" class="filter-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <select id="statusFilter" class="filter-select">
                                    <option value="">All Status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div id="loadingIndicator" class="loading-indicator" style="display: none;">
                        <div class="loading-spinner"></div>
                        <p>Memuat data...</p>
                    </div>

                    <!-- Questions List Container -->
                    <div class="questions-list" id="questionsListContainer">
                        <!-- Initial content will be loaded here -->
                    </div>

                    <!-- Pagination Container -->
                    <div id="paginationContainer"></div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
        <div class="image-modal-content">
            <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
            <img id="modalImage" class="modal-image" src="/placeholder.svg" alt="Question Image">
        </div>
    </div>

    <script src="../assets/js/admin/admin_layout.js"></script>
    <script src="../assets/js/admin/admin_questions.js"></script>

    <script>
        function showImagePreview(input) {
            const file = input.files[0];
            const preview = document.getElementById('imagePreview');
            const uploadInfo = document.getElementById('uploadInfo');
            const fileName = document.getElementById('fileName');

            if (file) {
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.');
                    input.value = '';
                    return;
                }

                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('Ukuran file terlalu besar. Maksimal 5MB.');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                    <img src="${e.target.result}" class="preview-image" alt="Preview">
                    <button type="button" class="remove-image" onclick="clearImagePreview()" title="Remove image">×</button>
                `;
                    preview.classList.add('has-image');
                    preview.onclick = null;

                    fileName.textContent = file.name;
                    uploadInfo.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }

        function clearImagePreview() {
            const input = document.getElementById('question_image');
            const preview = document.getElementById('imagePreview');
            const uploadInfo = document.getElementById('uploadInfo');

            input.value = '';
            preview.classList.remove('has-image');
            preview.innerHTML = `
            <div class="upload-placeholder">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <circle cx="9" cy="9" r="2"/>
                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                </svg>
                <p>Click to upload image</p>
                <span>JPG, PNG, GIF, WEBP (Max 5MB)</span>
            </div>
        `;
            preview.onclick = function() {
                document.getElementById('question_image').click();
            };
            uploadInfo.style.display = 'none';
        }
    </script>
</body>

</html>