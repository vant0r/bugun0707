<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

$total_users = count(vpy_filter('users', fn($u) => ($u['role'] ?? '') === 'user'));
$total_admins = count(vpy_filter('users', fn($u) => ($u['role'] ?? '') === 'admin'));
$total_tests_taken = count(vpy_read_json('natijalar', []));
$total_questions = vpy_test_count();

$payments = vpy_filter('tolovlar', fn($p) => ($p['status'] ?? '') === 'success');
$total_revenue = array_sum(array_column($payments, 'amount'));
$active_subs = 0;
$now = time();
foreach ($payments as $p) {
    if (!empty($p['expires_at']) && strtotime($p['expires_at']) > $now) $active_subs++;
}

$users = vpy_read_json('users', []);
usort($users, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
$recent_users = array_slice(array_filter($users, fn($u) => ($u['role'] ?? '') === 'user'), 0, 5);

$payments_all = vpy_read_json('tolovlar', []);
usort($payments_all, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
$recent_payments = array_slice($payments_all, 0, 5);

$tests_by_day = [];
foreach (vpy_read_json('natijalar', []) as $r) {
    $d = substr($r['created_at'] ?? '', 0, 10);
    $tests_by_day[$d] = ($tests_by_day[$d] ?? 0) + 1;
}
$last_7 = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $last_7[$d] = $tests_by_day[$d] ?? 0;
}

vpy_panel_head(t('admin_dashboard'), <<<CSS
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:24px}
.kpi{padding:24px;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r);position:relative;overflow:hidden;transition:transform var(--t),box-shadow var(--t)}
.kpi:hover{transform:translateY(-4px);box-shadow:var(--shadow)}
.kpi.dark{background:linear-gradient(135deg,#1E1B18,#2A2520);color:#fff;border:1px solid rgba(232,168,56,0.18)}
.kpi.green{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff}
.kpi.amber{background:linear-gradient(135deg,#F5D08A,#E8A838);color:var(--dark)}
.kpi-ico{width:42px;height:42px;border-radius:12px;background:rgba(13,107,78,0.08);color:var(--primary);display:grid;place-items:center;margin-bottom:14px}
.kpi.dark .kpi-ico{background:rgba(232,168,56,0.18);color:var(--accent)}
.kpi.green .kpi-ico{background:rgba(255,255,255,0.18)}
.kpi.amber .kpi-ico{background:rgba(30,27,24,0.12);color:var(--dark)}
.kpi-ico svg{width:20px;height:20px}
.kpi-num{font-family:var(--serif);font-size:2.2rem;font-weight:700;line-height:1;letter-spacing:-0.02em}
.kpi-label{font-size:0.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-top:6px;font-weight:600}
.kpi.dark .kpi-label,.kpi.green .kpi-label{color:rgba(255,253,249,0.65)}
.kpi.amber .kpi-label{color:rgba(30,27,24,0.65)}
.kpi-trend{margin-top:10px;font-size:0.78rem;color:var(--primary);display:flex;align-items:center;gap:4px;font-weight:600}
.split-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:22px;margin-bottom:22px}
.chart-card{padding:28px;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r-lg)}
.chart-bars{display:flex;align-items:flex-end;justify-content:space-between;gap:10px;height:180px;margin-top:24px}
.chart-bar{flex:1;background:linear-gradient(180deg,var(--primary) 0%,var(--primary-dark) 100%);border-radius:8px 8px 0 0;position:relative;min-height:6px;transition:transform var(--t);cursor:default}
.chart-bar:hover{transform:scaleY(1.05)}
.chart-bar:hover::after{content:attr(data-val);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:var(--dark);color:#fff;padding:4px 8px;border-radius:6px;font-size:0.78rem;font-weight:600;white-space:nowrap}
.chart-labels{display:flex;justify-content:space-between;gap:10px;margin-top:8px;font-size:0.75rem;color:var(--muted)}
.chart-labels span{flex:1;text-align:center}
.recent-row{display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid var(--border)}
.recent-row:last-child{border-bottom:none}
.recent-avatar{width:38px;height:38px;border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:700;font-size:0.8rem;flex-shrink:0}
.recent-meta{flex:1;min-width:0}
.recent-name{font-weight:600;color:var(--dark);font-size:0.92rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.recent-sub{font-size:0.78rem;color:var(--muted)}
.recent-amount{font-family:var(--serif);font-weight:700;color:var(--primary);font-size:1rem;flex-shrink:0}
@media (max-width:1024px){.kpi-grid{grid-template-columns:repeat(2,1fr)}.split-grid{grid-template-columns:1fr}}
@media (max-width:480px){.kpi-grid{grid-template-columns:1fr}}
CSS);
vpy_panel_sidebar('index', true);
?>

<main class="main">
<?php vpy_panel_topbar(t('admin_dashboard'), date('d.m.Y · H:i')); ?>

<div class="kpi-grid">
    <div class="kpi green">
        <div class="kpi-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg></div>
        <div class="kpi-num"><?= number_format($total_users) ?></div>
        <div class="kpi-label"><?= e(t('admin_total_users')) ?></div>
    </div>
    <div class="kpi">
        <div class="kpi-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8"/></svg></div>
        <div class="kpi-num"><?= number_format($total_tests_taken) ?></div>
        <div class="kpi-label"><?= e(t('admin_total_tests')) ?></div>
    </div>
    <div class="kpi amber">
        <div class="kpi-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
        <div class="kpi-num"><?= number_format($total_revenue / 1000, 0, '.', ' ') ?>k</div>
        <div class="kpi-label"><?= e(t('admin_total_revenue')) ?></div>
    </div>
    <div class="kpi dark">
        <div class="kpi-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
        <div class="kpi-num"><?= number_format($active_subs) ?></div>
        <div class="kpi-label"><?= e(t('admin_active_subs')) ?></div>
    </div>
</div>

<div class="split-grid">
    <div class="chart-card">
        <div class="card-head" style="margin-bottom:8px"><h2><?= e(t('admin_total_tests')) ?> · 7 kun</h2></div>
        <?php $maxv = max(1, max($last_7)); ?>
        <div class="chart-bars">
            <?php foreach ($last_7 as $date => $val): ?>
                <div class="chart-bar" style="height:<?= max(6, ($val / $maxv) * 180) ?>px" data-val="<?= $val ?> test"></div>
            <?php endforeach; ?>
        </div>
        <div class="chart-labels">
            <?php foreach ($last_7 as $date => $val): ?>
                <span><?= e(date('d.m', strtotime($date))) ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="chart-card">
        <div class="card-head" style="margin-bottom:14px"><h2><?= e(t('admin_recent_users')) ?></h2><a href="/admin/users.php" style="font-size:0.85rem;color:var(--primary);font-weight:600">→</a></div>
        <?php foreach ($recent_users as $usr):
            $color = vpy_avatar_color($usr['name']);
        ?>
        <div class="recent-row">
            <div class="recent-avatar" style="background:<?= e($color) ?>"><?= e(vpy_user_initials($usr['name'])) ?></div>
            <div class="recent-meta">
                <div class="recent-name"><?= e($usr['name']) ?></div>
                <div class="recent-sub"><?= e($usr['phone']) ?> · <?= e(vpy_time_ago($usr['created_at'])) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="card-head"><h2><?= e(t('admin_recent_payments')) ?></h2><a href="/admin/tolovlar.php" style="font-size:0.85rem;color:var(--primary);font-weight:600">→</a></div>
    <?php if (empty($recent_payments)): ?>
        <div class="empty"><h3>To'lovlar yo'q</h3></div>
    <?php else: ?>
    <table class="tbl">
        <thead><tr><th>#</th><th><?= e(t('rating_user')) ?></th><th><?= e(t('admin_tariffs')) ?></th><th><?= e(t('invoice_amount')) ?></th><th><?= e(t('admin_status_active')) ?></th><th><?= e(t('invoice_date')) ?></th></tr></thead>
        <tbody>
            <?php foreach ($recent_payments as $p):
                $usr = vpy_find('users', 'id', $p['user_id']);
                $st = $p['status'];
                $chip = $st === 'success' ? 'success' : ($st === 'pending' ? 'warning' : 'danger');
            ?>
            <tr>
                <td>#<?= e($p['invoice_number']) ?></td>
                <td><?= e($usr['name'] ?? '—') ?></td>
                <td><?= e($p['tariff_name']) ?></td>
                <td><strong><?= number_format((float)$p['amount'], 0, '.', ' ') ?></strong></td>
                <td><span class="chip chip-<?= $chip ?>"><?= e($st) ?></span></td>
                <td><?= e(vpy_time_ago($p['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</main>
<?php vpy_panel_foot(); ?>
