<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/public_layout.php';

$lang = vpy_lang_code();
$is_cyrl = $lang === 'uz_cyrillic';
$tariffs = vpy_filter('tariflar', fn($t) => !empty($t['active']));
usort($tariffs, fn($a, $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));
$reviews = vpy_filter('sharhlar', fn($r) => ($r['status'] ?? '') === 'approved' && !empty($r['featured']));
$blog_posts = array_slice(vpy_filter('blog', fn($p) => ($p['status'] ?? '') === 'published'), 0, 3);

$stat_users = (int)vpy_setting('stat_users', '8420');
$stat_tests = (int)vpy_setting('stat_tests', '287000');
$stat_score = (float)vpy_setting('stat_score', '18.4');
$stat_success = (int)vpy_setting('stat_success', '96');

$hero_bg = vpy_setting('hero_bg_image', '');
$banners = array_filter([vpy_setting('banner_image_1',''),vpy_setting('banner_image_2',''),vpy_setting('banner_image_3','')]);
$ticker_texts = array_filter([vpy_setting('ticker_text_1',''),vpy_setting('ticker_text_2',''),vpy_setting('ticker_text_3',''),vpy_setting('ticker_text_4','')]);
$founder_active = vpy_setting('founder_active', '0') === '1';


$page_css = <<<CSS
/* HERO */
.hero{position:relative;padding:140px 0 80px;overflow:hidden}
.hero-bg{position:absolute;inset:0;z-index:0}
.hero-bg img{width:100%;height:100%;object-fit:cover;position:fixed;top:0;left:0}
.hero-bg::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(244,246,249,0.35) 0%,rgba(244,246,249,0.55) 100%)}
[data-theme="dark"] .hero-bg::after{background:linear-gradient(180deg,rgba(17,17,19,0.35) 0%,rgba(17,17,19,0.55) 100%)}
.hero-grid{display:grid;grid-template-columns:1fr 1fr;gap:50px;align-items:center;position:relative;z-index:2}
.hero-content{padding:28px 32px;background:var(--glass);backdrop-filter:blur(20px) saturate(160%);-webkit-backdrop-filter:blur(20px) saturate(160%);border:1px solid var(--border);border-radius:var(--r-lg)}
.hero-content h1{margin-top:18px}
.hero-sub{margin-top:20px;max-width:50ch}
.hero-cta{display:flex;flex-wrap:wrap;gap:12px;margin-top:30px}
.hero-trust{display:flex;gap:32px;margin-top:42px;padding-top:24px;border-top:1px solid var(--border)}
.trust-item{display:flex;flex-direction:column;gap:2px}
.trust-num{font-family:var(--serif);font-size:clamp(1.4rem,2.2vw,1.9rem);font-weight:700;color:var(--primary);line-height:1}
.trust-label{font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em}
/* HERO MOCKUP */
.hero-mockup{position:relative;perspective:1200px;display:flex;align-items:center;justify-content:center}
.mockup-stack{transform-style:preserve-3d;transform:rotateY(-8deg) rotateX(4deg) rotate(-1deg);transition:transform 0.6s ease}
.mockup-card{background:var(--glass-strong);backdrop-filter:blur(24px) saturate(180%);-webkit-backdrop-filter:blur(24px) saturate(180%);border:1px solid var(--border-strong);border-radius:var(--r-lg);padding:28px;box-shadow:var(--shadow-lg);max-width:400px;width:100%;position:relative}
.mockup-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.mockup-dots{display:flex;gap:5px}.mockup-dots span{width:8px;height:8px;border-radius:50%;background:var(--border-strong)}
.mockup-tab{font-size:0.75rem;font-weight:600;color:var(--muted);padding:4px 10px;background:var(--blue-soft);border-radius:var(--pill)}
.mockup-meta{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.mockup-q-num{font-size:0.78rem;font-weight:600;color:var(--dark-soft)}
.mockup-timer{display:flex;align-items:center;gap:5px;font-size:0.78rem;font-weight:600;color:var(--primary)}
.timer-dot{width:6px;height:6px;border-radius:50%;background:var(--primary);animation:vp 2s infinite}
.mockup-progress{height:4px;border-radius:2px;background:var(--border);margin-bottom:18px;position:relative;overflow:hidden}
.mockup-progress::after{content:"";position:absolute;left:0;top:0;height:100%;width:65%;background:linear-gradient(90deg,var(--primary),var(--primary-light));border-radius:2px}
.mockup-question{font-family:var(--serif);font-size:1.05rem;font-weight:600;margin-bottom:14px;line-height:1.4}
.mockup-answers{display:flex;flex-direction:column;gap:8px}
.mockup-answer{display:flex;align-items:center;gap:10px;padding:11px 14px;background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm);font-size:0.88rem;transition:var(--t)}
.mockup-answer.correct{background:rgba(26,95,180,0.08);border-color:var(--primary);color:var(--primary);font-weight:600}
.mockup-answer .letter{width:28px;height:28px;border-radius:8px;background:var(--blue-soft);color:var(--primary);display:grid;place-items:center;font-weight:700;font-size:0.78rem;flex-shrink:0}
.mockup-answer.correct .letter{background:var(--primary);color:#fff}
.mockup-foot{margin-top:18px;display:flex;justify-content:space-between;align-items:center}
.mockup-stat{display:flex;gap:16px;font-size:0.78rem;color:var(--muted)}
.mockup-stat strong{color:var(--primary);font-weight:700}
.mockup-next{padding:8px 14px;background:var(--dark);color:#fff;border-radius:var(--pill);font-size:0.78rem;font-weight:600;display:flex;align-items:center;gap:5px}
/* FLOATING CARDS */
.mockup-floater{position:absolute;background:var(--glass-strong);backdrop-filter:blur(20px);border:1px solid var(--border-strong);border-radius:var(--r-sm);padding:12px 16px;box-shadow:0 15px 35px rgba(26,95,180,0.12);display:flex;align-items:center;gap:10px;z-index:5;animation:floatBadge 6s ease-in-out infinite}
.mockup-floater.f1{top:-20px;left:-30px;animation-delay:0.5s}
.mockup-floater.f2{bottom:50px;right:-40px;animation-delay:1.2s}
.mockup-floater.f3{top:45%;right:-30px;animation-delay:0.8s}
@keyframes floatBadge{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.floater-icon{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;color:#fff;flex-shrink:0}
.floater-icon.blue{background:linear-gradient(135deg,var(--primary),var(--primary-dark));box-shadow:0 6px 16px var(--primary-glow)}
.floater-icon.accent{background:linear-gradient(135deg,var(--accent),#1976B5);box-shadow:0 6px 16px rgba(33,133,208,0.3)}
.floater-icon.dark{background:var(--dark);box-shadow:0 6px 16px rgba(26,29,35,0.2)}
.floater-icon svg{width:16px;height:16px}
.floater-text{display:flex;flex-direction:column;gap:1px}
.floater-num{font-family:var(--serif);font-size:1rem;font-weight:700;color:var(--dark);line-height:1}
.floater-label{font-size:0.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.04em}
/* BANNER */
.banner-section{display:none;padding:0;margin-top:-30px;position:relative;z-index:2}
@media (min-width:769px){.banner-section{display:block}}
.banner-slider{position:relative;border-radius:var(--r-lg);overflow:hidden;height:320px;box-shadow:var(--shadow-lg)}
.banner-slide{position:absolute;inset:0;opacity:0;transition:opacity 1.2s ease}
.banner-slide.active{opacity:1}
.banner-slide img{width:100%;height:100%;object-fit:cover}
/* FEATURES BENTO */
.bento{display:grid;grid-template-columns:repeat(6,1fr);grid-auto-rows:minmax(180px,auto);gap:20px;margin-top:56px}
.bento-cell{position:relative;padding:30px;border-radius:var(--r-lg);overflow:hidden;transition:transform var(--t),box-shadow var(--t);cursor:default;background:var(--glass);backdrop-filter:blur(20px) saturate(160%);-webkit-backdrop-filter:blur(20px) saturate(160%);border:1px solid var(--border-strong)}
.bento-cell:hover{transform:translateY(-5px);box-shadow:var(--shadow-lg)}
.b-c1{grid-column:span 3;grid-row:span 2}
.b-c2{grid-column:span 3;grid-row:span 1}
.b-c3{grid-column:span 2;grid-row:span 1}
.b-c4{grid-column:span 4;grid-row:span 2}
.b-c5{grid-column:span 2;grid-row:span 1}
.b-c6{grid-column:span 6;grid-row:span 1;min-height:auto}
.bento-cell.dark{background:rgba(17,19,24,0.75);backdrop-filter:blur(24px) saturate(160%);-webkit-backdrop-filter:blur(24px) saturate(160%);color:var(--light);border:1px solid rgba(255,255,255,0.08)}
.bento-cell.dark::before{content:"";position:absolute;top:-50%;right:-30%;width:80%;height:160%;background:radial-gradient(ellipse,rgba(61,125,212,0.15),transparent 60%);z-index:-1;pointer-events:none}
.bento-cell.dark .h-card,.bento-cell.dark p{color:var(--light)}
.bento-cell.blue{background:rgba(20,86,168,0.75);backdrop-filter:blur(24px) saturate(160%);-webkit-backdrop-filter:blur(24px) saturate(160%);color:#fff}
.bento-cell.blue .h-card,.bento-cell.blue p{color:#fff}
.feat-icon{width:50px;height:50px;border-radius:14px;display:grid;place-items:center;margin-bottom:18px;background:var(--blue-soft);color:var(--primary);transition:transform 0.4s cubic-bezier(0.34,1.56,0.64,1)}
.bento-cell:hover .feat-icon{transform:rotate(-8deg) scale(1.08)}
.bento-cell.dark .feat-icon{background:rgba(61,125,212,0.2);color:var(--primary-light)}
.bento-cell.blue .feat-icon{background:rgba(255,255,255,0.15);color:#fff}
.feat-icon svg{width:24px;height:24px}
.bento-cell h3{margin-bottom:8px}
.bento-cell p{font-size:0.9rem;line-height:1.55;color:var(--dark-soft)}
/* DEMO */
.demo-frame{position:relative;margin-top:56px;border-radius:var(--r-xl);background:var(--glass);backdrop-filter:blur(24px) saturate(160%);-webkit-backdrop-filter:blur(24px) saturate(160%);border:1px solid var(--border-strong);padding:36px;box-shadow:var(--shadow-lg);overflow:hidden}
.demo-frame::before{content:"";position:absolute;inset:0;background:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='60' height='60'><path d='M0 0h60v1H0zM0 0v60h1V0z' fill='%231A5FB4' opacity='0.04'/></svg>");pointer-events:none}
.demo-grid{display:grid;grid-template-columns:1.2fr 1fr;gap:36px;position:relative;z-index:2}
.demo-chip{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;background:var(--glass);border:1px solid var(--border);border-radius:var(--pill);font-size:0.78rem;font-weight:600;margin-bottom:8px;color:var(--dark-soft)}
.demo-chip-dot{width:6px;height:6px;border-radius:50%;background:var(--primary);animation:vp 2s infinite}
.demo-chip-dot.accent{background:var(--accent)}
.demo-chip-dot.dark{background:var(--dark)}
.demo-stat{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;background:var(--glass);border:1px solid var(--border);border-radius:var(--r-sm);margin-top:12px}
.demo-stat-label{font-size:0.72rem;text-transform:uppercase;color:var(--muted);letter-spacing:0.06em}
.demo-stat-value{font-family:var(--serif);font-size:1.2rem;font-weight:700;color:var(--dark);margin-top:3px}
/* STATS */
.stats-wrap{background:linear-gradient(135deg,var(--dark) 0%,#1E2535 100%);border-radius:var(--r-xl);padding:64px 50px;color:#fff;position:relative;overflow:hidden}
[data-theme="dark"] .stats-wrap{background:linear-gradient(135deg,#080B12 0%,#0E1320 100%)}
.stats-wrap::before{content:"";position:absolute;top:-40%;right:-15%;width:50%;height:180%;background:radial-gradient(ellipse,rgba(26,95,180,0.2),transparent 60%);pointer-events:none}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;position:relative;z-index:2}
.stat-cell{text-align:center}
.stat-num{font-family:var(--serif);font-size:clamp(2rem,3.5vw,3.2rem);font-weight:700;line-height:1;color:var(--primary-light)}
.stat-suffix{font-family:var(--serif);font-size:clamp(1.4rem,2vw,2rem);font-weight:700;color:var(--primary-light)}
.stat-bar{width:40px;height:3px;border-radius:2px;background:rgba(61,125,212,0.3);margin:12px auto}
.stat-label{font-size:0.78rem;color:rgba(240,244,255,0.55);text-transform:uppercase;letter-spacing:0.06em}
/* TARIFFS */
.tariffs-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin-top:48px}
.tariff-card{padding:34px 28px;border-radius:var(--r-lg);background:var(--glass);backdrop-filter:blur(20px) saturate(160%);-webkit-backdrop-filter:blur(20px) saturate(160%);border:1px solid var(--border-strong);display:flex;flex-direction:column;transition:transform var(--t),box-shadow var(--t)}
.tariff-card:hover{transform:translateY(-5px);box-shadow:var(--shadow-lg)}
.tariff-card.featured{border:2px solid var(--primary);box-shadow:0 12px 40px var(--primary-glow);background:var(--glass-strong)}
.tariff-badge{display:inline-block;padding:4px 12px;background:var(--primary);color:#fff;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;border-radius:var(--pill);margin-bottom:12px;align-self:flex-start}
.tariff-name{font-family:var(--serif);font-size:1.4rem;font-weight:700;margin-bottom:4px}
.tariff-desc{font-size:0.85rem;color:var(--muted);margin-bottom:20px}
.tariff-price-row{display:flex;align-items:baseline;gap:6px}
.tariff-old{font-size:0.88rem;color:var(--muted);text-decoration:line-through}
.tariff-price{font-family:var(--serif);font-size:2.2rem;font-weight:700;color:var(--primary);line-height:1}
.tariff-period{font-size:0.82rem;color:var(--muted);margin-bottom:22px;margin-top:4px}
.tariff-features{flex:1;display:flex;flex-direction:column;gap:10px;margin-bottom:22px;padding-top:18px;border-top:1px dashed var(--border-strong)}
.tariff-features li{display:flex;align-items:flex-start;gap:8px;font-size:0.85rem;color:var(--dark-soft);line-height:1.4}
.tariff-features svg{width:15px;height:15px;color:var(--primary);flex-shrink:0;margin-top:2px}
.tariff-card .btn{width:100%}
/* REVIEWS */
.reviews-track-wrap{overflow:hidden;padding:20px 0;margin-top:40px}
.reviews-track{display:flex;gap:20px;width:max-content;animation:reviewScroll 40s linear infinite}
.reviews-track:hover{animation-play-state:paused}
@keyframes reviewScroll{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
.review-card{flex:0 0 340px;padding:28px;background:var(--glass);backdrop-filter:blur(20px) saturate(160%);-webkit-backdrop-filter:blur(20px) saturate(160%);border:1px solid var(--border-strong);border-radius:var(--r-lg);transition:transform var(--t)}
.review-card:hover{transform:translateY(-3px)}
.review-stars{display:flex;gap:2px;margin-bottom:14px;color:var(--primary)}
.review-stars svg{width:15px;height:15px}
.review-text{font-size:0.9rem;line-height:1.6;color:var(--dark-soft);margin-bottom:16px;min-height:60px}
.review-author{display:flex;align-items:center;gap:10px}
.review-avatar{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:700;font-size:0.72rem;flex-shrink:0}
.review-name{font-weight:600;font-size:0.88rem;display:block}
.review-city{font-size:0.75rem;color:var(--muted)}
/* FAQ */
.faq-grid{display:grid;grid-template-columns:1fr 1.3fr;gap:48px;align-items:start}
.faq-side{padding:28px;background:var(--glass);backdrop-filter:blur(20px) saturate(160%);-webkit-backdrop-filter:blur(20px) saturate(160%);border:1px solid var(--border);border-radius:var(--r-lg)}
.faq-side-deco{margin-top:28px;padding:16px;background:var(--blue-soft);border:1px solid var(--border);border-radius:var(--r-sm);display:flex;align-items:center;gap:12px}
.faq-side-icon{width:40px;height:40px;border-radius:10px;background:var(--primary);color:#fff;display:grid;place-items:center}
.faq-side-icon svg{width:18px;height:18px}
.faq-side-deco-text{display:flex;flex-direction:column;gap:2px;font-size:0.85rem}
.faq-side-deco-text strong{color:var(--dark)}
.faq-side-deco-text span{color:var(--muted)}
.faq-item{border:1px solid var(--border);border-radius:var(--r-sm);overflow:hidden;margin-bottom:8px;transition:var(--t)}
.faq-item:hover,.faq-item.open{border-color:var(--border-strong)}
.faq-q{width:100%;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:0.92rem;font-weight:600;color:var(--dark);text-align:left;cursor:pointer;background:var(--glass);transition:background var(--t)}
.faq-q:hover{background:var(--blue-soft)}
.faq-toggle{width:24px;height:24px;border-radius:6px;background:var(--blue-soft);display:grid;place-items:center;color:var(--primary);flex-shrink:0;transition:transform 0.35s ease}
.faq-toggle svg{width:14px;height:14px}
.faq-item.open .faq-toggle{transform:rotate(45deg)}
.faq-a{max-height:0;overflow:hidden;transition:max-height 0.4s ease}
.faq-item.open .faq-a{max-height:300px}
.faq-a-inner{padding:0 20px 16px;font-size:0.88rem;line-height:1.65;color:var(--muted)}
/* BLOG */
.blog-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin-top:40px}
.blog-card{border-radius:var(--r-lg);overflow:hidden;background:var(--glass);backdrop-filter:blur(20px) saturate(160%);-webkit-backdrop-filter:blur(20px) saturate(160%);border:1px solid var(--border-strong);transition:transform var(--t),box-shadow var(--t)}
.blog-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg)}
.blog-card-img{height:180px;overflow:hidden;background:var(--blue-soft)}
.blog-card-img img{width:100%;height:100%;object-fit:cover;transition:transform 0.6s ease}
.blog-card:hover img{transform:scale(1.05)}
.blog-card-body{padding:22px}
.blog-card-date{font-size:0.72rem;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.04em}
.blog-card-title{font-family:var(--serif);font-size:1.05rem;font-weight:600;line-height:1.3;margin-bottom:8px;color:var(--dark)}
.blog-card-text{font-size:0.85rem;color:var(--muted);line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
/* FOUNDER */
.founder-card{display:grid;grid-template-columns:auto 1fr;gap:40px;align-items:center;padding:48px;background:var(--glass);backdrop-filter:blur(24px) saturate(160%);-webkit-backdrop-filter:blur(24px) saturate(160%);border:1px solid var(--border-strong);border-radius:var(--r-xl);box-shadow:var(--shadow)}
.founder-img{width:180px;height:180px;border-radius:50%;object-fit:cover;border:4px solid var(--primary);box-shadow:0 8px 32px var(--primary-glow)}
.founder-info h3{font-family:var(--serif);font-size:1.5rem;font-weight:700;margin-bottom:4px}
.founder-info .title{font-size:0.88rem;color:var(--primary);font-weight:600;margin-bottom:14px}
.founder-info p{font-size:0.9rem;color:var(--muted);line-height:1.7}
/* CTA */
.cta-wrap{position:relative;padding:68px 48px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:var(--r-xl);color:#fff;box-shadow:0 20px 60px var(--primary-glow);overflow:hidden;text-align:center}
.cta-wrap::before{content:"";position:absolute;top:-50%;right:-20%;width:60%;height:200%;background:radial-gradient(ellipse,rgba(255,255,255,0.08),transparent 60%);pointer-events:none}
.cta-wrap .h-section em{color:#fff}
.cta-wrap .lead{color:rgba(255,255,255,0.8)}
.cta-note{margin-top:18px;display:inline-flex;align-items:center;gap:6px;font-size:0.82rem;color:rgba(255,255,255,0.65)}
.cta-note svg{width:14px;height:14px}
/* RESPONSIVE */
@media (max-width:1024px){.hero-grid{grid-template-columns:1fr}.hero-mockup{display:none}.bento{grid-template-columns:repeat(2,1fr)}.b-c1,.b-c2,.b-c3,.b-c4,.b-c5,.b-c6{grid-column:span 2;grid-row:span 1}.b-c6{grid-column:span 2}.demo-grid{grid-template-columns:1fr}.stats-grid{grid-template-columns:repeat(2,1fr)}.tariffs-grid{grid-template-columns:1fr;max-width:450px;margin-left:auto;margin-right:auto}.faq-grid{grid-template-columns:1fr}.blog-grid{grid-template-columns:1fr}.founder-card{grid-template-columns:1fr;text-align:center;gap:24px}.founder-img{margin:0 auto}.hero-content{padding:24px}}
@media (max-width:768px){.hero{padding:120px 0 60px}.hero-trust{flex-wrap:wrap;gap:18px}.bento{grid-template-columns:1fr}.b-c1,.b-c2,.b-c3,.b-c4,.b-c5,.b-c6{grid-column:span 1}.stats-wrap{padding:48px 24px}.stats-grid{grid-template-columns:1fr;gap:20px}.cta-wrap{padding:48px 24px}.review-card{flex-basis:280px;padding:24px}.hero-content{padding:20px 16px}}
CSS;

vpy_public_head(t('site_name') . ' — ' . t('site_tagline'), vpy_setting('site_description'), $page_css);
vpy_public_navbar('home');


?>
<main>
<!-- TICKER STRIP -->
<?php if (!empty($ticker_texts)): ?>
<div class="ticker-wrap" style="margin-top:84px">
    <div class="ticker-inner">
        <?php for ($rep = 0; $rep < 3; $rep++): foreach ($ticker_texts as $txt): ?>
        <span class="ticker-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg><?= e($txt) ?></span>
        <?php endforeach; endfor; ?>
    </div>
</div>
<?php endif; ?>

<!-- HERO -->
<section class="hero" id="hero">
    <?php if ($hero_bg): ?><div class="hero-bg"><img src="<?= e($hero_bg) ?>" alt=""></div><?php endif; ?>
    <div class="container">
        <div class="hero-grid">
            <div class="hero-content reveal">
                <span class="eyebrow"><?= e(t('hero_badge')) ?></span>
                <h1 class="h-display" style="margin-top:18px"><em><?= e(t('hero_title_2')) ?></em><br><?= e(t('hero_title_1')) ?></h1>
                <p class="lead hero-sub"><?= e(t('hero_subtitle')) ?></p>
                <div class="hero-cta">
                    <a href="/register.php" class="btn btn-primary"><?= e(t('hero_cta_primary')) ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                    <a href="#demo" class="btn btn-ghost"><svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M8 5v14l11-7z"/></svg><?= e(t('hero_cta_secondary')) ?></a>
                </div>
                <div class="hero-trust">
                    <div class="trust-item"><span class="trust-num" data-counter="<?= $stat_users ?>">0</span><span class="trust-label"><?= e(t('hero_trust_users')) ?></span></div>
                    <div class="trust-item"><span class="trust-num" data-counter="<?= $stat_success ?>" data-suffix="%">0</span><span class="trust-label"><?= e(t('hero_trust_pass')) ?></span></div>
                    <div class="trust-item"><span class="trust-num" data-counter="4000" data-suffix="+">0</span><span class="trust-label"><?= e(t('hero_trust_questions')) ?></span></div>
                </div>
            </div>
            <div class="hero-mockup reveal r2">
                <div class="mockup-stack" id="mockupStack">
                    <div class="mockup-card">
                        <div class="mockup-head"><div class="mockup-dots"><span></span><span></span><span></span></div><div class="mockup-tab"><?= e(t('mockup_title')) ?></div></div>
                        <div class="mockup-meta"><div class="mockup-q-num"><?= e(t('mockup_question_label')) ?> 13 / 20</div><div class="mockup-timer"><span class="timer-dot"></span>14:32</div></div>
                        <div class="mockup-progress"></div>
                        <div class="mockup-question"><?= e(t('mockup_question_text')) ?></div>
                        <div class="mockup-answers">
                            <div class="mockup-answer"><span class="letter">A</span><?= e(t('mockup_answer_a')) ?></div>
                            <div class="mockup-answer"><span class="letter">B</span><?= e(t('mockup_answer_b')) ?></div>
                            <div class="mockup-answer"><span class="letter">C</span><?= e(t('mockup_answer_c')) ?></div>
                            <div class="mockup-answer correct"><span class="letter">D</span><?= e(t('mockup_answer_d')) ?></div>
                        </div>
                        <div class="mockup-foot">
                            <div class="mockup-stat"><span><strong>12</strong> <?= e(t('count_correct')) ?></span><span><strong>1</strong> <?= e(t('count_wrong')) ?></span></div>
                            <span class="mockup-next"><?= e(t('btn_next')) ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" style="width:12px;height:12px"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                        </div>
                    </div>
                    <!-- Floating mini-cards -->
                    <div class="mockup-floater f1">
                        <div class="floater-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
                        <div class="floater-text"><span class="floater-num"><?= $stat_success ?>%</span><span class="floater-label"><?= e(t('hero_trust_pass')) ?></span></div>
                    </div>
                    <div class="mockup-floater f2">
                        <div class="floater-icon accent"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div>
                        <div class="floater-text"><span class="floater-num">25:00</span><span class="floater-label"><?= e(t('test_duration_label')) ?></span></div>
                    </div>
                    <div class="mockup-floater f3">
                        <div class="floater-icon dark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12"/></svg></div>
                        <div class="floater-text"><span class="floater-num">4000+</span><span class="floater-label"><?= e(t('hero_trust_questions')) ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- BANNER (desktop only) -->
<?php if (!empty($banners)): ?>
<section class="banner-section">
    <div class="container">
        <div class="banner-slider">
            <?php foreach ($banners as $i => $b): ?>
            <div class="banner-slide <?= $i===0?'active':'' ?>"><img src="<?= e($b) ?>" alt="Banner"></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- FEATURES BENTO GRID -->
<section id="features">
    <div class="container">
        <div class="section-head" style="text-align:center;max-width:700px;margin:0 auto">
            <span class="eyebrow reveal"><?= e(t('nav_about')) ?></span>
            <h2 class="h-section reveal r1" style="margin-top:16px"><?= e(t('platform_title')) ?></h2>
            <p class="lead reveal r2" style="margin:12px auto 0;text-align:center"><?= e(t('platform_subtitle')) ?></p>
        </div>
        <div class="bento">
            <div class="bento-cell b-c1 reveal">
                <div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/><circle cx="12" cy="12" r="10"/></svg></div>
                <h3 class="h-card"><?= e(t('feat_1_title')) ?></h3>
                <p><?= e(t('feat_1_desc')) ?></p>
            </div>
            <div class="bento-cell dark b-c2 reveal r1">
                <div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg></div>
                <h3 class="h-card"><?= e(t('feat_2_title')) ?></h3>
                <p><?= e(t('feat_2_desc')) ?></p>
            </div>
            <div class="bento-cell b-c3 reveal r2">
                <div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12a9 9 0 019-9 9.75 9.75 0 016.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg></div>
                <h3 class="h-card"><?= e(t('feat_3_title')) ?></h3>
                <p><?= e(t('feat_3_desc')) ?></p>
            </div>
            <div class="bento-cell blue b-c4 reveal r3">
                <div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6"/><path d="M23 11h-6"/></svg></div>
                <h3 class="h-card"><?= e(t('feat_4_title')) ?></h3>
                <p><?= e(t('feat_4_desc')) ?></p>
            </div>
            <div class="bento-cell b-c5 reveal r4">
                <div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg></div>
                <h3 class="h-card"><?= e(t('feat_5_title')) ?></h3>
                <p><?= e(t('feat_5_desc')) ?></p>
            </div>
            <div class="bento-cell b-c6 reveal" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px">
                <div style="display:flex;align-items:center;gap:20px">
                    <div class="feat-icon" style="margin-bottom:0"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="7"/><path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12"/></svg></div>
                    <div><h3 class="h-card" style="margin:0"><?= e(t('feat_6_title')) ?></h3><p style="margin-top:4px"><?= e(t('feat_6_desc')) ?></p></div>
                </div>
                <a href="/register.php" class="btn btn-dark"><?= e(t('btn_start')) ?></a>
            </div>
        </div>
    </div>
</section>


<!-- DEMO SHOWCASE -->
<section id="demo">
    <div class="container">
        <div class="section-head" style="text-align:center;max-width:700px;margin:0 auto">
            <span class="eyebrow reveal"><?= e(t('demo_chip_realtime')) ?></span>
            <h2 class="h-section reveal r1" style="margin-top:16px"><?= e(t('demo_title')) ?></h2>
            <p class="lead reveal r2" style="margin:12px auto 0;text-align:center"><?= e(t('demo_subtitle')) ?></p>
        </div>
        <div class="demo-frame reveal">
            <div class="demo-grid">
                <div>
                    <div class="mockup-head"><div class="mockup-dots"><span></span><span></span><span></span></div><div class="mockup-tab"><?= e(t('ticket_label')) ?> #08 — 14/20</div></div>
                    <div class="mockup-meta" style="margin-top:20px"><div class="mockup-q-num"><?= e(t('mockup_question_label')) ?> 14</div><div class="mockup-timer"><span class="timer-dot"></span>18:42</div></div>
                    <div class="mockup-progress" style="--p:70%"></div>
                    <div class="mockup-question">Yo'lda regulyator qo'lini yon tomonlarga uzatgan paytda harakatga ruxsat etiladimi?</div>
                    <div class="mockup-answers">
                        <div class="mockup-answer"><span class="letter">A</span>Faqat to'g'ri yo'nalishda</div>
                        <div class="mockup-answer correct"><span class="letter">B</span>Yon tomonlardan to'g'ri va o'ngga</div>
                        <div class="mockup-answer"><span class="letter">C</span>Hech qanday harakat ruxsat etilmaydi</div>
                        <div class="mockup-answer"><span class="letter">D</span>Faqat orqadan</div>
                    </div>
                    <div style="margin-top:16px;padding:12px 16px;background:var(--blue-soft);border-left:3px solid var(--primary);border-radius:10px;font-size:0.85rem;color:var(--dark-soft);line-height:1.5">
                        <strong style="color:var(--primary)"><?= e(t('test_explain')) ?>:</strong> Regulyator qo'llari yon tomonlarga uzatilganda yon tomondagi haydovchilar to'g'ri va o'ng tomonga harakat qilishlari mumkin.
                    </div>
                </div>
                <div>
                    <div class="demo-chip"><span class="demo-chip-dot"></span><?= e(t('demo_chip_realtime')) ?></div>
                    <div class="demo-chip"><span class="demo-chip-dot accent"></span><?= e(t('demo_chip_official')) ?></div>
                    <div class="demo-chip"><span class="demo-chip-dot dark"></span><?= e(t('demo_chip_smart')) ?></div>
                    <div class="demo-stat"><div><div class="demo-stat-label"><?= e(t('demo_label_timer')) ?></div><div class="demo-stat-value">25:00</div></div><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div>
                    <div class="demo-stat"><div><div class="demo-stat-label"><?= e(t('demo_label_progress')) ?></div><div class="demo-stat-value">14 / 20</div></div><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg></div>
                    <div class="demo-stat"><div><div class="demo-stat-label"><?= e(t('demo_label_score')) ?></div><div class="demo-stat-value">13 / 14</div></div><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--dark)" stroke-width="1.5"><circle cx="12" cy="8" r="7"/><path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12"/></svg></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STATISTICS -->
<section>
    <div class="container">
        <div class="stats-wrap reveal">
            <div style="text-align:center;margin-bottom:40px;position:relative;z-index:2">
                <span class="eyebrow" style="background:rgba(255,255,255,0.08);border-color:rgba(255,255,255,0.15);color:var(--primary-light)"><?= e(t('stats_title')) ?></span>
                <h2 class="h-section" style="margin-top:16px;color:#fff"><?= e(t('stats_title')) ?></h2>
                <p class="lead" style="margin:10px auto 0;color:rgba(240,244,255,0.6);text-align:center"><?= e(t('stats_subtitle')) ?></p>
            </div>
            <div class="stats-grid">
                <div class="stat-cell reveal r1"><div><span class="stat-num" data-counter="<?= $stat_users ?>">0</span><span class="stat-suffix">+</span></div><div class="stat-bar"></div><div class="stat-label"><?= e(t('stats_users')) ?></div></div>
                <div class="stat-cell reveal r2"><div><span class="stat-num" data-counter="<?= $stat_tests ?>">0</span></div><div class="stat-bar"></div><div class="stat-label"><?= e(t('stats_tests')) ?></div></div>
                <div class="stat-cell reveal r3"><div><span class="stat-num" data-counter="<?= (int)round($stat_score) ?>" data-decimal="<?= $stat_score ?>">0</span></div><div class="stat-bar"></div><div class="stat-label"><?= e(t('stats_score')) ?> / 20</div></div>
                <div class="stat-cell reveal r4"><div><span class="stat-num" data-counter="<?= $stat_success ?>">0</span><span class="stat-suffix">%</span></div><div class="stat-bar"></div><div class="stat-label"><?= e(t('stats_success')) ?></div></div>
            </div>
        </div>
    </div>
</section>


<!-- TARIFFS -->
<section id="tariffs">
    <div class="container">
        <div class="section-head" style="text-align:center;max-width:700px;margin:0 auto">
            <span class="eyebrow reveal"><?= e(t('nav_tariffs')) ?></span>
            <h2 class="h-section reveal r1" style="margin-top:16px"><?= e(t('tariffs_title')) ?></h2>
            <p class="lead reveal r2" style="margin:12px auto 0;text-align:center"><?= e(t('tariffs_subtitle')) ?></p>
        </div>
        <div class="tariffs-grid">
            <?php foreach ($tariffs as $i => $tf):
                $features = $is_cyrl ? ($tf['features_cyrl'] ?? $tf['features']) : $tf['features'];
                $name = $is_cyrl ? ($tf['name_cyrl'] ?? $tf['name']) : $tf['name'];
                $desc = $is_cyrl ? ($tf['description_cyrl'] ?? $tf['description']) : $tf['description'];
                $period = $is_cyrl ? ($tf['period_label_cyrl'] ?? $tf['period_label']) : $tf['period_label'];
                $featured = !empty($tf['highlight']) || !empty($tf['popular']);
            ?>
            <div class="tariff-card reveal r<?= $i + 1 ?> <?= $featured ? 'featured' : '' ?>">
                <?php if (!empty($tf['popular'])): ?><div class="tariff-badge"><?= e(t('tariffs_badge_popular')) ?></div><?php endif; ?>
                <h3 class="tariff-name"><?= e($name) ?></h3>
                <p class="tariff-desc"><?= e($desc) ?></p>
                <?php if (!empty($tf['old_price'])): ?><div class="tariff-price-row"><span class="tariff-old"><?= number_format((float)$tf['old_price'], 0, '.', ' ') ?></span></div><?php endif; ?>
                <div class="tariff-price-row"><span class="tariff-price"><?= number_format((float)$tf['price'], 0, '.', ' ') ?></span><span class="muted" style="font-size:1rem;font-weight:500"><?= e(t('valyuta_sum')) ?></span></div>
                <div class="tariff-period"><?= e($period) ?><?php if (!empty($tf['price_per_day'])): ?> · <?= number_format((float)$tf['price_per_day'],0,'.',' ') ?> so'm/kun<?php endif; ?></div>
                <ul class="tariff-features">
                    <?php foreach ((array)$features as $f): ?>
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg><span><?= e($f) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= vpy_is_logged() ? '/user/tariflar.php?tarif=' : '/register.php?tarif=' ?><?= (int)$tf['id'] ?>" class="btn <?= $featured ? 'btn-primary' : 'btn-dark' ?>"><?= e(t('tariffs_buy')) ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- REVIEWS -->
<section>
    <div class="container">
        <div class="section-head" style="text-align:center;max-width:700px;margin:0 auto">
            <span class="eyebrow reveal"><?= e(t('reviews_title')) ?></span>
            <h2 class="h-section reveal r1" style="margin-top:16px"><?= e(t('reviews_title')) ?></h2>
            <p class="lead reveal r2" style="margin:12px auto 0;text-align:center"><?= e(t('reviews_subtitle')) ?></p>
        </div>
    </div>
    <div class="reviews-track-wrap reveal">
        <div class="reviews-track">
            <?php
            $review_pool = $reviews;
            if (count($review_pool) < 4) $review_pool = array_merge(vpy_filter('sharhlar', fn($r) => ($r['status'] ?? '') === 'approved'), $review_pool);
            $loop = array_merge($review_pool, $review_pool);
            foreach ($loop as $r):
                $text = $is_cyrl && !empty($r['text_cyrl']) ? $r['text_cyrl'] : $r['text'];
                $color = vpy_avatar_color($r['name']);
            ?>
            <article class="review-card">
                <div class="review-stars"><?php for ($s = 0; $s < (int)$r['rating']; $s++): ?><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><?php endfor; ?></div>
                <p class="review-text">"<?= e($text) ?>"</p>
                <div class="review-author">
                    <div class="review-avatar" style="background:<?= e($color) ?>"><?= e(vpy_user_initials($r['name'])) ?></div>
                    <div><span class="review-name"><?= e($r['name']) ?></span><span class="review-city"><?= e($r['city'] ?? t('site_city')) ?></span></div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- FAQ -->
<section id="faq">
    <div class="container">
        <div class="faq-grid">
            <div class="faq-side reveal">
                <span class="eyebrow"><?= e(t('faq_title')) ?></span>
                <h2 class="h-section" style="margin-top:16px"><?= e(t('faq_title')) ?></h2>
                <p class="lead" style="margin-top:14px"><?= e(t('faq_subtitle')) ?></p>
                <div class="faq-side-deco">
                    <span class="faq-side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></span>
                    <div class="faq-side-deco-text"><strong><?= e(t('contact_title')) ?></strong><span><?= e(vpy_setting('contact_phone', t('footer_phone_value'))) ?></span></div>
                </div>
            </div>
            <div class="faq-list">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                <div class="faq-item reveal r<?= min($i, 4) ?>">
                    <button class="faq-q"><span><?= e(t('faq_' . $i . '_q')) ?></span><span class="faq-toggle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span></button>
                    <div class="faq-a"><div class="faq-a-inner"><?= e(t('faq_' . $i . '_a')) ?></div></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</section>

<!-- BLOG -->
<?php if (!empty($blog_posts)): ?>
<section>
    <div class="container">
        <div class="section-head" style="text-align:center;max-width:700px;margin:0 auto">
            <span class="eyebrow reveal"><?= e(t('nav_blog')) ?></span>
            <h2 class="h-section reveal r1" style="margin-top:16px"><?= e(t('blog_title')) ?></h2>
        </div>
        <div class="blog-grid">
            <?php foreach ($blog_posts as $bp):
                $btitle = $is_cyrl && !empty($bp['title_cyrl']) ? $bp['title_cyrl'] : ($bp['title'] ?? '');
                $btext = $is_cyrl && !empty($bp['excerpt_cyrl']) ? $bp['excerpt_cyrl'] : ($bp['excerpt'] ?? '');
            ?>
            <a href="/blog.php?id=<?= (int)($bp['id'] ?? 0) ?>" class="blog-card reveal">
                <div class="blog-card-img"><?php if (!empty($bp['image'])): ?><img src="<?= e($bp['image']) ?>" alt="<?= e($btitle) ?>"><?php endif; ?></div>
                <div class="blog-card-body">
                    <div class="blog-card-date"><?= e(date('d.m.Y', strtotime($bp['created_at'] ?? 'now'))) ?></div>
                    <h3 class="blog-card-title"><?= e($btitle) ?></h3>
                    <p class="blog-card-text"><?= e($btext) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- FOUNDER -->
<?php if ($founder_active && vpy_setting('founder_name')): ?>
<section>
    <div class="container">
        <div class="founder-card reveal">
            <?php if (vpy_setting('founder_image')): ?><img src="<?= e(vpy_setting('founder_image')) ?>" alt="<?= e(vpy_setting('founder_name')) ?>" class="founder-img"><?php endif; ?>
            <div class="founder-info">
                <span class="eyebrow" style="margin-bottom:12px"><?= $is_cyrl ? 'Асосчи' : 'Asoschi' ?></span>
                <h3><?= e(vpy_setting('founder_name')) ?></h3>
                <div class="title"><?= e(vpy_setting('founder_title')) ?></div>
                <p><?= e(vpy_setting('founder_description')) ?></p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section style="padding-bottom:0">
    <div class="container">
        <div class="cta-wrap reveal">
            <span class="eyebrow" style="background:rgba(255,255,255,0.08);border-color:rgba(255,255,255,0.15);color:rgba(255,255,255,0.9)"><?= e(t('hero_badge')) ?></span>
            <h2 class="h-section" style="margin-top:20px;color:#fff"><em><?= e(t('cta_title')) ?></em></h2>
            <p class="lead" style="margin:12px auto 0;text-align:center"><?= e(t('cta_subtitle')) ?></p>
            <a href="/register.php" class="btn" style="margin-top:24px;background:#fff;color:var(--primary);box-shadow:0 8px 24px rgba(0,0,0,0.15)"><?= e(t('cta_button')) ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
            <div class="cta-note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg><?= e(t('cta_note')) ?></div>
        </div>
    </div>
</section>
</main>


<script>
(function(){
    'use strict';
    /* BANNER slider */
    var bs=document.querySelectorAll('.banner-slide');
    if(bs.length>1){var bc=0;setInterval(function(){bs[bc].classList.remove('active');bc=(bc+1)%bs.length;bs[bc].classList.add('active');},5000);}

    /* COUNTER animation */
    function easeOutCubic(t){return 1-Math.pow(1-t,3);}
    function animateCounter(el){
        var target=parseFloat(el.getAttribute('data-counter'));
        var decimal=parseFloat(el.getAttribute('data-decimal'));
        var suffix=el.getAttribute('data-suffix')||'';
        var hasDecimal=!isNaN(decimal);
        var dur=1800,start=performance.now();
        function tick(now){
            var p=Math.min(1,(now-start)/dur);
            var eased=easeOutCubic(p);
            var val=hasDecimal?(decimal*eased):Math.round(target*eased);
            if(hasDecimal){el.textContent=val.toFixed(1).replace('.',',');}
            else if(target>=1000){el.textContent=val.toLocaleString('uz-UZ').replace(/,/g,' ');}
            else{el.textContent=val;}
            if(p<1)requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }
    if('IntersectionObserver' in window){
        var co=new IntersectionObserver(function(entries){entries.forEach(function(en){if(en.isIntersecting){animateCounter(en.target);co.unobserve(en.target);}});},{threshold:0.4});
        document.querySelectorAll('[data-counter]').forEach(function(el){co.observe(el);});
    }else{document.querySelectorAll('[data-counter]').forEach(function(el){animateCounter(el);});}

    /* PARALLAX MOCKUP */
    var stack=document.getElementById('mockupStack');
    if(stack&&window.matchMedia('(min-width:1024px)').matches){
        var rafId=null;
        window.addEventListener('mousemove',function(e){
            if(rafId)return;
            rafId=requestAnimationFrame(function(){
                var rx=(e.clientY/window.innerHeight-0.5)*8;
                var ry=(e.clientX/window.innerWidth-0.5)*-10;
                stack.style.transform='rotateY('+(ry-8)+'deg) rotateX('+(rx+4)+'deg) rotate(-1deg)';
                rafId=null;
            });
        },{passive:true});
    }

    /* FAQ ACCORDION */
    document.querySelectorAll('.faq-q').forEach(function(q){
        q.addEventListener('click',function(){
            var item=q.parentElement;
            var isOpen=item.classList.contains('open');
            document.querySelectorAll('.faq-item.open').forEach(function(o){o.classList.remove('open');});
            if(!isOpen)item.classList.add('open');
        });
    });

    /* SMOOTH SCROLL */
    document.querySelectorAll('a[href^="#"]').forEach(function(a){
        a.addEventListener('click',function(e){
            var id=a.getAttribute('href');
            if(id==='#'||id.length<2)return;
            var t=document.querySelector(id);
            if(t){e.preventDefault();window.scrollTo({top:t.getBoundingClientRect().top+window.scrollY-90,behavior:'smooth'});}
        });
    });

    /* RIPPLE */
    document.querySelectorAll('.btn').forEach(function(btn){
        btn.addEventListener('click',function(e){
            var rect=btn.getBoundingClientRect();
            var ripple=document.createElement('span');
            var size=Math.max(rect.width,rect.height);
            ripple.style.cssText='position:absolute;border-radius:50%;background:rgba(255,255,255,0.3);pointer-events:none;transform:scale(0);animation:rippleAnim 0.6s ease-out;width:'+size+'px;height:'+size+'px;left:'+(e.clientX-rect.left-size/2)+'px;top:'+(e.clientY-rect.top-size/2)+'px';
            btn.appendChild(ripple);
            setTimeout(function(){ripple.remove();},650);
        });
    });

    /* SERVICE WORKER */
    if('serviceWorker' in navigator&&location.protocol==='https:'){
        window.addEventListener('load',function(){navigator.serviceWorker.register('/sw.js').catch(function(){});});
    }
})();
</script>
<style>@keyframes rippleAnim{to{transform:scale(4);opacity:0}}</style>

<?php vpy_public_footer(); ?>