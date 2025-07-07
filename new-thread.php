<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get forum categories
$stmt = $pdo->query("SELECT * FROM forum_categories ORDER BY name");
$categories = $stmt->fetchAll();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = (int)$_POST['category_id'];
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    
    // Validation
    if (empty($title) || empty($content) || !$category_id) {
        $error = 'Semua field harus diisi';
    } elseif (strlen($title) < 5) {
        $error = 'Judul minimal 5 karakter';
    } elseif (strlen($content) < 10) {
        $error = 'Konten minimal 10 karakter';
    } else {
        // Check if category exists
        $stmt = $pdo->prepare("SELECT id FROM forum_categories WHERE id = ?");
        $stmt->execute([$category_id]);
        
        if (!$stmt->fetch()) {
            $error = 'Kategori tidak valid';
        } else {
            // Insert new thread
            $stmt = $pdo->prepare("
                INSERT INTO forum_threads (category_id, user_id, title, content)
                VALUES (?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$category_id, $_SESSION['user_id'], $title, $content])) {
                $thread_id = $pdo->lastInsertId();
                redirect("thread.php?id=$thread_id");
            } else {
                $error = 'Terjadi kesalahan saat membuat thread';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Thread Baru - Forum - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/pages/new-thread.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="index.php">
                    <h1><?php echo SITE_NAME; ?></h1>
                </a>
            </div>
            
            <div class="nav-menu">
                <a href="index.php" class="nav-link">Beranda</a>
                <a href="drilling.php" class="nav-link">Drilling Soal</a>
                <a href="forum.php" class="nav-link active">Forum</a>
                <a href="leaderboard.php" class="nav-link">Leaderboard</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <?php if (isAdmin()): ?>
                    <a href="admin/" class="nav-link admin-link">Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="nav-link logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div class="breadcrumb">
                <a href="forum.php">Forum</a>
                <span class="separator">›</span>
                <span class="current">Buat Thread Baru</span>
            </div>
            <h1>Buat Thread Baru</h1>
            <p>Mulai diskusi baru dengan komunitas DrillPTN</p>
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

        <div class="thread-form-container">
            <form method="POST" class="thread-form">
                <div class="form-group">
                    <label for="category_id">Kategori *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Pilih kategori yang sesuai dengan topik diskusi Anda</small>
                </div>

                <div class="form-group">
                    <label for="title">Judul Thread *</label>
                    <input type="text" id="title" name="title" required 
                           placeholder="Masukkan judul yang menarik dan deskriptif"
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    <small>Minimal 5 karakter. Gunakan judul yang jelas dan mudah dipahami.</small>
                </div>

                <div class="form-group">
                    <label for="content">Konten *</label>
                    <textarea id="content" name="content" required rows="10" 
                              placeholder="Tulis pertanyaan, diskusi, atau topik yang ingin Anda bahas..."><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                    <small>Minimal 10 karakter. Jelaskan topik Anda dengan detail agar mendapat respon yang baik.</small>
                </div>

                <div class="form-actions">
                    <a href="forum.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Buat Thread</button>
                </div>
            </form>

            <!-- Guidelines -->
            <div class="guidelines">
                <h3>Panduan Membuat Thread</h3>
                <ul>
                    <li>Gunakan judul yang jelas dan deskriptif</li>
                    <li>Pilih kategori yang sesuai dengan topik</li>
                    <li>Jelaskan pertanyaan atau topik dengan detail</li>
                    <li>Gunakan bahasa yang sopan dan mudah dipahami</li>
                    <li>Cari terlebih dahulu apakah topik serupa sudah ada</li>
                    <li>Sertakan konteks yang cukup untuk membantu orang lain memahami</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="assets/js/global.js"></script>
    <script src="assets/js/pages/new-thread.js"></script>
</body>
</html>
