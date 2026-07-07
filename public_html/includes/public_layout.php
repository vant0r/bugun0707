<?php
if (!defined('VATANPARVAR')) require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

function vpy_public_css() {
    return <<<CSS
/* === VATANPARVAR DESIGN SYSTEM === */
:root{
    --bg:#F4F6F9;--bg2:#EAECF0;--surface:#FFFFFF;--surface2:#F8F9FC;
    --primary:#1456A8;--primary-dark:#0E3D7A;--primary-light:#2B72C9;--primary-glow:rgba(20,86,168,0.22);
    --blue-soft:rgba(20,86,168,0.10);--blue-mid:rgba(20,86,168,0.18);
    --accent:#1976B5;--accent2:#4BA3D9;
    --dark:#111318;--dark-soft:#2C3040;--muted:#5A6070;--light:#FFFFFF;
    --glass:rgba(255,255,255,0.75);--glass-strong:rgba(255,255,255,0.92);
    --border:rgba(20,86,168,0.12);--border-strong:rgba(20,86,168,0.24);
    --shadow-sm:0 2px 8px rgba(20,86,168,0.08);
    --shadow:0 8px 32px rgba(20,86,168,0.12);
    --shadow-lg:0 20px 60px rgba(20,86,168,0.18);
    --r-sm:12px;--r:18px;--r-lg:28px;--r-xl:40px;--pill:100px;
    --t:0.35s cubic-bezier(0.4,0,0.2,1);--t-bounce:0.55s cubic-bezier(0.34,1.56,0.64,1);
    --serif:"Playfair Display",Georgia,serif;
    --sans:"Manrope","Inter",-apple-system,BlinkMacSystemFont,system-ui,sans-serif;
    --container:min(1280px,92vw);
}
[data-theme="dark"]{
    --bg:#111113;--bg2:#1A1A1D;--surface:#202024;--surface2:#27272B;
    --primary:#5AA3E8;--primary-dark:#4088CC;--primary-light:#7BBAEF;--primary-glow:rgba(90,163,232,0.20);
    --blue-soft:rgba(90,163,232,0.10);--blue-mid:rgba(90,163,232,0.16);
    --dark:#E8E8EC;--dark-soft:#B0B0B8;--muted:#78788A;--light:#111113;
    --glass:rgba(26,26,29,0.84);--glass-strong:rgba(32,32,36,0.96);
    --border:rgba(255,255,255,0.07);--border-strong:rgba(255,255,255,0.13);
    --shadow-sm:0 2px 8px rgba(0,0,0,0.30);
    --shadow:0 8px 32px rgba(0,0,0,0.40);
    --shadow-lg:0 20px 60px rgba(0,0,0,0.50);
}
CSS;
}

