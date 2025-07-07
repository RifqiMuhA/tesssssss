<?php
require_once '../config.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

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

    if ($action == 'pin_thread') {
        $thread_id = (int)$_POST['thread_id'];

        // Get current status first
        $stmt = $pdo->prepare("SELECT is_pinned FROM forum_threads WHERE id = ?");
        $stmt->execute([$thread_id]);
        $thread = $stmt->fetch();

        if ($thread) {
            $new_status = $thread['is_pinned'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE forum_threads SET is_pinned = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $thread_id])) {
                $_SESSION['success_message'] = $new_status ? 'Thread berhasil di-pin' : 'Thread berhasil di-unpin';
            } else {
                $_SESSION['error_message'] = 'Gagal mengubah status pin thread';
            }
        } else {
            $_SESSION['error_message'] = 'Thread tidak ditemukan';
        }
        header("Location: forum.php");
        exit();
    } elseif ($action == 'lock_thread') {
        $thread_id = (int)$_POST['thread_id'];

        // Get current status first
        $stmt = $pdo->prepare("SELECT is_locked FROM forum_threads WHERE id = ?");
        $stmt->execute([$thread_id]);
        $thread = $stmt->fetch();

        if ($thread) {
            $new_status = $thread['is_locked'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE forum_threads SET is_locked = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $thread_id])) {
                $_SESSION['success_message'] = $new_status ? 'Thread berhasil dikunci' : 'Thread berhasil dibuka';
            } else {
                $_SESSION['error_message'] = 'Gagal mengubah status lock thread';
            }
        } else {
            $_SESSION['error_message'] = 'Thread tidak ditemukan';
        }
        header("Location: forum.php");
        exit();
    } elseif ($action == 'toggle_thread_status') {
        $thread_id = (int)$_POST['thread_id'];

        // Get current status first
        $stmt = $pdo->prepare("SELECT is_active FROM forum_threads WHERE id = ?");
        $stmt->execute([$thread_id]);
        $thread = $stmt->fetch();

        if ($thread) {
            $new_status = $thread['is_active'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE forum_threads SET is_active = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $thread_id])) {
                $_SESSION['success_message'] = $new_status ? 'Thread berhasil diaktifkan' : 'Thread berhasil dinonaktifkan';
            } else {
                $_SESSION['error_message'] = 'Gagal mengubah status thread';
            }
        } else {
            $_SESSION['error_message'] = 'Thread tidak ditemukan';
        }
        header("Location: forum.php");
        exit();
    } elseif ($action == 'toggle_post_status') {
        $post_id = (int)$_POST['post_id'];

        // Get current status first
        $stmt = $pdo->prepare("SELECT is_active FROM forum_posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();

        if ($post) {
            $new_status = $post['is_active'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE forum_posts SET is_active = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $post_id])) {
                $_SESSION['success_message'] = $new_status ? 'Post berhasil diaktifkan' : 'Post berhasil dinonaktifkan';
            } else {
                $_SESSION['error_message'] = 'Gagal mengubah status post';
            }
        } else {
            $_SESSION['error_message'] = 'Post tidak ditemukan';
        }
        header("Location: forum.php");
        exit();
    }
}

// Get categories
$stmt = $pdo->query("SELECT * FROM forum_categories ORDER BY name");
$categories = $stmt->fetchAll();

$view = isset($_GET['view']) ? sanitize($_GET['view']) : 'threads';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Management - Admin - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin/admin_layout.css">
    <link rel="stylesheet" href="../assets/css/admin/admin_forum.css">
    <link rel="icon" href="../assets/img/logo.png" type="image/png">
</head>

<body>
    <div class="admin-layout">
        <?php include '../includes/admin_sidebar.php'; ?>

        <main class="admin-main">
            <div class="admin-container">
                <div class="admin-header">
                    <div>
                        <h1>Forum Management</h1>
                        <p>Moderate forum threads and posts</p>
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

                <!-- View Toggle -->
                <div class="view-toggle">
                    <button type="button" class="toggle-btn <?php echo ($view == 'threads') ? 'active' : ''; ?>"
                        data-view="threads">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                        Threads
                    </button>
                    <button type="button" class="toggle-btn <?php echo ($view == 'posts') ? 'active' : ''; ?>"
                        data-view="posts">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        Posts
                    </button>
                </div>

                <!-- Filters -->
                <div class="admin-filters">
                    <div class="filter-form">
                        <div class="filter-row">
                            <div class="search-group">
                                <input type="text" id="searchInput" placeholder="Search..." class="search-input">
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
                        </div>
                    </div>
                </div>

                <!-- Loading Indicator -->
                <div id="loadingIndicator" class="loading-indicator" style="display: none;">
                    <div class="loading-spinner"></div>
                    <p>Memuat data...</p>
                </div>

                <!-- Content List Container -->
                <div class="forum-list" id="forumListContainer">
                    <!-- Initial content will be loaded here -->
                </div>

                <!-- Pagination Container -->
                <div id="paginationContainer"></div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin/admin_layout.js"></script>
    <script src="../assets/js/admin/admin_forum.js"></script>
</body>

</html>