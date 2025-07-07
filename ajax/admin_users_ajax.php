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
        case 'search_users':
            $search = isset($input['search']) ? sanitize($input['search']) : '';
            $status_filter = isset($input['status']) ? sanitize($input['status']) : '';
            $grade_filter = isset($input['grade']) ? sanitize($input['grade']) : '';
            $page = isset($input['page']) ? max(1, (int)$input['page']) : 1;
            $per_page = 20;
            $offset = ($page - 1) * $per_page;

            // Build WHERE clause
            $where_conditions = ["role_id = 1"];
            $params = [];

            if ($search) {
                $where_conditions[] = "(full_name LIKE ? OR username LIKE ? OR email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            if ($status_filter !== '') {
                $where_conditions[] = "is_active = ?";
                $params[] = (int)$status_filter;
            }

            if ($grade_filter) {
                $where_conditions[] = "grade = ?";
                $params[] = $grade_filter;
            }

            $where_clause = "WHERE " . implode(" AND ", $where_conditions);

            // Get users
            $stmt = $pdo->prepare("
                SELECT u.*, 
                       (SELECT COUNT(*) FROM user_sessions us WHERE us.user_id = u.id AND us.is_completed = 1) as completed_sessions,
                       (SELECT COUNT(*) FROM user_answers ua WHERE ua.user_id = u.id AND ua.is_correct = 1) as correct_answers
                FROM users u
                $where_clause
                ORDER BY u.created_at DESC
                LIMIT $offset, $per_page
            ");

            $stmt->execute($params);
            $users = $stmt->fetchAll();

            // Get total count
            $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users u $where_clause");
            $count_stmt->execute($params);
            $total_users = $count_stmt->fetch()['total'];
            $total_pages = ceil($total_users / $per_page);

            // Generate HTML
            $html = generateUsersTableHTML($users);
            $pagination = generatePaginationHTML($page, $total_pages, $total_users);

            echo json_encode([
                'success' => true,
                'html' => $html,
                'pagination' => $pagination,
                'total_users' => $total_users,
                'total_pages' => $total_pages,
                'current_page' => $page
            ]);
            break;

        case 'toggle_status':
            $user_id = (int)$input['user_id'];

            // Get current status first
            $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ? AND role_id = 1");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user) {
                $new_status = $user['is_active'] ? 0 : 1;
                $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role_id = 1");
                if ($stmt->execute([$new_status, $user_id])) {
                    echo json_encode([
                        'success' => true,
                        'message' => $new_status ? 'User berhasil diaktifkan' : 'User berhasil dinonaktifkan',
                        'new_status' => $new_status
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Gagal mengubah status user']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'User tidak ditemukan']);
            }
            break;

        case 'reset_password':
            $user_id = (int)$input['user_id'];
            $new_password = password_hash('password123', PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role_id = 1");
            if ($stmt->execute([$new_password, $user_id])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Password berhasil direset ke "password123"'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Gagal mereset password']);
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

function generateUsersTableHTML($users)
{
    if (empty($users)) {
        return '
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
                <h3>No Users Found</h3>
                <p>Tidak ada user yang sesuai dengan filter pencarian</p>
            </div>';
    }

    $html = '
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Sekolah</th>
                    <th>Kelas</th>
                    <th>Poin</th>
                    <th>Sesi</th>
                    <th>Akurasi</th>
                    <th>Status</th>
                    <th>Bergabung</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($users as $user) {
        $accuracy = $user['total_questions_answered'] > 0 ?
            round(($user['correct_answers'] / $user['total_questions_answered']) * 100, 1) : 0;
        $badge = getUserBadge($user['points']);

        $html .= '
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="user-avatar">
                                ' . strtoupper(substr($user['full_name'], 0, 2)) . '
                            </div>
                            <div class="user-details">
                                <div class="user-name">' . htmlspecialchars($user['full_name']) . '</div>
                                <div class="user-username">@' . htmlspecialchars($user['username']) . '</div>
                            </div>
                        </div>
                    </td>
                    <td>' . htmlspecialchars($user['email']) . '</td>
                    <td>' . ($user['school_name'] ? htmlspecialchars($user['school_name']) : '-') . '</td>
                    <td>' . ($user['grade'] ? 'Kelas ' . $user['grade'] : '-') . '</td>
                    <td>
                        <div class="points-display">
                            <span class="points-number">' . number_format($user['points']) . '</span>
                            <span class="badge-mini" style="background-color: ' . $badge['color'] . '">
                                ' . $badge['name'] . '
                            </span>
                        </div>
                    </td>
                    <td>' . number_format($user['completed_sessions']) . '</td>
                    <td>' . $accuracy . '%</td>
                    <td>
                        <span class="status-badge ' . ($user['is_active'] ? 'active' : 'inactive') . '">
                            ' . ($user['is_active'] ? 'Aktif' : 'Nonaktif') . '
                        </span>
                    </td>
                    <td>' . date('d M Y', strtotime($user['created_at'])) . '</td>
                    <td>
                        <div class="action-buttons">
                            <!-- Toggle Status -->
                            <button type="button" class="btn-action toggle ajax-action" 
                                data-action="toggle_status" 
                                data-user-id="' . $user['id'] . '"
                                title="' . ($user['is_active'] ? 'Nonaktifkan' : 'Aktifkan') . '">
                                ' . ($user['is_active'] ?
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>' :
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                        <line x1="1" y1="1" x2="23" y2="23" />
                                    </svg>') . '
                            </button>

                            <!-- Edit User -->
                            <button type="button" class="btn-action edit" title="Edit User"
                                onclick="openEditModal(' . htmlspecialchars(json_encode($user)) . ')">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z" />
                                </svg>
                            </button>

                            <!-- Reset Password -->
                            <button type="button" class="btn-action reset ajax-action" 
                                data-action="reset_password" 
                                data-user-id="' . $user['id'] . '"
                                title="Reset Password">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
                                    <path d="M21 3v5h-5" />
                                    <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
                                    <path d="M8 16H3v5" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>';
    }

    $html .= '
            </tbody>
        </table>';

    return $html;
}

function generatePaginationHTML($page, $total_pages, $total_users)
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
                (' . number_format($total_users) . ' total users)
              </div>';

    if ($page < $total_pages) {
        $html .= '<button class="pagination-btn" onclick="loadPage(' . ($page + 1) . ')">Next →</button>';
    } else {
        $html .= '<span></span>';
    }

    $html .= '</div>';
    return $html;
}
