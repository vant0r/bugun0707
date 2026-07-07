<?php
require_once __DIR__ . '/includes/public_layout.php';

$lang = vpy_lang_code();
$is_cyrl = $lang === 'uz_cyrillic';
$tariffs = vpy_filter('tariflar', fn($t) => !empty($t['active']));
usort($tariffs, fn($a, $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));

vpy_public_head(t('tariffs_title'), t('tariffs_subtitle'), <<<CSS
.tariffs-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:54px}
.tariff-card{position:relative;padding:42px 36px;border-radius:var(--r-lg);background:var(--glass);backdrop-filter:blur(30px) saturate(160%);-webkit-backdrop-filter:blur(30px) saturate(160%);border:1px solid var(--border);transition:transform var(--t),box-shadow var(--t),border-color var(--t);display:flex;flex-direction:column;overflow:hidden}
.tariff-card:hover{transform:translateY(-8px) scale(1.01);box-shadow:var(--shadow-lg);border-color:var(--border-strong)}
.tariff-card.featured{background:linear-gradient(135deg,#FFFDF9 0%,#FAF7F2 100%);border:2px solid var(--accent);box-shadow:0 30px 60px rgba(232,168,56,0.18);transform:scale(1.04)}
.tariff-card.featured:hover{transform:translateY(-10px) scale(1.06)}
.tariff-badge{position:absolute;top:-1px;right:32px;padding:8px 18px;background:linear-gradient(135deg,var(--accent),#D88F1A);color:#fff;font-size:0.72rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;border-radius:0 0 12px 12px;box-shadow:0 8px 18px rgba(232,168,56,0.35)}
.tariff-name{font-family:var(--serif);font-size:1.6rem;font-weight:700;color:var(--dark);margin-bottom:8px}
.tariff-desc{font-size:0.92rem;color:var(--muted);margin-bottom:28px;min-height:42px}
.tariff-price-row{display:flex;align-items:baseline;gap:8px;margin-bottom:6px}
.tariff-old{font-size:1rem;color:var(--muted);text-decoration:line-through;text-decoration-color:rgba(122,111,98,0.5);font-weight:500}
.tariff-price{font-family:var(--serif);font-size:2.8rem;font-weight:700;color:var(--primary);line-height:1}
.tariff-period{font-size:0.92rem;color:var(--muted);margin-bottom:30px}
.tariff-features{flex:1;display:flex;flex-direction:column;gap:14px;margin-bottom:30px;padding-top:24px;border-top:1px dashed var(--border-strong)}
.tariff-features li{display:flex;align-items:flex-start;gap:12px;font-size:0.92rem;color:var(--dark-soft);line-height:1.45}
.tariff-features svg{width:18px;height:18px;color:var(--primary);flex-shrink:0;margin-top:2px}
.tariff-card .btn{width:100%;padding:16px}
.compare-table{margin-top:80px;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r-lg);padding:28px;overflow-x:auto}
.compare-table table{width:100%;border-collapse:collapse;font-size:0.92rem;min-width:600px}
.compare-table th,.compare-table td{padding:14px 18px;text-align:left;border-bottom:1px solid var(--border)}
.compare-table th{font-weight:700;color:var(--dark);font-size:0.78rem;text-transform:uppercase;letter-spacing:0.06em;background:rgba(13,107,78,0.04)}
.compare-table td.center{text-align:center}
.compare-table .check{color:var(--primary);font-weight:700}
.compare-table .x{color:var(--muted);opacity:0.5}
.payment-methods{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;margin-top:54px}
.pay-card{padding:28px 24px;background:var(--glass);backdrop-filter:blur(20px);border:1px solid var(--border);border-radius:var(--r);text-align:center;transition:var(--t)}
.pay-card:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:var(--primary)}
.pay-card-ico{width:54px;height:54px;border-radius:14px;background:rgba(13,107,78,0.08);color:var(--primary);display:grid;place-items:center;margin:0 auto 14px}
.pay-card-ico svg{width:24px;height:24px}
.pay-card h3{font-family:var(--serif);font-size:1.1rem;font-weight:600;margin-bottom:6px}
.pay-card p{font-size:0.85rem;color:var(--muted)}
@media (max-width:1024px){.tariffs-grid{grid-template-columns:1fr;max-width:520px;margin:54px auto 0}.tariff-card.featured{transform:none}.tariff-card.featured:hover{transform:translateY(-10px)}}
CSS);
vpy_public_navbar('tariflar');
?>

<main>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow"><?= e(t('nav_tariffs')) ?></span>
        <h1 class="h-display"><?= e(t('tariffs_title')) ?></h1>
        <p class="lead"><?= e(t('tariffs_subtitle')) ?></p>
    </div>
</section>

<section style="padding-top:0">
    <div class="container">
        <div class="tariffs-grid">
            <?php foreach ($tariffs as $i => $tf):
                $features = $is_cyrl ? ($tf['features_cyrl'] ?? $tf['features']) : $tf['features'];
                $name = $is_cyrl ? ($tf['name_cyrl'] ?? $tf['name']) : $tf['name'];
                $desc = $is_cyrl ? ($tf['description_cyrl'] ?? $tf['description']) : $tf['description'];
                $period = $is_cyrl ? ($tf['period_label_cyrl'] ?? $tf['period_label']) : $tf['period_label'];
                $featured = !empty($tf['highlight']) || !empty($tf['popular']);
            ?>
            <div class="tariff-card reveal r<?= $i + 1 ?> <?= $featured ? 'featured' : '' ?>">
                <?php if (!empty($tf['popular'])): ?>
                    <div class="tariff-badge"><?= e(t('tariffs_badge_popular')) ?></div>
                <?php endif; ?>
                <h3 class="tariff-name"><?= e($name) ?></h3>
                <p class="tariff-desc"><?= e($desc) ?></p>
                <?php if (!empty($tf['old_price'])): ?>
                    <div class="tariff-price-row"><span class="tariff-old"><?= number_format((float)$tf['old_price'], 0, '.', ' ') ?></span></div>
                <?php endif; ?>
                <div class="tariff-price-row">
                    <span class="tariff-price"><?= number_format((float)$tf['price'], 0, '.', ' ') ?></span>
                    <span class="muted" style="font-size:1rem;font-weight:500"><?= e(t('valyuta_sum')) ?></span>
                </div>
                <div class="tariff-period"><?= e($period) ?></div>
                <ul class="tariff-features">
                    <?php foreach ((array)$features as $f): ?>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><?= e($f) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= vpy_is_logged() ? '/user/tariflar.php?tarif=' : '/register.php?tarif=' ?><?= (int)$tf['id'] ?>" class="btn <?= $featured ? 'btn-primary' : 'btn-dark' ?>">
                    <?= e(t('tariffs_buy')) ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section>
    <div class="container">
        <div style="text-align:center;margin-bottom:40px">
            <span class="eyebrow"><?= e(t('pay_select_method')) ?></span>
            <h2 class="h-section" style="margin-top:18px"><?= e(t('pay_select_method')) ?></h2>
        </div>
        <div class="payment-methods">
            <div class="pay-card reveal r1">
                <div class="pay-card-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
                <h3><?= e(t('pay_method_click')) ?></h3>
                <p>Onlayn karta orqali</p>
            </div>
            <div class="pay-card reveal r2">
                <div class="pay-card-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3M12 17h.01"/></svg></div>
                <h3><?= e(t('pay_method_payme')) ?></h3>
                <p>Tezkor mobil to'lov</p>
            </div>
            <div class="pay-card reveal r3">
                <div class="pay-card-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg></div>
                <h3><?= e(t('pay_method_invoice')) ?></h3>
                <p>Bank o'tkazmasi</p>
            </div>
            <div class="pay-card reveal r4">
                <div class="pay-card-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
                <h3><?= e(t('pay_method_cash')) ?></h3>
                <p>Avtomaktabga to'g'ridan</p>
            </div>
        </div>
    </div>
</section>
</main>

<?php vpy_public_footer(); ?>
