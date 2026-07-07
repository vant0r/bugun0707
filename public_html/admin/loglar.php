<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf')) && vpy_post('action') === 'clear') {
    vpy_write_json('loglar', []);
    vpy_log('logs_cleared', 'Loglar tozalandi', ['admin' => vpy_user()['id']]);
    vpy_flash_set('success', t('msg_deleted'));
    vpy_redirect('/admin/loglar.php');
}

$type_filter = vpy_get('type', '');
$logs = vpy_read_json('loglar', []);
if ($type_filter) $logs = array_values(array_filter($logs, fn($l) => ($l['type'] ?? '') === $type_filter));
usort($logs, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

$page = max(1, (int)vpy_get('p', 1));
$pag = vpy_paginate($logs, 50, $page);

$types = [];
foreach (vpy_read_json('loglar', []) as $l) {
    $t = $l['type'] ?? 'unknown';
    $types[$t] = ($types[$t] ?? 0) + 1;
}

vpy_panel_head(t('admin_logs'));
vpy_panel_sidebar('loglar', true);
?>
<main class="main">
<?php vpy_panel_topbar(t('admin_logs'), count($logs) . ' / ' . count(vpy_read_json('loglar', [])),
    '<form method="post" style="display:inline" onsubmit="return confirm(\'Barcha loglar o\\\'chirilsinmi?\')"><input type="hidden" name="csrf" value="' . e(vpy_csrf()) . '"><input type="hidden" name="action" value="clear"><button type="submit" class="btn btn-danger">Hammasini tozalash</button></form>'
); ?>

<div class="card">
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px">
        <a href="?" class="chip <?= $type_filter === '' ? 'chip-success' : 'chip-muted' ?>" style="text-decoration:none">Hammasi · <?= count(vpy_read_json('loglar', [])) ?></a>
        <?php foreach ($types as $tp => $cnt): ?>
            <a href="?type=<?= urlencode($tp) ?>" class="chip <?= $type_filter === $tp ? 'chip-success' : 'chip-muted' ?>" style="text-decoration:none"><?= e($tp) ?> · <?= $cnt ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($pag['items'])): ?>
        <div class="empty"><h3>Loglar yo'q</h3></div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead><tr><th>#</th><th>Turi</th><th>Xabar</th><th>Foydalanuvchi</th><th>IP</th><th>Sana</th></tr></thead>
            <tbody>
                <?php foreach ($pag['items'] as $l):
                    $usr = !empty($l['user_id']) ? vpy_find('users', 'id', $l['user_id']) : null;
                ?>
                <tr>
                    <td>#<?= (int)$l['id'] ?></td>
                    <td><span class="chip chip-muted"><?= e($l['type'] ?? '—') ?></span></td>
                    <td>
                        <strong><?= e($l['message'] ?? '') ?></strong>
                        <?php if (!empty($l['meta'])): ?>
                        <div style="font-size:0.74rem;color:var(--muted);margin-top:2px;font-family:ui-monospace,monospace"><?= e(json_encode($l['meta'], JSON_UNESCAPED_UNICODE)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($usr['name'] ?? '—') ?></td>
                    <td style="font-family:ui-monospace,monospace;font-size:0.78rem;color:var(--muted)"><?= e($l['ip'] ?? '—') ?></td>
                    <td><?= e(vpy_time_ago($l['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pag['pages'] > 1): ?>
    <div class="pagination">
        <?php
        $start = max(1, $page - 3); $end = min($pag['pages'], $page + 3);
        for ($i = $start; $i <= $end; $i++):
        ?><?= $i === $page ? '<span class="active">' . $i . '</span>' : '<a href="?p=' . $i . '&type=' . urlencode($type_filter) . '">' . $i . '</a>' ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
</main>
<?php vpy_panel_foot(); ?>
