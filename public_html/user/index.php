<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_login('/login.php');

$u = vpy_user();
$lang_code = vpy_lang_code();

$results = vpy_filter('natijalar', fn($r) => (int)$r['user_id'] === (int)$u['id']);
usort($results, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
$total_tests = count($results);
$avg_score = $total_tests ? round(array_sum(array_column($results, 'score')) / $total_tests, 1) : 0;
$best_score = $total_tests ? max(array_column($results, 'score')) : 0;
$active_tariff = vpy_active_tariff_for_user($u['id']);

$all_users = vpy_filter('users', fn($x) => ($x['role'] ?? 'user') === 'user');
usort($all_users, fn($a, $b) => ($b['best_score'] ?? 0) <=> ($a['best_score'] ?? 0) ?: ($b['tests_taken'] ?? 0) <=> ($a['tests_taken'] ?? 0));
$rank = 0;
foreach ($all_users as $i => $au) if ((int)$au['id'] === (int)$u['id']) { $rank = $i + 1; break; }

$total_questions = vpy_test_count();

vpy_panel_head(t('user_dashboard_title'), <<<CSS
.greeting{font-family:var(--serif);font-weight:500;font-size:clamp(1.6rem,3vw,2.2rem);line-height:1.1;letter-spacing:-0.02em;margin-bottom:6px}
.greeting em{font-style:italic;color:var(--primary)}
.dash-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:22px}
.kpi{padding:24px;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r);position:relative;overflow:hidden;transition:transform var(--t),box-shadow var(--t)}
.kpi:hover{transform:translateY(-4px);box-shadow:var(--shadow)}
.kpi.dark{background:linear-gradient(135deg,#1E1B18 0%,#2A2520 100%);color:#fff;border:1px solid rgba(232,168,56,0.18)}
.kpi.amber{background:linear-gradient(135deg,#F5D08A,#E8A838);color:var(--dark)}
.kpi.green{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff}
.kpi-ico{width:42px;height:42px;border-radius:12px;background:rgba(13,107,78,0.08);color:var(--primary);display:grid;place-items:center;margin-bottom:14px}
.kpi.dark .kpi-ico{background:rgba(232,168,56,0.18);color:var(--accent)}
.kpi.amber .kpi-ico{background:rgba(30,27,24,0.12);color:var(--dark)}
.kpi.green .kpi-ico{background:rgba(255,255,255,0.15);color:#fff}
.kpi-ico svg{width:20px;height:20px}
.kpi-num{font-family:var(--serif);font-size:2rem;font-weight:700;line-height:1;letter-spacing:-0.02em}
.kpi-label{font-size:0.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-top:6px;font-weight:600}
.kpi.dark .kpi-label,.kpi.green .kpi-label{color:rgba(255,253,249,0.6)}
.kpi.amber .kpi-label{color:rgba(30,27,24,0.65)}
.action-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:22px}
.action-card{padding:30px;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r-lg);text-decoration:none;color:inherit;transition:transform var(--t),box-shadow var(--t),border-color var(--t);position:relative;overflow:hidden;display:flex;flex-direction:column;gap:14px}
.action-card:hover{transform:translateY(-6px);box-shadow:var(--shadow);border-color:var(--primary)}
.action-card.featured{background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);color:#fff;border-color:transparent}
.action-card.featured::before{content:"";position:absolute;top:-50%;right:-30%;width:80%;height:160%;background:radial-gradient(ellipse,rgba(232,168,56,0.18),transparent 60%);pointer-events:none}
.action-ico{width:56px;height:56px;border-radius:18px;background:rgba(13,107,78,0.08);color:var(--primary);display:grid;place-items:center;transition:transform var(--t-bounce)}
.action-card:hover .action-ico{transform:rotate(-8deg) scale(1.08)}
.action-card.featured .action-ico{background:rgba(255,255,255,0.18);color:#fff}
.action-ico svg{width:26px;height:26px}
.action-card h3{font-family:var(--serif);font-size:1.2rem;font-weight:600;line-height:1.2}
.action-card p{font-size:0.88rem;color:var(--muted);line-height:1.5}
.action-card.featured p{color:rgba(255,253,249,0.75)}
.action-card .arrow{margin-top:auto;display:inline-flex;align-items:center;gap:6px;font-size:0.85rem;font-weight:600;color:var(--primary)}
.action-card.featured .arrow{color:var(--accent)}
.row-2{display:grid;grid-template-columns:1.5fr 1fr;gap:22px}
.test-row{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 18px;border-radius:14px;background:rgba(255,253,249,0.5);border:1px solid var(--border);margin-bottom:10px;transition:var(--t)}
.test-row:hover{background:var(--light);border-color:var(--primary)}
.test-row-meta{flex:1}
.test-row-title{font-weight:600;color:var(--dark);font-size:0.9rem}
.test-row-time{font-size:0.78rem;color:var(--muted)}
.test-row-score{font-family:var(--serif);font-size:1.3rem;font-weight:700;color:var(--primary)}
.test-row-score.fail{color:#C73E36}
.tariff-status{padding:24px;background:linear-gradient(135deg,var(--accent) 0%,#D88F1A 100%);color:#fff;border-radius:var(--r-lg);position:relative;overflow:hidden;box-shadow:0 18px 36px rgba(232,168,56,0.3)}
.tariff-status.muted{background:linear-gradient(135deg,var(--dark),#2A2520)}
.tariff-status::before{content:"";position:absolute;top:-50%;right:-30%;width:80%;height:160%;background:radial-gradient(ellipse,rgba(255,255,255,0.18),transparent 60%);pointer-events:none}
.tariff-status h3{font-family:var(--serif);font-size:1.2rem;font-weight:600;margin-bottom:4px;position:relative;z-index:2}
.tariff-status p{font-size:0.85rem;color:rgba(255,255,255,0.85);margin-bottom:14px;position:relative;z-index:2}
.tariff-status .price{font-family:var(--serif);font-size:1.8rem;font-weight:700;line-height:1;position:relative;z-index:2;margin-bottom:14px}
.tariff-status .btn{position:relative;z-index:2;background:#fff;color:var(--dark)}
.empty-mini{text-align:center;padding:30px 18px;color:var(--muted);font-size:0.9rem}
@media (max-width:1024px){.dash-grid{grid-template-columns:repeat(2,1fr)}.action-grid{grid-template-columns:1fr}.row-2{grid-template-columns:1fr}}
@media (max-width:480px){.dash-grid{grid-template-columns:1fr}}
CSS);
vpy_panel_sidebar('index', false);
?>

<main class="main">
<?php vpy_panel_topbar(t('user_welcome') . ', ' . explode(' ', $u['name'])[0], date('d.m.Y · H:i')); ?>

<div class="dash-grid">
    <div class="kpi green">
        <div class="kpi-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/></svg>
        </div>
        <div class="kpi-num"><?= $total_tests ?></div>
        <div class="kpi-label"><?= e(t('admin_total_tests')) ?></div>
    </div>
    <div class="kpi">
        <div class="kpi-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12"/></svg>
        </div>
        <div class="kpi-num"><?= $best_score ?>/20</div>
        <div class="kpi-label"><?= e(t('user_score')) ?> · max</div>
    </div>
    <div class="kpi amber">
        <div class="kpi-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
        </div>
        <div class="kpi-num"><?= number_format($avg_score, 1) ?></div>
        <div class="kpi-label">O'rtacha ball</div>
    </div>
    <div class="kpi dark">
        <div class="kpi-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/></svg>
        </div>
        <div class="kpi-num">#<?= $rank ?: '—' ?></div>
        <div class="kpi-label"><?= e(t('user_rank')) ?></div>
    </div>
</div>

<div class="action-grid">
    <a href="/user/test.php?type=quick" class="action-card featured">
        <div class="action-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        </div>
        <h3><?= e(t('user_quick_test')) ?></h3>
        <p><?= e(t('user_quick_test_desc')) ?> · 25 daqiqa</p>
        <span class="arrow">
            <?= e(t('btn_start')) ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </span>
    </a>
    <a href="/user/test.php?type=full" class="action-card">
        <div class="action-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
        </div>
        <h3><?= e(t('user_full_test')) ?></h3>
        <p><?= e(t('user_full_test_desc')) ?></p>
        <span class="arrow">
            <?= e(t('btn_start')) ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </span>
    </a>
    <a href="/user/testlar.php" class="action-card">
        <div class="action-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        </div>
        <h3><?= e(t('tickets_title')) ?></h3>
        <p><?= e(t('tickets_subtitle')) ?> · 62 ta bilet</p>
        <span class="arrow">
            <?= e(t('btn_continue')) ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </span>
    </a>
</div>

<div class="row-2">
    <div class="card">
        <div class="card-head">
            <h2><?= e(t('user_recent_tests')) ?></h2>
            <a href="/user/natijalar.php" style="font-size:0.85rem;color:var(--primary);font-weight:600"><?= e(t('user_view_all')) ?> →</a>
        </div>
        <?php if (empty($results)): ?>
            <div class="empty-mini"><?= e(t('user_no_tests')) ?></div>
        <?php else: ?>
            <?php foreach (array_slice($results, 0, 5) as $r): ?>
                <a href="/user/test-result.php?id=<?= (int)$r['id'] ?>" class="test-row" style="text-decoration:none;color:inherit">
                    <div class="test-row-meta">
                        <div class="test-row-title">
                            <?= ($r['type'] ?? 'quick') === 'full' ? e(t('user_full_test')) : e(t('user_quick_test')) ?>
                            <?php if ($r['score'] >= 18): ?>
                                <span class="chip chip-success" style="margin-left:6px;font-size:0.65rem;padding:2px 8px"><?= e(t('admin_status_success')) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="test-row-time"><?= e(vpy_time_ago($r['created_at'])) ?> · <?= floor((int)$r['duration'] / 60) ?>:<?= sprintf('%02d', (int)$r['duration'] % 60) ?></div>
                    </div>
                    <div class="test-row-score <?= $r['score'] < 18 ? 'fail' : '' ?>"><?= (int)$r['score'] ?>/<?= (int)$r['total'] ?></div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div>
        <?php if ($active_tariff): ?>
            <?php $days_left = max(0, ceil((strtotime($active_tariff['expires_at']) - time()) / 86400)); ?>
            <div class="tariff-status">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;position:relative;z-index:2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span style="font-size:0.78rem;text-transform:uppercase;letter-spacing:0.08em;font-weight:700">Faol obuna</span>
                </div>
                <h3><?= e($active_tariff['tariff_name']) ?></h3>
                <p><?= $days_left ?> kun qoldi · <?= e(vpy_date($active_tariff['expires_at'], 'd.m.Y')) ?> gacha</p>
                <a href="/user/tariflar.php" class="btn btn-sm" style="background:rgba(255,255,255,0.95);color:var(--dark);padding:8px 16px;font-size:0.82rem;display:inline-flex">
                    Yangilash
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 019-9 9.75 9.75 0 016.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                </a>
            </div>
        <?php else: ?>
            <div class="tariff-status">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;position:relative;z-index:2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span style="font-size:0.78rem;text-transform:uppercase;letter-spacing:0.08em;font-weight:700">Premiumga ko'taring</span>
                </div>
                <h3>Cheksiz testlar</h3>
                <p>4000+ savol, aqlli tahlil va sertifikat</p>
                <div class="price">89 000 <span style="font-size:0.5em">so'm/oy</span></div>
                <a href="/user/tariflar.php" class="btn btn-sm" style="background:#fff;color:var(--dark);padding:10px 18px;font-size:0.85rem;display:inline-flex">
                    <?= e(t('tariffs_buy')) ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>
        <?php endif; ?>

        <div class="card" style="margin-top:18px">
            <div style="display:flex;align-items:center;gap:14px">
                <div style="width:54px;height:54px;border-radius:18px;background:linear-gradient(135deg,#1E1B18,#3B362F);color:var(--accent);display:grid;place-items:center;flex-shrink:0">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/><path d="M16 3.13a4 4 0 010 7.75"/><path d="M21 21v-2a4 4 0 00-3-3.87"/></svg>
                </div>
                <div>
                    <div style="font-family:var(--serif);font-size:1.05rem;font-weight:600"><?= e(t('referral_title')) ?></div>
                    <div style="font-size:0.82rem;color:var(--muted)">5 000 so'm har bir taklif uchun</div>
                </div>
            </div>
            <a href="/user/referallar.php" class="btn btn-ghost" style="width:100%;margin-top:14px;justify-content:center">
                <?= e(t('btn_more')) ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </div>
    </div>
</div>

</main>

<?php vpy_panel_foot(); ?>