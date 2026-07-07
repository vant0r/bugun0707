<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf')) && vpy_post('action') === 'delete') {
    vpy_delete('tariflar', 'id', (int)vpy_post('id'));
    vpy_flash_set('success', t('msg_deleted'));
    vpy_redirect('/admin/tariflar.php');
}

$tariffs = vpy_read_json('tariflar', []);
usort($tariffs, fn($a, $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));

vpy_panel_head(t('admin_tariffs'));
vpy_panel_sidebar('tariflar', true);
?>
<main class="main">
<?php vpy_panel_topbar(t('admin_tariffs'), count($tariffs) . ' ta tarif',
    '<a href="/admin/tariflar-form.php" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>' . e(t('admin_add')) . '</a>'
); ?>

<div class="card">
    <?php if (empty($tariffs)): ?>
        <div class="empty"><h3>Tariflar yo'q</h3></div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead><tr><th>#</th><th>Nomi</th><th>Narx</th><th>Muddat</th><th><?= e(t('tariffs_badge_popular')) ?></th><th><?= e(t('admin_status_active')) ?></th><th></th></tr></thead>
            <tbody>
                <?php foreach ($tariffs as $t): ?>
                <tr>
                    <td>#<?= (int)$t['id'] ?></td>
                    <td>
                        <strong><?= e($t['name']) ?></strong>
                        <div style="font-size:0.78rem;color:var(--muted);margin-top:2px"><?= e(mb_substr($t['description'] ?? '', 0, 60, 'UTF-8')) ?></div>
                    </td>
                    <td>
                        <strong style="font-family:var(--serif);font-size:1.1rem;color:var(--primary)"><?= number_format((float)$t['price'], 0, '.', ' ') ?></strong>
                        <?php if (!empty($t['old_price'])): ?>
                            <div style="font-size:0.78rem;color:var(--muted);text-decoration:line-through"><?= number_format((float)$t['old_price'], 0, '.', ' ') ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)($t['duration_days'] ?? 0) ?> kun</td>
                    <td>
                        <?php if (!empty($t['popular'])): ?>
                            <span class="chip chip-warning">★ <?= e(t('tariffs_badge_popular')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($t['active'])): ?>
                            <span class="chip chip-success"><?= e(t('admin_status_active')) ?></span>
                        <?php else: ?>
                            <span class="chip chip-muted">Yopiq</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="row-actions">
                            <a href="/admin/tariflar-form.php?id=<?= (int)$t['id'] ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
                            <form method="post" style="display:inline" onsubmit="return confirm('O\'chirilsinmi?')">
                                <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                <button type="submit" class="danger"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 01-2 2H9a2 2 0 01-2-2L5 6"/></svg></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
</main>
<?php vpy_panel_foot(); ?>
