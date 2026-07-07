<?php
if (!defined('VATANPARVAR')) require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notifications.php';

function vpy_panel_css() {
    return <<<CSS
:root{
    --bg:#F4F6F9;--bg2:#EAECF0;--surface:#FFFFFF;--surface2:#F8F9FC;
    --primary:#1456A8;--primary-dark:#0E3D7A;--primary-light:#2B72C9;--primary-glow:rgba(20,86,168,0.22);
    --blue-soft:rgba(20,86,168,0.10);--blue-mid:rgba(20,86,168,0.18);
    --accent:#1976B5;--accent2:#4BA3D9;
    --dark:#111318;--dark-soft:#2C3040;--muted:#5A6070;--light:#FFFFFF;
    --glass:rgba(255,255,255,0.75);--glass-strong:rgba(255,255,255,0.92);
    --border:rgba(20,86,168,0.12);--border-strong:rgba(20,86,168,0.24);
    --shadow-sm:0 2px 8px rgba(20,86,168,0.08);--shadow:0 8px 32px rgba(20,86,168,0.12);
    --r-sm:12px;--r:18px;--r-lg:28px;--pill:100px;
    --t:0.35s cubic-bezier(0.4,0,0.2,1);
    --serif:"Playfair Display",Georgia,serif;
    --sans:"Manrope","Inter",-apple-system,BlinkMacSystemFont,system-ui,sans-serif;
    --panel-bg-img:none;
}
[data-theme="dark"]{
    --bg:#111113;--bg2:#1A1A1D;--surface:#202024;--surface2:#27272B;
    --primary:#5AA3E8;--primary-dark:#4088CC;--primary-light:#7BBAEF;--primary-glow:rgba(90,163,232,0.20);
    --blue-soft:rgba(90,163,232,0.10);--blue-mid:rgba(90,163,232,0.16);
    --dark:#E8E8EC;--dark-soft:#B0B0B8;--muted:#78788A;--light:#111113;
    --glass:rgba(26,26,29,0.84);--glass-strong:rgba(32,32,36,0.96);
    --border:rgba(255,255,255,0.07);--border-strong:rgba(255,255,255,0.13);
    --shadow-sm:0 2px 8px rgba(0,0,0,0.30);--shadow:0 8px 32px rgba(0,0,0,0.40);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{font-family:var(--sans);font-size:15px;line-height:1.55;color:var(--dark);background:var(--bg);min-height:100vh;-webkit-font-smoothing:antialiased;-webkit-tap-highlight-color:transparent;overflow-x:hidden;transition:background var(--t),color var(--t)}
body::after{content:"";position:fixed;inset:0;pointer-events:none;z-index:0;background:var(--panel-bg-img);background-size:cover;background-position:center;opacity:0.12}
img,svg{max-width:100%;display:block;height:auto}
a{color:inherit;text-decoration:none;transition:var(--t);-webkit-tap-highlight-color:transparent}
button{font:inherit;cursor:pointer;border:none;background:none;color:inherit;-webkit-tap-highlight-color:transparent}
*:focus,*:active{outline:none;-webkit-tap-highlight-color:transparent}
*:focus-visible{outline:2px solid var(--primary);outline-offset:2px;border-radius:4px}
*::-moz-focus-inner{border:0}
ul{list-style:none}
input,textarea,select{font:inherit;color:inherit}
.layout{display:grid;grid-template-columns:260px 1fr;min-height:100vh;position:relative;z-index:2}
.sidebar{position:sticky;top:0;height:100vh;background:var(--glass-strong);backdrop-filter:blur(28px) saturate(180%);-webkit-backdrop-filter:blur(28px) saturate(180%);border-right:1px solid var(--border);padding:24px 18px;display:flex;flex-direction:column;gap:4px;overflow-y:auto;z-index:10;transition:background var(--t)}
.s-brand{display:flex;align-items:center;gap:11px;padding:8px 10px 20px;font-family:var(--serif);font-weight:700;font-size:1rem;color:var(--dark);border-bottom:1px solid var(--border);margin-bottom:14px}
.s-logo{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:grid;place-items:center;color:#fff;box-shadow:0 4px 12px var(--primary-glow);flex-shrink:0}
.s-logo svg{width:18px;height:18px}
.s-section{font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--muted);padding:14px 10px 4px}
.s-link{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:var(--r-sm);color:var(--dark-soft);font-size:0.88rem;font-weight:500;transition:var(--t);position:relative}
.s-link svg{width:17px;height:17px;flex-shrink:0;color:var(--muted);transition:var(--t)}
.s-link:hover{background:var(--blue-soft);color:var(--primary)}
.s-link:hover svg{color:var(--primary)}
.s-link.active{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;box-shadow:0 6px 18px var(--primary-glow)}
.s-link.active svg{color:#fff}
.s-link .badge{margin-left:auto;background:var(--accent);color:#fff;font-size:0.68rem;font-weight:700;padding:2px 7px;border-radius:var(--pill)}
.s-link.active .badge{background:rgba(255,255,255,0.25)}
.s-foot{margin-top:auto;padding-top:14px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:4px}
.main{padding:28px 32px;min-width:0}
.topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:26px;flex-wrap:wrap}
.tb-title h1{font-family:var(--sans);font-weight:700;font-size:clamp(1.2rem,2.5vw,2rem);line-height:1.1;letter-spacing:-0.015em;margin-bottom:3px}
.tb-title p{font-size:0.85rem;color:var(--muted)}
.tb-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;flex-shrink:0}
CSS;
}


function vpy_panel_css2() {
    return <<<CSS
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 18px;border-radius:var(--pill);font-weight:600;font-size:0.85rem;cursor:pointer;transition:transform var(--t),box-shadow var(--t),background var(--t);border:none;font-family:inherit;white-space:nowrap;position:relative;overflow:hidden}
.btn:hover{transform:translateY(-2px)}.btn:active{transform:translateY(0) scale(0.98)}
.btn svg{width:15px;height:15px}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;box-shadow:0 6px 20px var(--primary-glow)}
.btn-primary:hover{box-shadow:0 10px 28px rgba(26,95,180,0.3)}
.btn-dark{background:var(--dark);color:var(--light);box-shadow:0 6px 20px rgba(26,29,35,0.2)}
.btn-ghost{background:var(--glass);backdrop-filter:blur(20px);border:1.5px solid var(--border);color:var(--dark)}
.btn-ghost:hover{background:var(--glass-strong);border-color:var(--border-strong)}
.btn-danger{background:linear-gradient(135deg,#DC3545,#A81D2B);color:#fff;box-shadow:0 6px 20px rgba(220,53,69,0.25)}
.btn-success{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;box-shadow:0 6px 20px var(--primary-glow)}
.btn-sm{padding:7px 13px;font-size:0.78rem}
.user-pop{position:relative}
.user-btn{display:flex;align-items:center;gap:9px;padding:6px 12px 6px 6px;background:var(--glass);backdrop-filter:blur(20px);border:1.5px solid var(--border);border-radius:var(--pill);cursor:pointer;transition:var(--t)}
.user-btn:hover{background:var(--glass-strong)}
.user-avatar{width:32px;height:32px;border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:700;font-size:0.78rem;flex-shrink:0}
.user-name{font-size:0.85rem;font-weight:600;color:var(--dark)}
.user-menu{position:absolute;top:calc(100% + 8px);right:0;min-width:220px;background:var(--glass-strong);backdrop-filter:blur(28px);-webkit-backdrop-filter:blur(28px);border:1px solid var(--border);border-radius:var(--r);padding:8px;box-shadow:var(--shadow);opacity:0;pointer-events:none;transform:translateY(-8px);transition:var(--t);z-index:50}
.user-pop.open .user-menu{opacity:1;pointer-events:auto;transform:translateY(0)}
.user-menu a{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;color:var(--dark-soft);font-size:0.88rem;font-weight:500}
.user-menu a:hover{background:var(--blue-soft);color:var(--primary)}
.user-menu a.danger:hover{background:rgba(220,53,69,0.08);color:#DC3545}
.user-menu hr{border:none;border-top:1px solid var(--border);margin:5px 0}
.card{background:var(--glass);backdrop-filter:blur(24px) saturate(160%);-webkit-backdrop-filter:blur(24px) saturate(160%);border:1px solid var(--border-strong);border-radius:var(--r-lg);padding:26px;box-shadow:var(--shadow-sm);transition:transform var(--t),box-shadow var(--t)}
.card:hover{box-shadow:var(--shadow)}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:12px}
.card-head h2{font-family:var(--serif);font-weight:600;font-size:1.3rem;letter-spacing:-0.01em;color:var(--dark)}
.flash{padding:12px 18px;border-radius:var(--r-sm);margin-bottom:16px;display:flex;align-items:center;gap:9px;font-size:0.88rem;animation:slideIn 0.4s ease}
@keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.flash.success{background:rgba(26,95,180,0.08);color:var(--primary-dark);border:1px solid rgba(26,95,180,0.15)}
.flash.error{background:rgba(220,53,69,0.08);color:#A81D2B;border:1px solid rgba(220,53,69,0.2)}
.flash.info{background:rgba(33,133,208,0.08);color:#155A8A;border:1px solid rgba(33,133,208,0.2)}
.flash svg{width:17px;height:17px;flex-shrink:0}
.field{margin-bottom:16px}
.field label{display:block;font-size:0.78rem;font-weight:600;color:var(--dark-soft);margin-bottom:6px;letter-spacing:0.02em}
.field input,.field textarea,.field select{width:100%;padding:12px 16px;border-radius:var(--r-sm);border:1.5px solid var(--border-strong);background:var(--surface);color:var(--dark);font-size:0.92rem;font-family:inherit;transition:var(--t)}
.field input:focus,.field textarea:focus,.field select:focus{outline:none;border-color:var(--primary);background:var(--light);box-shadow:0 0 0 3px var(--primary-glow)}
.field textarea{min-height:110px;resize:vertical;line-height:1.5}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
table.tbl{width:100%;border-collapse:separate;border-spacing:0;font-size:0.88rem}
table.tbl thead th{text-align:left;padding:12px 14px;background:var(--blue-soft);font-weight:700;color:var(--dark-soft);font-size:0.75rem;text-transform:uppercase;letter-spacing:0.06em;border-bottom:1px solid var(--border)}
table.tbl thead th:first-child{border-radius:var(--r-sm) 0 0 0}
table.tbl thead th:last-child{border-radius:0 var(--r-sm) 0 0}
table.tbl tbody td{padding:12px 14px;border-bottom:1px solid var(--border);vertical-align:middle}
table.tbl tbody tr:hover{background:var(--blue-soft)}
table.tbl tbody tr:last-child td{border-bottom:none}
.row-actions{display:flex;gap:5px}
.row-actions a,.row-actions button{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;background:var(--blue-soft);color:var(--primary);transition:var(--t);font-size:0.78rem}
.row-actions a:hover,.row-actions button:hover{background:var(--primary);color:#fff;transform:translateY(-2px)}
.row-actions .danger{background:rgba(220,53,69,0.08);color:#DC3545}
.row-actions .danger:hover{background:#DC3545;color:#fff}
.row-actions svg{width:13px;height:13px}
.chip{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:var(--pill);font-size:0.72rem;font-weight:600}
.chip-success{background:rgba(26,95,180,0.1);color:var(--primary-dark)}
.chip-warning{background:rgba(33,133,208,0.12);color:#155A8A}
.chip-danger{background:rgba(220,53,69,0.08);color:#A81D2B}
.chip-muted{background:rgba(107,114,128,0.1);color:var(--muted)}
.empty{text-align:center;padding:50px 28px;color:var(--muted)}
.empty svg{width:50px;height:50px;margin:0 auto 16px;opacity:0.4}
.empty h3{font-family:var(--serif);font-weight:600;font-size:1.2rem;color:var(--dark);margin-bottom:6px}
.pagination{display:flex;gap:5px;justify-content:center;margin-top:22px;flex-wrap:wrap}
.pagination a,.pagination span{min-width:36px;height:36px;padding:0 10px;border-radius:10px;display:grid;place-items:center;font-size:0.82rem;font-weight:600;color:var(--dark-soft);background:var(--glass);border:1px solid var(--border);transition:var(--t)}
.pagination a:hover{background:var(--glass-strong);border-color:var(--border-strong)}
.pagination .active{background:var(--primary);color:#fff;border-color:var(--primary)}
.theme-toggle-s{width:36px;height:20px;border-radius:10px;background:var(--bg2);border:1.5px solid var(--border);position:relative;cursor:pointer;transition:var(--t);flex-shrink:0}
.theme-toggle-s::after{content:"";position:absolute;top:2px;left:2px;width:12px;height:12px;border-radius:50%;background:var(--primary);transition:transform var(--t)}
[data-theme="dark"] .theme-toggle-s::after{transform:translateX(16px);background:#fff}
[data-theme="dark"] .theme-toggle-s{background:var(--primary-dark)}
.burger-mobile{display:none;width:36px;height:36px;border-radius:8px;background:var(--surface);border:1.5px solid var(--border-strong);align-items:center;justify-content:center;cursor:pointer;flex-shrink:0}
.burger-mobile span{display:block;width:16px;height:2px;background:var(--dark);position:relative;border-radius:1px}
.burger-mobile span::before,.burger-mobile span::after{content:"";position:absolute;left:0;width:16px;height:2px;background:var(--dark);border-radius:1px;transition:var(--t)}
.burger-mobile span::before{top:-5px}.burger-mobile span::after{top:5px}
@media (max-width:1024px){.layout{grid-template-columns:1fr}.sidebar{position:fixed;top:0;left:-100%;width:260px;height:100vh;transition:left 0.4s cubic-bezier(0.4,0,0.2,1);box-shadow:10px 0 40px rgba(0,0,0,0.15);z-index:999}.sidebar.open{left:0}.burger-mobile{display:flex}.main{padding:18px}.field-row{grid-template-columns:1fr}}
@media (max-width:640px){.main{padding:14px}.card{padding:20px;border-radius:var(--r)}.topbar{margin-bottom:18px}.tb-title h1{font-size:1.1rem}.tb-title p{display:none}table.tbl{font-size:0.8rem}table.tbl thead th,table.tbl tbody td{padding:9px 10px}.user-name{display:none}}
@keyframes spin{to{transform:rotate(360deg)}}
.spinner{width:18px;height:18px;border:2px solid var(--blue-mid);border-top-color:var(--primary);border-radius:50%;animation:spin 0.8s linear infinite}
CSS;
}


function vpy_panel_head($title, $extra_css = '') {
    $is_cyrl = vpy_lang_code() === 'uz_cyrillic';
    $css = vpy_panel_css() . vpy_panel_css2();
    echo '<!DOCTYPE html><html lang="' . ($is_cyrl ? 'uz-Cyrl' : 'uz') . '" data-theme="light"><head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">';
    echo '<meta name="theme-color" content="#1A5FB4">';
    echo '<meta name="robots" content="noindex">';
    echo '<title>' . e($title) . ' — ' . e(t('site_name')) . '</title>';
    echo '<link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">';
    echo '<link rel="manifest" href="/manifest.json">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">';
    echo '<style>' . $css . $extra_css . '</style>';
    // Inject panel background image if set
    $panel_bg = vpy_setting('panel_bg_image', '');
    if ($panel_bg) {
        echo '<style>:root{--panel-bg-img:url(' . e($panel_bg) . ')}</style>';
    }
    echo '<script>!function(){var t=localStorage.getItem("vpy_theme");if(t)document.documentElement.setAttribute("data-theme",t);else if(matchMedia("(prefers-color-scheme:dark)").matches)document.documentElement.setAttribute("data-theme","dark")}()</script>';
    echo '</head><body>';
    // Ctrl+U protection
    echo '<script>document.addEventListener("keydown",function(e){if((e.ctrlKey||e.metaKey)&&e.key==="u"){e.preventDefault();window.location="/ogoh.php"}});document.addEventListener("contextmenu",function(e){e.preventDefault();window.location="/ogoh.php"});</script>';
    // Layout wrapper ochiladi — sidebar + main grid
    echo '<div class="layout">';
}

function vpy_panel_sidebar($current, $is_admin = false) {
    $u = vpy_user();
    if (!$u) return;
    $items = $is_admin ? [
        ['/admin/', 'index', 'admin_dashboard', 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
        ['/admin/users.php', 'users', 'admin_users', 'M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2|circle:cx=8.5,cy=7,r=4|M20 8v6M23 11h-6'],
        ['/admin/savollar.php', 'savollar', 'admin_questions', 'M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3|M12 17h.01|circle:cx=12,cy=12,r=10'],
        ['/admin/biletlar.php', 'biletlar', 'admin_tickets', 'M21 8v13H3V8|M1 3h22v5H1z|M10 12h4'],
        ['/admin/tariflar.php', 'tariflar', 'admin_tariffs', 'M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z|circle:cx=7,cy=7,r=1.4'],
        ['/admin/tolovlar.php', 'tolovlar', 'admin_payments', 'M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6'],
        ['/admin/blog.php', 'blog', 'admin_blog', 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z|M14 2v6h6|M16 13H8M16 17H8'],
        ['/admin/sharhlar.php', 'sharhlar', 'admin_reviews', 'M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8z'],
        ['/admin/loglar.php', 'loglar', 'admin_logs', 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z|M14 2v6h6|M16 13H8|M10 9H8'],
        ['/admin/sozlamalar.php', 'sozlamalar', 'admin_settings', 'circle:cx=12,cy=12,r=3|M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z'],
        ['/admin/support.php', 'support', 'support_title', 'M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z']
    ] : [
        ['/user/', 'index', 'user_dashboard_title', 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
        ['/user/testlar20.php', 'testlar20', 'biletlar_20_title', 'M21 8v13H3V8|M1 3h22v5H1z|M10 12h4'],
        ['/user/testlar50.php', 'testlar50', 'biletlar_50_title', 'M4 5h16v2H4V5zm0 6h16v2H4v-2zm0 6h16v2H4v-2z'],
        ['/user/test.php?type=exam', 'exam', 'exam_title', 'M9 12l2 2 4-4|circle:cx=12,cy=12,r=10'],
        ['/user/xatolarim.php', 'xatolarim', 'xatolarim_title', 'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['/user/natijalar.php', 'natijalar', 'user_recent_tests', 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z|M14 2v6h6|M16 13H8M16 17H8'],
        ['/user/reyting.php', 'reyting', 'rating_title', 'M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2|circle:cx=8.5,cy=7,r=4|M20 8v6M23 11h-6'],
        ['/user/tariflar.php', 'tariflar', 'tariffs_title', 'M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z|circle:cx=7,cy=7,r=1.4'],
        ['/user/referallar.php', 'referallar', 'referral_title', 'circle:cx=9,cy=7,r=4|M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2|M16 3.13a4 4 0 010 7.75|M21 21v-2a4 4 0 00-3-3.87'],
        ['/user/profil.php', 'profil', 'profile_title', 'M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2|circle:cx=12,cy=7,r=4'],
        ['/user/support.php', 'support', 'support_title', 'M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z'],
    ];
    echo '<aside class="sidebar" id="sidebar">';
    $logo_url = vpy_setting('site_logo', '');
    if ($logo_url) {
        echo '<a href="/" class="s-brand"><img src="' . e($logo_url) . '" alt="VATANPARVAR" style="height:32px;width:auto;border-radius:6px"> VATANPARVAR</a>';
    } else {
        echo '<a href="/" class="s-brand"><span class="s-logo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l2-2 4 4 8-8 4 4"/></svg></span>VATANPARVAR</a>';
    }
    echo '<div class="s-section">' . e($is_admin ? t('admin_title') : t('nav_dashboard')) . '</div>';
    foreach ($items as $it) {
        list($url, $key, $label, $icon) = $it;
        $active = $current === $key ? 'active' : '';
        echo '<a href="' . e($url) . '" class="s-link ' . $active . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">';
        foreach (explode('|', $icon) as $part) {
            if (strpos($part, 'circle:') === 0) { $attrs = substr($part, 7); echo '<circle ' . str_replace(',', ' ', preg_replace('/(\w+)=/', '$1="', $attrs)) . '"/>'; }
            else echo '<path d="' . $part . '"/>';
        }
        echo '</svg><span>' . e(t($label)) . '</span></a>';
    }
    if (!$is_admin) {
        $unread = vpy_notify_unread_count($u['id']);
        if ($unread > 0) echo '<a href="/user/" class="s-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>Bildirishnomalar<span class="badge">' . $unread . '</span></a>';
    }
    if (!$is_admin) {
        $unread = vpy_notify_unread_count($u['id']);
        echo '<a href="/user/bildirishnomalar.php" class="s-link ' . ($current === 'bildirishnomalar' ? 'active' : '') . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg><span>Bildirishnomalar</span>' . ($unread ? '<span class="badge">' . $unread . '</span>' : '') . '</a>';
    }
    echo '<div class="s-foot"><button class="theme-toggle-s" id="themeToggleS" aria-label="Tema"></button>';
    echo '<a href="/" class="s-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>' . e(t('nav_home')) . '</a>';
    echo '</div></aside>';
}


function vpy_panel_topbar($title, $subtitle = '', $actions_html = '') {
    $u = vpy_user();
    $color = $u ? vpy_avatar_color($u['name']) : '#1A5FB4';
    echo '<div class="topbar">';
    echo '<div style="display:flex;align-items:center;gap:12px"><button class="burger-mobile" id="burgerMobile" aria-label="Menyu"><span></span></button><div class="tb-title">';
    echo '<h1>' . e($title) . '</h1>';
    if ($subtitle) echo '<p>' . e($subtitle) . '</p>';
    echo '</div></div><div class="tb-actions">' . $actions_html;
    if ($u) {
        echo '<div class="user-pop" id="userPop"><button class="user-btn"><span class="user-avatar" style="background:' . e($color) . '">' . e(vpy_user_initials($u['name'])) . '</span><span class="user-name">' . e($u['name']) . '</span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="6 9 12 15 18 9"/></svg></button>';
        echo '<div class="user-menu"><a href="' . (vpy_is_admin() ? '/admin/sozlamalar.php' : '/user/profil.php') . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' . e(t('profile_title')) . '</a>';
        echo '<a href="?lang=' . (vpy_lang_code() === 'uz_latin' ? 'uz_cyrillic' : 'uz_latin') . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/></svg>' . (vpy_lang_code() === 'uz_latin' ? 'Кирилл' : 'Lotin') . '</a><hr>';
        echo '<a href="/logout.php" class="danger"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>' . e(t('nav_logout')) . '</a></div></div>';
    }
    echo '</div></div>';
    foreach (vpy_flash_get() as $f) {
        $type = $f['type'] ?? 'info';
        $icon = $type === 'success' ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg>' : ($type === 'error' ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>' : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>');
        echo '<div class="flash ' . e($type) . '">' . $icon . '<span>' . e($f['msg']) . '</span></div>';
    }
}

function vpy_panel_foot() {
    echo '</div>'; // .layout wrapper yopiladi
    echo <<<'HTML'
<script>
(function(){
    var burger=document.getElementById('burgerMobile');
    var sidebar=document.getElementById('sidebar');
    if(burger&&sidebar){
        burger.addEventListener('click',function(e){e.stopPropagation();sidebar.classList.toggle('open');});
        document.addEventListener('click',function(e){if(sidebar.classList.contains('open')&&!sidebar.contains(e.target)&&e.target!==burger)sidebar.classList.remove('open');});
    }
    var userPop=document.getElementById('userPop');
    if(userPop){var ub=userPop.querySelector('.user-btn');ub.addEventListener('click',function(e){e.stopPropagation();userPop.classList.toggle('open');});document.addEventListener('click',function(){userPop.classList.remove('open');});}
    // Theme toggle in sidebar
    var ts=document.getElementById('themeToggleS');
    if(ts)ts.addEventListener('click',function(){var d=document.documentElement;var c=d.getAttribute('data-theme')==='dark'?'light':'dark';d.setAttribute('data-theme',c);localStorage.setItem('vpy_theme',c);});
})();
</script>
</body></html>
HTML;
}