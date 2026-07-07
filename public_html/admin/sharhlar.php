<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    $action = vpy_post('action');
    $id = (int)vpy_post('id');
    if ($action === 'delete') {
        vpy_delete('sharhlar', 'id', $id);
        vpy_flash_set('success', t('msg_deleted'));
    } elseif ($action === 'approve' || $action === 'reject' || $action === 'feature' || $action === 'unfeature') {
        $r = vpy_find('sharhlar', 'id', $id);
        if ($r) {
            if ($action === 'approve') $r['status'] = 'approved';
            if ($action === 'reject') $r['status'] = 'rejected';
            if ($action === 'feature') $r['featured'] = true;
            if ($action === 'unfeature') $r['featured'] = false;
            vpy_upsert('sharhlar', $r);
            vpy_flash_set('success', t('msg_updated'));
        }
    }
    vpy_redirect('/admin/sharhlar.php');
}

$reviews = vpy_read_json('sharhlar', []);
usort($reviews, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

vpy_panel_head(t('admin_reviews'));
vpy_panel_sidebar('sharhlar', true);
?>
<main class="main">
<?php vpy_panel_topbar(t('admin_reviews'), count($reviews) . ' ta sharh',
    '<a href="/admin/sharhlar-form.php" class="btn btn-primary">' . e(t('admin_add')) . '</a>'
); ?>

<div class="card">
    <?php if (empty($reviews)): ?>
        <div class="empty"><h3>Sharhlar yo'q</h3></div>
    <?php else: ?>
    <?php foreach ($reviews as $r):
        $color = vpy_avatar_color($r['name']);
    ?>
    <div style="padding:18px;border:1px solid var(--border);border-radius:var(--r);margin-bottom:12px;background:rgba(255,253,249,0.5);display:flex;gap:18px;align-items:flex-start;flex-wrap:wrap">
        <div style="width:48px;height:48px;border-radius:50%;background:<?= e($color) ?>;color:#fff;display:grid;place-items:center;font-weight:700;flex-shrink:0"><?= e(vpy_user_initials($r['name'])) ?></div>
        <div style="flex:1;min-width:200px">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <strong><?= e($r['name']) ?></strong>
                <span style="font-size:0.78rem;color:var(--muted)"><?= e($r['city'] ?? '') ?></span>
                <span style="color:var(--accent)"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></span>
                <?php if (($r['status'] ?? '') === 'approved'): ?><span class="chip chip-success">Tasdiqlangan</span>
                <?php elseif (($r['status'] ?? '') === 'rejected'): ?><span class="chip chip-danger">Bekor qilingan</span>
                <?php else: ?><span class="chip chip-warning">Kutilmoqda</span><?php endif; ?>
                <?php if (!empty($r['featured'])): ?><span class="chip chip-warning">★ Featured</span><?php endif; ?>
            </div>
            <p style="margin-top:10px;line-height:1.5;color:var(--dark-soft)"><?= e($r['text']) ?></p>
            <div style="font-size:0.78rem;color:var(--muted);margin-top:8px"><?= e(vpy_time_ago($r['created_at'])) ?></div>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            <?php if (($r['status'] ?? '') !== 'approved'): ?>
                <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button type="submit" class="btn btn-success btn-sm">Tasdiqlash</button></form>
            <?php endif; ?>
            <?php if (($r['status'] ?? '') === 'approved' && empty($r['featured'])): ?>
                <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>"><input type="hidden" name="action" value="feature"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button type="submit" class="btn btn-ghost btn-sm">★ Featured</button></form>
            <?php elseif (!empty($r['featured'])): ?>
                <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>"><input type="hidden" name="action" value="unfeature"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button type="submit" class="btn btn-ghost btn-sm">Olib tashlash</button></form>
            <?php endif; ?>
            <a href="/admin/sharhlar-form.php?id=<?= (int)$r['id'] ?>" class="btn btn-ghost btn-sm"><?= e(t('admin_edit')) ?></a>
            <form method="post" style="display:inline" onsubmit="return confirm('O\'chirilsinmi?')"><input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button type="submit" class="btn btn-danger btn-sm"><?= e(t('admin_delete')) ?></button></form>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
</main>
<?php vpy_panel_foot(); ?>
