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

        // Notifications
        vpy_notify_payment_success($payment['user_id'], $payment['tariff_name'], $payment['amount']);
        vpy_notify_tariff_activated($payment['user_id'], $payment['tariff_name'], $payment['expires_at']);

        // Support chat notification
        $msg = 'Sizning ' . $payment['tariff_name'] . ' tarifi uchun to\'lovingiz tasdiqlandi! Tarif ' . vpy_date($payment['expires_at'], 'd.m.Y') . ' gacha amal qiladi.';
        if ($comment) $msg .= ' Izoh: ' . $comment;
        vpy_support_send($payment['user_id'], $msg, true);

        vpy_log('payment_approved', 'To\'lov tasdiqlandi', ['id' => $id, 'admin' => vpy_user()['id'], 'comment' => $comment]);
        vpy_flash_set('success', 'To\'lov tasdiqlandi! Foydalanuvchiga tarif faollashtirildi va bildirishnoma yuborildi.');
    } elseif ($payment && $action === 'reject') {
        $comment = trim(vpy_post('comment', ''));
        $payment['status'] = 'failed';
        if ($comment) $payment['admin_comment'] = $comment;
        $payment['rejected_by'] = vpy_user()['id'];
        $payment['rejected_at'] = date('Y-m-d H:i:s');
        vpy_upsert('tolovlar', $payment);

        // Notifications
        vpy_notify_payment_rejected($payment['user_id'], $payment['tariff_name'], $payment['amount'], $comment);

        // Support chat
        $msg = 'Sizning ' . $payment['tariff_name'] . ' tarifi uchun to\'lovingiz rad etildi.';
        if ($comment) $msg .= ' Sabab: ' . $comment;
        vpy_support_send($payment['user_id'], $msg, true);

        vpy_log('payment_rejected', 'To\'lov rad etildi', ['id' => $id, 'admin' => vpy_user()['id'], 'comment' => $comment]);
        vpy_flash_set('success', 'To\'lov rad etildi. Foydalanuvchiga bildirishnoma yuborildi.');
    } elseif ($action === 'delete' && $payment) {
        vpy_delete('tolovlar', 'id', $id);
        vpy_flash_set('success', t('msg_deleted'));
    }
    vpy_redirect('/admin/tolovlar.php' . ($status_filter ?? '' ? '?status=' . urlencode(vpy_get('status','')) : ''));
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

vpy_panel_head(t('admin_payments'), <<<CSS
.pay-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:18px}
.pay-stat{padding:16px 18px;background:var(--glass);backdrop-filter:blur(16px);border:1px solid var(--border);border-radius:14px}
.pay-stat-num{font-family:var(--sans);font-size:1.4rem;font-weight:800;color:var(--primary)}
.pay-stat-label{font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.05em;margin-top:2px}
.status-tabs{display:flex;gap:4px;padding:4px;background:var(--glass);border:1px solid var(--border);border-radius:var(--pill);width:fit-content;margin-bottom:16px;flex-wrap:wrap}
.status-tabs a{padding:7px 14px;border-radius:var(--pill);font-size:0.8rem;font-weight:700;color:var(--dark-soft);text-decoration:none;transition:var(--t);white-space:nowrap}
.status-tabs a.active{background:var(--primary);color:#fff}
.status-tabs a:hover:not(.active){background:var(--blue-soft)}
.ss-thumb{width:48px;height:48px;border-radius:8px;object-fit:cover;cursor:pointer;border:1px solid var(--border);transition:var(--t)}
.ss-thumb:hover{transform:scale(1.1);box-shadow:var(--shadow)}
.ss-modal{position:fixed;inset:0;background:rgba(0,0,0,0.8);backdrop-filter:blur(6px);display:none;align-items:center;justify-content:center;z-index:1000;padding:20px;cursor:pointer}
.ss-modal.show{display:flex}
.ss-modal img{max-width:90%;max-height:90%;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.5)}
.badge-review{background:rgba(245,158,11,0.12);color:#B45309;border:1px solid rgba(245,158,11,0.2);padding:3px 10px;border-radius:var(--pill);font-size:0.7rem;font-weight:700;animation:pulse-badge 2s ease-in-out infinite}
@keyframes pulse-badge{0%,100%{opacity:1}50%{opacity:0.7}}
CSS);
vpy_panel_sidebar('tolovlar', true);
?>
<main class="main">
<?php vpy_panel_topbar(t('admin_payments'), 'Jami: ' . number_format($total_success, 0, '.', ' ') . " so'm"); ?>