function vpy_public_css2() {
    return <<<CSS
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%;overflow-x:hidden}
body{font-family:var(--sans);font-weight:500;font-size:clamp(15px,1vw,16px);line-height:1.65;color:var(--dark);background:var(--bg);overflow-x:hidden;min-height:100vh;position:relative;-webkit-font-smoothing:antialiased;-webkit-tap-highlight-color:transparent;transition:background var(--t),color var(--t)}
img,svg{max-width:100%;display:block;height:auto}
a{color:inherit;text-decoration:none;transition:var(--t);-webkit-tap-highlight-color:transparent}
button{font:inherit;cursor:pointer;border:none;background:none;color:inherit;-webkit-tap-highlight-color:transparent}
*:focus,*:active{outline:none;-webkit-tap-highlight-color:transparent}
*:focus-visible{outline:2px solid var(--primary);outline-offset:2px;border-radius:4px}
*::-moz-focus-inner{border:0}
ul{list-style:none}
input,textarea,select{font:inherit;color:inherit}
main{position:relative;z-index:2}
.container{width:var(--container);margin:0 auto;position:relative}
.h-display{font-family:var(--sans);font-weight:800;font-size:clamp(2rem,4.5vw,4rem);line-height:1.08;letter-spacing:-0.03em;color:var(--dark)}
.h-display em{font-style:italic;font-weight:600;color:var(--primary)}
.h-section{font-family:var(--sans);font-weight:700;font-size:clamp(1.6rem,3vw,2.6rem);line-height:1.1;letter-spacing:-0.02em}
.h-card{font-family:var(--sans);font-weight:800;font-size:clamp(1rem,1.4vw,1.2rem);letter-spacing:-0.01em;line-height:1.3}
.eyebrow{display:inline-flex;align-items:center;gap:8px;padding:7px 16px;background:var(--blue-soft);border:1px solid var(--border);border-radius:var(--pill);font-size:0.75rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:var(--primary)}
.eyebrow::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--primary);box-shadow:0 0 0 3px var(--blue-mid);animation:vp 2s ease-in-out infinite}
@keyframes vp{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.5);opacity:0.6}}
.lead{font-size:clamp(0.97rem,1.1vw,1.08rem);font-weight:500;line-height:1.7;color:var(--muted);max-width:60ch}
.muted{color:var(--muted)}
.reveal{opacity:0;transform:translateY(28px);transition:opacity 0.7s ease,transform 0.7s ease}
.reveal.in{opacity:1;transform:translateY(0)}
.r1{transition-delay:0.08s}.r2{transition-delay:0.16s}.r3{transition-delay:0.24s}.r4{transition-delay:0.32s}
.btn{position:relative;display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:13px 26px;border-radius:var(--pill);font-weight:600;font-size:0.9rem;cursor:pointer;transition:transform var(--t),box-shadow var(--t),background var(--t);overflow:hidden;font-family:inherit;border:none;white-space:nowrap}
.btn:hover{transform:translateY(-2px)}
.btn:active{transform:translateY(0) scale(0.98)}
.btn svg{width:15px;height:15px;transition:transform var(--t-bounce)}
.btn:hover svg{transform:translateX(3px)}
.btn-primary{background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);color:#fff;box-shadow:0 8px 24px var(--primary-glow)}
.btn-primary:hover{box-shadow:0 14px 36px rgba(26,95,180,0.35)}
.btn-accent{background:linear-gradient(135deg,#2185D0 0%,#1A5FB4 100%);color:#fff;box-shadow:0 8px 24px rgba(33,133,208,0.3)}
.btn-dark{background:var(--dark);color:var(--light);box-shadow:0 8px 24px rgba(26,29,35,0.2)}
.btn-ghost{background:var(--glass);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1.5px solid var(--border);color:var(--dark)}
.btn-ghost:hover{background:var(--glass-strong);border-color:var(--border-strong)}
.btn-nav{padding:10px 20px;font-size:0.84rem}
.glass{background:var(--glass);backdrop-filter:blur(28px) saturate(180%);-webkit-backdrop-filter:blur(28px) saturate(180%);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow)}
CSS;
}

