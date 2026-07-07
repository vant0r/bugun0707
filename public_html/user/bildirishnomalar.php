<?php
require_once __DIR__ . '/../includes/panel_layout.php';
require_once __DIR__ . '/../includes/notifications.php';
vpy_require_login('/login.php');

$u = vpy_user();

// Mark as read
if (vpy_get('read')) {
    vpy_notify_mark_read((int)vpy_get('read'), $u['id']);
    $url = vpy_get('url', '/user/bildirishnomalar.php');
    vpy_redirect($url);
}

// Mark all read
if (vpy_get('readall') && vpy_csrf_check(vpy_get('t'))) {
    $rows = vpy_read_json('bildirishnomalar', []);
    foreach ($rows as &$r) {
        if ((int)$r['user_id'] === (int)$u['id'] && empty($r['is_read'])) {
            $r['is_read'] = 1;
            $r['read_at'] = date('Y-m-d H:i:s');
        }
    }
    unset($r);
    vpy_write_json('bildirishnomalar', $rows);
    vpy_redirect('/user/bildirishnomalar.php');
}

$notifications = array_values(array_filter(
    vpy_read_json('bildirishnomalar', []),
    fn($n) => (int)$n['user_id'] === (int)$u['id']
));
usort($notifications, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
$unread = count(array_filter($notifications, fn($n) => empty($n['is_read'])));

vpy_panel_head('Bildirishnomalar', <<<CSS
.notif-list{display:flex;flex-direction:column;gap:8px}
.notif-item{display:flex;align-items:flex-start;gap:14px;padding:16px 18px;background:var(--glass);backdrop-filter:blur(16px);border:1px solid var(--border);border-radius:14px;transition:var(--t);text-decoration:none;color:inherit}
.notif-item:hover{background:var(--glass-strong);border-color:var(--border-strong)}
.notif-item.unread{border-left:3px solid var(--primary);background:var(--blue-soft)}
.notif-ico{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;flex-shrink:0}
.notif-ico.success{background:rgba(20,86,168,0.1);color:var(--primary)}
.notif-ico.error{background:rgba(220,53,69,0.08);color:#DC3545}
.notif-ico.info{background:rgba(90,163,232,0.1);color:#4A9EE8}
.notif-ico.admin{background:rgba(245,158,11,0.1);color:#B45309}
.notif-ico svg{width:18px;height:18px}
.notif-body{flex:1;min-width:0}
.notif-title{font-weight:700;font-size:0.9rem;margin-bottom:2px}
.notif-msg{font-size:0.84rem;color:var(--muted);line-height:1.5}
.notif-time{font-size:0.72rem;color:var(--muted);margin-top:4px}
.notif-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px}
.notif-empty{text-align:center;padding:60px 20px;color:var(--muted)}
.notif-empty svg{width:48px;height:48px;margin:0 auto 12px;opacity:0.3}
CSS);
vpy_panel_sidebar('profil', false);
?>
<main class="main">
<?php vpy_panel_topbar('Bildirishnomalar', $unread ? $unread . ' ta o\'qilmagan' : 'Hammasi o\'qilgan'); ?>

<div class="card">
    <div class="notif-header">
        <h2 style="font-weight:700;font-size:1.1rem">Bildirishnomalar (<?= count($notifications) ?>)</h2>
        <?php if ($unread > 0): ?>
        <a href="?readall=1&t=<?= e(vpy_csrf()) ?>" class="btn btn-ghost btn-sm">Hammasini o'qilgan qilish</a>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
    <div class="notif-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        <p style="font-weight:600">Hali bildirishnoma yo'q</p>
    </div>
    <?php else: ?>
    <div class="notif-list">
        <?php foreach ($notifications as $n):
            $is_unread = empty($n['is_read']);
            $type = $n['type'] ?? 'info';
            $icons = [
                'success' => '<polyline points="20 6 9 17 4 12"/>',
                'error' => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
                'info' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
                'admin' => '<path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/>',
            ];
            $url = !empty($n['url']) ? $n['url'] : '/user/bildirishnomalar.php';
            $link = $is_unread ? '/user/bildirishnomalar.php?read=' . (int)$n['id'] . '&url=' . urlencode($url) : $url;
        ?>
        <a href="<?= e($link) ?>" class="notif-item <?= $is_unread ? 'unread' : '' ?>">
            <div class="notif-ico <?= e($type) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><?= $icons[$type] ?? $icons['info'] ?></svg></div>
            <div class="notif-body">
                <div class="notif-title"><?= e($n['title'] ?? '') ?></div>
                <div class="notif-msg"><?= e($n['message'] ?? '') ?></div>
                <div class="notif-time"><?= e(vpy_time_ago($n['created_at'] ?? '')) ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</main>
<?php vpy_panel_foot(); ?>
