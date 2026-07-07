<?php
/*
 * Cron: kunlik vazifalar
 * Sozlash: 0 3 * * * /usr/bin/php /path/to/cron/daily.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$started_at = microtime(true);
$report = ['date' => date('Y-m-d H:i:s'), 'tasks' => []];

// 1. Eski log fayllarini tozalash (30 kundan eski)
$logs = vpy_read_json('loglar', []);
$cutoff = time() - 30 * 86400;
$kept = array_values(array_filter($logs, fn($l) => strtotime($l['created_at'] ?? '') >= $cutoff));
$removed = count($logs) - count($kept);
vpy_write_json('loglar', $kept);
$report['tasks']['logs_cleaned'] = $removed;

// 2. Tugagan obunalarni belgilash va foydalanuvchilarga bildirishnoma
$payments = vpy_read_json('tolovlar', []);
$expired = 0;
$now = time();
foreach ($payments as &$p) {
    if (($p['status'] ?? '') === 'success' && !empty($p['expires_at']) && strtotime($p['expires_at']) < $now && empty($p['expired_notified'])) {
        $p['expired_notified'] = 1;
        $expired++;
    }
}
unset($p);
vpy_write_json('tolovlar', $payments);
$report['tasks']['expired_subs'] = $expired;

// 3. Eski o'qilgan bildirishnomalarni o'chirish (60 kundan eski)
$notifs = vpy_read_json('bildirishnomalar', []);
$cutoff2 = time() - 60 * 86400;
$kept_notifs = array_values(array_filter($notifs, function($n) use ($cutoff2) {
    return empty($n['is_read']) || strtotime($n['created_at'] ?? '') >= $cutoff2;
}));
$removed_notifs = count($notifs) - count($kept_notifs);
vpy_write_json('bildirishnomalar', $kept_notifs);
$report['tasks']['notifs_cleaned'] = $removed_notifs;

// 4. Foydalanuvchi statistikasini yangilash
$users = vpy_read_json('users', []);
$results = vpy_read_json('natijalar', []);
foreach ($users as &$u) {
    $u_results = array_filter($results, fn($r) => (int)$r['user_id'] === (int)$u['id']);
    $u['tests_taken'] = count($u_results);
    $u['best_score'] = empty($u_results) ? 0 : max(array_column($u_results, 'score'));
}
unset($u);
vpy_write_json('users', $users);
$report['tasks']['users_updated'] = count($users);

// 5. Yakunlovchi log
$report['duration_ms'] = round((microtime(true) - $started_at) * 1000);
vpy_log('cron_daily', 'Kunlik cron yakunlandi', $report);

if (PHP_SAPI === 'cli') {
    echo "Daily cron completed:\n";
    foreach ($report['tasks'] as $k => $v) echo "  - $k: $v\n";
    echo "Duration: " . $report['duration_ms'] . "ms\n";
}
