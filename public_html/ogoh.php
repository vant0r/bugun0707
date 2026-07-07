<?php
define('VATANPARVAR', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$lang = vpy_lang_code();
$is_cyrl = $lang === 'uz_cyrillic';
?>
<!DOCTYPE html>
<html lang="<?= $is_cyrl ? 'uz-Cyrl' : 'uz' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<title>Ogohlantirish — VatanParvar</title>
<link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Manrope",sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0F1117;color:#F0F4FF;padding:24px;position:relative;overflow:hidden}
body::before{content:"";position:absolute;inset:0;background:radial-gradient(ellipse at 30% 50%,rgba(26,95,180,0.15),transparent 60%);pointer-events:none}
body::after{content:"";position:absolute;inset:0;background:radial-gradient(ellipse at 70% 60%,rgba(220,53,69,0.08),transparent 50%);pointer-events:none}
.box{position:relative;text-align:center;max-width:520px;width:100%;padding:60px 40px;background:rgba(26,32,50,0.85);backdrop-filter:blur(30px);border:1px solid rgba(220,53,69,0.25);border-radius:28px;box-shadow:0 30px 80px rgba(0,0,0,0.5);animation:pop 0.5s cubic-bezier(0.34,1.56,0.64,1)}
@keyframes pop{from{opacity:0;transform:scale(0.9) translateY(20px)}to{opacity:1;transform:scale(1) translateY(0)}}
.icon{width:80px;height:80px;margin:0 auto 24px;border-radius:50%;background:rgba(220,53,69,0.12);border:2px solid rgba(220,53,69,0.3);display:grid;place-items:center;animation:pulse 2s ease-in-out infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(220,53,69,0.4)}50%{box-shadow:0 0 0 20px rgba(220,53,69,0)}}
.icon svg{width:40px;height:40px;color:#DC3545}
h1{font-size:1.8rem;font-weight:700;margin-bottom:12px;color:#fff}
p{font-size:1rem;line-height:1.7;color:rgba(240,244,255,0.7);margin-bottom:28px}
.warn-list{text-align:left;margin:0 auto 28px;max-width:380px;display:flex;flex-direction:column;gap:10px}
.warn-list li{display:flex;align-items:flex-start;gap:10px;font-size:0.9rem;color:rgba(240,244,255,0.8);line-height:1.5}
.warn-list li::before{content:"⚠️";flex-shrink:0;font-size:0.85rem}
.btn{display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:100px;font-weight:700;font-size:0.92rem;background:linear-gradient(135deg,#1A5FB4,#144A8C);color:#fff;text-decoration:none;box-shadow:0 8px 24px rgba(26,95,180,0.3);transition:transform 0.3s,box-shadow 0.3s}
.btn:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(26,95,180,0.4)}
.footer-note{margin-top:24px;font-size:0.75rem;color:rgba(240,244,255,0.4)}
</style>
</head>
<body>
<div class="box">
    <div class="icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/>
            <line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
    </div>
    <h1><?= $is_cyrl ? 'Огоҳлантириш!' : 'Ogohlantirish!' ?></h1>
    <p><?= $is_cyrl 
        ? 'Sayt materiallarini nusxalash, kodini ko\'rish yoki manba kodini ochishga urinish taqiqlanadi.'
        : 'Sayt materiallarini nusxalash, kodini ko\'rish yoki manba kodini ochishga urinish taqiqlanadi.' ?></p>
    <ul class="warn-list">
        <li><?= $is_cyrl 
            ? 'Saytning manba kodi mualliflik huquqi bilan himoyalangan'
            : 'Saytning manba kodi mualliflik huquqi bilan himoyalangan' ?></li>
        <li><?= $is_cyrl 
            ? 'Kontentni ruxsatsiz nusxalash qonunga zid'
            : 'Kontentni ruxsatsiz nusxalash qonunga zid' ?></li>
        <li><?= $is_cyrl 
            ? 'Har qanday urinish qayd etiladi va administratorga xabar beriladi'
            : 'Har qanday urinish qayd etiladi va administratorga xabar beriladi' ?></li>
    </ul>
    <a href="/" class="btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        <?= $is_cyrl ? 'Бош саҳифага қайтиш' : 'Bosh sahifaga qaytish' ?>
    </a>
    <div class="footer-note">&copy; <?= date('Y') ?> VatanParvar — <?= $is_cyrl ? 'Барча ҳуқуқлар ҳимояланган' : 'Barcha huquqlar himoyalangan' ?></div>
</div>
</body>
</html>
