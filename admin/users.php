<?php
require_once '../config.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Handle actions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'toggle_status') {
        $user_id = (int)$_POST['user_id'];

        // Get current status first
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ? AND role_id = 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user) {
            $new_status = $user['is_active'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role_id = 1");
            if ($stmt->execute([$new_status, $user_id])) {
                $success = $new_status ? 'User berhasil diaktifkan' : 'User berhasil dinonaktifkan';
            } else {
                $error = 'Gagal mengubah status user';
            }
        } else {
            $error = 'User tidak ditemukan';
        }
    } elseif ($action == 'edit_user') {
        $user_id = (int)$_POST['user_id'];
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $username = sanitize($_POST['username']);
        $school_name = sanitize($_POST['school_name']);
        $grade = $_POST['grade'] ? (int)$_POST['grade'] : null;

        // Check if username/email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        if ($stmt->fetch()) {
            $error = 'Username atau email sudah digunakan';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, username = ?, school_name = ?, grade = ? WHERE id = ? AND role_id = 1");
            if ($stmt->execute([$full_name, $email, $username, $school_name, $grade, $user_id])) {
                $success = 'Data user berhasil diperbarui';
            } else {
                $error = 'Gagal memperbarui data user';
            }
        }
    } elseif ($action == 'reset_password') {
        $user_id = (int)$_POST['user_id'];
        $new_password = password_hash('password123', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role_id = 1");
        if ($stmt->execute([$new_password, $user_id])) {
            $success = 'Password berhasil direset ke "password123"';
        } else {
            $error = 'Gagal mereset password';
        }
    }
}

// For initial page load - get default data
$page = 1;
$per_page = 20;
$offset = 0;

// Default query for initial load
$stmt = $pdo->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM user_sessions us WHERE us.user_id = u.id AND us.is_completed = 1) as completed_sessions,
           (SELECT COUNT(*) FROM user_answers ua WHERE ua.user_id = u.id AND ua.is_correct = 1) as correct_answers
    FROM users u
    WHERE role_id = 1
    ORDER BY u.created_at DESC
    LIMIT $offset, $per_page
");

$stmt->execute();
$users = $stmt->fetchAll();

// Get total count for initial load
$count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role_id = 1");
$count_stmt->execute();
$total_users = $count_stmt->fetch()['total'];
$total_pages = ceil($total_users / $per_page);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin/admin_layout.css">
    <link rel="stylesheet" href="../assets/css/admin/admin_users.css">
    <link rel="icon" href="../assets/img/logo.png" type="image/png">
</head>

<body>
    <div class="admin-layout">
        <?php include '../includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-container">
                <div class="admin-header">
                    <div>
                        <h1>User Management</h1>
                        <p>Kelola pengguna platform DrillPTN</p>
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

                <!-- Filters -->
                <div class="admin-filters">
                    <div class="filter-form">
                        <div class="filter-row">
                            <div class="search-group">
                                <input type="text" id="searchInput" placeholder="Cari nama, username, atau email..." class="search-input">
                                <button type="button" id="searchBtn" class="search-btn">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8" />
                                        <path d="m21 21-4.35-4.35" />
                                    </svg>
                                </button>
                            </div>

                            <select id="statusFilter" class="filter-select">
                                <option value="">Semua Status</option>
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>

                            <select id="gradeFilter" class="filter-select">
                                <option value="">Semua Kelas</option>
                                <option value="10">Kelas 10</option>
                                <option value="11">Kelas 11</option>
                                <option value="12">Kelas 12</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Loading Indicator -->
                <div id="loadingIndicator" class="loading-indicator" style="display: none;">
                    <div class="loading-spinner"></div>
                    <p>Memuat data...</p>
                </div>

                <!-- Users Table Container -->
                <div class="admin-table-container" id="usersTableContainer">
                    <!-- Initial content will be loaded here -->
                </div>

                <!-- Pagination Container -->
                <div id="paginationContainer"></div>
            </div>
        </main>
    </div>

    <!-- Edit User Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Edit User</h3>
                <button class="modal-close" type="button">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" class="modal-form" id="editForm">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="editUserId">

                    <div class="form-group">
                        <label for="editFullName">Nama Lengkap</label>
                        <input type="text" id="editFullName" name="full_name" required>
                    </div>

                    <div class="form-group">
                        <label for="editUsername">Username</label>
                        <input type="text" id="editUsername" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="editEmail">Email</label>
                        <input type="email" id="editEmail" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="editSchool">Nama Sekolah</label>
                        <input type="text" id="editSchool" name="school_name">
                    </div>

                    <div class="form-group">
                        <label for="editGrade">Kelas</label>
                        <select id="editGrade" name="grade">
                            <option value="">Pilih Kelas</option>
                            <option value="10">Kelas 10</option>
                            <option value="11">Kelas 11</option>
                            <option value="12">Kelas 12</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" form="editForm" class="btn-primary">Simpan</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin/admin_layout.js"></script>
    <script src="../assets/js/admin/admin_users.js"></script>
</body>

</html>