<?php
require_once 'config.php';

if(isLoggedIn()) {
    redirect('drilling.php');
}

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role_id = 1");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_questions FROM questions WHERE is_active = 1");
$total_questions = $stmt->fetch()['total_questions'];

$stmt = $pdo->query("SELECT COUNT(*) as total_forums FROM forum_threads");
$total_forums = $stmt->fetch()['total_forums'];

$stmt = $pdo->query("SELECT COUNT(*) as total_replies FROM forum_posts");
$total_replies = $stmt->fetch()['total_replies'];

// Get recent forum threads
$stmt = $pdo->query("
    SELECT ft.title, ft.created_at, u.full_name 
    FROM forum_threads ft 
    JOIN users u ON ft.user_id = u.id 
    ORDER BY ft.created_at DESC 
    LIMIT 5
");
$recent_threads = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drill PTN - CBT UTBK Gratis</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="images/logo.png">
    <link rel="stylesheet" href="assets/css/pages/index.css">
</head>
<body>
    <!-- Loading Screen --> 
    <div class="loading-overlay">
        <div class="loading-container">
            <div class="loading-spinner"></div>
            <img src="assets/img/logo.png" alt="Logo" class="loading-logo">
        </div>
    </div>

    <!-- Simple Navbar (Muncul pas scroll aja) -->
    <nav class="simple-navbar">
        <div class="simple-logo" style="display: flex; align-items: center; gap: 0.5rem;">
            <img src="assets/img/logo.png" alt="Logo Drill PTN" style="height: 40px;">
            <h1 style="margin: 0; color: #2196F3;">DrillPTN</h1>
        </div>
        <ul class="simple-nav-menu">
            <li><a href="#home" class="simple-nav-link">Home</a></li>
            <li><a href="#about" class="simple-nav-link">About</a></li>
            <li><a href="#tryout" class="simple-nav-link">Drilling</a></li>
            <li><a href="#forum" class="simple-nav-link">Forum</a></li>
            <li><a href="#testimoni" class="simple-nav-link">Testimoni</a></li>
            <li><a href="login.php" class="simple-login-btn">Login</a></li>
        </ul>
        <div class="simple-menu-toggle">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>

    <!-- Header Navigation -->
    <header class="header">
        <nav class="nav-container">
            <div class="logo" style="display: flex; align-items: center; gap: 0.5rem;">
                <img src="assets/img/logo.png" alt="Logo Drill PTN" style="height: 50px; padding-left: 10px;">
                <h1 style="margin: 0; color: #2196F3;">DrillPTN</h1>
            </div>
            
            <ul class="nav-menu">
                <li><a href="#home" class="simple-nav-link">Home</a></li>
                <li><a href="#about" class="simple-nav-link">About</a></li>
                <li><a href="#tryout" class="simple-nav-link">Drilling</a></li>
                <li><a href="#forum" class="simple-nav-link">Forum</a></li>
                <li><a href="#testimoni" class="simple-nav-link">Testimoni</a></li>
                <li><a href="login.php" class="simple-login-btn">Login</a></li>
            </ul>

            <div class="menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content" id="home">
        <!-- Hero Video Section -->
        <section class="hero-video-container">
            <video class="hero-video" autoplay muted loop playsinline>
                <source src="assets/video/hero-video.mp4" type="video/mp4">
            </video>
            
            <div class="video-overlay"></div>
            
            <div class="hero-content">
                <h1 class="hero-title">
                    <span class="line1">DrillPTN</span>
                    <span class="line2">Platform CBT UTBK Gratis Terlengkap!</span>
                </h1>
                <div class="hero-buttons">
                    <a href="#" class="btn-daftar">Mulai Latihan</a>
                </div>
            </div>
        </section>
    </main>
    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="about-bg-element"></div>
        <div class="about-bg-element"></div>
        
        <div class="about-container">
            <div class="about-content">
                <div class="about-badge">Tentang Kami</div>
                <h2 class="about-title">
                    Apa itu <span class="highlight">Drill PTN?</span>
                </h2>
                <p class="about-description">
                    Drill PTN adalah platform Computer Based Test (CBT) yang menyediakan latihan UTBK gratis untuk seluruh pejuang PTN di Indonesia. Kami berkomitmen membantu siswa mempersiapkan diri menghadapi ujian masuk perguruan tinggi negeri dengan fasilitas terbaik tanpa biaya.
                </p>
                <p class="about-description">
                    Dengan sistem CBT yang mirip dengan ujian sesungguhnya, Drill PTN memberikan pengalaman latihan yang realistis dan komprehensif.
                </p>
                
                <div class="about-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?= number_format($total_questions); ?>+</span>
                        <span class="stat-label">Soal <b>UTBK</b></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= number_format($total_questions); ?>+</span>
                        <span class="stat-label">Soal <b>Drilling</b></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= number_format($total_forums+$total_replies); ?>+</span>
                        <span class="stat-label">Forum <b>Diskusi</b></span>
                    </div>
                </div>
            </div>

            <div class="about-visual">
                <div class="visual-grid">
                    <div class="visual-card">
                        <div class="card-icon"><img src="assets/img/logo.png" alt="" style="height: 80px;"></div>
                        <div class="card-title">Drill UTBK</div>
                    </div>
                    <div class="visual-card">
                        <div class="card-icon"><img src="assets/img/100.png" alt="" style="height: 60px;"></div>
                        <div class="card-title">Analisis Skor</div>
                    </div>
                    <div class="visual-card">
                        <div class="card-icon"><img src="assets/img/forum.webp" alt="" style="height: 60px;"></div>
                        <div class="card-title">Forum Diskusi</div>
                    </div>
                    <div class="visual-card">
                        <div class="card-icon"><img src="assets/img/progress.webp" alt="" style="height: 60px;"></div>
                        <div class="card-title">Progress Tracking</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Try Out Section -->
    <section class="tryout-section" id="tryout">
        <div class="tryout-container">
            <div class="section-header">
                <div class="section-badge">Latihan</div>
                <h2 class="section-title">
                    Paket <span class="highlight">Drilling</span> UTBK
                </h2>
                <p class="section-description">
                    Berbagai paket latihan soal UTBK yang disusun sesuai dengan kisi-kisi terbaru dan format ujian sesungguhnya
                </p>
                <p style="margin-top: 20px;">
                    <a href="#" target="_blank">Lihat Semua Paket →</a>
                </p>
            </div>

            <div class="topik-grid">
                <div class="topik-card" data-category="tps">
                    <div class="card-glow purple-glow"></div>
                    <div class="competition-icon">
                        <span style="font-size: 3rem;">🧠</span>
                    </div>
                    <h3 class="competition-title">Tes Potensi Skolastik</h3>
                    <div class="card-pattern"></div>
                </div>

                <div class="topik-card" data-category="literasi">
                    <div class="card-glow blue-glow"></div>
                    <div class="competition-icon">
                        <span style="font-size: 3rem;">📚</span>
                    </div>
                    <h3 class="competition-title">Literasi Bahasa Indonesia</h3>
                    <div class="card-pattern"></div>
                </div>

                <div class="topik-card" data-category="inggris">
                    <div class="card-glow teal-glow"></div>
                    <div class="competition-icon">
                        <span style="font-size: 3rem;">🌏</span>
                    </div>
                    <h3 class="competition-title">Literasi Bahasa Inggris</h3>
                    <div class="card-pattern"></div>
                </div>

                <div class="topik-card" data-category="penalaran">
                    <div class="card-glow pink-glow"></div>
                    <div class="competition-icon">
                        <span style="font-size: 3rem;">🔬</span>
                    </div>
                    <h3 class="competition-title">Penalaran Matematika</h3>
                    <div class="card-pattern"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Forum Section -->
    <section class="forum-section" id="forum">
        <div class="forum-container">
            <div class="forum-content">
                <div class="section-header-talk">
                    <div class="section-badge">Komunitas</div>
                    <h2 class="section-title">
                        <span class="highlight">Forum</span> Diskusi
                    </h2>
                    <p class="section-description">
                        Bergabunglah dengan komunitas pejuang PTN untuk saling berbagi tips, strategi, dan membahas soal-soal UTBK bersama
                    </p>
                </div>
                
                <div class="bubble-container">
                    <div class="speaker-visual">
                        <div class="speaker-frame">
                            <div class="speaker-glow-container">
                                <div class="frame-glow"></div>
                                <div class="speaker-bg-placeholder"></div>
                            </div>
                            
                            <div class="speaker-image-container">
                                <div style="font-size: 8rem; text-align: center; margin-bottom: 90px;">
                                    <img src="assets/img/forum-section.webp" alt="" style="height: 200px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="speaker-details">
                        <div class="details-card">
                            <div class="card-header">
                                <h3 class="speaker-name">Forum Diskusi Aktif</h3>
                                <div class="expertise-badge">Community Driven</div>
                            </div>
                            
                            <p class="speaker-bio">
                                Tempat berkumpulnya ribuan pejuang PTN dari seluruh Indonesia. 
                                Diskusikan strategi belajar, bahas soal sulit, dan saling memberikan motivasi dalam perjalanan menuju PTN impian.
                            </p>

                            <div class="anticipation-banner">
                                <div class="banner-content">
                                    <span class="banner-text">Gabung sekarang dan mulai berdiskusi!</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimoni Section -->
    <section class="testimoni-section" id="testimoni">
        <div class="testimoni-container">
            <div class="section-header">
                <div class="section-badge">Testimoni</div>
                <h2 class="section-title">
                    <span class="title-emoji"><img src="assets/img/testimoni-asset-1.png" alt="" style="height: 60px;"></span>
                    Apa Kata Mereka?
                    <span class="title-emoji"><img src="assets/img/testimoni-asset-2.png" alt="" style="height: 60px;"></span>
                </h2>
                <p class="section-description">
                    Setiap pelajar dari latar yang berbeda membagikan cerita mereka.
                </p>
            </div>

            <div class="testimoni-grid">
                <!-- Testimoni 1 -->
                <div class="testimoni-card">
                    <div class="quote-icon"><img src="assets/img/anotation.png" alt="anotation" style="height: 40px;"></div>
                    <div class="testimoni-content">
                        <div class="testimoni-text collapsed" 
                            data-full="Awalnya aku gak ngerti sama sekali di dalam kelas. Setelah beberapa minggu ngikutin metode di Drill PTN, alhamdulillah mulai ngerti dan tau banyak tentang pelajaran persiapan UTBK. Platform ini sangat membantu dalam memahami konsep-konsep yang sulit, dan sistem CBT-nya mirip banget sama ujian aslinya. Soal-soalnya juga bervariasi dan sesuai dengan kisi-kisi terbaru. Terima kasih Drill PTN sudah membantu perjalanan belajarku menuju PTN impian!" 
                            data-short="Awalnya aku gak ngerti sama sekali di dalam kelas. Setelah beberapa minggu ngikutin metode di Drill PTN, alhamdulillah mulai ngerti dan tau banyak tentang pelajaran persiapan UTBK.">
                            Awalnya aku gak ngerti sama sekali di dalam kelas. Setelah beberapa minggu ngikutin metode di Drill PTN, alhamdulillah mulai ngerti dan tau banyak tentang pelajaran persiapan UTBK.
                        </div>
                        <button class="read-more-btn" onclick="toggleText(this)">
                            <span class="btn-text">Baca Selengkapnya</span>
                            <span class="arrow">▼</span>
                        </button>
                    </div>
                    <div class="testimoni-author">
                        <img src="assets/img/avatar1.png" alt="Sharif Balfas" class="author-avatar">
                        <div class="author-info">
                            <div class="author-name">Sharif Balfas</div>
                            <div class="author-school">Sekolah Indonesia Kuala Lumpur</div>
                        </div>
                    </div>
                </div>

                <!-- Testimoni 2 -->
                <div class="testimoni-card">
                    <div class="quote-icon"><img src="assets/img/anotation.png" alt="anotation" style="height: 40px;"></div>
                    <div class="testimoni-content">
                        <div class="testimoni-text collapsed" 
                            data-full="Di Drill PTN bukan hanya belajar, tetapi aku juga diajarkan strategi untuk lulus UTBK, seperti minimal skor yang harus dicapai, materi-materi yang selalu keluar setiap tahun, bahkan tentang tips dan trik mengerjakan soal dengan efisien. Forum diskusinya juga sangat membantu karena bisa bertanya langsung sama teman-teman lain dan mentor. Analisis hasil latihannya detail banget, jadi aku tau bagian mana yang masih perlu diperbaiki. Benar-benar platform yang lengkap untuk persiapan UTBK!" 
                            data-short="Di Drill PTN bukan hanya belajar, tetapi aku juga diajarkan strategi untuk lulus UTBK, seperti minimal skor yang harus dicapai, materi-materi yang selalu keluar setiap tahun, bahkan tentang">
                            Di Drill PTN bukan hanya belajar, tetapi aku juga diajarkan strategi untuk lulus UTBK, seperti minimal skor yang harus dicapai, materi-materi yang selalu keluar setiap tahun, bahkan tentang
                        </div>
                        <button class="read-more-btn" onclick="toggleText(this)">
                            <span class="btn-text">Baca Selengkapnya</span>
                            <span class="arrow">▼</span>
                        </button>
                    </div>
                    <div class="testimoni-author">
                        <img src="assets/img/avatar2.png" alt="Nailah Syakirah" class="author-avatar">
                        <div class="author-info">
                            <div class="author-name">Ghina Amalia</div>
                            <div class="author-school">Ilmu Psikologi Unpad</div>
                        </div>
                    </div>
                </div>

                <!-- Testimoni 3 -->
                <div class="testimoni-card">
                    <div class="quote-icon"><img src="assets/img/anotation.png" alt="anotation" style="height: 40px;"></div>
                    <div class="testimoni-content">
                        <div class="testimoni-text collapsed" 
                            data-full="Cocok banget buat belajar. Fiturnya ada banyak, bermanfaat, dan worth it. Selain itu, cukup praktis karena bisa dibuka lewat HP maupun laptop. Interface-nya user friendly dan gampang dipahami. Yang paling aku suka adalah adanya progress tracking yang membantu monitor perkembangan belajarku. Soal-soal latihannya juga update terus mengikuti trend UTBK terbaru. Pokoknya recommended banget buat yang mau serius persiapan UTBK!" 
                            data-short="Cocok banget buat belajar. Fiturnya ada banyak, bermanfaat, dan worth it. Selain itu, cukup praktis karena bisa dibuka lewat HP maupun laptop.">
                            Cocok banget buat belajar. Fiturnya ada banyak, bermanfaat, dan worth it. Selain itu, cukup praktis karena bisa dibuka lewat HP maupun laptop.
                        </div>
                        <button class="read-more-btn" onclick="toggleText(this)">
                            <span class="btn-text">Baca Selengkapnya</span>
                            <span class="arrow">▼</span>
                        </button>
                    </div>
                    <div class="testimoni-author">
                        <img src="assets/img/avatar3.jpg" alt="Hilmi Izdihar E." class="author-avatar">
                        <div class="author-info">
                            <div class="author-name">Hilmi Izdihar E.</div>
                            <div class="author-school">SMAN 8 Jakarta</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Card Promo Section -->
    <section class="card-promo-section">
        <div class="card-promo-container">
            <div class="card-promo-card">
                <div class="promo-content">
                    <h2 class="promo-title">Mulai belajar di DrillPTN</h2>
                    <p class="promo-subtitle">
                        Website gratis untuk kesempatan yang sama bagi calon generasi emas Indonesia 
                    </p>
                </div>
                
                <div class="promo-visual">
                    <img src="assets/img/card-promo.png" alt="Drill PTN Child" class="promo-image">
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-main">
                    <div class="footer-brand">
                        <div class="footer-logo">
                            <img src="assets/img/logo.png" alt="Drill PTN 2025">
                        </div>
                        <h3 class="footer-title">Drill PTN <span style="background: linear-gradient(135deg, #FFD700, #2196F3); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">2025</span></h3>
                        <p class="footer-desc">Platform CBT UTBK Gratis Terlengkap</p>
                    </div>
                    
                    <div class="footer-links">                        
                        <div class="link-group">
                            <h4 class="link-title" style="background: linear-gradient(135deg, #FFD700, #2196F3); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Fitur</h4>
                            <ul class="link-list">
                                <li><a href="#tryout">Try Out UTBK</a></li>
                                <li><a href="#forum">Forum Diskusi</a></li>
                                <li><a href="#statistik">Statistik</a></li>
                            </ul>
                        </div>
                        
                        <div class="link-group">
                            <h4 class="link-title" style="background: linear-gradient(135deg, #FFD700, #2196F3); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Media Sosial</h4>
                            <ul class="link-list">
                                <li>
                                  <a href="https://www.instagram.com/drillptn.official" target="_blank">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                         viewBox="0 0 24 24" style="display: inline; vertical-align: middle; margin-right: 5px;">
                                      <path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.9.3 2.3.5.6.3 1 .7 1.3 1.3.3.4.4 1.1.5 2.3.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.3 1.9-.5 2.3-.3.6-.7 1-1.3 1.3-.4.3-1.1.4-2.3.5-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.9-.3-2.3-.5-.6-.3-1-.7-1.3-1.3-.3-.4-.4-1.1-.5-2.3-.1-1.3-.1-1.7-.1-4.9s0-3.6.1-4.9c.1-1.2.3-1.9.5-2.3.3-.6.7-1 1.3-1.3.4-.3 1.1-.4 2.3-.5C8.4 2.2 8.8 2.2 12 2.2zm0-2.2C8.7 0 8.3 0 7 .1 5.7.2 4.8.5 4 .9c-.9.5-1.6 1.2-2.1 2.1-.4.8-.7 1.7-.8 3C1 7.8 1 8.3 1 12s0 4.2.1 5.5c.1 1.3.4 2.2.8 3 .5.9 1.2 1.6 2.1 2.1.8.4 1.7.7 3 .8 1.3.1 1.7.1 5.1.1s3.8 0 5.1-.1c1.3-.1 2.2-.4 3-.8.9-.5 1.6-1.2 2.1-2.1.4-.8.7-1.7.8-3 .1-1.3.1-1.7.1-5.1s0-3.8-.1-5.1c-.1-1.3-.4-2.2-.8-3-.5-.9-1.2-1.6-2.1-2.1-.8-.4-1.7-.7-3-.8C15.7 0 15.3 0 12 0z"/>
                                      <path d="M12 5.8A6.2 6.2 0 0 0 5.8 12 6.2 6.2 0 0 0 12 18.2 6.2 6.2 0 0 0 18.2 12 6.2 6.2 0 0 0 12 5.8zm0 10.2a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/>
                                      <circle cx="18.4" cy="5.6" r="1.4"/>
                                    </svg>
                                    Instagram
                                  </a>
                                </li>
                              </ul>
                        </div>

                        <div class="link-group">
                            <h4 class="link-title" style="background: linear-gradient(135deg, #FFD700, #2196F3); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Contact Person</h4>
                            <ul class="link-list">
                                <li><a href="https://wa.me/6285316553801" target="_blank">WA: 085316553801 (Admin)</a></li>
                                <li><a href="mailto:info@drillptn.com">Email: info@drillptn.com</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <div class="footer-divider"></div>
                    <div class="footer-copyright" style="background: linear-gradient(135deg, #FFD700, #2196F3); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        <p>&copy; 2025 Drill PTN. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    
    <script src="assets/js/pages/index.js"></script>
</body>
</html>