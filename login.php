<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, full_name, role_id FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_id'] = $user['role_id'];
            
            redirect('drilling.php');
        } else {
            $error = 'Username atau password salah';
        }
    }
}

// Show error from PHP if exists
if (!empty($error)) {
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('errorAlert').style.display = 'block';
                document.getElementById('errorMessage').innerText = '" . htmlspecialchars($error) . "';
            });
          </script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Drill PTN</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/pages/login.css">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
</head>
<body>
    <div class="login-container">
        <div class="login-wrapper">
            <!-- Visual Section (Hidden on Mobile) -->
            <div class="login-visual">
                <div class="visual-icon">
                    <img src="assets/img/logo.png" alt="ogo Drill PTN" style="width: 50px; height: 50px;">
                </div>
                <h2 class="visual-title">Drill PTN</h2>
                <p class="visual-subtitle">Platform CBT UTBK Gratis Terlengkap untuk Pejuang PTN Indonesia</p>
            </div>

            <!-- Form Section -->
            <div class="login-form-section">
                <div class="login-header">
                    <h1>Selamat <span class="highlight">Datang</span></h1>
                    <p>Masuk ke akun Anda untuk mulai berlatih</p>
                </div>
                
                <div class="alert alert-error" style="display: none;" id="errorAlert">
                    <span id="errorMessage"></span>
                </div>
                
                <form method="POST" class="login-form" id="loginForm">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required 
                               placeholder="Masukkan username Anda"
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required 
                               placeholder="Masukkan password Anda">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span id="btnText">Masuk</span>
                    </button>
                </form>
                
                <div class="login-footer">
                    <p>Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
                    <p><a href="index.php">← Kembali ke beranda</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/pages/login.js"></script>
</body>
</html>