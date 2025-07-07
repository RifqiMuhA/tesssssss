<?php
require_once 'config.php';

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
    <link rel="stylesheet" href="css/welcome.css">
    <script src="js/welcome.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: white;
            overflow-x: hidden;
            height: 200vh;
        }

        /* Header Navigation */
        .header {
            background: white;
            padding: 20px 0;
            position: relative;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        /* Simple Navbar (appears on scroll) */
        .simple-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1001;
            background: white;
            border-radius: 50px;
            margin: 15px 90px;
            padding: 5px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-120px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .simple-navbar.visible {
            transform: translateY(0);
            opacity: 1;
        }

        .simple-logo {
            padding: 0px 20px;
        }

        .simple-nav-menu {
            display: flex;
            list-style: none;
            gap: 40px;
            align-items: center;
            margin: 0;
        }

        .simple-nav-link {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 500;
            font-size: 16px;
            transition: color 0.3s ease;
            padding-bottom: 10px;
        }

        .simple-nav-link:hover {
            color: #57007F;
            border-bottom: 2px solid #e7b4ff;
            transition: ease-in-out;
        }

        .simple-login-btn {
            background: #57007F;
            color: white;
            padding: 7px 15px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .simple-login-btn:hover {
            background: #40005d;
        }

        .simple-dashboard-btn {
            background: linear-gradient(135deg, #40005d, #4A98F9);
            color: white;
            padding: 7px 15px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        /* Mobile menu toggle for simple navbar */
        .simple-menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 4px;
        }

        .simple-menu-toggle span {
            width: 25px;
            height: 3px;
            background: #2c3e50;
            transition: all 0.3s ease;
        }

        .simple-menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .simple-menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .simple-menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }

        .nav-container {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 50px;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: #6c757d;
            font-weight: 500;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #2c3e50;
        }

        .contact-btn {
            background: #4A98F9;
            color: white;
            padding: 12px 28px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .contact-btn:hover {
            background: #3a82d9;
            transform: translateY(-2px);
        }

        /* Mobile menu toggle for main navbar */
        .menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 4px;
        }

        .menu-toggle span {
            width: 30px;
            height: 3px;
            background: #2c3e50;
            transition: all 0.3s ease;
        }

        .menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }

        /* Main Content */
        .main-content {
            width: 100%;
            margin: 0px auto;
            padding: 0 20px;
            overflow: hidden;
            background: white;
        }

        /* Hero Video Section */
        .hero-video-container {
            position: relative;
            width: 100%;
            height: 600px;
            border-radius: 24px;
            overflow: hidden;
            background: #000;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .hero-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            overflow: hidden;
            opacity: 0;
            transform: scale(1.1);
        }

        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.6) 100%);
            z-index: 2;
        }

        .hero-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 3;
            width: 90%;
            max-width: 800px;
        }

        .hero-title {
            font-size: clamp(48px, 8vw, 84px);
            font-weight: 900;
            color: white;
            line-height: 1.1;
            margin-bottom: 50px;
            letter-spacing: -2px;
        }

        .hero-title .line1 {
            display: block;
            margin-bottom: 10px;
        }

        .hero-title .line2 {
            display: block;
            font-weight: 500;
            letter-spacing: 2px;
            font-size: 0.3em;
        }

        .hero-subtitle {
            position: absolute;
            top: 120px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            color: #57007F;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            backdrop-filter: blur(10px);
            text-align: center;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-daftar {
            background: linear-gradient(135deg, #57007F, #4A98F9);
            color: white;
            padding: 16px 32px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(87, 0, 127, 0.3);
        }

        .btn-daftar:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.3);
        }

        .btn-contact {
            background: transparent;
            color: white;
            padding: 18px 40px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .btn-contact:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.8);
            transform: translateY(-3px);
        }

        /* About Section - START */
        .about-section {
            padding: 100px 20px;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        .about-content {
            opacity: 0;
            transform: translateX(-50px);
        }

        .about-badge {
            display: inline-block;
            background: linear-gradient(135deg, #57007F, #4A98F9);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .about-title {
            font-size: clamp(32px, 5vw, 48px);
            font-weight: 800;
            color: #2c3e50;
            line-height: 1.2;
            margin-bottom: 24px;
        }

        .about-title .highlight {
            color: #57007F;
            position: relative;
        }

        .about-title .highlight::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(135deg, #57007F, #4A98F9);
            border-radius: 2px;
        }

        .about-description {
            font-size: 18px;
            color: #6c757d;
            line-height: 1.7;
            margin-bottom: 32px;
        }

        .about-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-bottom: 40px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 800;
            color: #57007F;
            display: block;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .about-visual {
            position: relative;
            opacity: 0;
            transform: translateX(50px);
        }

        .visual-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            transform: rotate(10deg);
        }

        .visual-card {
            background: white;
            text-align: center;
            border-radius: 16px;
            padding: 30px 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            border: 1px solid rgba(87, 0, 127, 0.1);
        }

        .visual-card:hover {
            transform: translateY(-10px);
        }

        .visual-card:nth-child(1) {
            background: linear-gradient(135deg, #fff7e6, #ffffff);
        }

        .visual-card:nth-child(2) {
            background: linear-gradient(135deg, #f0f4ff, #ffffff);
            transform: translateY(40px);
        }

        .visual-card:nth-child(3) {
            background: linear-gradient(135deg, #f7e6ff, #ffffff);
            transform: translateY(-20px);
        }

        .visual-card:nth-child(4) {
            background: linear-gradient(135deg, #f8e6f3, #ffffff); 
        }

        .card-icon {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 16px;
        }

        .visual-card:nth-child(1) .card-icon {
            background: linear-gradient(135deg, #FFCC00, #FFB300);
        }

        .visual-card:nth-child(2) .card-icon {
            background: linear-gradient(135deg, #4A98F9, #3a82d9);
            color: white;
        }

        .visual-card:nth-child(3) .card-icon {
            background: linear-gradient(135deg, #57007F, #40005d);
            color: white;
        }

        .visual-card:nth-child(4) .card-icon {
            background: linear-gradient(135deg, #C62C92, #9e2378);
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }

        .card-desc {
            font-size: 14px;
            color: #6c757d;
        }

        /* Floating background elements */
        .about-bg-element {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(87, 0, 127, 0.1), rgba(74, 152, 249, 0.1));
            animation: float 6s ease-in-out infinite;
        }

        .about-bg-element:nth-child(1) {
            width: 200px;
            height: 200px;
            top: 10%;
            left: -5%;
            animation-delay: 0s;
        }

        .about-bg-element:nth-child(2) {
            width: 150px;
            height: 150px;
            bottom: 20%;
            right: -3%;
            animation-delay: 2s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .mascot:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
        }

        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loading-container {
            position: relative;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loading-spinner {
            width: 80px;
            height: 80px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid purple;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            position: absolute;
        }

        .loading-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            z-index: 1;
            position: relative;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 1024px) {
            /* Simple Navbar - Mobile */
            .simple-navbar {
                margin: 10px 15px;
                padding: 8px 20px;
                border-radius: 25px;
            }

            .simple-logo {
                padding: 0px 10px;
            }

            .simple-nav-menu {
                position: fixed;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100vh;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(20px);
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 30px;
                transition: left 0.3s ease;
                z-index: 1002;
            }

            .simple-nav-menu.active {
                left: 0;
            }

            .simple-menu-toggle {
                display: flex;
                z-index: 1003;
            }

            /* Hide some nav items in mobile simple navbar */
            .simple-nav-menu li:nth-child(3),
            .simple-nav-menu li:nth-child(4) {
                display: flex;
            }
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            /* Main Header Navigation - Mobile */
            .nav-container {
                padding: 0 15px;
            }

            .nav-menu {
                position: fixed;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100vh;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(20px);
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 30px;
                transition: left 0.3s ease;
                z-index: 1002;
            }

            .nav-menu.active {
                left: 0;
            }

            .menu-toggle {
                display: flex;
                z-index: 1003;
            }

            /* Main content mobile adjustments */
            .main-content {
                padding: 0 15px;
                margin: 40px auto;
            }

            .hero-video-container {
                height: 500px;
                border-radius: 16px;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-daftar,
            .btn-contact {
                width: 280px;
                text-align: center;
            }

            /* About Section Mobile - Hide visual grid completely */
            .about-section {
                padding: 60px 15px;
            }

            .about-container {
                grid-template-columns: 1fr;
                gap: 0px;
                text-align: center;
            }

            .about-visual {
                display: none; /* Completely hide the visual grid on mobile */
            }

            .about-stats {
                justify-content: center;
                gap: 30px;
                flex-wrap: wrap;
            }

            .stat-item {
                min-width: 120px;
            }

            /* Mascot mobile */
            .mascot {
                width: 60px;
                height: 60px;
                bottom: 20px;
                right: 20px;
            }
        }

        @media (max-width: 480px) {
            .hero-title .line2 {
                letter-spacing: 2px;
            }

            .about-stats {
                flex-direction: column;
                gap: 20px;
                align-items: center;
            }

            .stat-item {
                min-width: auto;
            }

            .simple-navbar {
                margin: 8px 10px;
                padding: 6px 15px;
            }

            .simple-logo img {
                height: 35px;
            }
        }
        /* Liliecomp Section Styles - Simplified and Beautiful */
        .liliecomp-section {
            padding: 120px 20px;
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 50%, #f0f8ff 100%);
            position: relative;
            overflow: hidden;
        }

        .liliecomp-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 80%, rgba(87, 0, 127, 0.05) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(74, 152, 249, 0.05) 0%, transparent 50%);
        }

        .liliecomp-container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
        }

        .section-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .section-badge {
            display: inline-block;
            background: linear-gradient(135deg, #57007F, #4A98F9);
            color: white;
            padding: 10px 24px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(87, 0, 127, 0.3);
        }

        .section-title {
            font-size: clamp(36px, 6vw, 56px);
            font-weight: 800;
            color: #2c3e50;
            line-height: 1.2;
            margin-bottom: 24px;
        }

        .section-title .highlight {
            color: #57007F;
            position: relative;
        }

        .section-title .highlight::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, #57007F, #4A98F9);
            border-radius: 2px;
        }

        .section-description {
            font-size: 18px;
            color: #6c757d;
            line-height: 1.7;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Competition Grid - 4 in 1 row for desktop */
        .competition-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-top: 80px;
        }

        .competition-card {
            background: white;
            border-radius: 28px;
            padding: 50px 30px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            /* cursor: pointer; */
            backdrop-filter: blur(10px);
        }

        .competition-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.6));
            z-index: 1;
            border-radius: 28px;
        }

        .competition-card > * {
            position: relative;
            z-index: 2;
        }

        .card-glow {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            border-radius: 50%;
            opacity: 0;
            transition: all 0.6s ease;
            z-index: 0;
        }

        .purple-glow { background: radial-gradient(circle, rgba(87, 0, 127, 0.2) 0%, transparent 70%); }
        .blue-glow { background: radial-gradient(circle, rgba(74, 152, 249, 0.2) 0%, transparent 70%); }
        .teal-glow { background: radial-gradient(circle, rgba(0, 188, 212, 0.2) 0%, transparent 70%); }
        .pink-glow { background: radial-gradient(circle, rgba(233, 30, 99, 0.2) 0%, transparent 70%); }

        .competition-card:hover .card-glow {
            opacity: 1;
            transform: scale(0.8);
        }

        .competition-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 50px rgba(87, 0, 127, 0.15);
        }

        .competition-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(87, 0, 127, 0.1), rgba(74, 152, 249, 0.1));
            border-radius: 24px;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .competition-icon::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.2) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .competition-card:hover .competition-icon::before {
            transform: translateX(100%);
        }

        .competition-card:hover .competition-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 12px 24px rgba(87, 0, 127, 0.2);
        }

        .competition-icon img {
            height: 80px;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }

        .competition-title {
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }

        .card-pattern {
            position: absolute;
            bottom: -20px;
            right: -20px;
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, rgba(87, 0, 127, 0.05), rgba(74, 152, 249, 0.05));
            border-radius: 50%;
            z-index: 1;
        }

        /* Liliefors Talkshow Section - Enhanced Design */
        .liliefors-section {
            padding: 120px 20px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: white;
            overflow: hidden;
            position: relative;
        }

        .liliefors-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'%3E%3Cg fill-opacity='0.03'%3E%3Cpolygon fill='%23ffffff' points='36 34 36 46 24 46 24 34 19 34 30 14 41 34'/%3E%3C/g%3E%3C/svg%3E") repeat;
        }

        .liliefors-container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .section-header-talk {
            text-align: center;
            margin-bottom: 80px;
        }

        .liliefors-content .section-badge {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .liliefors-content .section-title,
        .liliefors-content .section-description {
            color: white;
        }

        .speaker-showcase {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 80px;
            align-items: center;
            margin-top: 60px;
        }

        /* UPDATE CSS yang sudah ada */
        .speaker-visual {
            position: relative;
            display: flex;
            justify-content: center;
            overflow: visible; /* TAMBAH ini */
        }

        .speaker-frame {
            position: relative;
            width: 300px;
            height: 400px;
            overflow: visible; /* TAMBAH ini */
        }

        /* HAPUS CSS lama .speaker-image-placeholder */
        /* TAMBAH CSS baru ini */
        .speaker-glow-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 24px;
            overflow: hidden;
            z-index: 1;
        }

        .speaker-bg-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            position: relative;
        }

        .speaker-bg-placeholder::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        .speaker-image-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            z-index: 3;
            overflow: visible;
        }

        /* UPDATE CSS untuk .anonymous-photo */
        .anonymous-photo {
            max-width: 120%; 
            height: auto;
            max-height: 120%; 
            object-fit: contain;
            object-position: bottom center;
            filter: drop-shadow(0 8px 32px rgba(0, 0, 0, 0.3)); 
            transition: all 0.4s ease;
        }

        /* UPDATE frame-glow z-index */
        .frame-glow {
            position: absolute;
            top: -20px;
            left: -20px;
            right: -20px;
            bottom: -20px;
            background: linear-gradient(135deg, rgba(87, 0, 127, 0.3), rgba(74, 152, 249, 0.3));
            border-radius: 32px;
            filter: blur(20px);
            opacity: 0.6;
            animation: frame-pulse 3s infinite;
            z-index: 0; /* TAMBAH ini */
        }

        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 2;
        }

        .float-element {
            position: absolute;
            font-size: 20px;
            opacity: 0.7;
            animation: float-around 6s infinite ease-in-out;
        }

        .element-1 { top: 10%; left: 10%; animation-delay: 0s; }
        .element-2 { top: 20%; right: 10%; animation-delay: 1.5s; }
        .element-3 { bottom: 20%; left: 15%; animation-delay: 3s; }
        .element-4 { bottom: 10%; right: 15%; animation-delay: 4.5s; }

        @keyframes float-around {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.7; }
            25% { transform: translateY(-15px) rotate(90deg); opacity: 1; }
            50% { transform: translateY(-10px) rotate(180deg); opacity: 0.8; }
            75% { transform: translateY(-20px) rotate(270deg); opacity: 0.9; }
        }

        .speaker-details {
            position: relative;
        }

        .details-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .speaker-name {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin: 0;
        }

        @media (max-width: 768px) {
            .speaker-name {
                font-size: 24px;
            }
        }

        .expertise-badge {
            background: linear-gradient(135deg, #57007F, #4A98F9);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .speaker-bio {
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 30px;
            color: rgba(255, 255, 255, 0.9);
        }

        .achievement-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .achievement-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .achievement-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .achievement-icon {
            color: #4A98F9;
            flex-shrink: 0;
        }

        .achievement-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .achievement-number {
            font-size: 16px;
            font-weight: 700;
            color: white;
        }

        .achievement-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
        }

        .anticipation-banner {
            background: linear-gradient(135deg, rgba(87, 0, 127, 0.3), rgba(74, 152, 249, 0.3));
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 16px;
            text-align: center;
        }

        .banner-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .banner-icon {
            font-size: 18px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .banner-text {
            font-weight: 600;
            color: white;
        }

        /* Timeline Section */
        .timeline-section {
            padding: 120px 20px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            position: relative;
        }

        .timeline-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .timeline-track {
            position: relative;
            margin-top: 80px;
        }

        .timeline-line {
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #57007F, #4A98F9);
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .timeline-item {
            display: flex;
            align-items: center;
            margin-bottom: 80px;
            position: relative;
        }

        .timeline-item:nth-child(even) {
            flex-direction: row-reverse;
        }

        .timeline-item:nth-child(even) .timeline-content {
            text-align: right;
        }

        .timeline-marker {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #57007F, #4A98F9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 0 8px white, 0 0 0 12px rgba(87, 0, 127, 0.2);
            z-index: 10;
        }

        @media (max-width: 768px) {
            /* Jadikan marker naik dikit posisinya ke atas */
            .timeline-marker {
                transform: translateX(-50%) translateY(-50%);
                top: -35px;
                left: -20px;
            }
        }

        .marker-inner {
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        .timeline-content {
            flex: 1;
            max-width: 45%;
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(87, 0, 127, 0.1);
            position: relative;
        }

        .timeline-content::before {
            content: '';
            position: absolute;
            top: 30px;
            width: 0;
            height: 0;
            border: 15px solid transparent;
        }

        .timeline-item:nth-child(odd) .timeline-content::before {
            right: -30px;
            border-left-color: white;
        }

        .timeline-item:nth-child(even) .timeline-content::before {
            left: -30px;
            border-right-color: white;
        }

        .timeline-date {
            color: #57007F;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .timeline-title {
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 12px;
        }

        .timeline-desc {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .timeline-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 20px;
            width: fit-content;
        }

        .timeline-item:nth-child(even) .timeline-status {
            margin-left: auto;
        }

        .timeline-status.active {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
        }

        .timeline-status.upcoming {
            background: rgba(251, 146, 60, 0.1);
            color: #ea580c;
        }

        .timeline-status.event {
            background: rgba(87, 0, 127, 0.1);
            color: #57007F;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse-dot 2s infinite;
        }

        .timeline-status.active .status-dot {
            background: #16a34a;
        }

        .timeline-status.upcoming .status-dot {
            background: #ea580c;
        }

        .timeline-status.event .status-dot {
            background: #57007F;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Footer */
        .footer {
            background: rgb(0, 0, 35);
            color: white;
            padding: 80px 20px 40px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-main {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 60px;
            margin-bottom: 60px;
        }

        .footer-brand {
            text-align: left;
        }

        .footer-logo {
            margin-bottom: 20px;
        }

        .footer-logo img {
            height: 60px;
        }

        .footer-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
            color: white;
        }

        .footer-desc {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            max-width: 300px;
        }

        .footer-links {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
        }

        .link-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: white;
        }

        .link-list {
            list-style: none;
        }

        .link-list li {
            margin-bottom: 12px;
        }

        .link-list a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .link-list a:hover {
            color: #4A98F9;
        }

        .footer-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
            margin-bottom: 30px;
        }

        .footer-copyright {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-copyright p {
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
        }

        .footer-credits {
            color: rgba(255, 255, 255, 0.6);
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .competition-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 25px;
            }
            
            .speaker-showcase {
                grid-template-columns: 1fr;
                gap: 50px;
                text-align: center;
            }
            
            .achievement-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .liliecomp-section,
            .liliefors-section,
            .timeline-section {
                padding: 80px 15px;
            }

            .competition-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }

            .competition-card {
                padding: 30px 20px 25px;
            }

            .competition-icon {
                width: 100px;
                height: 100px;
                margin-bottom: 20px;
            }

            .competition-title {
                font-size: 18px;
            }

            .speaker-frame {
                width: 250px;
                height: 320px;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            @media screen and (max-width: 768px) {
                .card-header {
                    align-items: center;
                }
            }

            .timeline-line {
                left: 30px;
            }

            .timeline-item {
                flex-direction: row !important;
                margin-left: 60px;
            }

            .timeline-item .timeline-content {
                text-align: left !important;
                max-width: none;
            }

            .timeline-marker {
                left: 30px !important;
                transform: translateX(-50%);
                width: 50px;
                height: 50px;
            }

            .timeline-content::before {
                left: -30px !important;
                border-right-color: white !important;
                border-left-color: transparent !important;
            }

            .timeline-status {
                margin-left: 0 !important;
            }

            .footer-main {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .footer-links {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .footer-copyright {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .section-header {
                margin-bottom: 60px;
            }

            .competition-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .speaker-frame {
                width: 200px;
                height: 280px;
            }

            .details-card {
                padding: 25px;
            }

            .timeline-item {
                margin-bottom: 60px;
            }

            .timeline-content {
                padding: 20px;
            }
        }
    </style>
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
        <div class="simple-logo"><img src="assets/img/logo.png" alt="Logo Drill PTN" style="height: 40px;"></div>
        <ul class="simple-nav-menu">
            <li><a href="#home" class="simple-nav-link">Home</a></li>
            <li><a href="#about" class="simple-nav-link">About</a></li>
            <li><a href="#tryout" class="simple-nav-link">Drilling</a></li>
            <li><a href="#forum" class="simple-nav-link">Forum</a></li>
            <li><a href="#statistik" class="simple-nav-link">Statistik</a></li>
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
            <div class="logo">
                <img src="assets/img/logo.png" alt="Logo Drill PTN" style="height: 50px; padding-left: 10px;">
            </div>
            
            <ul class="nav-menu">
                <li><a href="#home" class="simple-nav-link">Home</a></li>
                <li><a href="#about" class="simple-nav-link">About</a></li>
                <li><a href="#tryout" class="simple-nav-link">Drilling</a></li>
                <li><a href="#forum" class="simple-nav-link">Forum</a></li>
                <li><a href="#statistik" class="simple-nav-link">Statistik</a></li>
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
    <section class="liliecomp-section" id="tryout">
        <div class="liliecomp-container">
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

            <div class="competition-grid">
                <div class="competition-card" data-category="tps">
                    <div class="card-glow purple-glow"></div>
                    <div class="competition-icon">
                        <span style="font-size: 3rem;">🧠</span>
                    </div>
                    <h3 class="competition-title">Tes Potensi Skolastik</h3>
                    <div class="card-pattern"></div>
                </div>

                <div class="competition-card" data-category="literasi">
                    <div class="card-glow blue-glow"></div>
                    <div class="competition-icon">
                        <span style="font-size: 3rem;">📚</span>
                    </div>
                    <h3 class="competition-title">Literasi Bahasa Indonesia</h3>
                    <div class="card-pattern"></div>
                </div>

                <div class="competition-card" data-category="inggris">
                    <div class="card-glow teal-glow"></div>
                    <div class="competition-icon">
                        <span style="font-size: 3rem;">🌏</span>
                    </div>
                    <h3 class="competition-title">Literasi Bahasa Inggris</h3>
                    <div class="card-pattern"></div>
                </div>

                <div class="competition-card" data-category="penalaran">
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
    <section class="liliefors-section" id="forum">
        <div class="liliefors-container">
            <div class="liliefors-content">
                <div class="section-header-talk">
                    <div class="section-badge">Komunitas</div>
                    <h2 class="section-title">
                        <span class="highlight">Forum</span> Diskusi
                    </h2>
                    <p class="section-description">
                        Bergabunglah dengan komunitas pejuang PTN untuk saling berbagi tips, strategi, dan membahas soal-soal UTBK bersama
                    </p>
                </div>
                
                <div class="speaker-showcase">
                    <div class="speaker-visual">
                        <div class="speaker-frame">
                            <div class="speaker-glow-container">
                                <div class="frame-glow"></div>
                                <div class="speaker-bg-placeholder"></div>
                            </div>
                            
                            <div class="speaker-image-container">
                                <div style="font-size: 8rem; text-align: center; margin-bottom: 90px;">💬</div>
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

    <!-- Statistik Section -->
    <section class="timeline-section" id="statistik">
        <div class="timeline-container">
            <div class="section-header">
                <div class="section-badge">Performance</div>
                <h2 class="section-title">
                    Statistik <span class="highlight">Pengguna</span>
                </h2>
                <p class="section-description">
                    Data dan pencapaian platform Drill PTN dalam membantu siswa mempersiapkan UTBK
                </p>
            </div>

            <div class="timeline-track">
                <div class="timeline-line"></div>
                
                <div class="timeline-item">
                    <div class="timeline-marker">
                        <div class="marker-inner">👥</div>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-date">100+ Pengguna</div>
                        <h3 class="timeline-title">Siswa Terdaftar</h3>
                        <p class="timeline-desc">Lebih dari 5.000 siswa telah bergabung dan aktif berlatih di platform kami</p>
                        <div class="timeline-status active">
                            <span class="status-dot"></span>
                            Active Users
                        </div>
                    </div>
                </div>

                <div class="timeline-item" data-date="10K+">
                    <div class="timeline-marker">
                        <div class="marker-inner">📝</div>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-date">1000+ Soal</div>
                        <h3 class="timeline-title">Ujian Diselesaikan</h3>
                        <p class="timeline-desc">Total soal drill yang telah dikerjakan oleh seluruh pengguna platform</p>
                        <div class="timeline-status upcoming">
                            <span class="status-dot"></span>
                            Completed Tests
                        </div>
                    </div>
                </div>

                <div class="timeline-item" data-date="85%">
                    <div class="timeline-marker">
                        <div class="marker-inner">🎯</div>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-date">85% Success Rate</div>
                        <h3 class="timeline-title">Tingkat Kelulusan PTN</h3>
                        <p class="timeline-desc">Persentase pengguna yang berhasil lolos PTN setelah berlatih di platform</p>
                        <div class="timeline-status upcoming">
                            <span class="status-dot"></span>
                            Success Rate
                        </div>
                    </div>
                </div>

                <div class="timeline-item" data-date="24/7">
                    <div class="timeline-marker">
                        <div class="marker-inner">🔄</div>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-date">24/7 Available</div>
                        <h3 class="timeline-title">Akses Tanpa Batas</h3>
                        <p class="timeline-desc">Platform dapat diakses kapan saja, dimana saja untuk latihan maksimal</p>
                        <div class="timeline-status event">
                            <span class="status-dot"></span>
                            Always Online
                        </div>
                    </div>
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
                        <h3 class="footer-title">Drill PTN <span style="background: linear-gradient(135deg, #e2a2ff, #4A98F9); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">2025</span></h3>
                        <p class="footer-desc">Platform CBT UTBK Gratis Terlengkap</p>
                    </div>
                    
                    <div class="footer-links">                        
                        <div class="link-group">
                            <h4 class="link-title" style="background: linear-gradient(135deg, #e2a2ff, #4A98F9); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Fitur</h4>
                            <ul class="link-list">
                                <li><a href="#tryout">Try Out UTBK</a></li>
                                <li><a href="#forum">Forum Diskusi</a></li>
                                <li><a href="#statistik">Statistik</a></li>
                            </ul>
                        </div>
                        
                        <div class="link-group">
                            <h4 class="link-title" style="background: linear-gradient(135deg, #e2a2ff, #4A98F9); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Media Sosial</h4>
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
                            <h4 class="link-title" style="background: linear-gradient(135deg, #e2a2ff, #4A98F9); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Contact Person</h4>
                            <ul class="link-list">
                                <li><a href="https://wa.me/6285316553801" target="_blank">WA: 085316553801 (Admin)</a></li>
                                <li><a href="mailto:info@drillptn.com">Email: info@drillptn.com</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <div class="footer-divider"></div>
                    <div class="footer-copyright" style="background: linear-gradient(135deg, #e2a2ff, #4A98F9); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        <p>&copy; 2025 Drill PTN. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <script>
        // Mobile menu functionality for main navbar
        const menuToggle = document.querySelector('.menu-toggle');
        const navMenu = document.querySelector('.nav-menu');

        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // Mobile menu functionality for simple navbar
        const simpleMenuToggle = document.querySelector('.simple-menu-toggle');
        const simpleNavMenu = document.querySelector('.simple-nav-menu');

        simpleMenuToggle.addEventListener('click', () => {
            simpleMenuToggle.classList.toggle('active');
            simpleNavMenu.classList.toggle('active');
        });

        // Close mobile menu when a link is clicked
        document.querySelectorAll('.simple-nav-link, .simple-login-btn').forEach(link => {
            link.addEventListener('click', () => {
                menuToggle.classList.remove('active');
                navMenu.classList.remove('active');
                simpleMenuToggle.classList.remove('active');
                simpleNavMenu.classList.remove('active');
            });
        });

        // Loading Animation
        window.addEventListener('load', function() {
            const loadingOverlay = document.querySelector('.loading-overlay');
            
            setTimeout(() => {
                gsap.to(loadingOverlay, {
                    opacity: 0,
                    duration: 0.5,
                    onComplete: () => {
                        loadingOverlay.style.display = 'none';
                        initAnimations();
                    }
                });
            }, 2000); 
        });

        // Initialize animations after loading
        function initAnimations() {
            // Animate video in
            gsap.to('.hero-video', {
                opacity: 1,
                scale: 1,
                duration: 1.5,
                ease: "power2.out"
            });

            // Animate mascot in after video
            gsap.to('.mascot', {
                opacity: 1,
                scale: 1,
                duration: 0.8,
                ease: "back.out(1.7)",
                delay: 1.5
            });

            // Floating animation for mascot
            gsap.to('.mascot', {
                y: -10,
                duration: 2,
                yoyo: true,
                repeat: -1,
                ease: "power2.inOut",
                delay: 2.5
            });

            // Setup scroll-based navbar switching
            setupNavbarScrolling();

            // Setup about section animations
            setupAboutAnimations();
        }

        // About section scroll animations
        function setupAboutAnimations() {
            const aboutContent = document.querySelector('.about-content');
            const aboutVisual = document.querySelector('.about-visual');
            const statNumbers = document.querySelectorAll('.stat-number');

            // Create intersection observer for about section
            const aboutObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Animate content from left
                        gsap.to(aboutContent, {
                            opacity: 1,
                            x: 0,
                            duration: 1,
                            ease: "power2.out"
                        });

                        // Animate visual from right (only if not hidden on mobile)
                        if (window.innerWidth > 768) {
                            gsap.to(aboutVisual, {
                                opacity: 1,
                                x: 0,
                                duration: 1,
                                ease: "power2.out",
                                delay: 0.3
                            });

                            // Animate visual cards
                            gsap.from('.visual-card', {
                                scale: 0.8,
                                opacity: 0,
                                duration: 0.8,
                                stagger: 0.1,
                                ease: "back.out(1.7)",
                                delay: 0.8
                            });
                        }

                        // Animate statistics numbers
                        statNumbers.forEach((stat, index) => {
                            const finalValue = stat.textContent;
                            const numericValue = parseInt(finalValue.replace(/\D/g, ''));
                            const suffix = finalValue.replace(/[0-9]/g, '');
                            
                            gsap.from(stat, {
                                textContent: 0,
                                duration: 2,
                                ease: "power2.out",
                                delay: 1.2 + (index * 0.2),
                                snap: { textContent: 1 },
                                onUpdate: function() {
                                    stat.textContent = Math.ceil(this.targets()[0].textContent) + suffix;
                                },
                                onComplete: function() {
                                    stat.textContent = finalValue;
                                }
                            });
                        });

                        // Only animate once
                        aboutObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.3 });

            aboutObserver.observe(document.querySelector('.about-section'));
        }

        // Navbar scrolling functionality
        function setupNavbarScrolling() {
            const header = document.querySelector('.header');
            const simpleNavbar = document.querySelector('.simple-navbar');
            const headerHeight = header.offsetHeight;
            
            let isSimpleNavbarVisible = false;

            window.addEventListener('scroll', () => {
                const scrollY = window.pageYOffset;
                
                // Show simple navbar when scrolled past header height + 50px
                if (scrollY > headerHeight + 50 && !isSimpleNavbarVisible) {
                    simpleNavbar.classList.add('visible');
                    isSimpleNavbarVisible = true;
                }
                // Hide simple navbar when back near top
                else if (scrollY <= headerHeight && isSimpleNavbarVisible) {
                    simpleNavbar.classList.remove('visible');
                    isSimpleNavbarVisible = false;
                }
            });
        }

        // Video error handling
        const video = document.querySelector('.hero-video');
        video.addEventListener('error', function() {
            // If video fails to load, show a background image instead
            const container = document.querySelector('.hero-video-container');
            container.style.background = 'linear-gradient(135deg, rgba(44, 62, 80, 0.8), rgba(52, 73, 94, 0.9)), url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'%3E%3Cg fill-opacity=\'0.1\'%3E%3Cpolygon fill=\'%23000\' points=\'50 0 60 40 100 50 60 60 50 100 40 60 0 50 40 40\'/%3E%3C/g%3E%3C/svg%3E")';
            container.style.backgroundSize = 'cover, 20px 20px';
            video.style.display = 'none';
        });

        // Smooth scrolling for navigation links (both navbars)
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Button interactions
        document.querySelectorAll('.btn-daftar, .btn-contact').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                gsap.to(this, {
                    scale: 1.05,
                    duration: 0.3,
                    ease: "power2.out"
                });
            });

            btn.addEventListener('mouseleave', function() {
                gsap.to(this, {
                    scale: 1,
                    duration: 0.3,
                    ease: "power2.out"
                });
            });
        });

        // Mascot click interaction
        document.querySelector('.mascot').addEventListener('click', function() {
            gsap.to(this, {
                rotation: 360,
                scale: 1.2,
                duration: 0.6,
                ease: "back.out(1.7)",
                onComplete: () => {
                    gsap.to(this, {
                        rotation: 0,
                        scale: 1,
                        duration: 0.3,
                        ease: "power2.out"
                    });
                }
            });
        });

        // Parallax effect on scroll
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const video = document.querySelector('.hero-video');
            if (video) {
                gsap.to(video, {
                    y: scrolled * 0.5,
                    duration: 0.1
                });
            }
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.nav-container') && !e.target.closest('.simple-navbar')) {
                menuToggle.classList.remove('active');
                navMenu.classList.remove('active');
                simpleMenuToggle.classList.remove('active');
                simpleNavMenu.classList.remove('active');
            }
        });

        // GSAP Animations for all sections
        function initAllAnimations() {
            // Register ScrollTrigger plugin
            gsap.registerPlugin(ScrollTrigger);
            
            // Liliecomp Section Animations
            gsap.set('.competition-card', { opacity: 0, y: 100, rotationX: 45 });
            
            ScrollTrigger.create({
                trigger: '.liliecomp-section',
                start: 'top 70%',
                onEnter: () => {
                    gsap.to('.competition-card', {
                        opacity: 1,
                        y: 0,
                        rotationX: 0,
                        duration: 1.2,
                        stagger: 0.2,
                        ease: "power3.out"
                    });
                }
            });

            // Competition card hover animations
            document.querySelectorAll('.competition-card').forEach(card => {
                card.addEventListener('mouseenter', () => {
                    gsap.to(card, {
                        y: -15,
                        scale: 1.02,
                        duration: 0.4,
                        ease: "power2.out"
                    });
                    
                    gsap.to(card.querySelector('.card-glow'), {
                        opacity: 1,
                        scale: 0.8,
                        duration: 0.6,
                        ease: "power2.out"
                    });
                    
                    gsap.to(card.querySelector('.competition-icon'), {
                        scale: 1.1,
                        rotation: 5,
                        duration: 0.4,
                        ease: "power2.out"
                    });
                });

                card.addEventListener('mouseleave', () => {
                    gsap.to(card, {
                        y: 0,
                        scale: 1,
                        duration: 0.4,
                        ease: "power2.out"
                    });
                    
                    gsap.to(card.querySelector('.card-glow'), {
                        opacity: 0,
                        scale: 1,
                        duration: 0.6,
                        ease: "power2.out"
                    });
                    
                    gsap.to(card.querySelector('.competition-icon'), {
                        scale: 1,
                        rotation: 0,
                        duration: 0.4,
                        ease: "power2.out"
                    });
                });
            });

            // Liliefors Section Animations
            gsap.set('.speaker-frame', { opacity: 0, scale: 0.8, rotationY: 45 });
            gsap.set('.details-card', { opacity: 0, x: 100 });
            gsap.set('.achievement-item', { opacity: 0, y: 30 });

            ScrollTrigger.create({
                trigger: '.liliefors-section',
                start: 'top 60%',
                onEnter: () => {
                    // Speaker frame animation
                    gsap.to('.speaker-frame', {
                        opacity: 1,
                        scale: 1,
                        rotationY: 0,
                        duration: 1.5,
                        ease: "power3.out"
                    });
                    
                    // Details card animation
                    gsap.to('.details-card', {
                        opacity: 1,
                        x: 0,
                        duration: 1.2,
                        delay: 0.5,
                        ease: "power3.out"
                    });
                    
                    // Achievement items stagger animation
                    gsap.to('.achievement-item', {
                        opacity: 1,
                        y: 0,
                        duration: 0.8,
                        stagger: 0.2,
                        delay: 1,
                        ease: "power2.out"
                    });
                }
            });

            // Floating elements continuous animation
            gsap.to('.float-element', {
                y: -20,
                duration: 3,
                stagger: 0.5,
                yoyo: true,
                repeat: -1,
                ease: "power2.inOut"
            });

            // Frame glow pulsing animation
            gsap.to('.frame-glow', {
                opacity: 0.8,
                scale: 1.05,
                duration: 3,
                yoyo: true,
                repeat: -1,
                ease: "power2.inOut"
            });

            // Timeline Section Animations
            gsap.set('.timeline-line', { scaleY: 0, transformOrigin: 'top' });
            gsap.set('.timeline-item', { opacity: 0 });

            ScrollTrigger.create({
                trigger: '.timeline-section',
                start: 'top 70%',
                onEnter: () => {
                    // Timeline line animation
                    gsap.to('.timeline-line', {
                        scaleY: 1,
                        duration: 2,
                        ease: "power2.out"
                    });
                    
                    // Timeline items animation
                    document.querySelectorAll('.timeline-item').forEach((item, index) => {
                        const isEven = index % 2 === 1;
                        
                        gsap.set(item, { opacity: 0 });
                        gsap.set(item.querySelector('.timeline-content'), { 
                            x: isEven ? 100 : -100,
                            opacity: 0
                        });
                        gsap.set(item.querySelector('.timeline-marker'), {
                            scale: 0,
                            opacity: 0
                        });

                        gsap.to(item, {
                            opacity: 1,
                            duration: 0.1,
                            delay: 0.5 + (index * 0.3)
                        });

                        gsap.to(item.querySelector('.timeline-content'), {
                            x: 0,
                            opacity: 1,
                            duration: 1,
                            delay: 0.5 + (index * 0.3),
                            ease: "power3.out"
                        });

                        gsap.to(item.querySelector('.timeline-marker'), {
                            scale: 1,
                            opacity: 1,
                            duration: 0.6,
                            delay: 0.8 + (index * 0.3),
                            ease: "back.out(1.7)"
                        });
                    });
                }
            });

            // Timeline status dots pulsing
            gsap.to('.status-dot', {
                opacity: 0.5,
                duration: 1,
                yoyo: true,
                repeat: -1,
                stagger: 0.3,
                ease: "power2.inOut"
            });

            // Footer entrance animation
            gsap.set('.footer-brand, .link-group', { opacity: 0, y: 50 });

            ScrollTrigger.create({
                trigger: '.footer',
                start: 'top 80%',
                onEnter: () => {
                    gsap.to('.footer-brand', {
                        opacity: 1,
                        y: 0,
                        duration: 1,
                        ease: "power2.out"
                    });
                    
                    gsap.to('.link-group', {
                        opacity: 1,
                        y: 0,
                        duration: 0.8,
                        stagger: 0.2,
                        delay: 0.3,
                        ease: "power2.out"
                    });
                }
            });

            // Parallax effect for section backgrounds
            gsap.to('.liliecomp-section::before', {
                yPercent: -50,
                ease: "none",
                scrollTrigger: {
                    trigger: '.liliecomp-section',
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: true
                }
            });

            // Mouse follow effect for competition cards
            document.querySelectorAll('.competition-card').forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left - rect.width / 2;
                    const y = e.clientY - rect.top - rect.height / 2;
                    
                    gsap.to(card, {
                        rotationY: x / 10,
                        rotationX: -y / 10,
                        duration: 0.3,
                        ease: "power2.out"
                    });
                });

                card.addEventListener('mouseleave', () => {
                    gsap.to(card, {
                        rotationY: 0,
                        rotationX: 0,
                        duration: 0.5,
                        ease: "power2.out"
                    });
                });
            });

            // Continuous animations for background elements
            gsap.to('.card-pattern', {
                rotation: 360,
                duration: 20,
                repeat: -1,
                ease: "none"
            });

            // Banner content bounce animation
            gsap.to('.banner-icon', {
                y: -10,
                duration: 0.6,
                yoyo: true,
                repeat: -1,
                ease: "power2.inOut"
            });
        }

        // Initialize all animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for GSAP to be ready
            if (typeof gsap !== 'undefined') {
                initAllAnimations();
            } else {
                // Fallback if GSAP isn't loaded
                setTimeout(initAllAnimations, 500);
            }
        });

        // Function to show anonymous photo when provided
        function showAnonymousPhoto(imageSrc) {
            const placeholder = document.querySelector('.placeholder-content');
            const photo = document.querySelector('.anonymous-photo');
            
            if (imageSrc && photo) {
                photo.src = imageSrc;
                photo.style.display = 'block';
                placeholder.style.display = 'none';
                
                // Animate photo reveal
                gsap.fromTo(photo, 
                    { opacity: 0, scale: 0.8 },
                    { opacity: 1, scale: 1, duration: 1, ease: "power2.out" }
                );
            }z
        }

        // Intersection Observer for performance
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        }; 

        // Enhanced scroll animations with performance optimization
        const animateOnScroll = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, observerOptions);

        // Observe all animated elements
        document.querySelectorAll('.competition-card, .timeline-item, .speaker-frame').forEach(el => {
            animateOnScroll.observe(el);
        });
    </script>
</body>
</html>