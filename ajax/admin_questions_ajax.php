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
        case 'search_questions':
            $search = isset($input['search']) ? sanitize($input['search']) : '';
            $category_filter = isset($input['category']) ? (int)$input['category'] : 0;
            $topic_filter = isset($input['topic']) ? (int)$input['topic'] : 0;
            $status_filter = isset($input['status']) ? sanitize($input['status']) : '';
            $page = isset($input['page']) ? max(1, (int)$input['page']) : 1;
            $per_page = 15;
            $offset = ($page - 1) * $per_page;

            // Build WHERE clause
            $where_conditions = [];
            $params = [];

            if ($search) {
                $where_conditions[] = "q.question_text LIKE ?";
                $params[] = "%$search%";
            }

            if ($category_filter) {
                $where_conditions[] = "qc.id = ?";
                $params[] = $category_filter;
            }

            if ($topic_filter) {
                $where_conditions[] = "qt.id = ?";
                $params[] = $topic_filter;
            }

            if ($status_filter !== '') {
                $where_conditions[] = "q.is_active = ?";
                $params[] = (int)$status_filter;
            }

            $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

            // Get questions
            $stmt = $pdo->prepare("
                SELECT q.*, qt.name as topic_name, qc.name as category_name, qc.type as category_type,
                       u.full_name as created_by_name
                FROM questions q
                JOIN question_topics qt ON q.topic_id = qt.id
                JOIN question_categories qc ON qt.category_id = qc.id
                LEFT JOIN users u ON q.created_by = u.id
                $where_clause
                ORDER BY q.created_at DESC
                LIMIT $offset, $per_page
            ");

            $stmt->execute($params);
            $questions = $stmt->fetchAll();

            // Get total count
            $count_stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM questions q
                JOIN question_topics qt ON q.topic_id = qt.id
                JOIN question_categories qc ON qt.category_id = qc.id
                $where_clause
            ");
            $count_stmt->execute($params);
            $total_questions = $count_stmt->fetch()['total'];
            $total_pages = ceil($total_questions / $per_page);

            // Generate HTML
            $html = generateQuestionsListHTML($questions);
            $pagination = generatePaginationHTML($page, $total_pages, $total_questions);

            echo json_encode([
                'success' => true,
                'html' => $html,
                'pagination' => $pagination,
                'total_questions' => $total_questions,
                'total_pages' => $total_pages,
                'current_page' => $page
            ]);
            break;

        case 'toggle_status':
            $question_id = (int)$input['question_id'];

            // Get current status first
            $stmt = $pdo->prepare("SELECT is_active FROM questions WHERE id = ?");
            $stmt->execute([$question_id]);
            $question = $stmt->fetch();

            if ($question) {
                $new_status = $question['is_active'] ? 0 : 1;
                $stmt = $pdo->prepare("UPDATE questions SET is_active = ? WHERE id = ?");
                if ($stmt->execute([$new_status, $question_id])) {
                    echo json_encode([
                        'success' => true,
                        'message' => $new_status ? 'Soal berhasil diaktifkan' : 'Soal berhasil dinonaktifkan',
                        'new_status' => $new_status
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Gagal mengubah status soal']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Soal tidak ditemukan']);
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

function generateQuestionsListHTML($questions)
{
    if (empty($questions)) {
        return '
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3" />
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                </svg>
                <h3>No Questions Found</h3>
                <p>Tidak ada soal yang sesuai dengan filter pencarian</p>
                <a href="?action=add" class="btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    Add First Question
                </a>
            </div>';
    }

    $html = '';
    foreach ($questions as $question) {
        $html .= generateQuestionCardHTML($question);
    }

    return $html;
}

function generateQuestionCardHTML($question)
{
    $options = ['A', 'B', 'C', 'D', 'E'];
    $optionsHtml = '';

    foreach ($options as $option) {
        $option_text = $question['option_' . strtolower($option)];
        $is_correct = ($option == $question['correct_answer']);

        $optionsHtml .= '
            <div class="option-item ' . ($is_correct ? 'correct' : '') . '">
                <span class="option-letter">' . $option . '</span>
                <span class="option-text">' . htmlspecialchars($option_text) . '</span>
                ' . ($is_correct ? '
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20,6 9,17 4,12" />
                    </svg>
                ' : '') . '
            </div>';
    }

    $imageHtml = '';
    if (!empty($question['question_image']) && file_exists("../resources/img/questions/" . $question['question_image'])) {
        $imageHtml = '
            <div class="question-image-container">
                <img src="../resources/img/questions/' . htmlspecialchars($question['question_image']) . '"
                    alt="Gambar Soal"
                    class="question-image"
                    onclick="openImageModal(this.src)"
                    title="Klik untuk memperbesar">
            </div>';
    } elseif (!empty($question['question_image'])) {
        $imageHtml = '
            <div class="question-image-error">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m21 21-6-6m6 6v-4.8m0 4.8h-4.8" />
                    <path d="M3 16.2V21m0 0h4.8M3 21l6-6" />
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                </svg>
                <span>❌ Gambar tidak ditemukan: ' . htmlspecialchars($question['question_image']) . '</span>
            </div>';
    }

    $explanationHtml = '';
    if ($question['explanation']) {
        $explanationHtml = '
            <div class="question-explanation">
                <strong>Explanation:</strong>
                ' . nl2br(htmlspecialchars($question['explanation'])) . '
            </div>';
    }

    return '
        <div class="question-card">
            <div class="question-header">
                <div class="question-meta">
                    <span class="category-badge">
                        ' . htmlspecialchars($question['category_name']) . '
                    </span>
                    <span class="topic-badge">' . htmlspecialchars($question['topic_name']) . '</span>
                    <span class="points-badge">' . $question['points'] . ' pts</span>
                    <span class="status-badge ' . ($question['is_active'] ? 'active' : 'inactive') . '">
                        ' . ($question['is_active'] ? 'Active' : 'Inactive') . '
                    </span>
                </div>

                <div class="question-actions">
                    <button type="button" class="btn-action toggle ajax-action" 
                        data-action="toggle_status" 
                        data-question-id="' . $question['id'] . '"
                        title="' . ($question['is_active'] ? 'Nonaktifkan' : 'Aktifkan') . '">
                        ' . ($question['is_active'] ?
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>' :
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                <line x1="1" y1="1" x2="23" y2="23" />
                            </svg>') . '
                    </button>
                </div>
            </div>

            <div class="question-content">
                ' . $imageHtml . '
                <div class="question-text">
                    ' . nl2br(htmlspecialchars($question['question_text'])) . '
                </div>

                <div class="question-options">
                    ' . $optionsHtml . '
                </div>

                ' . $explanationHtml . '
            </div>

            <div class="question-footer">
                <div class="question-info">
                    Created by ' . htmlspecialchars($question['created_by_name'] ?? 'Unknown') . '
                    on ' . date('d M Y', strtotime($question['created_at'])) . '
                </div>
            </div>
        </div>';
}

function generatePaginationHTML($page, $total_pages, $total_questions)
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
                (' . number_format($total_questions) . ' total questions)
              </div>';

    if ($page < $total_pages) {
        $html .= '<button class="pagination-btn" onclick="loadPage(' . ($page + 1) . ')">Next →</button>';
    } else {
        $html .= '<span></span>';
    }

    $html .= '</div>';
    return $html;
}
