<?php
require_once __DIR__ . '/../includes/panel_layout.php';
require_once __DIR__ . '/../includes/notifications.php';
vpy_require_admin('/login.php');

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    $action = vpy_post('action');
    $id = (int)vpy_post('id');
    $payment = vpy_find('tolovlar', 'id', $id);

    if ($payment && $action === 'approve' && !in_array($payment['status'] ?? '', ['success'])) {
        $tariff = vpy_find('tariflar', 'id', $payment['tariff_id']);
        $comment = trim(vpy_post('comment', ''));
        $payment['status'] = 'success';
        $payment['paid_at'] = date('Y-m-d H:i:s');
        $payment['expires_at'] = date('Y-m-d H:i:s', strtotime('+' . (int)($tariff['duration_days'] ?? 30) . ' days'));
        if (empty($payment['transaction_id'])) $payment['transaction_id'] = 'MANUAL-' . strtoupper(vpy_random_string(6));
        if ($comment) $payment['admin_comment'] = $comment;
        $payment['approved_by'] = vpy_user()['id'];
        $payment['approved_at'] = date('Y-m-d H:i:s');
        vpy_upsert('tolovlar', $payment);

        vpy_notify_payment_success($payment['user_id'], $payment['tariff_name'], $payment['amount']);
        vpy_notify_tariff_activated($payment['user_id'], $payment['tariff_name'], $payment['expires_at']);

        $msg = 'Sizning ' . $payment['tariff_name'] . ' tarifi uchun to\'lovingiz tasdiqlandi! Tarif ' . vpy_date($payment['expires_at'], 'd.m.Y') . ' gacha amal qiladi.';
        if ($comment) $msg .= ' Izoh: ' . $comment;
        vpy_support_send($payment['user_id'], $msg, true);

        vpy_log('payment_approved', 'To\'lov tasdiqlandi', ['id' => $id, 'admin' => vpy_user()['id'], 'comment' => $comment]);
        vpy_flash_set('success', 'To\'lov tasdiqlandi! Tarif faollashtirildi.');
    } elseif ($payment && $action === 'reject') {
        $comment = trim(vpy_post('comment', ''));
        $payment['status'] = 'failed';
        if ($comment) $payment['admin_comment'] = $comment;
        $payment['rejected_by'] = vpy_user()['id'];
        $payment['rejected_at'] = date('Y-m-d H:i:s');
        vpy_upsert('tolovlar', $payment);

        vpy_notify_payment_rejected($payment['user_id'], $payment['tariff_name'], $payment['amount'], $comment);

        $msg = 'Sizning ' . $payment['tariff_name'] . ' tarifi uchun to\'lovingiz rad etildi.';
        if ($comment) $msg .= ' Sabab: ' . $comment;
        vpy_support_send($payment['user_id'], $msg, true);

        vpy_log('payment_rejected', 'To\'lov rad etildi', ['id' => $id, 'admin' => vpy_user()['id'], 'comment' => $comment]);
        vpy_flash_set('success', 'To\'lov rad etildi.');
    } elseif ($action === 'delete' && $payment) {
        vpy_delete('tolovlar', 'id', $id);
        vpy_flash_set('success', t('msg_deleted'));
    }
    $redir = '/admin/tolovlar.php';
    if (vpy_get('status','')) $redir .= '?status=' . urlencode(vpy_get('status',''));
    vpy_redirect($redir);
}


