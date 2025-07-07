<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Games - DrillPTN</title>
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <link rel="stylesheet" href="assets/css/pages/games.css">
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="games-container">
        <div class="page-header">
            <h1 class="page-title">DrillPTN Games</h1>
            <p class="page-subtitle">
                Bermain sambil belajar! Nikmati koleksi game edukatif yang kami sediakan untuk membuat pembelajaran menjadi lebih menyenangkan dan interaktif.
            </p>
        </div>

        <div class="games-grid">
            <!-- Game 1: Photobooth -->
            <div class="game-card">
                <div class="video-container">
                    <video class="game-video" autoplay muted loop poster="assets/img/games/photobooth-thumb.jpg">
                        <source src="assets/vid/tutorial-photobooth.mkv" type="video/mp4">
                    </video>
                </div>
                <div class="game-info">
                    <h3 class="game-title">Photo Booth Kreatif</h3>
                    <p class="game-description">
                        Abadikan momen belajarmu dengan photo booth interaktif! Tambahkan stiker lucu, filter keren, dan buat photostrip dengan teman
                    </p>
                    <a href="games/photobooth.php" class="game-button">Main Sekarang</a>
                </div>
            </div>

            <!-- Game 2: Quiz Challenge -->
            <!-- <div class="game-card">
                <div class="video-container">
                    <video class="game-video" autoplay muted loop poster="assets/img/games/quiz-thumb.jpg">
                        <source src="assets/videos/quiz-preview.mp4" type="video/mp4">
                        <source src="assets/videos/quiz-preview.webm" type="video/webm">
                        <img src="assets/img/games/quiz-preview.jpg" alt="Quiz Preview" style="width: 100%; height: 100%; object-fit: cover;">
                    </video>
                </div>
                <div class="game-info">
                    <h3 class="game-title">Quiz Challenge</h3>
                    <p class="game-description">
                        Uji pengetahuanmu dengan quiz interaktif yang menantang! Kumpulkan poin, naik level, dan tantang teman-temanmu dalam kompetisi seru.
                    </p>
                    <a href="quiz-game.php" class="game-button">Mulai Quiz</a>
                </div>
            </div> -->

            <!-- Game 3: Butterfly Games -->
            <!-- <div class="game-card">
                <div class="video-container">
                    <video class="game-video" autoplay muted loop poster="assets/img/games/puzzle-thumb.jpg">
                        <source src="assets/videos/puzzle-preview.mp4" type="video/mp4">
                    </video>
                </div>
                <div class="game-info">
                    <h3 class="game-title">Butterfly Games</h3>
                    <p class="game-description">
                        Asah hasil belajarmu sambil bermain! Jadikan momen belajarmu menjadi chellange yang menarik.
                    </p>
                    <a href="word-puzzle.php" class="game-button">Mainkan</a>
                </div>
            </div> -->
        </div>
    </div>

    <script src="assets/js/navbar.js"></script>
    <script>
        // Handle video loading errors gracefully
        document.querySelectorAll('.game-video').forEach(video => {
            if (video.tagName === 'VIDEO') {
                video.addEventListener('error', function() {
                    console.log('Video failed to load, falling back to image');
                    // You can add fallback behavior here
                });

                video.addEventListener('loadstart', function() {
                    this.parentElement.style.opacity = '0.7';
                });

                video.addEventListener('canplay', function() {
                    this.parentElement.style.opacity = '1';
                });
            }
        });
    </script>
</body>

</html>