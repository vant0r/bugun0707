<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_login('/login.php');

$u = vpy_user();
$lang_code = vpy_lang_code();
$is_cyrl = $lang_code === 'uz_cyrillic';

$tariffs = vpy_filter('tariflar', fn($t) => !empty($t['active']));
usort($tariffs, fn($a, $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));

$active_payment = vpy_active_tariff_for_user($u['id']);

// Available payment methods (only active ones)
$methods = [];
if (vpy_setting('payment_click_active') === '1') $methods[] = ['key'=>'click','name'=>'Click','sub'=>'Karta orqali','icon'=>'M2 5h20v14H2z|M2 10h20'];
if (vpy_setting('payment_payme_active') === '1') $methods[] = ['key'=>'payme','name'=>'Payme','sub'=>'Mobil to\'lov','icon'=>'circle:cx=12,cy=12,r=10|M12 6v6l4 2'];
if (vpy_setting('payment_humo_active') === '1') $methods[] = ['key'=>'humo','name'=>'Humo','sub'=>vpy_setting('humo_card_number',''),'icon'=>'M2 5h20v14H2z|M2 10h20|M6 14h4'];
if (vpy_setting('payment_uzcard_active') === '1') $methods[] = ['key'=>'uzcard','name'=>'Uzcard','sub'=>vpy_setting('uzcard_card_number',''),'icon'=>'M2 5h20v14H2z|M2 10h20|M14 14h4'];
if (vpy_setting('payment_visa_active') === '1') $methods[] = ['key'=>'visa','name'=>'Visa','sub'=>vpy_setting('visa_card_number',''),'icon'=>'M2 5h20v14H2z|M2 10h20|M6 14h12'];
if (vpy_setting('payment_invoice_active') === '1') $methods[] = ['key'=>'invoice','name'=>'Bank o\'tkazma','sub'=>'Kompaniya hisobiga','icon'=>'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z|M14 2v6h6'];

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

        // Handle screenshot upload for card payments
        if (in_array($method, ['humo','uzcard','visa']) && !empty($_FILES['screenshot']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $fname = 'pay_' . $payment['id'] . '_' . time() . '.' . $ext;
                $dest = VPY_UPLOADS . '/' . $fname;
                if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $dest)) {
                    $payment['screenshot'] = '/assets/uploads/' . $fname;
                    $payment['status'] = 'reviewing'; // Admin tasdiqlashi kerak
                }
            }
        }

        vpy_upsert('tolovlar', $payment);
        vpy_log('payment_init', sprintf('To\'lov: %s — %s', $tariff['name'], $method), ['user_id' => $u['id'], 'payment_id' => $payment['id']]);

        // Notify user that screenshot received
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
            // Humo/Uzcard/Visa — screenshot yuborildi
            vpy_flash_set('success', 'To\'lov ma\'lumotlari yuborildi. Admin tasdiqlashini kuting.');
            vpy_redirect('/user/tariflar.php');
        }
    }
}

$selected = (int)vpy_get('tarif', 0);


