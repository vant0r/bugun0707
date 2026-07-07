<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$id = (int)vpy_get('id');
$num = vpy_get('num');
$payment = null;
foreach (vpy_read_json('tolovlar', []) as $p) {
    if (($id && (int)$p['id'] === $id) || ($num && ($p['invoice_number'] ?? '') === $num)) {
        $payment = $p;
        break;
    }
}
if (!$payment) {
    http_response_code(404);
    die('<h1 style="font-family:system-ui;text-align:center;margin-top:20vh;color:var(--primary,#1456A8)">404 — Hisob-faktura topilmadi</h1>');
}

$user = vpy_find('users', 'id', $payment['user_id']);
$tariff = vpy_find('tariflar', 'id', $payment['tariff_id']);
$lang = vpy_lang_code();
$is_cyrl = $lang === 'uz_cyrillic';
$tariff_name = $is_cyrl && !empty($tariff['name_cyrl']) ? $tariff['name_cyrl'] : ($tariff['name'] ?? $payment['tariff_name'] ?? '');
$method = $payment['method'] ?? '';
$status = $payment['status'] ?? 'pending';
$status_labels = ['success'=>'Tasdiqlangan','reviewing'=>'Tekshirilmoqda','pending'=>'Kutilmoqda','failed'=>'Rad etilgan'];
$status_label = $status_labels[$status] ?? $status;

