<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf')) && vpy_post('action') === 'delete') {
    vpy_delete('blog', 'id', (int)vpy_post('id'));
    vpy_flash_set('success', t('msg_deleted'));
    vpy_redirect('/admin/blog.php');
}

$posts = vpy_read_json('blog', []);
usort($posts, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

vpy_panel_head(t('admin_blog'));
vpy_panel_sidebar('blog', true);
?>
<main class="main">
<?php vpy_panel_topbar(t('admin_blog'), count($posts) . ' ta post',
    '<a href="/admin/blog-form.php" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>' . e(t('admin_add')) . '</a>'
); ?>
<div class="card">
    <?php if (empty($posts)): ?>
        <div class="empty"><h3>Postlar yo'q</h3></div>
    <?php else: ?>
    <table class="tbl">
        <thead><tr><th>#</th><th>Sarlavha</th><th>Kategoriya</th><th>Ko'rishlar</th><th>Status</th><th>Sana</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($posts as $p): ?>
            <tr>
                <td>#<?= (int)$p['id'] ?></td>
                <td>
                    <strong><?= e($p['title']) ?></strong>
                    <div style="font-size:0.78rem;color:var(--muted)">/<?= e($p['slug']) ?></div>
                </td>
                <td><span class="chip chip-success"><?= e($p['category'] ?? '—') ?></span></td>
                <td><?= number_format((int)($p['views'] ?? 0)) ?></td>
                <td>
                    <?php if (($p['status'] ?? '') === 'published'): ?>
                        <span class="chip chip-success">Chop etilgan</span>
                    <?php else: ?>
                        <span class="chip chip-muted">Qoralama</span>
                    <?php endif; ?>
                    <?php if (!empty($p['featured'])): ?>
                        <span class="chip chip-warning">★</span>
                    <?php endif; ?>
                </td>
                <td><?= e(vpy_date($p['created_at'], 'd.m.Y')) ?></td>
                <td>
                    <div class="row-actions">
                        <a href="/blog.php?slug=<?= e($p['slug']) ?>" target="_blank"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>
                        <a href="/admin/blog-form.php?id=<?= (int)$p['id'] ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
                        <form method="post" style="display:inline" onsubmit="return confirm('O\'chirilsinmi?')">
                            <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="danger"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 01-2 2H9a2 2 0 01-2-2L5 6"/></svg></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</main>
<?php vpy_panel_foot(); ?>
