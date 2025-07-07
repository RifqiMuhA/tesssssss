<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$thread_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$thread_id) {
    redirect('forum.php');
}

// Get thread details
$stmt = $pdo->prepare("
    SELECT ft.*, fc.name as category_name, u.full_name as author_name, u.id as author_id
    FROM forum_threads ft
    JOIN forum_categories fc ON ft.category_id = fc.id
    JOIN users u ON ft.user_id = u.id
    WHERE ft.id = ?
");
$stmt->execute([$thread_id]);
$thread = $stmt->fetch();

if (!$thread) {
    redirect('forum.php');
}

// Update views count
$stmt = $pdo->prepare("UPDATE forum_threads SET views_count = views_count + 1 WHERE id = ?");
$stmt->execute([$thread_id]);

// Get posts with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("
    SELECT fp.*, u.full_name as author_name, u.id as author_id,
           (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = fp.id) as likes_count,
           (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = fp.id AND pl.user_id = ?) as user_liked
    FROM forum_posts fp
    JOIN users u ON fp.user_id = u.id
    WHERE fp.thread_id = ?
    ORDER BY fp.created_at ASC
    LIMIT $offset, $per_page
");
$stmt->execute([$_SESSION['user_id'], $thread_id]);
$posts = $stmt->fetchAll();

// Get total posts count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM forum_posts WHERE thread_id = ?");
$stmt->execute([$thread_id]);
$total_posts = $stmt->fetch()['total'];
$total_pages = ceil($total_posts / $per_page);

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'add_reply' && !$thread['is_locked']) {
        $content = sanitize($_POST['content']);

        if (empty($content)) {
            $error = 'Konten balasan harus diisi';
        } elseif (strlen($content) < 5) {
            $error = 'Konten balasan minimal 5 karakter';
        } else {
            // Insert new post
            $stmt = $pdo->prepare("
                INSERT INTO forum_posts (thread_id, user_id, content)
                VALUES (?, ?, ?)
            ");

            if ($stmt->execute([$thread_id, $_SESSION['user_id'], $content])) {
                // Update thread replies count
                $stmt = $pdo->prepare("
                    UPDATE forum_threads 
                    SET replies_count = replies_count + 1 
                    WHERE id = ?
                ");
                $stmt->execute([$thread_id]);

                $success = 'Balasan berhasil ditambahkan';

                // Redirect to last page
                $last_page = ceil(($total_posts + 1) / $per_page);
                redirect("thread.php?id=$thread_id&page=$last_page#reply-form");
            } else {
                $error = 'Terjadi kesalahan saat menambahkan balasan';
            }
        }
    } elseif ($action == 'like_post') {
        $post_id = (int)$_POST['post_id'];

        // Check if already liked
        $stmt = $pdo->prepare("
            SELECT id FROM post_likes 
            WHERE post_id = ? AND user_id = ?
        ");
        $stmt->execute([$post_id, $_SESSION['user_id']]);

        if ($stmt->fetch()) {
            // Unlike
            $stmt = $pdo->prepare("
                DELETE FROM post_likes 
                WHERE post_id = ? AND user_id = ?
            ");
            $stmt->execute([$post_id, $_SESSION['user_id']]);
        } else {
            // Like
            $stmt = $pdo->prepare("
                INSERT INTO post_likes (post_id, user_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$post_id, $_SESSION['user_id']]);
        }

        // Redirect to prevent resubmission
        redirect("thread.php?id=$thread_id&page=$page");
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($thread['title']); ?> - Forum - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/pages/thread.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="icon" href="assets/img/logo.png" type="image/x-icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container">
        <div class="thread-header">
            <div class="breadcrumb">
                <a href="forum.php">Forum</a>
                <span class="separator">›</span>
                <span class="current"><?php echo htmlspecialchars($thread['title']); ?></span>
            </div>
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

        <!-- Original Post -->
        <div class="post-container">
            <div class="post original-post">
                <div class="post-author">
                    <div class="author-avatar">
                        <?php echo strtoupper(substr($thread['author_name'], 0, 2)); ?>
                    </div>
                    <div class="author-info">
                        <div class="author-name"><?php echo htmlspecialchars($thread['author_name']); ?></div>
                    </div>
                </div>

                <div class="post-content">
                    <div class="thread-badges">
                        <?php if ($thread['is_pinned']): ?>
                            <span class="badge badge-pinned">Pinned</span>
                        <?php endif; ?>
                        <?php if ($thread['is_locked']): ?>
                            <span class="badge badge-locked">Locked</span>
                        <?php endif; ?>
                        <span class="badge badge-category"><?php echo htmlspecialchars($thread['category_name']); ?></span>
                    </div>

                    <h1 class="thread-title"><?php echo htmlspecialchars($thread['title']); ?></h1>

                    <div class="thread-meta">
                        <span>oleh <?php echo htmlspecialchars($thread['author_name']); ?></span>
                        <span>•</span>
                        <span><?php echo date('d M Y, H:i', strtotime($thread['created_at'])); ?></span>
                        <span>•</span>
                        <span><?php echo number_format($thread['views_count']); ?> views</span>
                        <span>•</span>
                        <span><?php echo number_format($thread['replies_count']); ?> replies</span>
                    </div>

                    <div class="post-text">
                        <?php echo nl2br(htmlspecialchars($thread['content'])); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts -->
        <?php if (count($posts) > 0): ?>
            <div class="posts-container">
                <h3>Balasan (<?php echo number_format($total_posts); ?>)</h3>

                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <div class="post-author">
                            <div class="author-avatar">
                                <?php echo strtoupper(substr($post['author_name'], 0, 2)); ?>
                            </div>
                            <div class="author-info">
                                <div class="author-name"><?php echo htmlspecialchars($post['author_name']); ?></div>
                                <?php if ($post['is_solution']): ?>
                                    <div class="author-role solution">Solution</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="post-content">
                            <div class="post-text">
                                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                            </div>

                            <div class="post-footer">
                                <div class="post-date">
                                    <?php echo date('d M Y, H:i', strtotime($post['created_at'])); ?>
                                </div>

                                <div class="post-actions">
                                    <button type="button" class="like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>"
                                        data-post-id="<?php echo $post['id']; ?>">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="<?php echo $post['user_liked'] ? '#ef4444' : 'none'; ?>" stroke="#ef4444" stroke-width="2">
                                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                                        </svg>
                                        <span class="like-count"><?php echo $post['likes_count']; ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="thread.php?id=<?php echo $thread_id; ?>&page=<?php echo $page - 1; ?>"
                        class="pagination-btn">← Sebelumnya</a>
                <?php endif; ?>

                <div class="pagination-info">
                    Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                </div>

                <?php if ($page < $total_pages): ?>
                    <a href="thread.php?id=<?php echo $thread_id; ?>&page=<?php echo $page + 1; ?>"
                        class="pagination-btn">Selanjutnya →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Reply Form -->
        <?php if (!$thread['is_locked']): ?>
            <div class="reply-form-container" id="reply-form">
                <h3>Tambah Balasan</h3>

                <form method="POST" class="reply-form">
                    <input type="hidden" name="action" value="add_reply">

                    <div class="form-group">
                        <textarea name="content" rows="6" required
                            placeholder="Tulis balasan Anda..."><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Kirim Balasan</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="locked-notice">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                    <circle cx="12" cy="16" r="1" />
                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
                <p>Thread ini telah dikunci dan tidak dapat menerima balasan baru.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/global.js"></script>
    <script src="assets/js/pages/thread.js"></script>
    <script src="assets/js/navbar.js"></script>
</body>

</html>