<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    // Password change
    if (!empty($_POST['new_password'])) {
        $old_pwd = vpy_post('old_password');
        $new_pwd = vpy_post('new_password');
        $confirm = vpy_post('confirm_password');
        if ($new_pwd !== $confirm) {
            vpy_flash_set('error', 'Parollar mos kelmaydi');
        } elseif (strlen($new_pwd) < 6) {
            vpy_flash_set('error', 'Parol kamida 6 ta belgi');
        } else {
            $r = vpy_password_change(vpy_user()['id'], $old_pwd, $new_pwd);
            vpy_flash_set($r['ok'] ? 'success' : 'error', $r['ok'] ? 'Parol o\'zgartirildi' : $r['error']);
        }
        vpy_redirect('/admin/sozlamalar.php?tab=system');
    }

    $settings = vpy_read_json('sozlamalar', []);
    $by_key = [];
    foreach ($settings as $i => $s) $by_key[$s['key']] = $i;

    // Handle file uploads
    $upload_fields = ['site_logo','hero_bg_image','banner_image_1','banner_image_2','banner_image_3','login_image_1','login_image_2','login_image_3','founder_image','panel_bg_image'];
    $upload_groups = ['site_logo'=>'general','hero_bg_image'=>'landing','banner_image_1'=>'landing','banner_image_2'=>'landing','banner_image_3'=>'landing','login_image_1'=>'landing','login_image_2'=>'landing','login_image_3'=>'landing','founder_image'=>'landing','panel_bg_image'=>'landing'];
    foreach ($upload_fields as $uf) {
        if (!empty($_FILES[$uf]['tmp_name']) && is_uploaded_file($_FILES[$uf]['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES[$uf]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','svg','gif'])) {
                $fname = $uf . '_' . time() . '.' . $ext;
                $dest = VPY_UPLOADS . '/' . $fname;
                if (move_uploaded_file($_FILES[$uf]['tmp_name'], $dest)) {
                    $url = '/assets/uploads/' . $fname;
                    $grp = $upload_groups[$uf] ?? 'landing';
                    if (isset($by_key[$uf])) {
                        $settings[$by_key[$uf]]['value'] = $url;
                    } else {
                        $settings[] = ['key' => $uf, 'value' => $url, 'group' => $grp];
                        $by_key[$uf] = count($settings) - 1;
                    }
                }
            }
        }
    }

    // Toggle fields ro'yxati (checkbox-like hidden inputs)
    $toggle_fields = ['founder_active','developer_active','payment_click_active','payment_payme_active','payment_humo_active','payment_uzcard_active','payment_visa_active','payment_invoice_active'];

    foreach ($_POST as $key => $val) {
        if ($key === 'csrf' || !is_string($val)) continue;
        if (in_array($key, $upload_fields)) continue; // skip file fields from POST
        if (in_array($key, $toggle_fields)) continue; // skip toggle fields - handled separately below
        if (isset($by_key[$key])) {
            $settings[$by_key[$key]]['value'] = (string)$val;
        } else {
            $settings[] = ['key' => $key, 'value' => (string)$val, 'group' => 'custom'];
        }
    }

    // Handle checkboxes (active toggles)
    foreach ($toggle_fields as $tf) {
        $v = (isset($_POST[$tf]) && $_POST[$tf] === '1') ? '1' : '0';
        if (isset($by_key[$tf])) {
            $settings[$by_key[$tf]]['value'] = $v;
        } else {
            $settings[] = ['key' => $tf, 'value' => $v, 'group' => $tf === 'founder_active' || $tf === 'developer_active' ? 'landing' : 'payments'];
        }
    }

    vpy_write_json('sozlamalar', $settings);
    vpy_flash_set('success', t('msg_saved'));
    vpy_redirect('/admin/sozlamalar.php?tab=' . vpy_get('tab', 'general'));
}

$settings = vpy_read_json('sozlamalar', []);
$grouped = [];
foreach ($settings as $s) $grouped[$s['group'] ?? 'general'][$s['key']] = $s['value'];

$tabs = [
    'general' => 'Umumiy',
    'contact' => 'Aloqa',
    'social' => 'Ijtimoiy',
    'landing' => 'Bosh sahifa',
    'company' => 'Kompaniya',
    'tests' => 'Testlar',
    'referral' => 'Referral',
    'payments' => "To'lovlar",
    'telegram' => 'Telegram',
    'system' => 'Tizim',
];
$current_tab = vpy_get('tab', 'general');
if (!isset($tabs[$current_tab])) $current_tab = 'general';

