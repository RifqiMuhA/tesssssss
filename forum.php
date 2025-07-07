<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if this is an AJAX request
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'latest';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get forum categories
$stmt = $pdo->query("SELECT * FROM forum_categories ORDER BY name");
$categories = $stmt->fetchAll();

// Build WHERE clause for threads
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(ft.title LIKE ? OR ft.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter) {
    $where_conditions[] = "ft.category_id = ?";
    $params[] = $category_filter;
}

if ($date_filter) {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(ft.created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "ft.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $where_conditions[] = "ft.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
    }
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Build ORDER BY clause
$order_clause = "ORDER BY ft.is_pinned DESC, ";
switch ($sort) {
    case 'most_viewed':
        $order_clause .= "ft.views_count DESC";
        break;
    case 'most_replies':
        $order_clause .= "ft.replies_count DESC";
        break;
    case 'oldest':
        $order_clause .= "ft.created_at ASC";
        break;
    default: // latest
        $order_clause .= "ft.created_at DESC";
        break;
}

// Get threads with pagination
$stmt = $pdo->prepare("
    SELECT ft.*, fc.name as category_name, u.full_name as author_name,
           (SELECT fp.created_at FROM forum_posts fp WHERE fp.thread_id = ft.id ORDER BY fp.created_at DESC LIMIT 1) as last_reply_at,
           (SELECT u2.full_name FROM forum_posts fp2 JOIN users u2 ON fp2.user_id = u2.id WHERE fp2.thread_id = ft.id ORDER BY fp2.created_at DESC LIMIT 1) as last_reply_author
    FROM forum_threads ft
    JOIN forum_categories fc ON ft.category_id = fc.id
    JOIN users u ON ft.user_id = u.id
    $where_clause
    $order_clause
    LIMIT $offset, $per_page
");

$count_params = $params;
$stmt->execute($count_params);
$threads = $stmt->fetchAll();

// Get total count for pagination
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM forum_threads ft
    JOIN forum_categories fc ON ft.category_id = fc.id
    JOIN users u ON ft.user_id = u.id
    $where_clause
");
$count_stmt->execute($count_params);
$total_threads = $count_stmt->fetch()['total'];
$total_pages = ceil($total_threads / $per_page);

// If AJAX request, return only the thread list and pagination
if ($isAjax) {
    ob_start();
?>
    <!-- Thread List -->
    <div class="thread-list">
        <?php if (count($threads) > 0): ?>
            <?php foreach ($threads as $thread): ?>
                <div class="thread-item <?php echo $thread['is_pinned'] ? 'pinned' : ''; ?>"
                    onclick="window.location.href='thread.php?id=<?php echo $thread['id']; ?>'">
                    <div class="thread-content">
                        <div class="thread-header">
                            <div class="thread-badges">
                                <?php if ($thread['is_pinned']): ?>
                                    <span class="badge badge-pinned">Pinned</span>
                                <?php endif; ?>
                                <?php if ($thread['is_locked']): ?>
                                    <span class="badge badge-locked">Locked</span>
                                <?php endif; ?>
                                <span class="badge badge-category"><?php echo htmlspecialchars($thread['category_name']); ?></span>
                            </div>
                        </div>

                        <h3 class="thread-title">
                            <?php echo htmlspecialchars($thread['title']); ?>
                        </h3>

                        <div class="thread-meta">
                            <span class="thread-author">oleh <?php echo htmlspecialchars($thread['author_name']); ?></span>
                            <span class="thread-date"><?php echo date('d M Y, H:i', strtotime($thread['created_at'])); ?></span>
                            <?php if ($thread['last_reply_at']): ?>
                                <span class="last-reply">
                                    Balasan terakhir oleh <?php echo htmlspecialchars($thread['last_reply_author']); ?>
                                    pada <?php echo date('d M Y, H:i', strtotime($thread['last_reply_at'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="thread-stats">
                        <div class="stat-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <span><?php echo number_format($thread['views_count']); ?></span>
                        </div>
                        <div class="stat-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                            </svg>
                            <span><?php echo number_format($thread['replies_count']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.35-4.35" />
                </svg>
                <h3>Tidak ada thread ditemukan</h3>
                <p>Coba ubah filter pencarian atau buat thread baru</p>
                <a href="new-thread.php" class="btn btn-primary">Buat Thread Baru</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="#" class="pagination-btn" data-page="<?php echo $page - 1; ?>">← Sebelumnya</a>
            <?php endif; ?>

            <div class="pagination-info">
                Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
            </div>

            <?php if ($page < $total_pages): ?>
                <a href="#" class="pagination-btn" data-page="<?php echo $page + 1; ?>">Selanjutnya →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php
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
    <title>Forum Diskusi - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/pages/forum.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
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
        <div class="forum-header">
            <div class="forum-title">
                <h1>Forum Diskusi</h1>
                <p>Diskusi, tanya jawab, dan berbagi pengalaman seputar UTBK</p>
            </div>

            <div class="forum-actions">
                <a href="new-thread.php" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    Buat Thread Baru
                </a>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="forum-filters">
            <div class="filter-form" id="filterForm">
                <div class="filter-row">
                    <div class="search-group">
                        <input type="text" id="searchInput" placeholder="Cari thread atau post..."
                            value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                        <button type="button" class="search-btn" id="searchBtn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                            </svg>
                        </button>
                    </div>

                    <select id="categoryFilter" class="filter-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select id="dateFilter" class="filter-select">
                        <option value="">Semua Waktu</option>
                        <option value="today" <?php echo ($date_filter == 'today') ? 'selected' : ''; ?>>Hari Ini</option>
                        <option value="week" <?php echo ($date_filter == 'week') ? 'selected' : ''; ?>>Minggu Ini</option>
                        <option value="month" <?php echo ($date_filter == 'month') ? 'selected' : ''; ?>>Bulan Ini</option>
                    </select>

                    <select id="sortFilter" class="filter-select">
                        <option value="latest" <?php echo ($sort == 'latest') ? 'selected' : ''; ?>>Terbaru</option>
                        <option value="oldest" <?php echo ($sort == 'oldest') ? 'selected' : ''; ?>>Terlama</option>
                        <option value="most_viewed" <?php echo ($sort == 'most_viewed') ? 'selected' : ''; ?>>Paling Dilihat</option>
                        <option value="most_replies" <?php echo ($sort == 'most_replies') ? 'selected' : ''; ?>>Paling Banyak Balasan</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Loading indicator -->
        <div id="loadingIndicator" class="loading-indicator" style="display: none;">
            <div class="loading-spinner"></div>
        </div>

        <!-- Thread List Container -->
        <div id="threadListContainer">
            <!-- Thread List -->
            <div class="thread-list">
                <?php if (count($threads) > 0): ?>
                    <?php foreach ($threads as $thread): ?>
                        <div class="thread-item <?php echo $thread['is_pinned'] ? 'pinned' : ''; ?>"
                            onclick="window.location.href='thread.php?id=<?php echo $thread['id']; ?>'">
                            <div class="thread-content">
                                <div class="thread-header">
                                    <div class="thread-badges">
                                        <?php if ($thread['is_pinned']): ?>
                                            <span class="badge badge-pinned"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pin-angle" viewBox="0 0 16 16">
                                                    <path d="M9.828.722a.5.5 0 0 1 .354.146l4.95 4.95a.5.5 0 0 1 0 .707c-.48.48-1.072.588-1.503.588-.177 0-.335-.018-.46-.039l-3.134 3.134a5.927 5.927 0 0 1 .16 1.013c.046.702-.032 1.687-.72 2.375a.5.5 0 0 1-.707 0l-2.829-2.828-3.182 3.182c-.195.195-1.219.902-1.414.707-.195-.195.512-1.22.707-1.414l3.182-3.182-2.828-2.829a.5.5 0 0 1 0-.707c.688-.688 1.673-.767 2.375-.72a5.922 5.922 0 0 1 1.013.16l3.134-3.133a2.772 2.772 0 0 1-.04-.461c0-.43.108-1.022.589-1.503a.5.5 0 0 1 .353-.146zm.122 2.112v-.002.002zm0-.002v.002a.5.5 0 0 1-.122.51L6.293 6.878a.5.5 0 0 1-.511.12H5.78l-.014-.004a4.507 4.507 0 0 0-.288-.076 4.922 4.922 0 0 0-.765-.116c-.422-.028-.836.008-1.175.15l5.51 5.509c.141-.34.177-.753.149-1.175a4.924 4.924 0 0 0-.192-1.054l-.004-.013v-.001a.5.5 0 0 1 .12-.512l3.536-3.535a.5.5 0 0 1 .532-.115l.096.022c.087.017.208.034.344.034.114 0 .23-.011.343-.04L9.927 2.028c-.029.113-.04.23-.04.343a1.779 1.779 0 0 0 .062.46z" />
                                                </svg></span>
                                        <?php endif; ?>
                                        <?php if ($thread['is_locked']): ?>
                                            <span class="badge badge-locked">Locked</span>
                                        <?php endif; ?>
                                        <span class="badge badge-category"><?php echo htmlspecialchars($thread['category_name']); ?></span>
                                    </div>
                                </div>

                                <h3 class="thread-title">
                                    <?php echo htmlspecialchars($thread['title']); ?>
                                </h3>

                                <div class="thread-meta">
                                    <span class="thread-author">oleh <?php echo htmlspecialchars($thread['author_name']); ?></span>
                                    <span class="thread-date"><?php echo date('d M Y, H:i', strtotime($thread['created_at'])); ?></span>
                                    <?php if ($thread['last_reply_at']): ?>
                                        <span class="last-reply">
                                            Balasan terakhir oleh <?php echo htmlspecialchars($thread['last_reply_author']); ?>
                                            pada <?php echo date('d M Y, H:i', strtotime($thread['last_reply_at'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="thread-stats">
                                <div class="stat-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <span><?php echo number_format($thread['views_count']); ?></span>
                                </div>
                                <div class="stat-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                    </svg>
                                    <span><?php echo number_format($thread['replies_count']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <h3>Tidak ada thread ditemukan</h3>
                        <p>Coba ubah filter pencarian atau buat thread baru</p>
                        <a href="new-thread.php" class="btn btn-primary">Buat Thread Baru</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="#" class="pagination-btn" data-page="<?php echo $page - 1; ?>">← Sebelumnya</a>
                    <?php endif; ?>

                    <div class="pagination-info">
                        Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                    </div>

                    <?php if ($page < $total_pages): ?>
                        <a href="#" class="pagination-btn" data-page="<?php echo $page + 1; ?>">Selanjutnya →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/global.js"></script>
    <script src="assets/js/navbar.js"></script>
    <script src="assets/js/pages/forum.js"></script>
</body>

</html>