function vpy_public_css3() {
    return <<<CSS
/* NAVBAR */
.navbar{position:fixed;top:14px;left:50%;transform:translateX(-50%);width:min(1200px,calc(100% - 28px));z-index:1000;transition:var(--t)}
.nav-inner{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:10px 10px 10px 22px;border-radius:var(--pill);background:var(--glass);backdrop-filter:blur(24px) saturate(180%);-webkit-backdrop-filter:blur(24px) saturate(180%);border:1px solid var(--border-strong);box-shadow:var(--shadow-sm)}
.navbar.scrolled .nav-inner{background:var(--glass-strong);box-shadow:var(--shadow);border-color:var(--border-strong)}
.nav-brand{display:flex;align-items:center;gap:11px;font-family:var(--serif);font-size:1.05rem;font-weight:700;color:var(--dark);flex-shrink:0}
.nav-logo{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:grid;place-items:center;color:#fff;box-shadow:0 4px 12px var(--primary-glow)}
.nav-logo svg{width:18px;height:18px}
.nav-links{display:flex;align-items:center;gap:2px}
.nav-link{padding:9px 14px;font-size:0.88rem;font-weight:500;color:var(--dark-soft);border-radius:var(--pill);transition:var(--t);position:relative}
.nav-link:hover,.nav-link.active{color:var(--primary);background:var(--blue-soft)}
.nav-cta{display:flex;align-items:center;gap:6px;flex-shrink:0}
.nav-lang{padding:8px 13px;border-radius:var(--pill);font-size:0.8rem;font-weight:600;color:var(--dark-soft);background:var(--blue-soft);border:1px solid var(--border);cursor:pointer;display:flex;align-items:center;gap:5px;transition:var(--t)}
.nav-lang:hover{background:var(--blue-mid);color:var(--primary)}
.nav-lang svg{width:13px;height:13px}
/* THEME TOGGLE */
.theme-toggle{width:38px;height:22px;border-radius:11px;background:var(--bg2);border:1.5px solid var(--border);position:relative;cursor:pointer;transition:var(--t);flex-shrink:0}
.theme-toggle::after{content:"";position:absolute;top:2px;left:2px;width:14px;height:14px;border-radius:50%;background:var(--primary);transition:transform var(--t),background var(--t)}
[data-theme="dark"] .theme-toggle::after{transform:translateX(16px);background:#fff}
[data-theme="dark"] .theme-toggle{background:var(--primary-dark)}
/* BURGER */
.burger{display:none;width:44px;height:44px;border-radius:50%;background:var(--glass-strong);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:2px solid var(--border-strong);align-items:center;justify-content:center;cursor:pointer;transition:var(--t);box-shadow:var(--shadow-sm)}
.burger span{display:block;width:18px;height:2px;background:var(--dark);border-radius:2px;position:relative;transition:var(--t)}
.burger span::before,.burger span::after{content:"";position:absolute;left:0;width:18px;height:2px;background:var(--dark);border-radius:2px;transition:var(--t)}
.burger span::before{top:-6px}.burger span::after{top:6px}
.burger.active span{background:transparent}
.burger.active span::before{top:0;transform:rotate(45deg)}
.burger.active span::after{top:0;transform:rotate(-45deg)}
/* MOBILE MENU — fixed overlay, content NOT obscured */
.mobile-menu{position:fixed;top:0;right:0;width:min(320px,100vw);height:100vh;z-index:999;background:var(--glass-strong);backdrop-filter:blur(40px) saturate(200%);-webkit-backdrop-filter:blur(40px) saturate(200%);border-left:1px solid var(--border);padding:80px 28px 40px;transform:translateX(100%);transition:transform 0.4s cubic-bezier(0.4,0,0.2,1);display:flex;flex-direction:column;gap:6px;overflow-y:auto;box-shadow:-10px 0 40px rgba(26,95,180,0.12)}
.mobile-menu.open{transform:translateX(0)}
.mobile-menu-overlay{position:fixed;inset:0;z-index:998;background:rgba(26,29,35,0.4);backdrop-filter:blur(4px);opacity:0;pointer-events:none;transition:opacity 0.4s ease}
.mobile-menu-overlay.open{opacity:1;pointer-events:auto}
.mobile-menu .m-link{display:flex;align-items:center;gap:12px;padding:14px 16px;font-size:0.97rem;font-weight:500;color:var(--dark);border-radius:var(--r-sm);transition:var(--t)}
.mobile-menu .m-link:hover,.mobile-menu .m-link.active{background:var(--blue-soft);color:var(--primary)}
.mobile-menu .m-divider{height:1px;background:var(--border);margin:10px 0}
.mobile-menu .m-utils{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:6px 16px 16px}
.mobile-menu .m-utils .nav-lang{padding:9px 16px}
.mobile-menu .m-utils-label{font-size:0.78rem;font-weight:600;color:var(--muted)}
.mobile-menu .m-actions{margin-top:auto;padding-top:20px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:8px}
/* SECTIONS */
section{padding:80px 0;position:relative}
.section-head{text-align:center;max-width:700px;margin:0 auto;padding:28px 32px;background:var(--glass);backdrop-filter:blur(20px) saturate(160%);-webkit-backdrop-filter:blur(20px) saturate(160%);border:1px solid var(--border);border-radius:var(--r-lg)}
.section-head .lead{margin:12px auto 0;text-align:center}
.page-hero{padding:150px 0 60px;text-align:center}
.page-hero .lead{margin:16px auto 0;text-align:center}
CSS;
}

function vpy_public_css4() {
    return <<<CSS
/* FOOTER */
.footer{background:linear-gradient(135deg,rgba(20,86,168,0.6) 0%,rgba(14,61,122,0.72) 100%);backdrop-filter:blur(26px) saturate(180%);-webkit-backdrop-filter:blur(26px) saturate(180%);color:rgba(240,244,255,0.85);padding:72px 0 32px;margin-top:80px;position:relative;z-index:5;overflow:hidden;border-top:1px solid rgba(255,255,255,0.14)}
[data-theme="dark"] .footer{background:linear-gradient(135deg,rgba(8,11,18,0.75) 0%,rgba(14,19,32,0.82) 100%);border-top-color:rgba(255,255,255,0.08)}
.footer::before{content:"";position:absolute;top:-40%;right:-10%;width:50%;height:200%;background:radial-gradient(ellipse,rgba(255,255,255,0.12),transparent 60%);pointer-events:none}
.footer-grid{display:grid;grid-template-columns:1.5fr 1fr 1fr 1fr;gap:48px;margin-bottom:48px;position:relative;z-index:2}
.footer-brand{display:flex;align-items:center;gap:12px;margin-bottom:16px;color:#fff;font-family:var(--serif);font-size:1.2rem;font-weight:700}
.footer-about{font-size:0.88rem;line-height:1.7;color:rgba(240,244,255,0.55);margin-bottom:22px;max-width:36ch}
.footer-social{display:flex;gap:8px}
.footer-social a{width:38px;height:38px;border-radius:10px;border:1px solid rgba(240,244,255,0.12);display:grid;place-items:center;color:rgba(240,244,255,0.65);transition:var(--t)}
.footer-social a:hover{background:var(--primary);color:#fff;border-color:var(--primary);transform:translateY(-2px)}
.footer-social svg{width:15px;height:15px}
.footer-col h4{color:#fff;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:18px}
.footer-col ul{display:flex;flex-direction:column;gap:9px}
.footer-col a{color:rgba(240,244,255,0.65);font-size:0.88rem;transition:var(--t)}
.footer-col a:hover{color:var(--primary-light);transform:translateX(3px)}
.footer-contact-line{display:flex;align-items:flex-start;gap:9px;font-size:0.88rem;color:rgba(240,244,255,0.65);line-height:1.5;margin-bottom:12px}
.footer-contact-line svg{width:14px;height:14px;color:var(--primary-light);flex-shrink:0;margin-top:3px}
.footer-bottom{border-top:1px solid rgba(240,244,255,0.08);padding-top:24px;display:flex;flex-wrap:wrap;gap:16px;justify-content:space-between;font-size:0.78rem;color:rgba(240,244,255,0.45);position:relative;z-index:2}
@media (max-width:1024px){.nav-links{display:none}.btn-nav{display:none}.nav-cta .theme-toggle,.nav-cta .nav-lang{display:none}.burger{display:flex}.footer-grid{grid-template-columns:1fr 1fr;gap:36px}.section-head{padding:22px 24px}}
@media (max-width:768px){section{padding:60px 0}.page-hero{padding:120px 0 40px}.footer-grid{grid-template-columns:1fr}.footer{padding:56px 0 24px}.container{width:calc(100% - 24px)}.section-head{padding:18px 18px;border-radius:var(--r)}.hero-content{padding:20px 18px;border-radius:var(--r)}}
@media (prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:0.01ms!important;transition-duration:0.01ms!important}}
/* TICKER STRIP */
.ticker-wrap{overflow:hidden;background:var(--primary);color:#fff;padding:10px 0;position:relative;z-index:3}
.ticker-inner{display:flex;gap:0;width:max-content;animation:ticker 30s linear infinite}
.ticker-inner:hover{animation-play-state:paused}
.ticker-item{display:flex;align-items:center;gap:10px;padding:0 36px;font-size:0.82rem;font-weight:500;white-space:nowrap;border-right:1px solid rgba(255,255,255,0.2)}
.ticker-item svg{width:13px;height:13px;opacity:0.8}
@keyframes ticker{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
CSS;
}


function vpy_public_head($title, $description = '', $extra_css = '') {
    $is_cyrl = vpy_lang_code() === 'uz_cyrillic';
    $css = vpy_public_css() . vpy_public_css2() . vpy_public_css3() . vpy_public_css4();
    echo '<!DOCTYPE html><html lang="' . ($is_cyrl ? 'uz-Cyrl' : 'uz') . '" data-theme="light"><head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">';
    echo '<meta name="theme-color" content="#1A5FB4">';
    echo '<meta name="description" content="' . e($description ?: vpy_setting('site_description')) . '">';
    echo '<title>' . e($title) . ' — ' . e(t('site_name')) . '</title>';
    echo '<link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">';
    echo '<link rel="manifest" href="/manifest.json">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">';
    echo '<style>' . $css . $extra_css . '</style>';
    echo '<script>!function(){var t=localStorage.getItem("vpy_theme");if(t)document.documentElement.setAttribute("data-theme",t);else if(matchMedia("(prefers-color-scheme:dark)").matches)document.documentElement.setAttribute("data-theme","dark")}()</script>';
    echo '</head><body>';
    // Ctrl+U / right-click protection
    echo '<script>document.addEventListener("keydown",function(e){if((e.ctrlKey||e.metaKey)&&e.key==="u"){e.preventDefault();window.location="/ogoh.php"}});document.addEventListener("contextmenu",function(e){e.preventDefault();window.location="/ogoh.php"});</script>';
}

function vpy_public_navbar($current = '') {
    $is_cyrl = vpy_lang_code() === 'uz_cyrillic';
    $items = [
        ['/', 'home', t('nav_home')],
        ['/tariflar.php', 'tariflar', t('nav_tariffs')],
        ['/blog.php', 'blog', t('nav_blog')],
        ['/aloqa.php', 'aloqa', t('nav_contact')],
    ];
    echo '<header class="navbar" id="navbar"><div class="nav-inner">';
    $logo_url = vpy_setting('site_logo', '');
    if ($logo_url) {
        echo '<a href="/" class="nav-brand"><img src="' . e($logo_url) . '" alt="VATANPARVAR" style="height:34px;width:auto;border-radius:8px"> VATANPARVAR</a>';
    } else {
        echo '<a href="/" class="nav-brand"><span class="nav-logo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l2-2 4 4 8-8 4 4"/></svg></span>VATANPARVAR</a>';
    }
    echo '<nav class="nav-links">';
    foreach ($items as $it) {
        $cls = $current === $it[1] ? 'active' : '';
        echo '<a href="' . e($it[0]) . '" class="nav-link ' . $cls . '">' . e($it[2]) . '</a>';
    }
    echo '</nav>';
    echo '<div class="nav-cta">';
    echo '<button class="theme-toggle" id="themeToggle" aria-label="Mavzuni o\'zgartirish"></button>';
    echo '<a class="nav-lang" href="?lang=' . ($is_cyrl ? 'uz_latin' : 'uz_cyrillic') . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/></svg>' . ($is_cyrl ? 'Lt' : 'Кр') . '</a>';
    if (vpy_is_logged()) {
        echo '<a href="' . (vpy_is_admin() ? '/admin/' : '/user/') . '" class="btn btn-primary btn-nav">' . e(t('nav_dashboard')) . '</a>';
    } else {
        echo '<a href="/login.php" class="btn btn-ghost btn-nav btn-nav-login">' . e(t('nav_login')) . '</a>';
        echo '<a href="/register.php" class="btn btn-primary btn-nav">' . e(t('nav_register')) . '</a>';
    }
    echo '<button class="burger" id="burger" aria-label="Menyu"><span></span></button>';
    echo '</div></div></header>';
    // Mobile menu
    echo '<div class="mobile-menu-overlay" id="mobileOverlay"></div>';
    echo '<div class="mobile-menu" id="mobileMenu">';
    foreach ($items as $it) {
        $cls = $current === $it[1] ? 'active' : '';
        echo '<a href="' . e($it[0]) . '" class="m-link ' . $cls . '">' . e($it[2]) . '</a>';
    }
    echo '<div class="m-divider"></div>';
    echo '<div class="m-utils"><span class="m-utils-label">' . ($is_cyrl ? 'Тема ва тил' : 'Tema va til') . '</span><div style="display:flex;align-items:center;gap:10px"><button class="theme-toggle" aria-label="Mavzuni o\'zgartirish"></button><a class="nav-lang" href="?lang=' . ($is_cyrl ? 'uz_latin' : 'uz_cyrillic') . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/></svg>' . ($is_cyrl ? 'Lt' : 'Кр') . '</a></div></div>';
    if (vpy_is_logged()) {
        echo '<div class="m-actions"><a href="' . (vpy_is_admin() ? '/admin/' : '/user/') . '" class="btn btn-primary" style="width:100%;justify-content:center">' . e(t('nav_dashboard')) . '</a></div>';
    } else {
        echo '<div class="m-actions"><a href="/login.php" class="btn btn-ghost" style="width:100%;justify-content:center">' . e(t('nav_login')) . '</a>';
        echo '<a href="/register.php" class="btn btn-primary" style="width:100%;justify-content:center">' . e(t('nav_register')) . '</a></div>';
    }
    echo '</div>';
}


function vpy_public_footer() {
    $year = date('Y');
    echo '<footer class="footer"><div class="container"><div class="footer-grid"><div>';
    $logo_url = vpy_setting('site_logo', '');
    if ($logo_url) {
        echo '<a href="/" class="footer-brand"><img src="' . e($logo_url) . '" alt="VATANPARVAR" style="height:32px;width:auto;border-radius:6px"> VATANPARVAR</a>';
    } else {
        echo '<a href="/" class="footer-brand"><span class="nav-logo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l2-2 4 4 8-8 4 4"/></svg></span>VATANPARVAR</a>';
    }
    echo '<p class="footer-about">' . e(t('footer_about')) . '</p>';
    echo '<div class="footer-social">';
    echo '<a href="https://t.me/' . e(ltrim(vpy_setting('contact_telegram', 'vatanparvaryaypan'), '@')) . '" target="_blank" aria-label="Telegram"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg></a>';
    echo '<a href="https://instagram.com/' . e(vpy_setting('contact_instagram', 'vatanparvaryaypan')) . '" target="_blank" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>';
    echo '</div></div>';
    echo '<div class="footer-col"><h4>' . e(t('footer_links')) . '</h4><ul>';
    echo '<li><a href="/">' . e(t('nav_home')) . '</a></li>';
    echo '<li><a href="/tariflar.php">' . e(t('nav_tariffs')) . '</a></li>';
    echo '<li><a href="/blog.php">' . e(t('nav_blog')) . '</a></li>';
    echo '<li><a href="/aloqa.php">' . e(t('nav_contact')) . '</a></li>';
    echo '</ul></div>';
    echo '<div class="footer-col"><h4>' . e(t('footer_contact')) . '</h4>';
    echo '<div class="footer-contact-line"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg><span>' . e(vpy_setting('contact_address', t('footer_address_value'))) . '</span></div>';
    echo '<div class="footer-contact-line"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg><span>' . e(vpy_setting('contact_phone', t('footer_phone_value'))) . '</span></div>';
    echo '<div class="footer-contact-line"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg><span>' . e(vpy_setting('contact_email', t('footer_email_value'))) . '</span></div>';
    echo '</div>';
    echo '<div class="footer-col"><h4>' . e(t('footer_hours')) . '</h4>';
    echo '<div class="footer-contact-line"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><span>' . e(t('footer_hours_value')) . '</span></div>';
    echo '</div></div>';
    echo '<div class="footer-bottom"><span>&copy; ' . $year . ' VATANPARVAR. ' . e(t('footer_rights')) . '.</span><span>' . e(t('footer_made')) . ' ' . e(t('site_city')) . '</span></div>';
    echo '</div></footer>';
    echo <<<'HTML'
<script>
(function(){
    // Navbar scroll
    var nav=document.getElementById('navbar');
    function ns(){if(window.scrollY>30)nav.classList.add('scrolled');else nav.classList.remove('scrolled');}
    if(nav){ns();window.addEventListener('scroll',ns,{passive:true});}
    // Burger / mobile menu
    var burger=document.getElementById('burger');
    var mm=document.getElementById('mobileMenu');
    var ov=document.getElementById('mobileOverlay');
    function closeMenu(){burger.classList.remove('active');mm.classList.remove('open');ov.classList.remove('open');document.body.style.overflow='';}
    function openMenu(){burger.classList.add('active');mm.classList.add('open');ov.classList.add('open');document.body.style.overflow='hidden';}
    if(burger&&mm){
        burger.addEventListener('click',function(){mm.classList.contains('open')?closeMenu():openMenu();});
        ov.addEventListener('click',closeMenu);
        mm.querySelectorAll('a').forEach(function(a){a.addEventListener('click',closeMenu);});
    }
    // Theme toggle
    var tts=document.querySelectorAll('.theme-toggle');
    tts.forEach(function(tt){
        tt.addEventListener('click',function(){
            var d=document.documentElement;
            var c=d.getAttribute('data-theme')==='dark'?'light':'dark';
            d.setAttribute('data-theme',c);
            localStorage.setItem('vpy_theme',c);
        });
    });
    // Reveal on scroll
    if('IntersectionObserver' in window){
        var io=new IntersectionObserver(function(es){es.forEach(function(en){if(en.isIntersecting){en.target.classList.add('in');io.unobserve(en.target);}});},{threshold:0.1,rootMargin:'0px 0px -50px 0px'});
        document.querySelectorAll('.reveal').forEach(function(el){io.observe(el);});
    } else {document.querySelectorAll('.reveal').forEach(function(el){el.classList.add('in');});}
})();
</script>
</body></html>
HTML;
}
