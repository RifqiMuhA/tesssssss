<?php
require_once '../config.php';

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'search_forum':
            $search = isset($input['search']) ? sanitize($input['search']) : '';
            $category_filter = isset($input['category']) ? (int)$input['category'] : 0;
            $view = isset($input['view']) ? sanitize($input['view']) : 'threads';
            $page = isset($input['page']) ? max(1, (int)$input['page']) : 1;
            $per_page = 20;
            $offset = ($page - 1) * $per_page;

            if ($view == 'threads') {
                // Get threads
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

                $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

                $stmt = $pdo->prepare("
                    SELECT ft.*, fc.name as category_name, u.full_name as author_name
                    FROM forum_threads ft
                    JOIN forum_categories fc ON ft.category_id = fc.id
                    JOIN users u ON ft.user_id = u.id
                    $where_clause
                    ORDER BY ft.is_pinned DESC, ft.created_at DESC
                    LIMIT $offset, $per_page
                ");

                $stmt->execute($params);
                $items = $stmt->fetchAll();

                // Get total count
                $count_stmt = $pdo->prepare("
                    SELECT COUNT(*) as total 
                    FROM forum_threads ft
                    JOIN forum_categories fc ON ft.category_id = fc.id
                    JOIN users u ON ft.user_id = u.id
                    $where_clause
                ");
                $count_stmt->execute($params);
                $total_items = $count_stmt->fetch()['total'];
            } else {
                // Get posts
                $where_conditions = [];
                $params = [];

                if ($search) {
                    $where_conditions[] = "fp.content LIKE ?";
                    $params[] = "%$search%";
                }

                if ($category_filter) {
                    $where_conditions[] = "fc.id = ?";
                    $params[] = $category_filter;
                }

                $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

                $stmt = $pdo->prepare("
                    SELECT fp.*, ft.title as thread_title, fc.name as category_name, u.full_name as author_name
                    FROM forum_posts fp
                    JOIN forum_threads ft ON fp.thread_id = ft.id
                    JOIN forum_categories fc ON ft.category_id = fc.id
                    JOIN users u ON fp.user_id = u.id
                    $where_clause
                    ORDER BY fp.created_at DESC
                    LIMIT $offset, $per_page
                ");

                $stmt->execute($params);
                $items = $stmt->fetchAll();

                // Get total count
                $count_stmt = $pdo->prepare("
                    SELECT COUNT(*) as total 
                    FROM forum_posts fp
                    JOIN forum_threads ft ON fp.thread_id = ft.id
                    JOIN forum_categories fc ON ft.category_id = fc.id
                    JOIN users u ON fp.user_id = u.id
                    $where_clause
                ");
                $count_stmt->execute($params);
                $total_items = $count_stmt->fetch()['total'];
            }

            $total_pages = ceil($total_items / $per_page);

            // Generate HTML
            $html = generateForumListHTML($items, $view);
            $pagination = generatePaginationHTML($page, $total_pages, $total_items, $view);

            echo json_encode([
                'success' => true,
                'html' => $html,
                'pagination' => $pagination,
                'total_items' => $total_items,
                'total_pages' => $total_pages,
                'current_page' => $page
            ]);
            break;

        case 'pin_thread':
            $thread_id = (int)$input['thread_id'];

            // Get current status first
            $stmt = $pdo->prepare("SELECT is_pinned FROM forum_threads WHERE id = ?");
            $stmt->execute([$thread_id]);
            $thread = $stmt->fetch();

            if ($thread) {
                $new_status = $thread['is_pinned'] ? 0 : 1;
                $stmt = $pdo->prepare("UPDATE forum_threads SET is_pinned = ? WHERE id = ?");
                if ($stmt->execute([$new_status, $thread_id])) {
                    echo json_encode([
                        'success' => true,
                        'message' => $new_status ? 'Thread berhasil di-pin' : 'Thread berhasil di-unpin',
                        'new_status' => $new_status
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Gagal mengubah status pin thread']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Thread tidak ditemukan']);
            }
            break;

        case 'lock_thread':
            $thread_id = (int)$input['thread_id'];

            // Get current status first
            $stmt = $pdo->prepare("SELECT is_locked FROM forum_threads WHERE id = ?");
            $stmt->execute([$thread_id]);
            $thread = $stmt->fetch();

            if ($thread) {
                $new_status = $thread['is_locked'] ? 0 : 1;
                $stmt = $pdo->prepare("UPDATE forum_threads SET is_locked = ? WHERE id = ?");
                if ($stmt->execute([$new_status, $thread_id])) {
                    echo json_encode([
                        'success' => true,
                        'message' => $new_status ? 'Thread berhasil dikunci' : 'Thread berhasil dibuka',
                        'new_status' => $new_status
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Gagal mengubah status lock thread']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Thread tidak ditemukan']);
            }
            break;

        case 'toggle_thread_status':
            $thread_id = (int)$input['thread_id'];

            // Get current status first
            $stmt = $pdo->prepare("SELECT is_active FROM forum_threads WHERE id = ?");
            $stmt->execute([$thread_id]);
            $thread = $stmt->fetch();

            if ($thread) {
                $new_status = $thread['is_active'] ? 0 : 1;
                $stmt = $pdo->prepare("UPDATE forum_threads SET is_active = ? WHERE id = ?");
                if ($stmt->execute([$new_status, $thread_id])) {
                    echo json_encode([
                        'success' => true,
                        'message' => $new_status ? 'Thread berhasil diaktifkan' : 'Thread berhasil dinonaktifkan',
                        'new_status' => $new_status
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Gagal mengubah status thread']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Thread tidak ditemukan']);
            }
            break;

        case 'toggle_post_status':
            $post_id = (int)$input['post_id'];

            // Get current status first
            $stmt = $pdo->prepare("SELECT is_active FROM forum_posts WHERE id = ?");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch();

            if ($post) {
                $new_status = $post['is_active'] ? 0 : 1;
                $stmt = $pdo->prepare("UPDATE forum_posts SET is_active = ? WHERE id = ?");
                if ($stmt->execute([$new_status, $post_id])) {
                    echo json_encode([
                        'success' => true,
                        'message' => $new_status ? 'Post berhasil diaktifkan' : 'Post berhasil dinonaktifkan',
                        'new_status' => $new_status
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Gagal mengubah status post']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Post tidak ditemukan']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

function generateForumListHTML($items, $view)
{
    if (empty($items)) {
        return '
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                </svg>
                <h3>No ' . ucfirst($view) . ' Found</h3>
                <p>Tidak ada ' . $view . ' yang sesuai dengan filter pencarian</p>
            </div>';
    }

    $html = '';

    if ($view == 'threads') {
        foreach ($items as $thread) {
            $html .= generateThreadItemHTML($thread);
        }
    } else {
        foreach ($items as $post) {
            $html .= generatePostItemHTML($post);
        }
    }

    return $html;
}

function generateThreadItemHTML($thread)
{
    $badges = '';
    if ($thread['is_pinned']) {
        $badges .= '<span class="badge badge-pinned">Pinned</span>';
    }
    if ($thread['is_locked']) {
        $badges .= '<span class="badge badge-locked">Locked</span>';
    }
    if (isset($thread['is_active']) && !$thread['is_active']) {
        $badges .= '<span class="badge badge-inactive">Inactive</span>';
    }
    $badges .= '<span class="badge badge-category">' . htmlspecialchars($thread['category_name']) . '</span>';

    return '
        <div class="forum-item thread-item" data-id="' . $thread['id'] . '">
            <div class="item-header">
                <div class="item-badges">
                    ' . $badges . '
                </div>

                <div class="item-actions">
                    <button type="button" class="btn-action pin ajax-action" 
                        data-action="pin_thread" 
                        data-thread-id="' . $thread['id'] . '"
                        title="' . ($thread['is_pinned'] ? 'Unpin' : 'Pin') . '">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pin-angle" viewBox="0 0 16 16"> <path d="M9.828.722a.5.5 0 0 1 .354.146l4.95 4.95a.5.5 0 0 1 0 .707c-.48.48-1.072.588-1.503.588-.177 0-.335-.018-.46-.039l-3.134 3.134a5.927 5.927 0 0 1 .16 1.013c.046.702-.032 1.687-.72 2.375a.5.5 0 0 1-.707 0l-2.829-2.828-3.182 3.182c-.195.195-1.219.902-1.414.707-.195-.195.512-1.22.707-1.414l3.182-3.182-2.828-2.829a.5.5 0 0 1 0-.707c.688-.688 1.673-.767 2.375-.72a5.922 5.922 0 0 1 1.013.16l3.134-3.133a2.772 2.772 0 0 1-.04-.461c0-.43.108-1.022.589-1.503a.5.5 0 0 1 .353-.146zm.122 2.112v-.002.002zm0-.002v.002a.5.5 0 0 1-.122.51L6.293 6.878a.5.5 0 0 1-.511.12H5.78l-.014-.004a4.507 4.507 0 0 0-.288-.076 4.922 4.922 0 0 0-.765-.116c-.422-.028-.836.008-1.175.15l5.51 5.509c.141-.34.177-.753.149-1.175a4.924 4.924 0 0 0-.192-1.054l-.004-.013v-.001a.5.5 0 0 1 .12-.512l3.536-3.535a.5.5 0 0 1 .532-.115l.096.022c.087.017.208.034.344.034.114 0 .23-.011.343-.04L9.927 2.028c-.029.113-.04.23-.04.343a1.779 1.779 0 0 0 .062.46z"/> </svg>
                    </button>

                    <button type="button" class="btn-action lock ajax-action" 
                        data-action="lock_thread" 
                        data-thread-id="' . $thread['id'] . '"
                        title="' . ($thread['is_locked'] ? 'Unlock' : 'Lock') . '">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                            <circle cx="12" cy="16" r="1" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                    </button>

                    <button type="button" class="btn-action toggle ajax-action" 
                        data-action="toggle_thread_status" 
                        data-thread-id="' . $thread['id'] . '"
                        title="' . (($thread['is_active'] ?? 1) ? 'Nonaktifkan' : 'Aktifkan') . '">
                        ' . (($thread['is_active'] ?? 1) ?
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>' :
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                <line x1="1" y1="1" x2="23" y2="23" />
                            </svg>') . '
                    </button>

                    <a href="../thread.php?id=' . $thread['id'] . '" class="btn-action view" title="View Thread">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="item-content">
                <h3 class="item-title">' . htmlspecialchars($thread['title']) . '</h3>
                <div class="item-excerpt">
                    ' . htmlspecialchars(substr($thread['content'], 0, 200)) . '...
                </div>
                <div class="item-meta">
                    <span>by ' . htmlspecialchars($thread['author_name']) . '</span>
                    <span>•</span>
                    <span>' . date('d M Y, H:i', strtotime($thread['created_at'])) . '</span>
                    <span>•</span>
                    <span>' . number_format($thread['views_count']) . ' views</span>
                    <span>•</span>
                    <span>' . number_format($thread['replies_count']) . ' replies</span>
                </div>
            </div>
        </div>';
}

function generatePostItemHTML($post)
{
    $badges = '';
    if (isset($post['is_solution']) && $post['is_solution']) {
        $badges .= '<span class="badge badge-solution">Solution</span>';
    }
    if (isset($post['is_active']) && !$post['is_active']) {
        $badges .= '<span class="badge badge-inactive">Inactive</span>';
    }
    $badges .= '<span class="badge badge-category">' . htmlspecialchars($post['category_name']) . '</span>';

    return '
        <div class="forum-item post-item" data-id="' . $post['id'] . '">
            <div class="item-header">
                <div class="item-badges">
                    ' . $badges . '
                </div>

                <div class="item-actions">
                    <button type="button" class="btn-action toggle ajax-action" 
                        data-action="toggle_post_status" 
                        data-post-id="' . $post['id'] . '"
                        title="' . (($post['is_active'] ?? 1) ? 'Nonaktifkan' : 'Aktifkan') . '">
                        ' . (($post['is_active'] ?? 1) ?
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>' :
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                <line x1="1" y1="1" x2="23" y2="23" />
                            </svg>') . '
                    </button>

                    <a href="../thread.php?id=' . $post['thread_id'] . '" class="btn-action view" title="View Thread">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="item-content">
                <h4 class="item-thread">
                    <a href="../thread.php?id=' . $post['thread_id'] . '">
                        ' . htmlspecialchars($post['thread_title']) . '
                    </a>
                </h4>
                <div class="item-excerpt">
                    ' . htmlspecialchars(substr($post['content'], 0, 300)) . '...
                </div>
                <div class="item-meta">
                    <span>by ' . htmlspecialchars($post['author_name']) . '</span>
                    <span>•</span>
                    <span>' . date('d M Y, H:i', strtotime($post['created_at'])) . '</span>
                    <span>•</span>
                    <span>' . number_format($post['likes_count'] ?? 0) . ' likes</span>
                </div>
            </div>
        </div>';
}

function generatePaginationHTML($page, $total_pages, $total_items, $view)
{
    if ($total_pages <= 1) return '';

    $html = '<div class="admin-pagination">';

    if ($page > 1) {
        $html .= '<button class="pagination-btn" onclick="loadPage(' . ($page - 1) . ')">← Previous</button>';
    } else {
        $html .= '<span></span>';
    }

    $html .= '<div class="pagination-info">
                Page ' . $page . ' of ' . $total_pages . '
                (' . number_format($total_items) . ' total ' . $view . ')
              </div>';

    if ($page < $total_pages) {
        $html .= '<button class="pagination-btn" onclick="loadPage(' . ($page + 1) . ')">Next →</button>';
    } else {
        $html .= '<span></span>';
    }

    $html .= '</div>';
    return $html;
}