$status_filter = vpy_get('status', '');
$payments = vpy_read_json('tolovlar', []);
if ($status_filter) $payments = array_values(array_filter($payments, fn($p) => ($p['status'] ?? '') === $status_filter));
usort($payments, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

$page = max(1, (int)vpy_get('p', 1));
$pag = vpy_paginate($payments, 20, $page);

// Statistika
$all_payments = vpy_read_json('tolovlar', []);
$total_success = array_sum(array_column(array_filter($all_payments, fn($p) => ($p['status'] ?? '') === 'success'), 'amount'));
$count_reviewing = count(array_filter($all_payments, fn($p) => ($p['status'] ?? '') === 'reviewing'));
$count_pending = count(array_filter($all_payments, fn($p) => ($p['status'] ?? '') === 'pending'));
$count_success = count(array_filter($all_payments, fn($p) => ($p['status'] ?? '') === 'success'));
$count_failed = count(array_filter($all_payments, fn($p) => ($p['status'] ?? '') === 'failed'));

vpy_panel_head(t('admin_payments'), <<<CSS
.pay-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px}
.pay-stat{padding:18px;background:var(--glass-strong);backdrop-filter:blur(16px);border:1px solid var(--border);border-radius:16px;text-align:center}
.pay-stat-num{font-size:1.5rem;font-weight:900;color:var(--primary);line-height:1}
.pay-stat-label{font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.05em;margin-top:6px;font-weight:600}
.status-tabs{display:flex;gap:4px;padding:5px;background:var(--glass);border:1px solid var(--border);border-radius:14px;width:fit-content;margin-bottom:18px;flex-wrap:wrap}
.status-tabs a{padding:8px 14px;border-radius:10px;font-size:0.78rem;font-weight:700;color:var(--dark-soft);text-decoration:none;transition:var(--t);white-space:nowrap}
.status-tabs a.active{background:var(--primary);color:#fff}
.status-tabs a:hover:not(.active){background:var(--blue-soft)}
/* Payment cards layout */
.pay-cards{display:flex;flex-direction:column;gap:12px}
.pay-item{display:grid;grid-template-columns:auto 1fr auto auto auto;gap:16px;align-items:center;padding:18px 20px;background:var(--glass-strong);backdrop-filter:blur(20px);border:1px solid var(--border);border-radius:16px;transition:var(--t)}
.pay-item:hover{border-color:var(--primary);box-shadow:0 4px 16px rgba(0,0,0,0.04)}
.pay-item.reviewing{border-left:4px solid #f59e0b}
.pay-item.pending{border-left:4px solid var(--muted)}
.pay-item.success{border-left:4px solid #10b981}
.pay-item.failed{border-left:4px solid #ef4444}
.pay-user{display:flex;flex-direction:column;gap:2px}
.pay-user-name{font-weight:700;font-size:0.9rem}
.pay-user-phone{font-size:0.75rem;color:var(--muted)}
.pay-user-tariff{font-size:0.78rem;color:var(--primary);font-weight:600}
.pay-amount{font-weight:800;font-size:1.1rem;color:var(--primary);white-space:nowrap}
.pay-method{padding:4px 10px;border-radius:8px;font-size:0.72rem;font-weight:700;text-transform:uppercase;background:var(--blue-soft);color:var(--primary)}
.pay-status{padding:5px 12px;border-radius:20px;font-size:0.72rem;font-weight:700;text-align:center;white-space:nowrap}
.pay-status.st-reviewing{background:rgba(245,158,11,0.12);color:#b45309;animation:pulse 2s infinite}
.pay-status.st-pending{background:rgba(148,163,184,0.12);color:#64748b}
.pay-status.st-success{background:rgba(16,185,129,0.12);color:#059669}
.pay-status.st-failed{background:rgba(239,68,68,0.12);color:#dc2626}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.6}}
.pay-actions-col{display:flex;gap:6px;align-items:center}
.pay-btn{width:34px;height:34px;border-radius:10px;border:1px solid var(--border);display:grid;place-items:center;cursor:pointer;transition:var(--t);background:transparent}
.pay-btn:hover{transform:scale(1.1)}
.pay-btn.approve{color:#059669;border-color:#10b981}
.pay-btn.approve:hover{background:rgba(16,185,129,0.1)}
.pay-btn.reject{color:#dc2626;border-color:#ef4444}
.pay-btn.reject:hover{background:rgba(239,68,68,0.1)}
.pay-btn.view{color:var(--primary);border-color:var(--primary)}
.pay-btn.view:hover{background:var(--blue-soft)}
.pay-btn.delete{color:var(--muted);border-color:var(--border)}
.pay-btn.delete:hover{color:#dc2626;border-color:#ef4444;background:rgba(239,68,68,0.05)}
.pay-time{font-size:0.72rem;color:var(--muted);white-space:nowrap}
.pay-ss{width:44px;height:44px;border-radius:10px;object-fit:cover;cursor:pointer;border:1px solid var(--border);transition:var(--t)}
.pay-ss:hover{transform:scale(1.15);box-shadow:var(--shadow)}
/* Detail modal */
.detail-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);z-index:1000;display:none;align-items:center;justify-content:center;padding:16px}
.detail-overlay.show{display:flex}
.detail-box{background:var(--surface);border:1px solid var(--border);border-radius:20px;max-width:560px;width:100%;max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.15);padding:28px}
.detail-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px}
.detail-title{font-size:1.1rem;font-weight:800}
.detail-close{width:32px;height:32px;border-radius:8px;border:1px solid var(--border);background:transparent;cursor:pointer;display:grid;place-items:center;color:var(--muted)}
.detail-close:hover{background:rgba(239,68,68,0.08);color:#ef4444;border-color:#ef4444}
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px}
.detail-field{padding:12px 14px;background:var(--glass);border:1px solid var(--border);border-radius:12px}
.detail-field-label{font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.04em;font-weight:600;margin-bottom:4px}
.detail-field-value{font-weight:700;font-size:0.9rem}
.detail-ss{width:100%;max-height:300px;object-fit:contain;border-radius:14px;border:1px solid var(--border);margin-bottom:18px;cursor:pointer}
.detail-actions{display:flex;gap:10px;flex-wrap:wrap}
.detail-actions .btn{flex:1;min-width:120px}
@media (max-width:768px){.pay-item{grid-template-columns:1fr;gap:10px}.detail-grid{grid-template-columns:1fr}}
CSS);
vpy_panel_sidebar('tolovlar', true);
?>

<main class="main">
<?php vpy_panel_topbar(t('admin_payments'), 'Jami: ' . number_format($total_success, 0, '.', ' ') . " so'm"); ?>

<!-- Statistika -->
<div class="pay-stats">
    <div class="pay-stat">
        <div class="pay-stat-num"><?= number_format($total_success, 0, '.', ' ') ?></div>
        <div class="pay-stat-label">Jami daromad</div>
    </div>
    <div class="pay-stat">
        <div class="pay-stat-num" style="color:#b45309"><?= $count_reviewing ?></div>
        <div class="pay-stat-label">Tekshirish</div>
    </div>
    <div class="pay-stat">
        <div class="pay-stat-num" style="color:#64748b"><?= $count_pending ?></div>
        <div class="pay-stat-label">Kutilmoqda</div>
    </div>
    <div class="pay-stat">
        <div class="pay-stat-num" style="color:#059669"><?= $count_success ?></div>
        <div class="pay-stat-label">Tasdiqlangan</div>
    </div>
    <div class="pay-stat">
        <div class="pay-stat-num" style="color:#dc2626"><?= $count_failed ?></div>
        <div class="pay-stat-label">Rad etilgan</div>
    </div>
</div>

<!-- Status filter -->
<div class="status-tabs">
    <a href="?" class="<?= $status_filter === '' ? 'active' : '' ?>">Hammasi (<?= count($all_payments) ?>)</a>
    <a href="?status=reviewing" class="<?= $status_filter === 'reviewing' ? 'active' : '' ?>">Tekshirish<?php if($count_reviewing): ?> (<?= $count_reviewing ?>)<?php endif; ?></a>
    <a href="?status=pending" class="<?= $status_filter === 'pending' ? 'active' : '' ?>">Kutilmoqda</a>
    <a href="?status=success" class="<?= $status_filter === 'success' ? 'active' : '' ?>">Tasdiqlangan</a>
    <a href="?status=failed" class="<?= $status_filter === 'failed' ? 'active' : '' ?>">Rad etilgan</a>
</div>

<!-- To'lovlar ro'yxati -->
<div class="card">
<?php if (empty($pag['items'])): ?>
    <div class="empty" style="padding:48px 20px;text-align:center">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="1.5" style="margin-bottom:12px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <h3 style="color:var(--dark);margin-bottom:4px">To'lovlar yo'q</h3>
        <p style="color:var(--muted);font-size:0.85rem">Bu filtrdagi to'lovlar hali yo'q</p>
    </div>
<?php else: ?>
    <div class="pay-cards">
    <?php foreach ($pag['items'] as $idx => $p):
        $usr = vpy_find('users', 'id', $p['user_id']);
        $st = $p['status'] ?? 'pending';
        $st_labels = ['success'=>'Tasdiqlangan','reviewing'=>'Tekshirilmoqda','pending'=>'Kutilmoqda','failed'=>'Rad etilgan'];
    ?>
        <div class="pay-item <?= e($st) ?>">
            <?php if (!empty($p['screenshot'])): ?>
                <img src="<?= e($p['screenshot']) ?>" alt="SS" class="pay-ss" onclick="showDetail(<?= $idx ?>)">
            <?php else: ?>
                <div style="width:44px;height:44px;border-radius:10px;background:var(--glass);border:1px solid var(--border);display:grid;place-items:center;color:var(--muted)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                </div>
            <?php endif; ?>

            <div class="pay-user">
                <span class="pay-user-name"><?= e($usr['name'] ?? '—') ?></span>
                <span class="pay-user-phone"><?= e($usr['phone'] ?? '') ?></span>
                <span class="pay-user-tariff"><?= e($p['tariff_name'] ?? '—') ?></span>
            </div>

            <div style="text-align:center">
                <div class="pay-amount"><?= number_format((float)($p['amount'] ?? 0), 0, '.', ' ') ?></div>
                <span class="pay-method"><?= e($p['method'] ?? '—') ?></span>
            </div>

            <div style="text-align:center">
                <div class="pay-status st-<?= e($st) ?>"><?= e($st_labels[$st] ?? $st) ?></div>
                <div class="pay-time"><?= e(vpy_time_ago($p['created_at'] ?? '')) ?></div>
            </div>

            <div class="pay-actions-col">
                <button type="button" class="pay-btn view" onclick="showDetail(<?= $idx ?>)" title="Batafsil">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M2 12s4-8 10-8 10 8 10 8-4 8-10 8-10-8-10-8z"/></svg>
                </button>
                <?php if (in_array($st, ['pending','reviewing'])): ?>
                <form method="post" style="display:contents" onsubmit="return confirm('Tasdiqlaysizmi? Foydalanuvchiga tarif faollashtiriladi.')">
                    <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="pay-btn approve" title="Tasdiqlash">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    </button>
                </form>
                <button type="button" class="pay-btn reject" title="Rad etish" onclick="rejectPayment(<?= (int)$p['id'] ?>)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
                <?php endif; ?>
                <form method="post" style="display:contents" onsubmit="return confirm('O\'chirilsinmi?')">
                    <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="pay-btn delete" title="O'chirish">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14H7L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <?php if ($pag['pages'] > 1): ?>
    <div class="pagination" style="margin-top:18px">
        <?php for ($i = 1; $i <= $pag['pages']; $i++): ?>
        <a href="?p=<?= $i ?><?= $status_filter ? '&status=' . e($status_filter) : '' ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
</div>


<!-- Detail Modal -->
<div class="detail-overlay" id="detailOverlay" onclick="if(event.target===this)closeDetail()">
    <div class="detail-box" id="detailBox"></div>
</div>

<!-- Reject form (hidden) -->
<form method="post" id="rejectForm" style="display:none">
    <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
    <input type="hidden" name="action" value="reject">
    <input type="hidden" name="id" id="rejectId">
    <input type="hidden" name="comment" id="rejectComment">
</form>

<script>
var payments = <?= json_encode(array_values($pag['items']), JSON_UNESCAPED_UNICODE) ?>;
var users = {};
<?php foreach ($pag['items'] as $p):
    $usr = vpy_find('users', 'id', $p['user_id']);
    if ($usr): ?>
users[<?= (int)$p['user_id'] ?>] = <?= json_encode(['name'=>$usr['name']??'','phone'=>$usr['phone']??''], JSON_UNESCAPED_UNICODE) ?>;
<?php endif; endforeach; ?>

function showDetail(idx) {
    var p = payments[idx];
    if (!p) return;
    var usr = users[p.user_id] || {name:'—',phone:''};
    var st = p.status || 'pending';
    var stLabels = {success:'Tasdiqlangan',reviewing:'Tekshirilmoqda',pending:'Kutilmoqda',failed:'Rad etilgan'};
    
    var html = '<div class="detail-header">';
    html += '<div class="detail-title">To\'lov #' + (p.invoice_number || p.id) + '</div>';
    html += '<button class="detail-close" onclick="closeDetail()"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>';
    html += '</div>';
    
    if (p.screenshot) {
        html += '<img src="' + p.screenshot + '" class="detail-ss" onclick="window.open(this.src)" alt="Screenshot">';
    }
    
    html += '<div class="detail-grid">';
    html += '<div class="detail-field"><div class="detail-field-label">Foydalanuvchi</div><div class="detail-field-value">' + usr.name + '</div></div>';
    html += '<div class="detail-field"><div class="detail-field-label">Telefon</div><div class="detail-field-value">' + (usr.phone || '—') + '</div></div>';
    html += '<div class="detail-field"><div class="detail-field-label">Tarif</div><div class="detail-field-value">' + (p.tariff_name || '—') + '</div></div>';
    html += '<div class="detail-field"><div class="detail-field-label">Summa</div><div class="detail-field-value" style="color:var(--primary);font-size:1.1rem">' + Number(p.amount||0).toLocaleString('uz-UZ').replace(/,/g,' ') + ' so\'m</div></div>';
    html += '<div class="detail-field"><div class="detail-field-label">To\'lov usuli</div><div class="detail-field-value">' + (p.method||'—').toUpperCase() + '</div></div>';
    html += '<div class="detail-field"><div class="detail-field-label">Status</div><div class="detail-field-value"><span class="pay-status st-' + st + '">' + (stLabels[st]||st) + '</span></div></div>';
    html += '<div class="detail-field"><div class="detail-field-label">Yaratilgan</div><div class="detail-field-value">' + (p.created_at || '—') + '</div></div>';
    html += '<div class="detail-field"><div class="detail-field-label">Transaction ID</div><div class="detail-field-value" style="font-size:0.78rem">' + (p.transaction_id || '—') + '</div></div>';
    if (p.expires_at) {
        html += '<div class="detail-field"><div class="detail-field-label">Amal qilish</div><div class="detail-field-value">' + p.expires_at + '</div></div>';
    }
    if (p.admin_comment) {
        html += '<div class="detail-field" style="grid-column:1/-1"><div class="detail-field-label">Admin izohi</div><div class="detail-field-value">' + p.admin_comment + '</div></div>';
    }
    html += '</div>';
    
    // Actions
    if (st === 'pending' || st === 'reviewing') {
        html += '<div class="detail-actions">';
        html += '<form method="post" style="flex:1"><input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="' + p.id + '"><button type="submit" class="btn btn-primary" style="width:100%" onclick="return confirm(\'Tasdiqlaysizmi?\')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:16px;height:16px"><polyline points="20 6 9 17 4 12"/></svg>Tasdiqlash</button></form>';
        html += '<button type="button" class="btn btn-ghost" style="flex:1;color:#dc2626;border-color:#ef4444" onclick="closeDetail();rejectPayment(' + p.id + ')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Rad etish</button>';
        html += '</div>';
    }
    
    document.getElementById('detailBox').innerHTML = html;
    document.getElementById('detailOverlay').classList.add('show');
}

function closeDetail() {
    document.getElementById('detailOverlay').classList.remove('show');
}

function rejectPayment(id) {
    var reason = prompt('Rad etish sababi (ixtiyoriy):');
    if (reason === null) return;
    document.getElementById('rejectId').value = id;
    document.getElementById('rejectComment').value = reason;
    document.getElementById('rejectForm').submit();
}

document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closeDetail();
});
</script>

</main>
<?php vpy_panel_foot(); ?>
