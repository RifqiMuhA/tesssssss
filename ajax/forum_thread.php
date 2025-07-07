<?php
// AHAX untuk forum.php
?>

<!-- Thread List -->
<div class="thread-list">
    <?php if (count($threads) > 0): ?>
        <?php foreach ($threads as $thread): ?>
            <div class="thread-item <?php echo $thread['is_pinned'] ? 'pinned' : ''; ?>"
                onclick="window.location.href='thread.php?id=<?php echo $thread['id']; ?>'">
                <div class="thread-content">
                    <div class="thread-header">
                        <div class="thread-badges">
                            <?php if ($thread['is_pinned']): ?>
                                <span class="badge badge-pinned">Pinned</span>
                            <?php endif; ?>
                            <?php if ($thread['is_locked']): ?>
                                <span class="badge badge-locked">Locked</span>
                            <?php endif; ?>
                            <span class="badge badge-category"><?php echo htmlspecialchars($thread['category_name']); ?></span>
                        </div>
                    </div>

                    <h3 class="thread-title">
                        <?php echo htmlspecialchars($thread['title']); ?>
                    </h3>

                    <div class="thread-meta">
                        <span class="thread-author">oleh <?php echo htmlspecialchars($thread['author_name']); ?></span>
                        <span class="thread-date"><?php echo date('d M Y, H:i', strtotime($thread['created_at'])); ?></span>
                        <?php if ($thread['last_reply_at']): ?>
                            <span class="last-reply">
                                Balasan terakhir oleh <?php echo htmlspecialchars($thread['last_reply_author']); ?>
                                pada <?php echo date('d M Y, H:i', strtotime($thread['last_reply_at'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="thread-stats">
                    <div class="stat-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <span><?php echo number_format($thread['views_count']); ?></span>
                    </div>
                    <div class="stat-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                        <span><?php echo number_format($thread['replies_count']); ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.35-4.35" />
            </svg>
            <h3>Tidak ada thread ditemukan</h3>
            <p>Coba ubah filter pencarian atau buat thread baru</p>
            <a href="new-thread.php" class="btn btn-primary">Buat Thread Baru</a>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="#" class="pagination-btn" data-page="<?php echo $page - 1; ?>">← Sebelumnya</a>
        <?php endif; ?>

        <div class="pagination-info">
            Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
        </div>

        <?php if ($page < $total_pages): ?>
            <a href="#" class="pagination-btn" data-page="<?php echo $page + 1; ?>">Selanjutnya →</a>
        <?php endif; ?>
    </div>
<?php endif; ?>