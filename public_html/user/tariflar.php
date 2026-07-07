<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_login('/login.php');

$u = vpy_user();
$lang_code = vpy_lang_code();
$is_cyrl = $lang_code === 'uz_cyrillic';

$tariffs = vpy_filter('tariflar', fn($t) => !empty($t['active']));
usort($tariffs, fn($a, $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));

$active_payment = vpy_active_tariff_for_user($u['id']);

// Faol to'lov usullarini olish
$methods = [];
if (vpy_setting('payment_click_active') === '1') $methods[] = ['key'=>'click','name'=>'Click','sub'=>'Karta orqali','color'=>'#00b4ff','icon'=>'credit-card'];
if (vpy_setting('payment_payme_active') === '1') $methods[] = ['key'=>'payme','name'=>'Payme','sub'=>'Mobil to\'lov','color'=>'#00cccc','icon'=>'smartphone'];
if (vpy_setting('payment_humo_active') === '1') $methods[] = ['key'=>'humo','name'=>'Humo','sub'=>vpy_setting('humo_card_number',''),'color'=>'#ff6b35','icon'=>'card'];
if (vpy_setting('payment_uzcard_active') === '1') $methods[] = ['key'=>'uzcard','name'=>'Uzcard','sub'=>vpy_setting('uzcard_card_number',''),'color'=>'#0066cc','icon'=>'card'];
if (vpy_setting('payment_visa_active') === '1') $methods[] = ['key'=>'visa','name'=>'Visa','sub'=>vpy_setting('visa_card_number',''),'color'=>'#1a1f71','icon'=>'card'];
if (vpy_setting('payment_invoice_active') === '1') $methods[] = ['key'=>'invoice','name'=>'Bank o\'tkazma','sub'=>'Kompaniya hisobiga','color'=>'#6366f1','icon'=>'building'];


if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    $tariff_id = (int)vpy_post('tariff_id');
    $method = vpy_post('method');
    $tariff = vpy_find('tariflar', 'id', $tariff_id);

    $allowed_methods = array_column($methods, 'key');
    if ($tariff && in_array($method, $allowed_methods, true)) {
        $payment = [
            'id' => vpy_id_next('tolovlar'),
            'user_id' => (int)$u['id'],
            'tariff_id' => (int)$tariff['id'],
            'tariff_name' => $tariff['name'],
            'amount' => (float)$tariff['price'],
            'method' => $method,
            'status' => 'pending',
            'transaction_id' => '',
            'invoice_number' => 'INV-' . date('Y') . '-' . sprintf('%04d', vpy_id_next('tolovlar')),
            'screenshot' => '',
            'expires_at' => null,
            'paid_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Karta to'lovlari uchun screenshot
        if (in_array($method, ['humo','uzcard','visa']) && !empty($_FILES['screenshot']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $fname = 'pay_' . $payment['id'] . '_' . time() . '.' . $ext;
                $dest = VPY_UPLOADS . '/' . $fname;
                if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $dest)) {
                    $payment['screenshot'] = '/assets/uploads/' . $fname;
                    $payment['status'] = 'reviewing';
                }
            }
        }

        vpy_upsert('tolovlar', $payment);
        vpy_log('payment_init', sprintf('To\'lov: %s — %s', $tariff['name'], $method), ['user_id' => $u['id'], 'payment_id' => $payment['id']]);

        if (in_array($method, ['humo','uzcard','visa']) && !empty($payment['screenshot'])) {
            vpy_notify_payment_reviewing($u['id'], $tariff['name']);
            vpy_notify_admin('Yangi to\'lov screenshot', $u['name'] . ' — ' . $tariff['name'] . ' — ' . vpy_money($tariff['price']));
        }

        if ($method === 'click') {
            vpy_redirect('/includes/payments/click.php?id=' . $payment['id']);
        } elseif ($method === 'payme') {
            vpy_redirect('/includes/payments/payme.php?id=' . $payment['id']);
        } elseif ($method === 'invoice') {
            vpy_redirect('/invoice.php?id=' . $payment['id']);
        } else {
            vpy_flash_set('success', 'To\'lov ma\'lumotlari yuborildi! Admin tekshirib tasdiqlaydi.');
            vpy_redirect('/user/tariflar.php');
        }
    }
}