// Card info for card methods
$card_number = '';
$card_name = '';
if ($method === 'humo') { $card_number = vpy_setting('humo_card_number',''); $card_name = vpy_setting('humo_card_name',''); }
elseif ($method === 'uzcard') { $card_number = vpy_setting('uzcard_card_number',''); $card_name = vpy_setting('uzcard_card_name',''); }
elseif ($method === 'visa') { $card_number = vpy_setting('visa_card_number',''); $card_name = vpy_setting('visa_card_name',''); }
?>
<!DOCTYPE html>
<html lang="<?= $is_cyrl ? 'uz-Cyrl' : 'uz' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#1456A8">
<meta name="robots" content="noindex">
<title>Hisob-faktura #<?= e($payment['invoice_number'] ?? $payment['id']) ?></title>
<link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--primary:#1456A8;--primary-dark:#0E3D7A;--dark:#111318;--muted:#5A6070;--border:rgba(20,86,168,0.12);--r:16px;--sans:"Manrope",sans-serif}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--sans);font-size:14px;line-height:1.55;color:var(--dark);background:#F4F6F9;padding:24px 16px;-webkit-font-smoothing:antialiased}
.actions{max-width:800px;margin:0 auto 16px;display:flex;justify-content:space-between;gap:12px}
.btn{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:100px;font-weight:700;font-size:0.84rem;cursor:pointer;border:none;text-decoration:none;transition:0.3s}
.btn:hover{transform:translateY(-1px)}
.btn svg{width:14px;height:14px}
.btn-back{background:rgba(255,255,255,0.9);border:1px solid var(--border);color:var(--muted)}
.btn-print{background:var(--primary);color:#fff;box-shadow:0 6px 18px rgba(20,86,168,0.25)}
.invoice{max-width:800px;margin:0 auto;background:#fff;border:1px solid var(--border);border-radius:24px;padding:44px;box-shadow:0 20px 50px rgba(0,0,0,0.06)}
.head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:36px;flex-wrap:wrap;gap:16px}
.brand{display:flex;align-items:center;gap:12px;font-weight:800;font-size:1.2rem}
.brand-ico{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;display:grid;place-items:center}
.brand-ico svg{width:20px;height:20px}
.inv-meta{text-align:right}
.inv-num{font-size:1.4rem;font-weight:800;color:var(--primary)}
.inv-date{font-size:0.82rem;color:var(--muted);margin-top:3px}
.status{display:inline-block;padding:5px 14px;border-radius:100px;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;margin-top:8px}
.status.success{background:rgba(20,86,168,0.08);color:var(--primary-dark)}
.status.reviewing{background:rgba(245,158,11,0.1);color:#B45309}
.status.pending{background:rgba(107,114,128,0.08);color:var(--muted)}
.status.failed{background:rgba(220,53,69,0.08);color:#A81D2B}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px}
.box{padding:18px;background:#F8F9FC;border:1px solid var(--border);border-radius:14px}
.box h4{font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);margin-bottom:8px}
.box strong{display:block;font-size:0.95rem;margin-bottom:4px}
.box span{display:block;font-size:0.82rem;color:var(--muted);line-height:1.5}
.tbl{width:100%;border-collapse:collapse;font-size:0.88rem;margin-bottom:24px}
.tbl th{text-align:left;padding:12px 14px;background:#F8F9FC;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--muted);border-bottom:2px solid var(--primary)}
.tbl th:last-child,.tbl td:last-child{text-align:right}
.tbl td{padding:14px;border-bottom:1px solid var(--border)}
.total-row{display:flex;justify-content:flex-end;align-items:center;gap:24px;padding:14px 0;border-top:2px solid var(--primary);margin-top:10px}
.total-label{font-size:1rem;font-weight:700;color:var(--dark)}
.total-amount{font-size:1.6rem;font-weight:800;color:var(--primary)}
/* Card payment info */
.card-pay-info{margin:24px 0;padding:20px;background:rgba(20,86,168,0.04);border:1px solid rgba(20,86,168,0.12);border-radius:14px}
.card-pay-info h4{font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--primary);margin-bottom:12px}
.card-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px dashed var(--border)}
.card-row:last-child{border-bottom:none}
.card-row-label{font-size:0.82rem;color:var(--muted);font-weight:600}
.card-row-value{font-size:0.95rem;font-weight:700;font-family:monospace;letter-spacing:0.03em}
/* Screenshot */
.screenshot-block{margin:20px 0;text-align:center}
.screenshot-block img{max-width:300px;border-radius:12px;border:1px solid var(--border);box-shadow:0 8px 24px rgba(0,0,0,0.08)}
.screenshot-block p{font-size:0.78rem;color:var(--muted);margin-top:8px}
/* Payment instructions */
.instructions{margin:24px 0;padding:18px;background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.2);border-radius:12px;font-size:0.85rem;color:#7C5A1A;line-height:1.6}
.instructions strong{color:#92610E}
.foot{margin-top:36px;padding-top:18px;border-top:1px solid var(--border);text-align:center;font-size:0.78rem;color:var(--muted)}
@media print{body{background:#fff;padding:0}.actions{display:none}.invoice{box-shadow:none;border:none;padding:24px}}
@media (max-width:640px){.invoice{padding:28px 18px}.grid2{grid-template-columns:1fr}.head{flex-direction:column}.inv-meta{text-align:left}}
</style>
</head>
<body>
<div class="actions">
    <a href="<?= vpy_is_logged() ? (vpy_is_admin() ? '/admin/tolovlar.php' : '/user/tariflar.php') : '/' ?>" class="btn btn-back"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>Orqaga</a>
    <button class="btn btn-print" onclick="window.print()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>Chop etish</button>
</div>

<div class="invoice">
    <div class="head">
        <div class="brand">
            <span class="brand-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M3 12l2-2 4 4 8-8 4 4"/></svg></span>
            VatanParvar
        </div>
        <div class="inv-meta">
            <div class="inv-num">#<?= e($payment['invoice_number'] ?? $payment['id']) ?></div>
            <div class="inv-date"><?= e(vpy_date($payment['created_at'], 'd.m.Y H:i')) ?></div>
            <span class="status <?= e($status) ?>"><?= e($status_label) ?></span>
        </div>
    </div>

    <div class="grid2">
        <div class="box">
            <h4>Sotuvchi</h4>
            <strong><?= e(vpy_setting('company_name', 'VatanParvar MChJ')) ?></strong>
            <span><?= e(vpy_setting('contact_address', '')) ?></span>
            <span>INN: <?= e(vpy_setting('company_inn', '')) ?></span>
            <span>H/r: <?= e(vpy_setting('company_account', '')) ?></span>
            <span><?= e(vpy_setting('company_bank', '')) ?> · MFO: <?= e(vpy_setting('company_mfo', '')) ?></span>
        </div>
        <div class="box">
            <h4>Xaridor</h4>
            <strong><?= e($user['name'] ?? '—') ?></strong>
            <span><?= e($user['phone'] ?? '—') ?></span>
            <span>ID: #<?= e($user['id'] ?? '—') ?></span>
        </div>
    </div>

    <table class="tbl">
        <thead><tr><th>#</th><th>Xizmat</th><th>Muddat</th><th>Summa</th></tr></thead>
        <tbody>
            <tr>
                <td>1</td>
                <td><strong>VatanParvar · <?= e($tariff_name) ?></strong><br><span style="font-size:0.78rem;color:var(--muted)">Online test platformasi obunasi</span></td>
                <td><?= (int)($tariff['duration_days'] ?? 30) ?> kun</td>
                <td><strong><?= number_format((float)$payment['amount'], 0, '.', ' ') ?> so'm</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="total-row">
        <span class="total-label">Jami to'lov:</span>
        <span class="total-amount"><?= number_format((float)$payment['amount'], 0, '.', ' ') ?> so'm</span>
    </div>

    <?php if (in_array($method, ['humo','uzcard','visa']) && $card_number): ?>
    <div class="card-pay-info">
        <h4><?= e(strtoupper($method)) ?> karta orqali to'lov</h4>
        <div class="card-row"><span class="card-row-label">Karta raqami</span><span class="card-row-value"><?= e($card_number) ?></span></div>
        <div class="card-row"><span class="card-row-label">Karta egasi</span><span class="card-row-value"><?= e($card_name) ?></span></div>
        <div class="card-row"><span class="card-row-label">To'lov summasi</span><span class="card-row-value" style="color:var(--primary)"><?= number_format((float)$payment['amount'], 0, '.', ' ') ?> so'm</span></div>
    </div>
    <?php endif; ?>

    <?php if (!empty($payment['screenshot'])): ?>
    <div class="screenshot-block">
        <p style="font-weight:700;margin-bottom:8px">Yuborilgan screenshot:</p>
        <img src="<?= e($payment['screenshot']) ?>" alt="To'lov screenshot">
        <p>Ushbu screenshot admin tomonidan tekshiriladi</p>
    </div>
    <?php endif; ?>

    <?php if ($status !== 'success' && $method === 'invoice'): ?>
    <div class="instructions">
        <strong>To'lov ko'rsatmasi:</strong> Yuqoridagi kompaniya rekvizitlari bo'yicha to'lovni amalga oshiring. To'lov maqsadida "Hisob-faktura #<?= e($payment['invoice_number'] ?? '') ?>" deb yozing. To'lov tasdiqlangach, sizga avtomatik bildirishnoma yuboriladi.
    </div>
    <?php elseif ($status !== 'success' && in_array($method, ['humo','uzcard','visa'])): ?>
    <div class="instructions">
        <strong>Holat:</strong> Sizning to'lov screenshotingiz qabul qilindi va admin tomonidan tekshirilmoqda. Tasdiqlangach sizga bildirishnoma yuboriladi.
    </div>
    <?php elseif ($status === 'success'): ?>
    <div style="margin:24px 0;padding:18px;background:rgba(20,86,168,0.06);border:1px solid rgba(20,86,168,0.12);border-radius:12px;font-size:0.88rem;color:var(--primary-dark);text-align:center">
        <strong>To'lov tasdiqlangan!</strong> Tarifingiz <?= e(vpy_date($payment['expires_at'] ?? '', 'd.m.Y')) ?> gacha amal qiladi.
    </div>
    <?php endif; ?>

    <div class="foot">
        <p>Ushbu hisob-faktura elektron tarzda yaratilgan.</p>
        <p style="margin-top:4px">&copy; <?= date('Y') ?> <?= e(vpy_setting('company_name', 'VatanParvar')) ?></p>
    </div>
</div>
</body>
</html>
