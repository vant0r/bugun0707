<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (vpy_is_logged()) vpy_redirect(vpy_is_admin() ? '/admin/' : '/user/');

$error = '';
$mode = vpy_get('mode', 'login'); // login or register
$phone_val = '';
$reg_vals = ['name' => '', 'phone' => '', 'referral' => vpy_get('ref', '')];

if (vpy_is_post()) {
    if (!vpy_csrf_check(vpy_post('csrf'))) { $error = t('xato_csrf'); }
    else {
        $action = vpy_post('action', 'login');
        if ($action === 'register') {
            $mode = 'register';
            $reg_vals['name'] = vpy_post('name');
            $reg_vals['phone'] = vpy_post('phone');
            $reg_vals['referral'] = vpy_post('referral');
            $r = vpy_register($reg_vals['name'], $reg_vals['phone'], vpy_post('password'), $reg_vals['referral']);
            if ($r['ok']) {
                $tariff = (int)vpy_get('tarif');
                vpy_redirect($tariff ? '/user/tariflar.php?tarif=' . $tariff : '/user/');
            }
            $error = $r['error'];
        } else {
            $mode = 'login';
            $phone_val = vpy_post('phone');
            $r = vpy_login($phone_val, vpy_post('password'), vpy_post('remember') === '1');
            if ($r['ok']) {
                $redirect = $_SESSION['vpy_login_redirect'] ?? null;
                unset($_SESSION['vpy_login_redirect']);
                vpy_redirect(vpy_safe_redirect_target($redirect, $r['user']['role'] === 'admin' ? '/admin/' : '/user/'));
            }
            $error = $r['error'];
        }
    }
}

$is_cyrl = vpy_lang_code() === 'uz_cyrillic';
$login_images = array_filter([
    vpy_setting('login_image_1', ''),
    vpy_setting('login_image_2', ''),
    vpy_setting('login_image_3', ''),
]);
$initial_mode = $mode;
?>

<!DOCTYPE html>
<html lang="<?= $is_cyrl ? 'uz-Cyrl' : 'uz' ?>" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#1A5FB4">
<meta name="robots" content="noindex">
<title><?= e(t('auth_login_title')) ?> — VATANPARVAR</title>
<link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<script>!function(){var t=localStorage.getItem("vpy_theme");if(t)document.documentElement.setAttribute("data-theme",t)}()</script>
<script>document.addEventListener("keydown",function(e){if((e.ctrlKey||e.metaKey)&&e.key==="u"){e.preventDefault();window.location="/ogoh.php"}});document.addEventListener("contextmenu",function(e){e.preventDefault();window.location="/ogoh.php"});</script>

