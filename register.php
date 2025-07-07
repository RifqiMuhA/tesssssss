<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $school_name = sanitize($_POST['school_name']);
    $grade = sanitize($_POST['grade']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Field yang wajib harus diisi';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak sama';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Username atau email sudah digunakan';
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, full_name, school_name, grade) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$username, $email, $hashed_password, $full_name, $school_name, $grade])) {
                $success = 'Akun berhasil dibuat. Silakan login.';
            } else {
                $error = 'Terjadi kesalahan saat membuat akun';
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
    <title>Daftar - Drill PTN</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/pages/register.css?v=<?php echo time(); ?>">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
</head>
<body data-error="<?php echo htmlspecialchars($error); ?>" data-success="<?php echo htmlspecialchars($success); ?>">
    <div class="register-container">
        <div class="register-wrapper">
            <!-- Form Section - Di Kiri -->
            <div class="register-form-section">
                <div class="register-header">
                    <h1>Daftar <span class="highlight">Akun</span></h1>
                    <p>Buat akun baru untuk memulai perjalanan UTBK</p>
                </div>
                
                <div class="alert alert-error" style="display: none;" id="errorAlert">
                    <span id="errorMessage"></span>
                </div>
                
                <div class="alert alert-success" style="display: none;" id="successAlert">
                    <span id="successMessage"></span>
                </div>
                
                <form method="POST" class="register-form" id="registerForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" required 
                                   placeholder="Username unik Anda"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required 
                                   placeholder="email@example.com"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Nama Lengkap *</label>
                        <input type="text" id="full_name" name="full_name" required 
                               placeholder="Nama lengkap Anda"
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="school_name">Nama Sekolah</label>
                            <input type="text" id="school_name" name="school_name" 
                                   placeholder="SMA/SMK Anda"
                                   value="<?php echo isset($_POST['school_name']) ? htmlspecialchars($_POST['school_name']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="grade">Kelas</label>
                            <select id="grade" name="grade">
                                <option value="">Pilih Kelas</option>
                                <option value="10" <?php echo (isset($_POST['grade']) && $_POST['grade'] == '10') ? 'selected' : ''; ?>>Kelas 10</option>
                                <option value="11" <?php echo (isset($_POST['grade']) && $_POST['grade'] == '11') ? 'selected' : ''; ?>>Kelas 11</option>
                                <option value="12" <?php echo (isset($_POST['grade']) && $_POST['grade'] == '12') ? 'selected' : ''; ?>>Kelas 12</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" required 
                                   placeholder="Min. 6 karakter">
                            <div id="password-strength" class="password-indicator"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Ulangi password">
                            <div id="password-match" class="password-indicator"></div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span id="btnText">Daftar Sekarang</span>
                    </button>
                </form>
                
                <div class="register-footer">
                    <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
                    <p><a href="index.php">← Kembali ke beranda</a></p>
                </div>
            </div>

            <!-- Visual Section - Di Kanan -->
            <div class="register-visual">
                <div class="visual-icon">
                    <img src="assets/img/logo.png" alt="Logo Drill PTN" style="width: 50px; height: 50px;">
                </div>
                <h2 class="visual-title">Bergabunglah</h2>
                <p class="visual-subtitle">Ribuan pejuang PTN sudah bergabung. Saatnya Anda menjadi bagian dari mereka!</p>
            </div>
        </div>
    </div>

    <script src="assets/js/pages/register.js?v=<?php echo time(); ?>"></script>
</body>
</html>