<?php
require_once '../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$post_id = (int)$_POST['post_id'];
$user_id = $_SESSION['user_id'];

if (!$post_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid post ID']);
    exit;
}

try {
    // Check if already liked
    $stmt = $pdo->prepare("
        SELECT id FROM post_likes 
        WHERE post_id = ? AND user_id = ?
    ");
    $stmt->execute([$post_id, $user_id]);
    $existing_like = $stmt->fetch();

    if ($existing_like) {
        // Unlike
        $stmt = $pdo->prepare("
            DELETE FROM post_likes 
            WHERE post_id = ? AND user_id = ?
        ");
        $stmt->execute([$post_id, $user_id]);
        $liked = false;
    } else {
        // Like
        $stmt = $pdo->prepare("
            INSERT INTO post_likes (post_id, user_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$post_id, $user_id]);
        $liked = true;
    }

    // Get updated like count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM post_likes WHERE post_id = ?
    ");
    $stmt->execute([$post_id]);
    $like_count = $stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'count' => $like_count
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