<!-- Stats -->
<div class="pay-stats">
    <div class="pay-stat">
        <div class="pay-stat-num"><?= number_format($total_success, 0, '.', ' ') ?></div>
        <div class="pay-stat-label">Jami daromad (so'm)</div>
    </div>
    <div class="pay-stat">
        <div class="pay-stat-num" style="color:#B45309"><?= $count_reviewing ?></div>
        <div class="pay-stat-label">Tekshirish kutmoqda</div>
    </div>
    <div class="pay-stat">
        <div class="pay-stat-num" style="color:var(--muted)"><?= $count_pending ?></div>
        <div class="pay-stat-label">To'lov kutilmoqda</div>
    </div>
    <div class="pay-stat">
        <div class="pay-stat-num"><?= count($all_payments) ?></div>
        <div class="pay-stat-label">Jami to'lovlar</div>
    </div>
</div>

<!-- Status filter tabs -->
<div class="status-tabs">
    <a href="?" class="<?= $status_filter === '' ? 'active' : '' ?>">Hammasi</a>
    <a href="?status=reviewing" class="<?= $status_filter === 'reviewing' ? 'active' : '' ?>">Tekshirish <?php if($count_reviewing): ?>(<?= $count_reviewing ?>)<?php endif; ?></a>
    <a href="?status=pending" class="<?= $status_filter === 'pending' ? 'active' : '' ?>">Kutilmoqda</a>
    <a href="?status=success" class="<?= $status_filter === 'success' ? 'active' : '' ?>">Tasdiqlangan</a>
    <a href="?status=failed" class="<?= $status_filter === 'failed' ? 'active' : '' ?>">Rad etilgan</a>
</div>

<div class="card">
    <?php if (empty($pag['items'])): ?>
        <div class="empty"><h3>To'lovlar yo'q</h3><p style="color:var(--muted)">Bu filtrdagi to'lovlar hali yo'q</p></div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead><tr><th>#</th><th>Foydalanuvchi</th><th>Tarif</th><th>Summa</th><th>Usul</th><th>Screenshot</th><th>Status</th><th>Sana</th><th>Amallar</th></tr></thead>
            <tbody>
            <?php foreach ($pag['items'] as $p):
                $usr = vpy_find('users', 'id', $p['user_id']);
                $st = $p['status'] ?? 'pending';
                if ($st === 'success') $chip = 'success';
                elseif ($st === 'reviewing') $chip = 'warning';
                elseif ($st === 'pending') $chip = 'muted';
                else $chip = 'danger';
                $st_label = ['success'=>'Tasdiqlangan','reviewing'=>'Tekshirilmoqda','pending'=>'Kutilmoqda','failed'=>'Rad etilgan'][$st] ?? $st;
            ?>
            <tr>
                <td><strong style="font-size:0.82rem">#<?= e($p['invoice_number'] ?? $p['id']) ?></strong></td>
                <td>
                    <div style="font-weight:700;font-size:0.88rem"><?= e($usr['name'] ?? '—') ?></div>
                    <div style="font-size:0.75rem;color:var(--muted)"><?= e($usr['phone'] ?? '') ?></div>
                </td>
                <td style="font-weight:600"><?= e($p['tariff_name'] ?? '—') ?></td>
                <td><strong style="font-size:1rem;color:var(--primary)"><?= number_format((float)($p['amount'] ?? 0), 0, '.', ' ') ?></strong></td>
                <td><span class="chip chip-muted"><?= e(strtoupper($p['method'] ?? '—')) ?></span></td>
                <td>
                    <?php if (!empty($p['screenshot'])): ?>
                    <img src="<?= e($p['screenshot']) ?>" alt="Screenshot" class="ss-thumb" onclick="showSS(this.src)">
                    <?php else: ?>
                    <span style="font-size:0.75rem;color:var(--muted)">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($st === 'reviewing'): ?>
                    <span class="badge-review">Tekshiring</span>
                    <?php else: ?>
                    <span class="chip chip-<?= $chip ?>"><?= e($st_label) ?></span>
                    <?php endif; ?>
                </td>
                <td style="font-size:0.78rem;color:var(--muted)"><?= e(vpy_time_ago($p['created_at'] ?? '')) ?></td>
                <td>
                    <div class="row-actions">
                        <a href="/invoice.php?id=<?= (int)$p['id'] ?>" target="_blank" title="Hisob-faktura"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg></a>
                        <?php if (in_array($st, ['pending','reviewing'])): ?>
                        <form method="post" style="display:inline" onsubmit="return confirm('Tasdiqlash — foydalanuvchiga tarif faollashtiriladi!')">
                            <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <input type="hidden" name="comment" value="">
                            <button type="submit" title="Tasdiqlash" style="background:rgba(20,86,168,0.1);color:var(--primary)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg></button>
                        </form>
                        <form method="post" style="display:inline" class="reject-form">
                            <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <input type="hidden" name="comment" class="reject-comment" value="">
                            <button type="submit" class="danger" title="Rad etish"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                        </form>
                        <?php endif; ?>
                        <form method="post" style="display:inline" onsubmit="return confirm('O\'chirilsinmi?')">
                            <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="danger" title="O'chirish"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14H7L5 6"/></svg></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pag['total_pages'] > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $pag['total_pages']; $i++): ?>
        <a href="?p=<?= $i ?><?= $status_filter ? '&status=' . e($status_filter) : '' ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<!-- Screenshot modal -->
<div class="ss-modal" id="ssModal" onclick="this.classList.remove('show')">
    <img id="ssModalImg" src="" alt="Screenshot">
</div>

<script>
function showSS(src){
    document.getElementById('ssModalImg').src = src;
    document.getElementById('ssModal').classList.add('show');
}
// Reject with comment prompt
document.querySelectorAll('.reject-form').forEach(function(form){
    form.addEventListener('submit', function(e){
        e.preventDefault();
        var reason = prompt('Rad etish sababi (ixtiyoriy):');
        if (reason === null) return; // cancelled
        form.querySelector('.reject-comment').value = reason;
        form.submit();
    });
});
</script>

</main>
<?php vpy_panel_foot(); ?>