vpy_panel_head(t('admin_settings'), <<<CSS
.s-tabs{display:flex;gap:4px;flex-wrap:wrap;padding:5px;background:var(--glass);backdrop-filter:blur(20px);border:1px solid var(--border);border-radius:16px;margin-bottom:20px}
.s-tabs a{padding:9px 14px;border-radius:12px;font-size:0.82rem;font-weight:600;color:var(--dark-soft);text-decoration:none;transition:var(--t)}
.s-tabs a.active{background:var(--primary);color:#fff;box-shadow:0 4px 12px var(--primary-glow)}
.s-tabs a:hover:not(.active){background:var(--blue-soft);color:var(--primary)}
.upload-box{position:relative;width:100%;padding:20px;border:2px dashed var(--border-strong);border-radius:var(--r-sm);text-align:center;cursor:pointer;transition:var(--t);background:var(--surface)}
.upload-box:hover{border-color:var(--primary);background:var(--blue-soft)}
.upload-box input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer}
.upload-box .preview{max-width:200px;max-height:100px;margin:8px auto 0;border-radius:8px;object-fit:cover}
.toggle-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border)}
.toggle-row:last-child{border-bottom:none}
.toggle-row label{flex:1;font-weight:600;font-size:0.9rem}
.toggle-switch{position:relative;width:44px;height:24px;border-radius:12px;background:var(--bg2);border:1.5px solid var(--border);cursor:pointer;transition:var(--t)}
.toggle-switch::after{content:"";position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:var(--muted);transition:var(--t)}
.toggle-switch.on{background:var(--primary);border-color:var(--primary)}
.toggle-switch.on::after{transform:translateX(20px);background:#fff}
/* Payment Summary */
.pay-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px}
.pay-summary-item{display:flex;align-items:center;gap:14px;padding:18px 20px;background:var(--glass-strong);backdrop-filter:blur(20px);border:1px solid var(--border);border-radius:16px}
.pay-summary-icon{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;flex-shrink:0}
.pay-summary-num{font-size:1.5rem;font-weight:800;line-height:1;color:var(--dark)}
.pay-summary-label{font-size:0.75rem;color:var(--muted);font-weight:600;margin-top:2px}
/* Section Titles */
.pay-section-title{display:flex;align-items:center;gap:10px;font-size:0.82rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin:28px 0 14px;padding-left:4px}
.pay-section-title svg{opacity:0.6}
/* Pro Cards */
.pay-card-pro{margin-bottom:14px;border-radius:18px;overflow:hidden;transition:all 0.3s cubic-bezier(.4,0,.2,1)}
.pay-card-pro.pcp-active{border-left:3px solid #10b981}
.pay-card-pro.pcp-inactive{opacity:0.55;border-left:3px solid transparent}
.pay-card-pro:hover{opacity:1}
.pcp-header{display:flex;align-items:center;gap:16px;padding:20px 24px}
.pcp-icon{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;flex-shrink:0}
.pcp-info{flex:1;min-width:0}
.pcp-name{font-size:1.05rem;font-weight:800;margin:0;color:var(--dark)}
.pcp-desc{font-size:0.78rem;color:var(--muted);margin:2px 0 0;font-weight:500}
.pcp-right{display:flex;align-items:center;gap:12px;flex-shrink:0}
.pcp-body{padding:0 24px 20px;border-top:1px solid var(--border);margin-top:0;padding-top:18px}
/* Fields animation */
.pay-fields{transition:all 0.35s cubic-bezier(.4,0,.2,1);max-height:500px;opacity:1;overflow:hidden}
.pay-fields-hidden{max-height:0;opacity:0;margin:0;padding:0;pointer-events:none}
/* Badges */
.pay-status-badge{padding:5px 12px;border-radius:20px;font-size:0.68rem;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;white-space:nowrap}
.badge-on{background:rgba(16,185,129,0.12);color:#10b981}
.badge-off{background:rgba(107,114,128,0.08);color:var(--muted)}
@media(max-width:640px){.pay-summary{grid-template-columns:1fr}.pcp-header{flex-wrap:wrap;gap:12px}.pcp-right{width:100%;justify-content:flex-end}}
CSS);
vpy_panel_sidebar('sozlamalar', true);
?>
<main class="main">
<?php vpy_panel_topbar(t('admin_settings'), $tabs[$current_tab]); ?>

<div class="s-tabs">
    <?php foreach ($tabs as $k => $name): ?>
        <a href="?tab=<?= e($k) ?>" class="<?= $current_tab === $k ? 'active' : '' ?>"><?= e($name) ?></a>
    <?php endforeach; ?>
</div>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">

<?php if ($current_tab === 'landing'): ?>
    <!-- SITE LOGO -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Sayt logotipi</h2></div>
        <p style="font-size:0.85rem;color:var(--muted);margin-bottom:14px">Logo yuklanganida navbarda, footerda, loginda avtomatik ko'rinadi</p>
        <div class="field">
            <label>Logo rasmi</label>
            <div class="upload-box">
                <input type="file" name="site_logo" accept="image/*">
                <p style="color:var(--muted);font-size:0.85rem">Logo yuklang (PNG, SVG, WebP)</p>
                <?php if (!empty($grouped['general']['site_logo'])): ?>
                <img class="preview" src="<?= e($grouped['general']['site_logo']) ?>" alt="Logo">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- HERO BACKGROUND -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Bosh sahifa fon rasmi</h2></div>
        <div class="field">
            <label>Hero orqa fon rasmi</label>
            <div class="upload-box">
                <input type="file" name="hero_bg_image" accept="image/*">
                <p style="color:var(--muted);font-size:0.85rem">Rasm yuklang yoki tashlang</p>
                <?php if (!empty($grouped['landing']['hero_bg_image'])): ?>
                <img class="preview" src="<?= e($grouped['landing']['hero_bg_image']) ?>" alt="">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- BANNERS -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Banner rasmlar (faqat desktop)</h2></div>
        <p style="font-size:0.85rem;color:var(--muted);margin-bottom:14px">Bir nechta rasm yuklab slayd-shou yasang. Mobileda ko'rinmaydi.</p>
        <div class="field-row">
            <div class="field">
                <label>Banner 1</label>
                <div class="upload-box"><input type="file" name="banner_image_1" accept="image/*"><p style="color:var(--muted);font-size:0.82rem">Rasm yuklang</p>
                <?php if (!empty($grouped['landing']['banner_image_1'])): ?><img class="preview" src="<?= e($grouped['landing']['banner_image_1']) ?>"><?php endif; ?></div>
            </div>
            <div class="field">
                <label>Banner 2</label>
                <div class="upload-box"><input type="file" name="banner_image_2" accept="image/*"><p style="color:var(--muted);font-size:0.82rem">Rasm yuklang</p>
                <?php if (!empty($grouped['landing']['banner_image_2'])): ?><img class="preview" src="<?= e($grouped['landing']['banner_image_2']) ?>"><?php endif; ?></div>
            </div>
        </div>
        <div class="field">
            <label>Banner 3</label>
            <div class="upload-box"><input type="file" name="banner_image_3" accept="image/*"><p style="color:var(--muted);font-size:0.82rem">Rasm yuklang</p>
            <?php if (!empty($grouped['landing']['banner_image_3'])): ?><img class="preview" src="<?= e($grouped['landing']['banner_image_3']) ?>"><?php endif; ?></div>
        </div>
    </div>

    <!-- TICKER STRIP -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Kamar (aylanuvchi matnlar)</h2></div>
        <p style="font-size:0.85rem;color:var(--muted);margin-bottom:14px">Qisqa so'zlar — ko'p so'z odamni chalg'itadi</p>
        <div class="field-row">
            <div class="field"><label>Matn 1</label><input type="text" name="ticker_text_1" value="<?= e($grouped['landing']['ticker_text_1'] ?? '') ?>"></div>
            <div class="field"><label>Matn 2</label><input type="text" name="ticker_text_2" value="<?= e($grouped['landing']['ticker_text_2'] ?? '') ?>"></div>
        </div>
        <div class="field-row">
            <div class="field"><label>Matn 3</label><input type="text" name="ticker_text_3" value="<?= e($grouped['landing']['ticker_text_3'] ?? '') ?>"></div>
            <div class="field"><label>Matn 4</label><input type="text" name="ticker_text_4" value="<?= e($grouped['landing']['ticker_text_4'] ?? '') ?>"></div>
        </div>
    </div>

    <!-- LOGIN IMAGES -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Kirish sahifasi rasmlari (slayd-shou)</h2></div>
        <p style="font-size:0.85rem;color:var(--muted);margin-bottom:14px">2 va undan ortiq rasm yuklab slayd-shou hosil qiling</p>
        <div class="field-row">
            <div class="field">
                <label>Rasm 1</label>
                <div class="upload-box"><input type="file" name="login_image_1" accept="image/*"><p style="color:var(--muted);font-size:0.82rem">Yuklang</p>
                <?php if (!empty($grouped['landing']['login_image_1'])): ?><img class="preview" src="<?= e($grouped['landing']['login_image_1']) ?>"><?php endif; ?></div>
            </div>
            <div class="field">
                <label>Rasm 2</label>
                <div class="upload-box"><input type="file" name="login_image_2" accept="image/*"><p style="color:var(--muted);font-size:0.82rem">Yuklang</p>
                <?php if (!empty($grouped['landing']['login_image_2'])): ?><img class="preview" src="<?= e($grouped['landing']['login_image_2']) ?>"><?php endif; ?></div>
            </div>
        </div>
        <div class="field">
            <label>Rasm 3</label>
            <div class="upload-box"><input type="file" name="login_image_3" accept="image/*"><p style="color:var(--muted);font-size:0.82rem">Yuklang</p>
            <?php if (!empty($grouped['landing']['login_image_3'])): ?><img class="preview" src="<?= e($grouped['landing']['login_image_3']) ?>"><?php endif; ?></div>
        </div>
    </div>

    <!-- PANEL BG -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Panel orqa fon rasmi</h2></div>
        <div class="field">
            <label>Admin/User panel orqa fon</label>
            <div class="upload-box"><input type="file" name="panel_bg_image" accept="image/*"><p style="color:var(--muted);font-size:0.82rem">Yuklang</p>
            <?php if (!empty($grouped['landing']['panel_bg_image'])): ?><img class="preview" src="<?= e($grouped['landing']['panel_bg_image']) ?>"><?php endif; ?></div>
        </div>
    </div>

    <!-- FOUNDER -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Asoschi bo'limi</h2></div>
        <div class="toggle-row">
            <label>Asoschi bo'limini ko'rsatish</label>
            <div class="toggle-switch <?= ($grouped['landing']['founder_active'] ?? '0') === '1' ? 'on' : '' ?>" data-field="founder_active"></div>
            <input type="hidden" name="founder_active" id="toggle_founder_active" value="<?= e($grouped['landing']['founder_active'] ?? '0') ?>">
        </div>
        <div class="field-row" style="margin-top:14px">
            <div class="field"><label>Ismi</label><input type="text" name="founder_name" value="<?= e($grouped['landing']['founder_name'] ?? '') ?>"></div>
            <div class="field"><label>Lavozimi</label><input type="text" name="founder_title" value="<?= e($grouped['landing']['founder_title'] ?? '') ?>"></div>
        </div>
        <div class="field"><label>Tavsif</label><textarea name="founder_description"><?= e($grouped['landing']['founder_description'] ?? '') ?></textarea></div>
        <div class="field">
            <label>Rasmi</label>
            <div class="upload-box"><input type="file" name="founder_image" accept="image/*"><p style="color:var(--muted);font-size:0.82rem">Yuklang</p>
            <?php if (!empty($grouped['landing']['founder_image'])): ?><img class="preview" src="<?= e($grouped['landing']['founder_image']) ?>"><?php endif; ?></div>
        </div>
    </div>

    <!-- DEVELOPER -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Dasturchi haqida</h2></div>
        <div class="toggle-row">
            <label>Dasturchi bo'limini ko'rsatish (Aloqa sahifasida)</label>
            <div class="toggle-switch <?= ($grouped['landing']['developer_active'] ?? '0') === '1' ? 'on' : '' ?>" data-field="developer_active"></div>
            <input type="hidden" name="developer_active" id="toggle_developer_active" value="<?= e($grouped['landing']['developer_active'] ?? '0') ?>">
        </div>
        <div class="field-row" style="margin-top:14px">
            <div class="field"><label>Ismi</label><input type="text" name="developer_name" value="<?= e($grouped['landing']['developer_name'] ?? '') ?>"></div>
            <div class="field"><label>Lavozimi</label><input type="text" name="developer_title" value="<?= e($grouped['landing']['developer_title'] ?? '') ?>"></div>
        </div>
        <div class="field-row">
            <div class="field"><label>Telefon</label><input type="text" name="developer_phone" value="<?= e($grouped['landing']['developer_phone'] ?? '') ?>"></div>
            <div class="field"><label>Telegram</label><input type="text" name="developer_telegram" value="<?= e($grouped['landing']['developer_telegram'] ?? '') ?>" placeholder="@username"></div>
        </div>
        <div class="field"><label>Tavsif</label><textarea name="developer_description"><?= e($grouped['landing']['developer_description'] ?? '') ?></textarea></div>
    </div>

    <!-- STATS -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Statistika raqamlari</h2></div>
        <div class="field-row">
            <div class="field"><label>Faol o'quvchilar</label><input type="number" name="stat_users" value="<?= e($grouped['landing']['stat_users'] ?? '0') ?>"></div>
            <div class="field"><label>Yechilgan testlar</label><input type="number" name="stat_tests" value="<?= e($grouped['landing']['stat_tests'] ?? '0') ?>"></div>
        </div>
        <div class="field-row">
            <div class="field"><label>O'rtacha ball</label><input type="text" name="stat_score" value="<?= e($grouped['landing']['stat_score'] ?? '0') ?>"></div>
            <div class="field"><label>Muvaffaqiyat %</label><input type="number" name="stat_success" value="<?= e($grouped['landing']['stat_success'] ?? '0') ?>"></div>
        </div>
    </div>

<?php elseif ($current_tab === 'payments'): ?>
    <?php include __DIR__ . '/_payments_tab.php'; ?>

<?php else: ?>
    <?php if ($current_tab === 'system'): ?>
    <!-- PASSWORD CHANGE -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Parolni o'zgartirish</h2></div>
        <div class="field"><label>Joriy parol</label><input type="password" name="old_password" autocomplete="off"></div>
        <div class="field"><label>Yangi parol</label><input type="password" name="new_password" autocomplete="off" minlength="6"></div>
        <div class="field"><label>Yangi parolni tasdiqlang</label><input type="password" name="confirm_password" autocomplete="off" minlength="6"></div>
    </div>
    <?php endif; ?>

    <div class="card">
        <?php
        $current_settings = $grouped[$current_tab] ?? [];
        $textareas = ['site_description', 'site_keywords', 'contact_address'];
        foreach ($current_settings as $key => $value): ?>
        <div class="field">
            <label><?= e(ucfirst(str_replace('_', ' ', $key))) ?></label>
            <?php if (in_array($key, $textareas)): ?>
                <textarea name="<?= e($key) ?>" rows="3"><?= e($value) ?></textarea>
            <?php elseif (strpos($key, '_secret') !== false || strpos($key, '_key') !== false || strpos($key, '_token') !== false || strpos($key, '_password') !== false): ?>
                <input type="password" name="<?= e($key) ?>" value="<?= e($value) ?>" autocomplete="off">
            <?php else: ?>
                <input type="text" name="<?= e($key) ?>" value="<?= e($value) ?>">
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    <div style="display:flex;gap:10px;margin-top:18px">
        <button type="submit" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg><?= e(t('btn_save')) ?></button>
    </div>
</form>

<script>
document.querySelectorAll('.toggle-switch').forEach(function(ts){
    ts.addEventListener('click', function(){
        var field = ts.getAttribute('data-field');
        var input = document.getElementById('toggle_' + field);
        var isOn;
        if(ts.classList.contains('on')){
            ts.classList.remove('on');
            input.value = '0';
            isOn = false;
        } else {
            ts.classList.add('on');
            input.value = '1';
            isOn = true;
        }

        // To'lov kartasi maydonlarini yashirish/ko'rsatish
        var fields = document.getElementById('fields_' + field);
        var card = document.querySelector('[data-pay-card="' + field + '"]');
        var badge = document.getElementById('badge_' + field);
        if (fields) {
            if (isOn) {
                fields.classList.remove('pay-fields-hidden');
            } else {
                fields.classList.add('pay-fields-hidden');
            }
        }
        if (card) {
            if (isOn) {
                card.classList.remove('pay-inactive','pcp-inactive');
                card.classList.add('pay-active','pcp-active');
            } else {
                card.classList.remove('pay-active','pcp-active');
                card.classList.add('pay-inactive','pcp-inactive');
            }
        }
        if (badge) {
            if (isOn) {
                badge.classList.remove('badge-off');
                badge.classList.add('badge-on');
                badge.textContent = 'Faol';
            } else {
                badge.classList.remove('badge-on');
                badge.classList.add('badge-off');
                badge.textContent = "O'chiq";
            }
        }
    });
});
</script>

</main>
<?php vpy_panel_foot(); ?>
