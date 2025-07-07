<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $pdo->prepare("
    SELECT u.*, r.name as role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('logout.php');
}

// Get user statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT us.id) as total_sessions,
        COUNT(DISTINCT CASE WHEN us.is_completed = 1 THEN us.id END) as completed_sessions,
        SUM(CASE WHEN ua.is_correct = 1 THEN 1 ELSE 0 END) as total_correct,
        COUNT(ua.id) as total_answered,
        SUM(ua.points_earned) as total_points_earned
    FROM users u
    LEFT JOIN user_sessions us ON u.id = us.user_id
    LEFT JOIN user_answers ua ON u.id = ua.user_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

$accuracy = $stats['total_answered'] > 0 ? round(($stats['total_correct'] / $stats['total_answered']) * 100, 1) : 0;
$badge = getUserBadge($user['points']);

// Get recent sessions
$stmt = $pdo->prepare("
    SELECT us.*, qc.name as category_name, qc.color as category_color,
           qt.name as topic_name
    FROM user_sessions us
    JOIN question_categories qc ON us.category_id = qc.id
    LEFT JOIN question_topics qt ON us.topic_id = qt.id
    WHERE us.user_id = ?
    ORDER BY us.session_start DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_sessions = $stmt->fetchAll();

// Get category performance
$stmt = $pdo->prepare("
    SELECT qc.name as category_name, qc.color as category_color,
           COUNT(ua.id) as questions_answered,
           SUM(CASE WHEN ua.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
           SUM(ua.points_earned) as points_earned
    FROM question_categories qc
    LEFT JOIN question_topics qt ON qc.id = qt.category_id
    LEFT JOIN questions q ON qt.id = q.topic_id
    LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.user_id = ?
    GROUP BY qc.id, qc.name, qc.color
    HAVING questions_answered > 0
    ORDER BY points_earned DESC
");
$stmt->execute([$user_id]);
$category_performance = $stmt->fetchAll();

// Handle form submissions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'update_avatar') {
        // Handle avatar upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($file['type'], $allowedTypes)) {
                $error = 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP';
            } elseif ($file['size'] > $maxSize) {
                $error = 'Ukuran file terlalu besar. Maksimal 5MB';
            } else {
                // Create avatars directory if not exists
                $avatarDir = 'assets/img/avatars/';
                if (!file_exists($avatarDir)) {
                    mkdir($avatarDir, 0755, true);
                }

                // Generate unique filename
                $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = 'avatar_' . $user_id . '_' . time() . '.' . $fileExtension;
                $filePath = $avatarDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    // Delete old avatar if exists
                    if ($user['avatar'] && file_exists($user['avatar'])) {
                        unlink($user['avatar']);
                    }

                    // Update database
                    $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    if ($stmt->execute([$filePath, $user_id])) {
                        $success = 'Foto profil berhasil diperbarui';
                        $user['avatar'] = $filePath; // Update local variable
                    } else {
                        $error = 'Gagal menyimpan foto profil ke database';
                        unlink($filePath); // Delete uploaded file
                    }
                } else {
                    $error = 'Gagal mengunggah file';
                }
            }
        } else {
            $error = 'Tidak ada file yang dipilih atau terjadi kesalahan';
        }
    } elseif ($action == 'remove_avatar') {
        // Remove avatar
        if ($user['avatar'] && file_exists($user['avatar'])) {
            unlink($user['avatar']);
        }

        $stmt = $pdo->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $success = 'Foto profil berhasil dihapus';
            $user['avatar'] = null;
        } else {
            $error = 'Gagal menghapus foto profil';
        }
    } elseif ($action == 'update_profile') {
        $full_name = sanitize($_POST['full_name']);
        $school_name = sanitize($_POST['school_name']);
        $grade = sanitize($_POST['grade']);

        if (empty($full_name)) {
            $error = 'Nama lengkap harus diisi';
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, school_name = ?, grade = ?
                WHERE id = ?
            ");

            if ($stmt->execute([$full_name, $school_name, $grade, $user_id])) {
                $success = 'Profile berhasil diperbarui';
                // Update session data
                $_SESSION['full_name'] = $full_name;
                // Refresh user data
                $user['full_name'] = $full_name;
                $user['school_name'] = $school_name;
                $user['grade'] = $grade;
            } else {
                $error = 'Terjadi kesalahan saat memperbarui profile';
            }
        }
    } elseif ($action == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password)) {
            $error = 'Password lama dan baru harus diisi';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Password baru dan konfirmasi tidak sama';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password baru minimal 6 karakter';
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = 'Password lama tidak benar';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");

            if ($stmt->execute([$hashed_password, $user_id])) {
                $success = 'Password berhasil diubah';
            } else {
                $error = 'Terjadi kesalahan saat mengubah password';
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
    <title>Profile - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/pages/profile.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="profile-header">
            <div class="profile-info-container">
                <div class="profile-avatar">
                    <div class="avatar-circle">
                        <?php if ($user['avatar'] && file_exists($user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>"
                                alt="<?php echo htmlspecialchars($user['full_name']); ?>">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-details">
                    <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <div class="profile-badges">
                        <span class="badge" style="background-color: <?php echo $badge['color']; ?>">
                            <?php echo $badge['name']; ?>
                        </span>
                        <?php if ($user['grade']): ?>
                            <span class="badge grade-badge">Kelas <?php echo $user['grade']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="profile-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($user['points']); ?></div>
                    <div class="stat-label">Total Poin</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_answered']); ?></div>
                    <div class="stat-label">Soal Dijawab</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $accuracy; ?>%</div>
                    <div class="stat-label">Akurasi</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['completed_sessions']; ?></div>
                    <div class="stat-label">Sesi Selesai</div>
                </div>
            </div>
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

        <div class="profile-content">
            <!-- Avatar Upload Section -->
            <div class="avatar-section">
                <h2>Foto Profil</h2>

                <div class="avatar-upload-area">
                    <div class="avatar-preview">
                        <?php if ($user['avatar'] && file_exists($user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>"
                                alt="<?php echo htmlspecialchars($user['full_name']); ?>"
                                id="avatar-preview-img">
                        <?php else: ?>
                            <span id="avatar-preview-initials"><?php echo strtoupper(substr($user['full_name'], 0, 2)); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="avatar-upload-content">
                        <h3>Upload Foto Profil</h3>
                        <p>Pilih gambar untuk foto profil Anda. Format yang didukung: JPG, PNG, GIF, WebP (maksimal 5MB)</p>

                        <div class="avatar-upload-actions">
                            <form method="POST" enctype="multipart/form-data" id="avatar-form" style="display: inline;">
                                <input type="hidden" name="action" value="update_avatar">
                                <input type="file" id="avatar-input" name="avatar" accept="image/*">
                                <button type="button" class="btn-avatar btn-upload" onclick="document.getElementById('avatar-input').click()">
                                    Pilih Gambar
                                </button>
                            </form>

                            <?php if ($user['avatar']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove_avatar">
                                    <button type="submit" class="btn-avatar btn-remove"
                                        onclick="return confirm('Yakin ingin menghapus foto profil?')">
                                        Hapus Foto
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Settings (existing code) -->
            <div class="profile-section">
                <h2>Pengaturan Profile</h2>

                <form method="POST" class="profile-form">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Nama Lengkap *</label>
                            <input type="text" id="full_name" name="full_name" required
                                value="<?php echo htmlspecialchars($user['full_name']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                                disabled class="disabled-input">
                            <small>Email tidak dapat diubah</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="school_name">Nama Sekolah</label>
                            <input type="text" id="school_name" name="school_name"
                                value="<?php echo htmlspecialchars($user['school_name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="grade">Kelas</label>
                            <select id="grade" name="grade">
                                <option value="">Pilih Kelas</option>
                                <option value="10" <?php echo ($user['grade'] == '10') ? 'selected' : ''; ?>>10</option>
                                <option value="11" <?php echo ($user['grade'] == '11') ? 'selected' : ''; ?>>11</option>
                                <option value="12" <?php echo ($user['grade'] == '12') ? 'selected' : ''; ?>>12</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="profile-section">
                <h2>Ubah Password</h2>

                <form method="POST" class="password-form">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label for="current_password">Password Lama *</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">Password Baru *</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password Baru *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-secondary">Ubah Password</button>
                </form>
            </div>

            <!-- Category Performance -->
            <?php if (count($category_performance) > 0): ?>
                <div class="profile-section">
                    <h2>Performa per Kategori</h2>
 
                    <div class="category-performance">
                        <?php foreach ($category_performance as $category): ?>
                            <?php $cat_accuracy = round(($category['correct_answers'] / $category['questions_answered']) * 100, 1); ?>
                            <div class="category-card" style="border-left-color: <?php echo $category['category_color']; ?>">
                                <div class="category-header">
                                    <h4><?php echo htmlspecialchars($category['category_name']); ?></h4>
                                    <span class="category-accuracy"><?php echo $cat_accuracy; ?>%</span>
                                </div>

                                <div class="category-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">Soal Dijawab</span>
                                        <span class="stat-value"><?php echo number_format($category['questions_answered']); ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Benar</span>
                                        <span class="stat-value"><?php echo number_format($category['correct_answers']); ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Poin</span>
                                        <span class="stat-value"><?php echo number_format($category['points_earned']); ?></span>
                                    </div>
                                </div>

                                <div class="category-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $cat_accuracy; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recent Sessions -->
            <?php if (count($recent_sessions) > 0): ?>
                <div class="profile-section">
                    <h2>Riwayat Drilling Terbaru</h2>

                    <div class="session-history">
                        <?php foreach ($recent_sessions as $session): ?>
                            <?php
                            $session_accuracy = $session['questions_answered'] > 0 ?
                                round(($session['correct_answers'] / $session['questions_answered']) * 100, 1) : 0;
                            ?>
                            <div class="session-card">
                                <div class="session-header">
                                    <div class="session-category" style="background-color: <?php echo $session['category_color']; ?>">
                                        <?php echo htmlspecialchars($session['category_name']); ?>
                                    </div>
                                    <div class="session-date">
                                        <?php echo date('d M Y, H:i', strtotime($session['session_start'])); ?>
                                    </div>
                                </div>

                                <div class="session-content">
                                    <?php if ($session['topic_name']): ?>
                                        <div class="session-topic"><?php echo htmlspecialchars($session['topic_name']); ?></div>
                                    <?php endif; ?>

                                    <div class="session-stats">
                                        <span class="session-progress">
                                            <?php echo $session['questions_answered']; ?>/<?php echo $session['total_questions']; ?> soal
                                        </span>
                                        <span class="session-accuracy"><?php echo $session_accuracy; ?>% akurasi</span>
                                        <span class="session-status <?php echo $session['is_completed'] ? 'completed' : 'active'; ?>">
                                            <?php echo $session['is_completed'] ? 'Selesai' : 'Aktif'; ?>
                                        </span>
                                    </div>
                                </div>

                                <?php if (!$session['is_completed'] && $session['is_active']): ?>
                                    <div class="session-actions">
                                        <a href="question.php?session_id=<?php echo $session['id']; ?>" class="btn btn-sm btn-primary">
                                            Lanjutkan
                                        </a>
                                    </div>
                                <?php elseif ($session['is_completed']): ?>
                                    <div class="session-actions">
                                        <a href="result.php?session_id=<?php echo $session['id']; ?>" class="btn btn-sm btn-outline">
                                            Lihat Hasil
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Avatar upload preview and handling
        document.getElementById('avatar-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP');
                    this.value = '';
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar. Maksimal 5MB');
                    this.value = '';
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.avatar-preview');
                    const initials = document.getElementById('avatar-preview-initials');
                    const existingImg = document.getElementById('avatar-preview-img');

                    if (existingImg) {
                        existingImg.src = e.target.result;
                    } else {
                        if (initials) initials.style.display = 'none';
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.id = 'avatar-preview-img';
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.objectFit = 'cover';
                        img.style.borderRadius = '50%';
                        preview.appendChild(img);
                    }

                    // Also update main avatar
                    const mainAvatar = document.querySelector('.profile-avatar .avatar-circle');
                    const mainImg = mainAvatar.querySelector('img');
                    const mainInitials = mainAvatar.querySelector('span');

                    if (mainImg) {
                        mainImg.src = e.target.result;
                    } else {
                        if (mainInitials) mainInitials.style.display = 'none';
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.style.width = '100%';
                        newImg.style.height = '100%';
                        newImg.style.objectFit = 'cover';
                        newImg.style.borderRadius = '50%';
                        mainAvatar.appendChild(newImg);
                    }
                };
                reader.readAsDataURL(file);

                // Auto submit form
                setTimeout(() => {
                    if (confirm('Upload foto profil sekarang?')) {
                        document.getElementById('avatar-form').submit();
                    }
                }, 500);
            }
        });

        // Profile form validation
        document.querySelector('.profile-form').addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value.trim();

            if (!fullName) {
                e.preventDefault();
                alert('Nama lengkap harus diisi');
                document.getElementById('full_name').focus();
                return false;
            }

            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            setLoading(submitBtn, true);
        });

        // Password form validation
        document.querySelector('.password-form').addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (!currentPassword || !newPassword || !confirmPassword) {
                e.preventDefault();
                alert('Semua field password harus diisi');
                return false;
            }

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Password baru dan konfirmasi tidak sama');
                document.getElementById('confirm_password').focus();
                return false;
            }

            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Password baru minimal 6 karakter');
                document.getElementById('new_password').focus();
                return false;
            }

            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            setLoading(submitBtn, true);
        });

        // Real-time password confirmation
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        if (newPasswordInput && confirmPasswordInput) {
            function validatePasswordMatch() {
                const newPassword = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                // Remove existing indicator
                const existingIndicator = document.getElementById('password-match-indicator');
                if (existingIndicator) {
                    existingIndicator.remove();
                }

                if (confirmPassword) {
                    const indicator = document.createElement('div');
                    indicator.id = 'password-match-indicator';
                    indicator.style.fontSize = '0.75rem';
                    indicator.style.marginTop = '0.25rem';

                    if (newPassword === confirmPassword) {
                        indicator.textContent = 'Password cocok ✓';
                        indicator.style.color = '#10b981';
                        confirmPasswordInput.style.borderColor = '#10b981';
                    } else {
                        indicator.textContent = 'Password tidak cocok';
                        indicator.style.color = '#ef4444';
                        confirmPasswordInput.style.borderColor = '#ef4444';
                    }

                    confirmPasswordInput.parentNode.appendChild(indicator);
                } else {
                    confirmPasswordInput.style.borderColor = '';
                }
            }

            confirmPasswordInput.addEventListener('input', validatePasswordMatch);
            newPasswordInput.addEventListener('input', () => {
                if (confirmPasswordInput.value) {
                    validatePasswordMatch();
                }
            });
        }

        // Stats animation
        function animateStats() {
            const statNumbers = document.querySelectorAll('.stat-number');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateStatNumber(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.5
            });

            statNumbers.forEach(stat => observer.observe(stat));
        }

        function animateStatNumber(element) {
            const finalValue = parseInt(element.textContent.replace(/[,%]/g, ''));
            if (isNaN(finalValue)) return;

            let currentValue = 0;
            const increment = finalValue / 30;
            const isPercentage = element.textContent.includes('%');

            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    currentValue = finalValue;
                    clearInterval(timer);
                }

                const displayValue = Math.floor(currentValue);
                element.textContent = isPercentage ?
                    `${displayValue}%` :
                    displayValue.toLocaleString();
            }, 50);
        }

        // Session card interactions
        function initializeSessionCards() {
            const sessionCards = document.querySelectorAll('.session-card');

            sessionCards.forEach(card => {
                card.addEventListener('click', (e) => {
                    const actionButton = card.querySelector('.session-actions .btn');
                    if (actionButton && !e.target.closest('.session-actions')) {
                        actionButton.click();
                    }
                });
            });
        }

        // Utility functions
        function setLoading(button, isLoading) {
            if (isLoading) {
                button.disabled = true;
                button.dataset.originalText = button.textContent;
                button.textContent = 'Loading...';
                button.style.opacity = '0.7';
            } else {
                button.disabled = false;
                button.textContent = button.dataset.originalText || 'Submit';
                button.style.opacity = '1';
            }
        }

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            animateStats();
            initializeSessionCards();

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>

    <script src="assets/js/global.js"></script>
    <!-- <script src="assets/js/pages/profile.js"></script> -->
    <script src="assets/js/navbar.js"></script>
</body>

</html>