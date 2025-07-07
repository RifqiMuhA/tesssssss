<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '@kaesquare123');
define('DB_NAME', 'projec15_drillptn_utbk');

// Site Configuration
define('SITE_NAME', 'DrillPTN');
define('SITE_URL', 'http://lemario.project2ks2.my.id/drillptn');
define('UPLOAD_PATH', 'resources/img/');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
session_start();

// Database Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
}

// Helper function to redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Helper function to sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Helper function to get user badge
function getUserBadge($points) {
    if ($points >= 1500) return ['name' => 'Platinum', 'color' => '#E5E7EB'];
    if ($points >= 1001) return ['name' => 'Gold', 'color' => '#FCD34D'];
    if ($points >= 201) return ['name' => 'Silver', 'color' => '#D1D5DB'];
    return ['name' => 'Bronze', 'color' => '#F59E0B'];
}

// Helper untuk mengupdate user stats terbaru
function updateUserStats($user_id, $pdo)
{
    try {
        // Calculate actual stats from user_answers
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_answered,
                SUM(points_earned) as total_points
            FROM user_answers 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch();

        // Update user table with actual data
        $stmt = $pdo->prepare("
            UPDATE users 
            SET total_questions_answered = ?, 
                points = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $stats['total_answered'] ?: 0,
            $stats['total_points'] ?: 0,
            $user_id
        ]);

        return true;
    } catch (Exception $e) {
        error_log("Error updating user stats: " . $e->getMessage());
        return false;
    }
}
?>