$selected = (int)vpy_get('tarif', 0);


vpy_panel_head(t('tariffs_title'), <<<CSS
/* TARIFF CARDS */
.tariffs-section{margin-bottom:32px}
.tariffs-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px}
.t-card{position:relative;padding:28px 24px 24px;border-radius:20px;background:var(--glass-strong);backdrop-filter:blur(24px);border:1.5px solid var(--border);transition:all 0.3s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column;overflow:hidden}
.t-card::before{content:"";position:absolute;top:0;left:0;right:0;height:4px;background:var(--primary);opacity:0;transition:opacity 0.3s}
.t-card:hover{transform:translateY(-6px);box-shadow:0 20px 40px rgba(0,0,0,0.08);border-color:var(--primary)}
.t-card:hover::before{opacity:1}
.t-card.featured{border:2px solid var(--primary);box-shadow:0 8px 32px var(--primary-glow)}
.t-card.featured::before{opacity:1;height:4px;background:linear-gradient(90deg,var(--primary),var(--accent))}
.t-card.current{background:linear-gradient(145deg,var(--primary),var(--primary-dark));color:#fff;border-color:transparent}
.t-card.current::before{display:none}
.t-badge{position:absolute;top:12px;right:12px;padding:4px 12px;border-radius:20px;font-size:0.68rem;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;background:var(--primary);color:#fff}
.t-card.current .t-badge{background:rgba(255,255,255,0.2);color:#fff}
.t-name{font-size:1.2rem;font-weight:800;margin-bottom:4px}
.t-desc{font-size:0.82rem;opacity:0.7;margin-bottom:18px;line-height:1.5}
.t-price-wrap{display:flex;align-items:baseline;gap:6px;margin-bottom:4px}
.t-price{font-size:2.2rem;font-weight:900;color:var(--primary);line-height:1;letter-spacing:-0.02em}
.t-card.current .t-price{color:#fff}
.t-currency{font-size:0.85rem;font-weight:600;opacity:0.7}
.t-period{font-size:0.78rem;opacity:0.6;margin-bottom:18px}
.t-features{flex:1;display:flex;flex-direction:column;gap:8px;margin-bottom:20px;padding-top:16px;border-top:1px solid rgba(0,0,0,0.06);font-size:0.84rem}
.t-card.current .t-features{border-color:rgba(255,255,255,0.15)}
.t-features li{display:flex;align-items:flex-start;gap:8px;line-height:1.5}
.t-features li svg{width:16px;height:16px;flex-shrink:0;margin-top:2px;color:var(--primary)}
.t-card.current .t-features li svg{color:rgba(255,255,255,0.8)}
.t-card .btn{width:100%;padding:14px;font-size:0.9rem;font-weight:700;border-radius:14px}

/* ACTIVE SUBSCRIPTION CARD */
.active-sub{padding:20px 24px;border-radius:18px;background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);color:#fff;margin-bottom:24px;display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.active-sub-icon{width:50px;height:50px;border-radius:14px;background:rgba(255,255,255,0.15);display:grid;place-items:center;flex-shrink:0}
.active-sub-info{flex:1;min-width:200px}
.active-sub-title{font-weight:800;font-size:1.05rem;margin-bottom:2px}
.active-sub-meta{font-size:0.8rem;opacity:0.75}
.active-sub-days{padding:8px 16px;border-radius:12px;background:rgba(255,255,255,0.15);font-weight:800;font-size:1.1rem;text-align:center}
.active-sub-days small{display:block;font-size:0.65rem;font-weight:600;opacity:0.7;margin-top:1px}
CSS);

vpy_panel_sidebar('tariflar', false);
?>

<main class="main">
<?php vpy_panel_topbar(t('tariffs_title'), t('tariffs_subtitle')); ?>

<?php if ($active_payment): 
    $days_left = max(0, ceil((strtotime($active_payment['expires_at']) - time()) / 86400));
?>
<div class="active-sub">
    <div class="active-sub-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
    </div>
    <div class="active-sub-info">
        <div class="active-sub-title"><?= e($active_payment['tariff_name']) ?> tarifi faol</div>
        <div class="active-sub-meta"><?= e(vpy_date($active_payment['expires_at'], 'd.m.Y')) ?> gacha amal qiladi</div>
    </div>
    <div class="active-sub-days">
        <?= $days_left ?>
        <small>kun qoldi</small>
    </div>
</div>
<?php endif; ?>

<div class="tariffs-section">
    <div class="tariffs-grid">
        <?php foreach ($tariffs as $tf):
            $features = $is_cyrl ? ($tf['features_cyrl'] ?? $tf['features']) : $tf['features'];
            $name = $is_cyrl ? ($tf['name_cyrl'] ?? $tf['name']) : $tf['name'];
            $desc = $is_cyrl ? ($tf['description_cyrl'] ?? $tf['description']) : $tf['description'];
            $period = $is_cyrl ? ($tf['period_label_cyrl'] ?? $tf['period_label']) : $tf['period_label'];
            $is_current = $active_payment && (int)$active_payment['tariff_id'] === (int)$tf['id'];
            $is_featured = !empty($tf['popular']) || !empty($tf['highlight']);
        ?>
        <div class="t-card <?= $is_current ? 'current' : ($is_featured ? 'featured' : '') ?>">
            <?php if ($is_current): ?><div class="t-badge">Faol</div>
            <?php elseif (!empty($tf['popular'])): ?><div class="t-badge">Mashhur</div><?php endif; ?>
            <h3 class="t-name"><?= e($name) ?></h3>
            <p class="t-desc"><?= e($desc) ?></p>
            <div class="t-price-wrap">
                <span class="t-price"><?= number_format((float)$tf['price'], 0, '.', ' ') ?></span>
                <span class="t-currency"><?= e(t('valyuta_sum')) ?></span>
            </div>
            <div class="t-period"><?= e($period) ?><?php if (!empty($tf['price_per_day'])): ?> · <?= number_format((float)$tf['price_per_day'],0,'.',' ') ?> so'm/kun<?php endif; ?></div>
            <ul class="t-features">
                <?php foreach ((array)$features as $f): ?>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span><?= e($f) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($is_current): ?>
                <button class="btn" style="background:rgba(255,255,255,0.2);color:#fff;cursor:default" disabled>Faol tarif</button>
            <?php else: ?>
                <button type="button" class="btn <?= $is_featured ? 'btn-primary' : 'btn-dark' ?>" onclick="openPayment(<?= (int)$tf['id'] ?>, '<?= e($name) ?>', <?= (float)$tf['price'] ?>)">Sotib olish</button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>


<!-- TO'LOV MODALI -->
<div class="pay-overlay" id="payOverlay">
    <div class="pay-modal" id="payModal">
        <div class="pay-header">
            <div>
                <h3 class="pay-title">To'lov usulini tanlang</h3>
                <p class="pay-subtitle" id="paySubtitle"></p>
            </div>
            <button type="button" class="pay-close" onclick="closePayment()" aria-label="Yopish">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form method="post" enctype="multipart/form-data" id="payForm">
            <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
            <input type="hidden" name="tariff_id" id="payTariffId">
            <input type="hidden" name="method" id="payMethod" value="">

            <!-- STEP 1: To'lov usulini tanlash -->
            <div class="pay-step" id="step1">
                <?php if (!empty($methods)): ?>
                <div class="methods-list">
                    <?php foreach ($methods as $i => $m): ?>
                    <div class="m-item <?= $i === 0 ? 'selected' : '' ?>" data-method="<?= e($m['key']) ?>" onclick="selectMethod(this)">
                        <div class="m-icon" style="background:<?= e($m['color']) ?>15;color:<?= e($m['color']) ?>">
                            <?php if ($m['icon'] === 'credit-card'): ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            <?php elseif ($m['icon'] === 'smartphone'): ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                            <?php elseif ($m['icon'] === 'building'): ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20"/><line x1="9" y1="6" x2="9" y2="6.01"/><line x1="15" y1="6" x2="15" y2="6.01"/><line x1="9" y1="10" x2="9" y2="10.01"/><line x1="15" y1="10" x2="15" y2="10.01"/><line x1="9" y1="14" x2="9" y2="14.01"/><line x1="15" y1="14" x2="15" y2="14.01"/><line x1="9" y1="18" x2="15" y2="18"/></svg>
                            <?php else: ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            <?php endif; ?>
                        </div>
                        <div class="m-info">
                            <div class="m-name"><?= e($m['name']) ?></div>
                            <div class="m-sub"><?= e($m['sub']) ?></div>
                        </div>
                        <div class="m-check">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="text-align:center;padding:40px 20px">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="1.5" style="margin-bottom:12px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <p style="color:var(--muted);font-weight:600">Hozirda faol to'lov usuli yo'q</p>
                    <p style="color:var(--muted);font-size:0.82rem;margin-top:4px">Admin bilan bog'laning</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- STEP 2: Karta ma'lumotlari (humo/uzcard/visa uchun) -->
            <div class="pay-step" id="step2" style="display:none">
                <div class="card-display">
                    <div class="card-display-header">
                        <span class="card-display-label">Quyidagi kartaga to'lang:</span>
                    </div>
                    <div class="card-display-number" id="displayCardNumber">—</div>
                    <div class="card-display-row">
                        <div>
                            <div class="card-display-sub">Karta egasi</div>
                            <div class="card-display-name" id="displayCardName">—</div>
                        </div>
                        <div style="text-align:right">
                            <div class="card-display-sub">To'lov summasi</div>
                            <div class="card-display-amount" id="displayAmount">—</div>
                        </div>
                    </div>
                    <div class="card-copy-btns">
                        <button type="button" class="copy-btn" onclick="copyToClipboard(document.getElementById('displayCardNumber').textContent.replace(/\s/g,''))">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            Karta raqamini nusxalash
                        </button>
                        <button type="button" class="copy-btn" onclick="copyToClipboard(document.getElementById('displayAmount').textContent.replace(/[^\d]/g,''))">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            Summani nusxalash
                        </button>
                    </div>
                </div>

                <div class="screenshot-upload">
                    <p class="screenshot-label">To'lovdan keyin screenshotni yuklang:</p>
                    <div class="screenshot-dropzone" id="screenshotDrop">
                        <input type="file" name="screenshot" accept="image/*" id="screenshotInput">
                        <div class="screenshot-placeholder" id="ssPlaceholder">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span>Rasmni tanlang yoki tashlang</span>
                            <small>JPG, PNG, WEBP · Max 5MB</small>
                        </div>
                        <div class="screenshot-preview" id="ssPreview" style="display:none">
                            <img id="ssPreviewImg" src="" alt="">
                            <button type="button" class="ss-remove" onclick="removeScreenshot(event)">✕</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="pay-footer">
                <button type="button" class="btn btn-ghost" id="payBackBtn" style="display:none" onclick="goToStep1()">Orqaga</button>
                <div style="flex:1"></div>
                <?php if (!empty($methods)): ?>
                <button type="button" class="btn btn-primary" id="payNextBtn" onclick="handleNext()">Davom etish</button>
                <button type="submit" class="btn btn-primary" id="paySubmitBtn" style="display:none">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    To'lash
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>


<style>
/* PAYMENT MODAL STYLES */
.pay-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);z-index:1000;display:none;align-items:center;justify-content:center;padding:16px;opacity:0;transition:opacity 0.25s}
.pay-overlay.show{display:flex;opacity:1}
.pay-modal{background:var(--surface);border:1px solid var(--border);border-radius:24px;max-width:480px;width:100%;max-height:85vh;overflow-y:auto;box-shadow:0 24px 80px rgba(0,0,0,0.15);animation:modalSlide 0.3s cubic-bezier(.4,0,.2,1)}
@keyframes modalSlide{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}
.pay-header{display:flex;align-items:flex-start;justify-content:space-between;padding:24px 24px 16px;border-bottom:1px solid var(--border)}
.pay-title{font-size:1.15rem;font-weight:800;margin:0}
.pay-subtitle{font-size:0.82rem;color:var(--muted);margin-top:4px}
.pay-close{width:36px;height:36px;border-radius:10px;border:1px solid var(--border);background:transparent;display:grid;place-items:center;cursor:pointer;color:var(--muted);transition:var(--t)}
.pay-close:hover{background:rgba(239,68,68,0.08);border-color:#ef4444;color:#ef4444}
.pay-step{padding:20px 24px}
.pay-footer{display:flex;align-items:center;gap:10px;padding:16px 24px;border-top:1px solid var(--border)}

/* METHOD LIST */
.methods-list{display:flex;flex-direction:column;gap:8px}
.m-item{display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:14px;border:1.5px solid var(--border);background:var(--glass);cursor:pointer;transition:all 0.2s}
.m-item:hover{border-color:var(--primary);background:rgba(13,107,78,0.03)}
.m-item.selected{border-color:var(--primary);background:rgba(13,107,78,0.05);box-shadow:0 2px 12px var(--primary-glow)}
.m-icon{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;flex-shrink:0}
.m-info{flex:1}
.m-name{font-weight:700;font-size:0.9rem}
.m-sub{font-size:0.75rem;color:var(--muted);margin-top:1px}
.m-check{width:24px;height:24px;border-radius:50%;border:2px solid var(--border);display:grid;place-items:center;transition:all 0.2s}
.m-item.selected .m-check{background:var(--primary);border-color:var(--primary);color:#fff}
.m-item:not(.selected) .m-check svg{display:none}

/* CARD DISPLAY */
.card-display{background:linear-gradient(135deg,#1a1a2e,#16213e);border-radius:16px;padding:20px;color:#fff;margin-bottom:18px}
.card-display-header{margin-bottom:12px}
.card-display-label{font-size:0.75rem;opacity:0.7;text-transform:uppercase;letter-spacing:0.05em;font-weight:600}
.card-display-number{font-size:1.4rem;font-weight:800;font-family:monospace;letter-spacing:0.08em;margin-bottom:14px}
.card-display-row{display:flex;justify-content:space-between;align-items:flex-end}
.card-display-sub{font-size:0.7rem;opacity:0.6;margin-bottom:2px}
.card-display-name{font-weight:700;font-size:0.85rem}
.card-display-amount{font-weight:800;font-size:1.1rem;color:#10b981}
.card-copy-btns{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap}
.copy-btn{display:flex;align-items:center;gap:6px;padding:8px 14px;border-radius:10px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);color:#fff;font-size:0.75rem;font-weight:600;cursor:pointer;transition:all 0.2s}
.copy-btn:hover{background:rgba(255,255,255,0.2)}

/* SCREENSHOT UPLOAD */
.screenshot-upload{margin-top:4px}
.screenshot-label{font-size:0.85rem;font-weight:700;margin-bottom:10px;color:var(--dark)}
.screenshot-dropzone{position:relative;border:2px dashed var(--border-strong);border-radius:14px;padding:30px 20px;text-align:center;cursor:pointer;transition:all 0.2s;background:rgba(255,253,249,0.6)}
.screenshot-dropzone:hover{border-color:var(--primary);background:rgba(13,107,78,0.03)}
.screenshot-dropzone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;z-index:2}
.screenshot-placeholder{display:flex;flex-direction:column;align-items:center;gap:8px}
.screenshot-placeholder span{font-size:0.88rem;font-weight:600;color:var(--dark)}
.screenshot-placeholder small{font-size:0.75rem;color:var(--muted)}
.screenshot-preview{position:relative;display:inline-block}
.screenshot-preview img{max-width:100%;max-height:180px;border-radius:10px;object-fit:contain}
.ss-remove{position:absolute;top:-8px;right:-8px;width:26px;height:26px;border-radius:50%;background:#ef4444;color:#fff;border:2px solid #fff;font-size:0.8rem;font-weight:700;cursor:pointer;display:grid;place-items:center;z-index:3}

@media (max-width:640px){.pay-modal{max-width:100%;border-radius:20px 20px 0 0;max-height:90vh;align-self:flex-end}.tariffs-grid{grid-template-columns:1fr}}
</style>


<script>
var cardData = {
    humo: {number:'<?= e(vpy_setting("humo_card_number","")) ?>',name:'<?= e(vpy_setting("humo_card_name","")) ?>'},
    uzcard: {number:'<?= e(vpy_setting("uzcard_card_number","")) ?>',name:'<?= e(vpy_setting("uzcard_card_name","")) ?>'},
    visa: {number:'<?= e(vpy_setting("visa_card_number","")) ?>',name:'<?= e(vpy_setting("visa_card_name","")) ?>'}
};
var currentPrice = 0;
var currentStep = 1;
var cardMethods = ['humo','uzcard','visa'];

function openPayment(id, name, price) {
    currentPrice = price;
    document.getElementById('payTariffId').value = id;
    document.getElementById('paySubtitle').textContent = name + ' — ' + price.toLocaleString('uz-UZ').replace(/,/g,' ') + " so'm";
    // Select first method
    var first = document.querySelector('.m-item');
    if (first) selectMethod(first);
    goToStep1();
    var overlay = document.getElementById('payOverlay');
    overlay.style.display = 'flex';
    setTimeout(function(){ overlay.classList.add('show'); }, 10);
    document.body.style.overflow = 'hidden';
}

function closePayment() {
    var overlay = document.getElementById('payOverlay');
    overlay.classList.remove('show');
    setTimeout(function(){ overlay.style.display = 'none'; }, 250);
    document.body.style.overflow = '';
}

function selectMethod(el) {
    document.querySelectorAll('.m-item').forEach(function(m){ m.classList.remove('selected'); });
    el.classList.add('selected');
    document.getElementById('payMethod').value = el.getAttribute('data-method');
}

function getSelectedMethod() {
    return document.getElementById('payMethod').value;
}

function goToStep1() {
    currentStep = 1;
    document.getElementById('step1').style.display = '';
    document.getElementById('step2').style.display = 'none';
    document.getElementById('payBackBtn').style.display = 'none';
    document.getElementById('payNextBtn').style.display = '';
    document.getElementById('paySubmitBtn').style.display = 'none';
}

function goToStep2() {
    var method = getSelectedMethod();
    currentStep = 2;
    
    // Karta ma'lumotlarini to'ldirish
    var num = cardData[method] ? cardData[method].number : '';
    var name = cardData[method] ? cardData[method].name : '';
    document.getElementById('displayCardNumber').textContent = formatCardNumber(num);
    document.getElementById('displayCardName').textContent = name || '—';
    document.getElementById('displayAmount').textContent = currentPrice.toLocaleString('uz-UZ').replace(/,/g,' ') + " so'm";
    
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = '';
    document.getElementById('payBackBtn').style.display = '';
    document.getElementById('payNextBtn').style.display = 'none';
    document.getElementById('paySubmitBtn').style.display = '';
}

function handleNext() {
    var method = getSelectedMethod();
    if (!method) return;
    
    if (cardMethods.indexOf(method) !== -1) {
        goToStep2();
    } else {
        // Click, Payme, Invoice — to'g'ridan-to'g'ri submit
        document.getElementById('payForm').submit();
    }
}

function formatCardNumber(num) {
    if (!num) return '—';
    num = num.replace(/\s/g, '');
    return num.replace(/(.{4})/g, '$1 ').trim();
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function(){
        var btn = event.currentTarget;
        var orig = btn.innerHTML;
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Nusxalandi!';
        btn.style.background = 'rgba(16,185,129,0.2)';
        btn.style.borderColor = '#10b981';
        setTimeout(function(){ btn.innerHTML = orig; btn.style.background = ''; btn.style.borderColor = ''; }, 2000);
    });
}

function removeScreenshot(e) {
    e.stopPropagation();
    document.getElementById('screenshotInput').value = '';
    document.getElementById('ssPreview').style.display = 'none';
    document.getElementById('ssPlaceholder').style.display = '';
}

// Screenshot preview
var ssInput = document.getElementById('screenshotInput');
if (ssInput) {
    ssInput.addEventListener('change', function(){
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e){
                document.getElementById('ssPreviewImg').src = e.target.result;
                document.getElementById('ssPreview').style.display = '';
                document.getElementById('ssPlaceholder').style.display = 'none';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// Close on overlay click
document.getElementById('payOverlay').addEventListener('click', function(e){
    if (e.target === this) closePayment();
});

// ESC to close
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closePayment();
});

<?php if ($selected): ?>
window.addEventListener('load', function(){
    var tariffs = <?= json_encode(array_map(fn($t) => ['id'=>$t['id'],'name'=>$t['name'],'price'=>$t['price']], $tariffs)) ?>;
    var sel = tariffs.find(function(t){ return t.id == <?= (int)$selected ?>; });
    if (sel) openPayment(sel.id, sel.name, parseFloat(sel.price));
});
<?php endif; ?>
</script>

</main>
<?php vpy_panel_foot(); ?>
