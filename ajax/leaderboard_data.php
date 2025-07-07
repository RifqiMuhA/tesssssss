<?php
// This file should be included from leaderboard.php when AJAX request is made
// Variables $leaderboard, $current_user_rank, $user_data, $badge are already available from leaderboard.php
?>

<!-- Current User Rank -->
<div class="current-rank">
    <div class="rank-card">
        <div class="rank-content">
            <h2 class="rank-title">Peringkat Kamu</h2>
            <div class="rank-info">
                <div class="rank-number">#<?php echo $current_user_rank; ?></div>
                <div class="rank-details">
                    <h3><?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>
                    <div class="rank-stats">
                        <div class="rank-stat">
                            <span class="rank-stat-value"><?php echo number_format($user_data['points']); ?></span>
                            <span class="rank-stat-label">Poin</span>
                        </div>
                        <div class="rank-stat">
                            <span class="rank-stat-value"><?php echo number_format($user_data['total_questions_answered']); ?></span>
                            <span class="rank-stat-label">Soal</span>
                        </div>
                        <div class="rank-stat">
                            <span class="rank-stat-value"><?php echo $user_data['accuracy']; ?>%</span>
                            <span class="rank-stat-label">Akurasi</span>
                        </div>
                        <div class="rank-stat">
                            <span class="rank-stat-value" style="background-color: <?php echo $badge['color']; ?>; padding: 0.25rem 0.5rem; border-radius: 0.375rem; color: white; font-size: 0.75rem;">
                                <?php echo $badge['name']; ?>
                            </span>
                            <span class="rank-stat-label">Badge</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaderboard -->
<div class="leaderboard-container">
    <div class="table-header">
        <h2>Pengguna Jagoan</h2>
    </div>

    <?php if (count($leaderboard) > 0): ?>
        <div class="leaderboard-list">
            <?php foreach ($leaderboard as $index => $user): ?>
                <?php
                $rank = $index + 1;
                $badge = getUserBadge($user['points']);
                $is_current_user = ($user['id'] == $_SESSION['user_id']);
                ?>
                <div class="leaderboard-item <?php echo $is_current_user ? 'current-user' : ''; ?> <?php echo $rank <= 3 ? 'rank-' . $rank : ''; ?>" style="--index: <?php echo $index; ?>">
                    <!-- Rank -->
                    <div class="rank-display">
                        <?php if ($rank <= 3): ?>
                            <div class="medal">
                                <?php if ($rank == 1): ?>🥇<?php elseif ($rank == 2): ?>🥈<?php else: ?>🥉<?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="rank-num"><?php echo $rank; ?></div>
                    </div>

                    <!-- User Info -->
                    <div class="user-info">
                        <h4>
                            <?php echo htmlspecialchars($user['full_name']); ?>
                            <?php if ($is_current_user): ?>
                                <span class="you-badge">Anda</span>
                            <?php endif; ?>
                        </h4>
                        <?php if ($user['school_name']): ?>
                            <div class="school"><?php echo htmlspecialchars($user['school_name']); ?></div>
                        <?php endif; ?>
                        <?php if ($user['grade']): ?>
                            <div class="grade">Kelas <?php echo $user['grade']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Points -->
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($user['points']); ?></span>
                        <span class="stat-label-small">Poin</span>
                    </div>

                    <!-- Questions -->
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($user['total_questions_answered']); ?></span>
                        <span class="stat-label-small">Soal</span>
                    </div>

                    <!-- Accuracy -->
                    <div class="accuracy-display">
                        <div class="accuracy-percentage"><?php echo $user['accuracy']; ?>%</div>
                        <div class="accuracy-bar">
                            <div class="accuracy-fill" style="width: <?php echo $user['accuracy']; ?>%"></div>
                        </div>
                    </div>

                    <!-- Badge -->
                    <div class="badge" style="background-color: <?php echo $badge['color']; ?>">
                        <?php echo $badge['name']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📊</div>
            <h3>Belum ada data leaderboard</h3>
            <p>Mulai drilling soal untuk masuk ke leaderboard</p>
            <a href="drilling.php" class="btn btn-primary">🚀 Mulai Drilling</a>
        </div>
    <?php endif; ?>
</div>