<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user data for profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get categories with question counts
$stmt = $pdo->query("
    SELECT qc.*, COUNT(q.id) as question_count
    FROM question_categories qc
    LEFT JOIN question_topics qt ON qc.id = qt.category_id
    LEFT JOIN questions q ON qt.id = q.topic_id AND q.is_active = 1
    GROUP BY qc.id
    ORDER BY qc.type, qc.name
");
$categories = $stmt->fetchAll();

// Group by type
$tps_categories = [];
$literasi_categories = [];

foreach ($categories as $category) {
    if ($category['type'] == 'TPS') {
        $tps_categories[] = $category;
    } else {
        $literasi_categories[] = $category;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drilling Soal - Drill PTN</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Base Styles -->
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
    
    <!-- External CSS Files -->
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/pages/drilling.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Drilling Soal UTBK</h1>
            <p>Pilih kategori soal yang ingin kamu latih dan tingkatkan kemampuanmu</p>
        </div>

        <!-- TPS Section -->
        <?php if (!empty($tps_categories)): ?>
        <section class="category-section" id="tpsSection">
            <div class="section-header" onclick="toggleSection('tpsSection')">
                <div class="section-header-content">
                    <h2 class="section-title">Tes Potensi Skolastik (TPS)</h2>
                    <div class="section-toggle">
                        <span class="section-count"><?php echo count($tps_categories); ?> kategori</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="categories-list">
                <?php foreach ($tps_categories as $category): ?>
                <div class="category-item">
                    <div class="category-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                    
                    <div class="category-main">
                        <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                    </div>
                    
                    <div class="category-meta">
                        <div class="question-count">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                            </svg>
                            <?php echo $category['question_count']; ?> soal
                        </div>
                        
                        <?php if ($category['question_count'] > 0): ?>
                            <div class="status-ready">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22,4 12,14.01 9,11.01"></polyline>
                                </svg>
                                Siap dilatih
                            </div>
                        <?php else: ?>
                            <div class="status-empty">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                </svg>
                                Belum tersedia
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="category-action">
                        <?php if ($category['question_count'] > 0): ?>
                            <a href="start-drilling.php?category_id=<?php echo $category['id']; ?>" 
                               class="btn btn-primary">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="5,3 19,12 5,21"></polygon>
                                </svg>
                                Mulai Drilling
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                </svg>
                                Tidak Tersedia
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Literasi Section -->
        <?php if (!empty($literasi_categories)): ?>
        <section class="category-section literasi-section" id="literasiSection">
            <div class="section-header" onclick="toggleSection('literasiSection')">
                <div class="section-header-content">
                    <h2 class="section-title">Tes Literasi</h2>
                    <div class="section-toggle">
                        <span class="section-count"><?php echo count($literasi_categories); ?> kategori</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="categories-list">
                <?php foreach ($literasi_categories as $category): ?>
                <div class="category-item">
                    <div class="category-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                        </svg>
                    </div>
                    
                    <div class="category-main">
                        <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                    </div>
                    
                    <div class="category-meta">
                        <div class="question-count">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                            </svg>
                            <?php echo $category['question_count']; ?> soal
                        </div>
                        
                        <?php if ($category['question_count'] > 0): ?>
                            <div class="status-ready">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22,4 12,14.01 9,11.01"></polyline>
                                </svg>
                                Siap dilatih
                            </div>
                        <?php else: ?>
                            <div class="status-empty">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                </svg>
                                Belum tersedia
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="category-action">
                        <?php if ($category['question_count'] > 0): ?>
                            <a href="start-drilling.php?category_id=<?php echo $category['id']; ?>" 
                               class="btn btn-primary">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="5,3 19,12 5,21"></polygon>
                                </svg>
                                Mulai Drilling
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                </svg>
                                Tidak Tersedia
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?> 

        <!-- Empty State -->
        <?php if (empty($tps_categories) && empty($literasi_categories)): ?>
        <div class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
            <h3>Belum Ada Kategori Soal</h3>
            <p>Admin belum menambahkan kategori soal. Silakan hubungi admin untuk informasi lebih lanjut.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- External JavaScript Files -->
    <script src="assets/js/navbar.js"></script>
    <script src="assets/js/pages/drilling.js"></script>
</body>
</html>