vpy_panel_head(t('tariffs_title'), <<<CSS
.tariffs-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;margin-bottom:28px}
.tariff-card{position:relative;padding:32px 26px;border-radius:var(--r-lg);background:var(--glass);backdrop-filter:blur(20px) saturate(160%);-webkit-backdrop-filter:blur(20px) saturate(160%);border:1px solid var(--border-strong);transition:var(--t);display:flex;flex-direction:column}
.tariff-card:hover{transform:translateY(-4px);box-shadow:var(--shadow)}
.tariff-card.featured{border:2px solid var(--primary);box-shadow:0 12px 36px var(--primary-glow)}
.tariff-card.current{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-color:transparent}
.tariff-badge{position:absolute;top:-1px;right:24px;padding:5px 14px;background:var(--primary);color:#fff;font-size:0.68rem;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;border-radius:0 0 10px 10px}
.tariff-card.current .tariff-badge{background:rgba(255,255,255,0.9);color:var(--primary)}
.tariff-name{font-family:var(--sans);font-size:1.3rem;font-weight:800;margin-bottom:4px}
.tariff-desc{font-size:0.84rem;opacity:0.8;margin-bottom:20px}
.tariff-price{font-family:var(--sans);font-size:2rem;font-weight:800;color:var(--primary);line-height:1}
.tariff-card.current .tariff-price{color:#fff}
.tariff-period{font-size:0.82rem;opacity:0.7;margin-bottom:20px;margin-top:4px}
.tariff-features{flex:1;display:flex;flex-direction:column;gap:8px;margin-bottom:20px;padding-top:16px;border-top:1px dashed rgba(255,255,255,0.15);font-size:0.84rem}
.tariff-card:not(.current) .tariff-features{border-color:var(--border)}
.tariff-features li{display:flex;align-items:flex-start;gap:8px;line-height:1.4}
.tariff-features svg{width:14px;height:14px;color:var(--primary);flex-shrink:0;margin-top:2px}
.tariff-card.current .tariff-features svg{color:rgba(255,255,255,0.8)}
.tariff-card .btn{width:100%}
/* PAY MODAL */
.pay-modal{position:fixed;inset:0;background:rgba(17,19,24,0.6);backdrop-filter:blur(6px);display:none;align-items:center;justify-content:center;z-index:1000;padding:16px}
.pay-modal.show{display:flex}
.pay-box{background:var(--glass-strong);backdrop-filter:blur(28px);border:1px solid var(--border-strong);border-radius:var(--r-lg);padding:32px;max-width:520px;width:100%;box-shadow:var(--shadow);max-height:90vh;overflow-y:auto}
.pay-box h3{font-family:var(--sans);font-size:1.3rem;font-weight:800;margin-bottom:4px}
.pay-box .sub{color:var(--muted);font-size:0.85rem;margin-bottom:20px}
.method-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:18px}
.method-opt{padding:14px 12px;border-radius:12px;border:1.5px solid var(--border-strong);background:var(--surface);cursor:pointer;text-align:center;transition:var(--t)}
.method-opt:hover{border-color:var(--primary)}
.method-opt.sel{background:var(--blue-soft);border-color:var(--primary);box-shadow:0 4px 12px var(--primary-glow)}
.method-opt-ico{width:32px;height:32px;border-radius:8px;background:var(--blue-soft);color:var(--primary);display:grid;place-items:center;margin:0 auto 6px}
.method-opt.sel .method-opt-ico{background:var(--primary);color:#fff}
.method-opt-name{font-weight:700;font-size:0.82rem}
.method-opt-sub{font-size:0.7rem;color:var(--muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
/* Card info display */
.card-info{margin:16px 0;padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:12px;display:none}
.card-info.show{display:block}
.card-info-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.card-info-row:last-child{margin-bottom:0}
.card-info-label{font-size:0.78rem;color:var(--muted);font-weight:600}
.card-info-value{font-size:0.92rem;font-weight:700;font-family:monospace;letter-spacing:0.05em}
.copy-btn{padding:4px 10px;border-radius:6px;background:var(--blue-soft);color:var(--primary);font-size:0.72rem;font-weight:700;cursor:pointer;border:1px solid var(--border);transition:var(--t)}
.copy-btn:hover{background:var(--primary);color:#fff}
/* Screenshot upload */
.screenshot-area{margin:14px 0;display:none}
.screenshot-area.show{display:block}
.screenshot-drop{padding:24px;border:2px dashed var(--border-strong);border-radius:12px;text-align:center;cursor:pointer;position:relative;transition:var(--t)}
.screenshot-drop:hover{border-color:var(--primary);background:var(--blue-soft)}
.screenshot-drop input{position:absolute;inset:0;opacity:0;cursor:pointer}
.screenshot-drop p{font-size:0.85rem;color:var(--muted);font-weight:500}
.screenshot-drop .preview{max-width:200px;max-height:120px;margin:10px auto 0;border-radius:8px;object-fit:cover;display:none}
.pay-actions{display:flex;gap:10px;margin-top:18px}
.pay-actions .btn{flex:1}
@media (max-width:640px){.method-grid{grid-template-columns:1fr 1fr}.pay-box{padding:24px 18px}}
CSS);
vpy_panel_sidebar('tariflar', false);
?>

<main class="main">
<?php vpy_panel_topbar(t('tariffs_title'), t('tariffs_subtitle')); ?>


<?php if ($active_payment): ?>
<div class="card" style="margin-bottom:18px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-color:transparent">
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <div style="width:48px;height:48px;border-radius:14px;background:rgba(255,255,255,0.15);display:grid;place-items:center;flex-shrink:0">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </div>
        <div style="flex:1">
            <div style="font-weight:800;font-size:1.1rem"><?= e(t('tariffs_active')) ?>: <?= e($active_payment['tariff_name']) ?></div>
            <div style="font-size:0.82rem;color:rgba(255,255,255,0.7);margin-top:2px"><?= e(vpy_date($active_payment['expires_at'], 'd.m.Y')) ?> gacha · <?= max(0, ceil((strtotime($active_payment['expires_at']) - time()) / 86400)) ?> kun qoldi</div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="tariffs-grid">
    <?php foreach ($tariffs as $tf):
        $features = $is_cyrl ? ($tf['features_cyrl'] ?? $tf['features']) : $tf['features'];
        $name = $is_cyrl ? ($tf['name_cyrl'] ?? $tf['name']) : $tf['name'];
        $desc = $is_cyrl ? ($tf['description_cyrl'] ?? $tf['description']) : $tf['description'];
        $period = $is_cyrl ? ($tf['period_label_cyrl'] ?? $tf['period_label']) : $tf['period_label'];
        $is_current = $active_payment && (int)$active_payment['tariff_id'] === (int)$tf['id'];
        $is_featured = !empty($tf['popular']) || !empty($tf['highlight']);
    ?>
    <div class="tariff-card <?= $is_current ? 'current' : ($is_featured ? 'featured' : '') ?>">
        <?php if ($is_current): ?><div class="tariff-badge"><?= e(t('tariffs_active')) ?></div>
        <?php elseif (!empty($tf['popular'])): ?><div class="tariff-badge"><?= e(t('tariffs_badge_popular')) ?></div><?php endif; ?>
        <h3 class="tariff-name"><?= e($name) ?></h3>
        <p class="tariff-desc"><?= e($desc) ?></p>
        <div class="tariff-price"><?= number_format((float)$tf['price'], 0, '.', ' ') ?> <span style="font-size:0.45em;font-weight:500"><?= e(t('valyuta_sum')) ?></span></div>
        <div class="tariff-period"><?= e($period) ?><?php if (!empty($tf['price_per_day'])): ?> · <?= number_format((float)$tf['price_per_day'],0,'.',' ') ?> so'm/kun<?php endif; ?></div>
        <ul class="tariff-features">
            <?php foreach ((array)$features as $f): ?>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg><span><?= e($f) ?></span></li>
            <?php endforeach; ?>
        </ul>
        <?php if ($is_current): ?>
            <button class="btn" style="background:rgba(255,255,255,0.9);color:var(--primary)" disabled><?= e(t('tariffs_active')) ?></button>
        <?php else: ?>
            <button type="button" class="btn <?= $is_featured ? 'btn-primary' : 'btn-dark' ?>" data-tariff="<?= (int)$tf['id'] ?>" data-name="<?= e($name) ?>" data-price="<?= (float)$tf['price'] ?>" onclick="openPay(this)"><?= e(t('tariffs_buy')) ?></button>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>


<!-- PAYMENT MODAL -->
<div class="pay-modal" id="payModal">
    <div class="pay-box">
        <h3>To'lov usulini tanlang</h3>
        <p class="sub" id="paySub"></p>
        <form method="post" enctype="multipart/form-data" id="payForm">
            <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
            <input type="hidden" name="tariff_id" id="payTariffId">
            <input type="hidden" name="method" id="payMethod" value="">

            <?php if (!empty($methods)): ?>
            <div class="method-grid">
                <?php foreach ($methods as $i => $m): ?>
                <div class="method-opt <?= $i === 0 ? 'sel' : '' ?>" data-method="<?= e($m['key']) ?>" onclick="selectMethod(this)">
                    <div class="method-opt-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php foreach(explode('|',$m['icon']) as $p){if(strpos($p,'circle:')===0){$a=substr($p,7);echo '<circle '.str_replace(',', ' ', preg_replace('/(\w+)=/','$1="',$a)).'"/>';}else echo '<path d="'.$p.'"/>';} ?></svg></div>
                    <div class="method-opt-name"><?= e($m['name']) ?></div>
                    <div class="method-opt-sub"><?= e($m['sub']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="text-align:center;padding:20px;color:var(--muted)">Hozirda faol to'lov usuli yo'q. Admin bilan bog'laning.</p>
            <?php endif; ?>

            <!-- Card info (shown for humo/uzcard/visa) -->
            <div class="card-info" id="cardInfo">
                <p style="font-size:0.82rem;font-weight:700;margin-bottom:10px;color:var(--dark)">Quyidagi kartaga to'lang:</p>
                <div class="card-info-row">
                    <span class="card-info-label">Karta raqami</span>
                    <span class="card-info-value" id="cardNumber">—</span>
                    <button type="button" class="copy-btn" onclick="copyText(document.getElementById('cardNumber').textContent)">Nusxa</button>
                </div>
                <div class="card-info-row">
                    <span class="card-info-label">Karta egasi</span>
                    <span class="card-info-value" id="cardName">—</span>
                </div>
                <div class="card-info-row">
                    <span class="card-info-label">To'lov summasi</span>
                    <span class="card-info-value" id="cardAmount" style="color:var(--primary)">—</span>
                    <button type="button" class="copy-btn" onclick="copyText(document.getElementById('cardAmount').textContent.replace(/\s/g,''))">Nusxa</button>
                </div>
            </div>

            <!-- Screenshot upload (shown for humo/uzcard/visa) -->
            <div class="screenshot-area" id="screenshotArea">
                <p style="font-size:0.82rem;font-weight:700;margin-bottom:8px;color:var(--dark)">To'lov screenshotini yuklang:</p>
                <div class="screenshot-drop" id="screenshotDrop">
                    <input type="file" name="screenshot" accept="image/*" id="screenshotInput">
                    <p>Rasmni tanlang yoki shu yerga tashlang</p>
                    <img class="preview" id="screenshotPreview" alt="">
                </div>
            </div>

            <div class="pay-actions">
                <button type="button" class="btn btn-ghost" onclick="closePay()">Bekor qilish</button>
                <?php if (!empty($methods)): ?>
                <button type="submit" class="btn btn-primary">To'lash</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>


<script>
var cardMethods = {
    humo: {number:'<?= e(vpy_setting("humo_card_number","")) ?>',name:'<?= e(vpy_setting("humo_card_name","")) ?>'},
    uzcard: {number:'<?= e(vpy_setting("uzcard_card_number","")) ?>',name:'<?= e(vpy_setting("uzcard_card_name","")) ?>'},
    visa: {number:'<?= e(vpy_setting("visa_card_number","")) ?>',name:'<?= e(vpy_setting("visa_card_name","")) ?>'}
};
var currentPrice = 0;

function openPay(btn){
    var id = btn.getAttribute('data-tariff');
    var name = btn.getAttribute('data-name');
    currentPrice = parseFloat(btn.getAttribute('data-price'));
    document.getElementById('payTariffId').value = id;
    document.getElementById('paySub').textContent = name + ' — ' + currentPrice.toLocaleString('uz-UZ').replace(/,/g, ' ') + " so'm";
    // Select first method
    var first = document.querySelector('.method-opt');
    if (first) selectMethod(first);
    document.getElementById('payModal').classList.add('show');
}
function closePay(){ document.getElementById('payModal').classList.remove('show'); }

function selectMethod(el){
    document.querySelectorAll('.method-opt').forEach(function(m){ m.classList.remove('sel'); });
    el.classList.add('sel');
    var method = el.getAttribute('data-method');
    document.getElementById('payMethod').value = method;

    var cardInfo = document.getElementById('cardInfo');
    var ssArea = document.getElementById('screenshotArea');

    if (cardMethods[method]) {
        cardInfo.classList.add('show');
        ssArea.classList.add('show');
        document.getElementById('cardNumber').textContent = cardMethods[method].number || '—';
        document.getElementById('cardName').textContent = cardMethods[method].name || '—';
        document.getElementById('cardAmount').textContent = currentPrice.toLocaleString('uz-UZ').replace(/,/g, ' ');
    } else {
        cardInfo.classList.remove('show');
        ssArea.classList.remove('show');
    }
}

function copyText(text){
    navigator.clipboard.writeText(text).then(function(){
        var btn = event.target;
        btn.textContent = '✓';
        setTimeout(function(){ btn.textContent = 'Nusxa'; }, 1500);
    });
}

// Screenshot preview
var ssInput = document.getElementById('screenshotInput');
var ssPreview = document.getElementById('screenshotPreview');
if (ssInput) {
    ssInput.addEventListener('change', function(){
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e){
                ssPreview.src = e.target.result;
                ssPreview.style.display = 'block';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// Close on backdrop click
document.getElementById('payModal').addEventListener('click', function(e){
    if (e.target === this) closePay();
});

<?php if ($selected): ?>
window.addEventListener('load', function(){
    var btn = document.querySelector('[data-tariff="<?= (int)$selected ?>"]');
    if (btn) openPay(btn);
});
<?php endif; ?>
</script>

</main>
<?php vpy_panel_foot(); ?>
