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

$merchant_id = vpy_setting('payme_merchant_id', VPY_PAYME_MERCHANT_ID);
$is_test = (bool)vpy_setting('payme_test', VPY_PAYME_TEST);

$payme_url = '';
if ($merchant_id) {
    $params = base64_encode(sprintf('m=%s;ac.order_id=%d;a=%d', $merchant_id, $payment['id'], $payment['amount'] * 100));
    $payme_url = ($is_test ? 'https://test.paycom.uz/' : 'https://checkout.paycom.uz/') . $params;
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Payme to'lov</title>
<link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
<style>
body{font-family:system-ui,sans-serif;background:#FAF7F2;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;margin:0}
.box{background:#fff;border-radius:32px;padding:48px;max-width:480px;width:100%;text-align:center;box-shadow:0 30px 60px rgba(0,0,0,0.1)}
.ico{width:80px;height:80px;border-radius:24px;background:linear-gradient(135deg,#00CCCC,#008888);color:#fff;display:grid;place-items:center;margin:0 auto 24px}
h1{font-size:1.5rem;margin-bottom:10px}
p{color:#7A6F62;margin-bottom:24px;line-height:1.5}
.amount{font-size:2.5rem;font-weight:700;color:#0D6B4E;margin-bottom:24px;font-family:Georgia,serif}
.btn{display:inline-flex;align-items:center;gap:10px;padding:18px 32px;background:#00CCCC;color:#fff;border-radius:100px;font-weight:600;text-decoration:none;box-shadow:0 12px 28px rgba(0,204,204,0.35)}
.btn-back{margin-top:14px;display:inline-block;color:#7A6F62;text-decoration:none;font-size:0.88rem}
.warn{background:rgba(232,168,56,0.1);color:#A87830;padding:14px;border-radius:14px;margin-bottom:20px;font-size:0.88rem}
</style>
</head>
<body>
<div class="box">
    <div class="ico"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="10"/><path d="M9 12h6"/></svg></div>
    <h1>Payme orqali to'lov</h1>
    <p><?= e($payment['tariff_name']) ?> · #<?= e($payment['invoice_number']) ?></p>
    <div class="amount"><?= number_format((float)$payment['amount'], 0, '.', ' ') ?> so'm</div>
    <?php if ($payme_url): ?>
        <a href="<?= e($payme_url) ?>" class="btn">Payme'ga o'tish <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
    <?php else: ?>
        <div class="warn">Payme sozlamalari to'liq emas. Iltimos, administrator bilan bog'laning.</div>
        <a href="/invoice.php?id=<?= (int)$payment['id'] ?>" class="btn">Hisob-fakturani ko'rish</a>
    <?php endif; ?>
    <div><a href="/user/tariflar.php" class="btn-back">← Orqaga qaytish</a></div>
</div>
</body>
</html>