<style>
:root{--bg:#F4F6F9;--primary:#1456A8;--primary-dark:#0E3D7A;--primary-glow:rgba(20,86,168,0.22);--dark:#111318;--dark-soft:#2C3040;--muted:#5A6070;--light:#FFFFFF;--glass:rgba(255,255,255,0.82);--glass-strong:rgba(255,255,255,0.94);--border:rgba(20,86,168,0.10);--border-strong:rgba(20,86,168,0.20);--shadow:0 8px 32px rgba(20,86,168,0.12);--r:18px;--r-lg:28px;--pill:100px;--t:0.4s cubic-bezier(0.4,0,0.2,1);--sans:"Manrope",sans-serif;--serif:"Playfair Display",serif}
[data-theme="dark"]{--bg:#111113;--primary:#5AA3E8;--primary-dark:#4088CC;--primary-glow:rgba(90,163,232,0.18);--dark:#E8E8EC;--dark-soft:#B0B0B8;--muted:#78788A;--light:#111113;--glass:rgba(26,26,29,0.86);--glass-strong:rgba(32,32,36,0.96);--border:rgba(255,255,255,0.06);--border-strong:rgba(255,255,255,0.12);--shadow:0 8px 32px rgba(0,0,0,0.40)}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
body{font-family:var(--sans);font-weight:500;font-size:16px;line-height:1.6;color:var(--dark);background:var(--bg);min-height:100vh;overflow:hidden;transition:background var(--t),color var(--t);-webkit-tap-highlight-color:transparent}
*:focus,*:active{outline:none;-webkit-tap-highlight-color:transparent}
*:focus-visible{outline:2px solid var(--primary);outline-offset:2px;border-radius:4px}
.auth-layout{display:grid;grid-template-columns:1fr 1fr;width:100%;min-height:100vh;position:relative;perspective:1200px}
/* Image and form swap with 3D rotation */
.auth-left{position:relative;display:flex;align-items:center;justify-content:center;padding:40px 36px;overflow:hidden;order:1;z-index:2;transition:transform 0.7s cubic-bezier(0.4,0,0.2,1),opacity 0.5s ease}
.auth-right{order:2;z-index:1;transition:transform 0.7s cubic-bezier(0.4,0,0.2,1),opacity 0.5s ease}
/* When register mode — smooth slide swap */
.auth-layout.mode-register .auth-left{transform:translateX(100%)}
.auth-layout.mode-register .auth-right{transform:translateX(-100%)}
.auth-left-inner{width:100%;max-width:420px;position:relative;height:480px}
/* PANELS with slide animation */
.auth-panel{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;transition:transform 0.55s cubic-bezier(0.4,0,0.2,1),opacity 0.4s ease;will-change:transform,opacity}
.auth-panel.panel-login{transform:translateX(0);opacity:1}
.auth-panel.panel-register{transform:translateX(120%);opacity:0;pointer-events:none}
/* When register mode */
.auth-left-inner.mode-register .panel-login{transform:translateX(-120%);opacity:0;pointer-events:none}
.auth-left-inner.mode-register .panel-register{transform:translateX(0);opacity:1;pointer-events:auto}
/* TOP BAR */
.top-bar{position:fixed;top:20px;left:20px;right:20px;display:flex;justify-content:space-between;align-items:center;z-index:100}
.back-link{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:var(--glass);backdrop-filter:blur(16px);border:1px solid var(--border);border-radius:var(--pill);font-size:0.82rem;font-weight:700;color:var(--dark);transition:var(--t);text-decoration:none}
.back-link:hover{background:var(--glass-strong);transform:translateX(-2px)}
.back-link svg{width:13px;height:13px}
.switch-link{padding:9px 16px;background:var(--glass);backdrop-filter:blur(16px);border:1px solid var(--border);border-radius:var(--pill);font-size:0.82rem;font-weight:700;color:var(--primary);cursor:pointer;transition:var(--t)}
.switch-link:hover{background:var(--primary);color:#fff}
/* BRAND */
.brand{display:inline-flex;align-items:center;gap:10px;margin-bottom:28px;font-family:var(--serif);font-weight:800;font-size:1.05rem;color:var(--dark);text-decoration:none}
.brand-logo{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:grid;place-items:center;color:#fff;box-shadow:0 4px 12px var(--primary-glow)}
.brand-logo svg{width:18px;height:18px}
h1{font-family:var(--sans);font-weight:800;font-size:clamp(1.6rem,3vw,2.1rem);line-height:1.1;margin-bottom:6px}
h1 em{font-style:italic;color:var(--primary)}
.sub{color:var(--muted);margin-bottom:24px;font-size:0.88rem;font-weight:500}
.error{background:rgba(220,53,69,0.06);border:1px solid rgba(220,53,69,0.15);color:#A81D2B;padding:11px 14px;border-radius:12px;margin-bottom:14px;font-size:0.85rem;font-weight:600;display:flex;align-items:center;gap:7px;animation:shake 0.4s ease}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-4px)}75%{transform:translateX(4px)}}
.error svg{width:15px;height:15px;flex-shrink:0}
.field{position:relative;margin-bottom:14px}
.field label{display:block;font-size:0.75rem;font-weight:700;color:var(--dark-soft);margin-bottom:5px;text-transform:uppercase;letter-spacing:0.04em}
.field input{width:100%;padding:13px 16px 13px 42px;border-radius:12px;border:1.5px solid var(--border-strong);background:var(--glass);color:var(--dark);font-size:0.9rem;font-weight:500;transition:border-color var(--t),box-shadow var(--t)}
.field input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow)}
.field-icon{position:absolute;left:14px;top:35px;color:var(--muted);pointer-events:none}
.field-icon svg{width:15px;height:15px}
.toggle-pwd{position:absolute;right:10px;top:31px;width:32px;height:32px;border-radius:8px;border:none;background:none;color:var(--muted);cursor:pointer;display:grid;place-items:center}
.toggle-pwd:hover{background:var(--primary-glow);color:var(--primary)}
.toggle-pwd svg{width:15px;height:15px}
.row{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:8px}
.remember{display:inline-flex;align-items:center;gap:6px;font-size:0.82rem;font-weight:600;color:var(--dark-soft);cursor:pointer}
.remember input{width:15px;height:15px;border-radius:3px;accent-color:var(--primary)}
.forgot{font-size:0.82rem;color:var(--primary);font-weight:700}
.strength{display:flex;gap:3px;margin-top:5px}
.strength span{flex:1;height:3px;border-radius:2px;background:var(--border);transition:var(--t)}
.strength.s1 span:nth-child(1){background:#DC3545}
.strength.s2 span:nth-child(-n+2){background:#F39C12}
.strength.s3 span:nth-child(-n+3){background:#2185D0}
.strength.s4 span{background:#1A5FB4}
.btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:14px;border-radius:var(--pill);border:none;font-family:inherit;font-weight:800;font-size:0.92rem;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;box-shadow:0 8px 24px var(--primary-glow);transition:transform var(--t),box-shadow var(--t)}
.btn:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(26,95,180,0.3)}
.btn:active{transform:scale(0.98)}
.btn svg{width:15px;height:15px}
.divider{display:flex;align-items:center;gap:12px;margin:20px 0 14px;color:var(--muted);font-size:0.78rem;font-weight:600}
.divider::before,.divider::after{content:"";flex:1;height:1px;background:var(--border)}
.alt{text-align:center;font-size:0.88rem;font-weight:600;color:var(--dark-soft)}
.alt a,.alt button{color:var(--primary);font-weight:800;background:none;border:none;cursor:pointer;font-family:inherit;font-size:inherit}
/* RIGHT SIDE — IMAGE SLIDESHOW */
.auth-right{position:relative;overflow:hidden;background:#222}
.auth-right::after{content:none}
.slide-container{position:absolute;inset:0}.slide-img{position:absolute;inset:0;object-fit:cover;width:100%;height:100%;opacity:0;transition:opacity 1.2s ease}.slide-img.active{opacity:1}
.right-content{position:absolute;bottom:40px;left:28px;right:28px;z-index:2;color:#fff;padding:20px 24px;background:rgba(0,0,0,0.45);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-radius:16px;border:1px solid rgba(255,255,255,0.08)}
.right-content h2{font-family:var(--sans);font-size:clamp(1.1rem,1.8vw,1.4rem);font-weight:800;margin-bottom:6px}
.right-content p{font-size:0.84rem;font-weight:500;color:rgba(255,255,255,0.75);line-height:1.5}
.dots{display:flex;gap:5px;margin-top:12px}
.dot{width:7px;height:7px;border-radius:50%;background:rgba(255,255,255,0.35);transition:var(--t)}
.dot.active{background:#fff;width:22px;border-radius:4px}
/* RESPONSIVE — mobile */
@media (max-width:900px){
    .auth-layout{grid-template-columns:1fr;perspective:none}
    .auth-layout.mode-register .auth-left{transform:none}
    .auth-layout.mode-register .auth-right{transform:none}
    .auth-right{position:fixed;inset:0;z-index:0}
    .auth-left{position:relative;z-index:2;background:transparent;padding:90px 20px 40px;transform:none!important}
    .auth-left-inner{background:var(--glass);backdrop-filter:blur(32px);-webkit-backdrop-filter:blur(32px);border:1px solid var(--border);border-radius:var(--r-lg);padding:32px 24px;box-shadow:var(--shadow);height:auto}
    .auth-panel{position:relative;inset:auto}
    .auth-panel.panel-register{position:absolute;inset:0;padding:32px 24px}
    .right-content{display:none}
}
</style>
</head>
<body>

<div class="top-bar">
    <a href="/" class="back-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M19 12H5M12 19l-7-7 7-7"/></svg><?= e(t('btn_back')) ?></a>
    <button class="switch-link" id="switchBtn"><?= $initial_mode === 'register' ? e(t('nav_login')) : e(t('nav_register')) ?></button>
</div>

<div class="auth-layout <?= $initial_mode === 'register' ? 'mode-register' : '' ?>">
    <section class="auth-left">
        <div class="auth-left-inner <?= $initial_mode === 'register' ? 'mode-register' : '' ?>" id="authInner">
            <!-- LOGIN PANEL -->
            <div class="auth-panel panel-login">
                <a href="/" class="brand"><span class="brand-logo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M3 12l2-2 4 4 8-8 4 4"/></svg></span>VATANPARVAR</a>
                <h1><em><?= e(t('auth_login_title')) ?></em></h1>
                <p class="sub"><?= e(t('auth_login_subtitle')) ?></p>
                <?php if ($error && $mode === 'login'): ?>
                <div class="error"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span><?= e($error) ?></span></div>
                <?php endif; ?>
                <form method="post" autocomplete="on" novalidate>
                    <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                    <input type="hidden" name="action" value="login">
                    <div class="field"><label><?= e(t('auth_phone')) ?></label><input name="phone" type="tel" inputmode="tel" required autocomplete="tel" placeholder="<?= e(t('auth_phone_ph')) ?>" value="<?= e($phone_val) ?>" class="phone-input"><span class="field-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg></span></div>
                    <div class="field"><label><?= e(t('auth_password')) ?></label><input name="password" type="password" required autocomplete="current-password" placeholder="<?= e(t('auth_password_ph')) ?>" id="loginPwd"><span class="field-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span><button type="button" class="toggle-pwd" onclick="var p=document.getElementById('loginPwd');p.type=p.type==='password'?'text':'password'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
                    <div class="row"><label class="remember"><input type="checkbox" name="remember" value="1" checked><?= e(t('auth_remember')) ?></label><a href="#" class="forgot"><?= e(t('auth_forgot')) ?></a></div>
                    <button type="submit" class="btn"><?= e(t('auth_login_btn')) ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
                </form>
                <div class="divider"><?= e(t('auth_or')) ?></div>
                <p class="alt"><?= e(t('auth_no_account')) ?> <button onclick="switchMode('register')"><?= e(t('auth_register_btn')) ?></button></p>
            </div>


            <!-- REGISTER PANEL -->
            <div class="auth-panel panel-register">
                <a href="/" class="brand"><span class="brand-logo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"VATANPARVAR><path d="M3 12l2-2 4 4 8-8 4 4"/></svg></span>VATANPARVAR</a>
                <h1><em><?= e(t('auth_register_title')) ?></em></h1>
                <p class="sub"><?= e(t('auth_register_subtitle')) ?></p>
                <?php if ($error && $mode === 'register'): ?>
                <div class="error"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span><?= e($error) ?></span></div>
                <?php endif; ?>
                <form method="post" autocomplete="on" novalidate>
                    <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                    <input type="hidden" name="action" value="register">
                    <div class="field"><label><?= e(t('auth_name')) ?></label><input name="name" type="text" required placeholder="<?= e(t('auth_name_ph')) ?>" value="<?= e($reg_vals['name']) ?>"><span class="field-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span></div>
                    <div class="field"><label><?= e(t('auth_phone')) ?></label><input name="phone" type="tel" inputmode="tel" required placeholder="<?= e(t('auth_phone_ph')) ?>" value="<?= e($reg_vals['phone']) ?>" class="phone-input"><span class="field-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg></span></div>
                    <div class="field"><label><?= e(t('auth_password')) ?></label><input name="password" type="password" required placeholder="<?= e(t('auth_password_ph')) ?>" id="regPwd" minlength="6"><span class="field-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span><button type="button" class="toggle-pwd" onclick="var p=document.getElementById('regPwd');p.type=p.type==='password'?'text':'password'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button><div class="strength" id="strength"><span></span><span></span><span></span><span></span></div></div>
                    <div class="field"><label><?= e(t('auth_referral')) ?></label><input name="referral" type="text" placeholder="<?= e(t('auth_referral_ph')) ?>" value="<?= e($reg_vals['referral']) ?>" maxlength="12" style="text-transform:uppercase"><span class="field-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/></svg></span></div>
                    <button type="submit" class="btn"><?= e(t('auth_register_btn')) ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
                </form>
                <div class="divider"><?= e(t('auth_or')) ?></div>
                <p class="alt"><?= e(t('auth_have_account')) ?> <button onclick="switchMode('login')"><?= e(t('auth_login_btn')) ?></button></p>
            </div>
        </div>
    </section>


    <!-- RIGHT SIDE — IMAGE SLIDESHOW -->
    <aside class="auth-right">
        <div class="slide-container">
            <?php if (!empty($login_images)): foreach ($login_images as $i => $img): ?>
            <img class="slide-img <?= $i === 0 ? 'active' : '' ?>" src="<?= e($img) ?>" alt="VATANPARVAR">
            <?php endforeach; else: ?>
            <div style="width:100%;height:100%;background:linear-gradient(135deg,#1A5FB4,#0D3B7A)"></div>
            <?php endif; ?>
        </div>
        <div class="right-content">
            <h2>VATANPARVAR</h2>
            <p><?= e(t('hero_subtitle')) ?></p>
            <?php if (count($login_images) > 1): ?>
            <div class="dots"><?php for ($i=0;$i<count($login_images);$i++): ?><span class="dot <?= $i===0?'active':'' ?>"></span><?php endfor; ?></div>
            <?php endif; ?>
        </div>
    </aside>
</div>

<script>
(function(){
    var inner=document.getElementById('authInner');
    var layout=document.querySelector('.auth-layout');
    var switchBtn=document.getElementById('switchBtn');
    var currentMode='<?= e($initial_mode) ?>';

    window.switchMode=function(mode){
        currentMode=mode;
        if(mode==='register'){
            inner.classList.add('mode-register');
            layout.classList.add('mode-register');
            switchBtn.textContent='<?= e(t('nav_login')) ?>';
        }else{
            inner.classList.remove('mode-register');
            layout.classList.remove('mode-register');
            switchBtn.textContent='<?= e(t('nav_register')) ?>';
        }
    };
    switchBtn.addEventListener('click',function(){
        switchMode(currentMode==='login'?'register':'login');
    });

    // Phone formatter
    document.querySelectorAll('.phone-input').forEach(function(phone){
        phone.addEventListener('input',function(e){
            var v=e.target.value.replace(/\D/g,'');
            if(v.startsWith('998'))v=v.substring(3);
            if(v.length>9)v=v.substring(0,9);
            var f='+998';
            if(v.length>0)f+=' '+v.substring(0,2);
            if(v.length>2)f+=' '+v.substring(2,5);
            if(v.length>5)f+=' '+v.substring(5,7);
            if(v.length>7)f+=' '+v.substring(7,9);
            e.target.value=f;
        });
    });

    // Password strength
    var regPwd=document.getElementById('regPwd');
    var str=document.getElementById('strength');
    if(regPwd&&str){regPwd.addEventListener('input',function(){
        var v=regPwd.value,l=0;
        if(v.length>=6)l=1;if(v.length>=8&&/\d/.test(v))l=2;
        if(v.length>=8&&/[A-Z]/.test(v)&&/\d/.test(v))l=3;
        if(v.length>=10&&/[A-Z]/.test(v)&&/\d/.test(v)&&/[^A-Za-z0-9]/.test(v))l=4;
        str.className='strength s'+l;
    });}

    // Slideshow
    var imgs=document.querySelectorAll('.slide-img');
    var dots=document.querySelectorAll('.dot');
    if(imgs.length>1){var cur=0;setInterval(function(){
        imgs[cur].classList.remove('active');if(dots[cur])dots[cur].classList.remove('active');
        cur=(cur+1)%imgs.length;
        imgs[cur].classList.add('active');if(dots[cur])dots[cur].classList.add('active');
    },4000);}
})();
</script>
</body>
</html>