<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../auth.php';

vpy_require_login('/login.php');
$id = (int)vpy_get('id');
$payment = vpy_find('tolovlar', 'id', $id);
if (!$payment || (int)$payment['user_id'] !== (int)vpy_user()['id']) {
    vpy_redirect('/user/tariflar.php');
}

$service_id = vpy_setting('click_service_id', VPY_CLICK_SERVICE_ID);
$merchant_id = vpy_setting('click_merchant_id', VPY_CLICK_MERCHANT_ID);

$click_url = '';
if ($service_id && $merchant_id) {
    $params = [
        'service_id' => $service_id,
        'merchant_id' => $merchant_id,
        'amount' => $payment['amount'],
        'transaction_param' => $payment['id'],
        'return_url' => 'https://' . VPY_DOMAIN . '/user/tariflar.php?status=ok',
    ];
    $click_url = 'https://my.click.uz/services/pay?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Click to'lov</title>
<link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
<style>
body{font-family:system-ui,sans-serif;background:#FAF7F2;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;margin:0}
.box{background:#fff;border-radius:32px;padding:48px;max-width:480px;width:100%;text-align:center;box-shadow:0 30px 60px rgba(0,0,0,0.1)}
.ico{width:80px;height:80px;border-radius:24px;background:linear-gradient(135deg,#0099FF,#0077CC);color:#fff;display:grid;place-items:center;margin:0 auto 24px}
h1{font-size:1.5rem;margin-bottom:10px;color:#1E1B18}
p{color:#7A6F62;margin-bottom:24px;line-height:1.5}
.amount{font-size:2.5rem;font-weight:700;color:#0D6B4E;margin-bottom:24px;font-family:Georgia,serif}
.btn{display:inline-flex;align-items:center;gap:10px;padding:18px 32px;background:#0099FF;color:#fff;border-radius:100px;font-weight:600;text-decoration:none;box-shadow:0 12px 28px rgba(0,153,255,0.35)}
.btn-back{margin-top:14px;display:inline-block;color:#7A6F62;text-decoration:none;font-size:0.88rem}
.warn{background:rgba(232,168,56,0.1);color:#A87830;padding:14px;border-radius:14px;margin-bottom:20px;font-size:0.88rem}
</style>
</head>
<body>
<div class="box">
    <div class="ico"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
    <h1>Click orqali to'lov</h1>
    <p><?= e($payment['tariff_name']) ?> · #<?= e($payment['invoice_number']) ?></p>
    <div class="amount"><?= number_format((float)$payment['amount'], 0, '.', ' ') ?> so'm</div>
    <?php if ($click_url): ?>
        <a href="<?= e($click_url) ?>" class="btn">Click sahifasiga o'tish <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
    <?php else: ?>
        <div class="warn">Click sozlamalari to'liq emas. Iltimos, administrator bilan bog'laning.</div>
        <a href="/invoice.php?id=<?= (int)$payment['id'] ?>" class="btn">Hisob-fakturani ko'rish</a>
    <?php endif; ?>
    <div><a href="/user/tariflar.php" class="btn-back">← Orqaga qaytish</a></div>
</div>
</body>
</html>
