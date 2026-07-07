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

    foreach ($_POST as $key => $val) {
        if ($key === 'csrf' || !is_string($val)) continue;
        if (in_array($key, $upload_fields)) continue; // skip file fields from POST
        if (isset($by_key[$key])) {
            $settings[$by_key[$key]]['value'] = (string)$val;
        } else {
            $settings[] = ['key' => $key, 'value' => (string)$val, 'group' => 'custom'];
        }
    }

    // Handle checkboxes (active toggles)
    $toggle_fields = ['founder_active','developer_active','payment_click_active','payment_payme_active','payment_humo_active','payment_uzcard_active','payment_visa_active','payment_invoice_active'];
    foreach ($toggle_fields as $tf) {
        $v = isset($_POST[$tf]) ? '1' : '0';
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
    <!-- CLICK -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Click</h2></div>
        <div class="toggle-row">
            <label>Click to'lovni faollashtirish</label>
            <div class="toggle-switch <?= ($grouped['payments']['payment_click_active'] ?? '0') === '1' ? 'on' : '' ?>" data-field="payment_click_active"></div>
            <input type="hidden" name="payment_click_active" id="toggle_payment_click_active" value="<?= e($grouped['payments']['payment_click_active'] ?? '0') ?>">
        </div>
        <div class="field-row" style="margin-top:14px">
            <div class="field"><label>Service ID</label><input type="text" name="click_service_id" value="<?= e($grouped['payments']['click_service_id'] ?? '') ?>"></div>
            <div class="field"><label>Merchant ID</label><input type="text" name="click_merchant_id" value="<?= e($grouped['payments']['click_merchant_id'] ?? '') ?>"></div>
        </div>
        <div class="field"><label>Secret Key</label><input type="password" name="click_secret_key" value="<?= e($grouped['payments']['click_secret_key'] ?? '') ?>" autocomplete="off"></div>
    </div>

    <!-- PAYME -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Payme</h2></div>
        <div class="toggle-row">
            <label>Payme to'lovni faollashtirish</label>
            <div class="toggle-switch <?= ($grouped['payments']['payment_payme_active'] ?? '0') === '1' ? 'on' : '' ?>" data-field="payment_payme_active"></div>
            <input type="hidden" name="payment_payme_active" id="toggle_payment_payme_active" value="<?= e($grouped['payments']['payment_payme_active'] ?? '0') ?>">
        </div>
        <div class="field-row" style="margin-top:14px">
            <div class="field"><label>Merchant ID</label><input type="text" name="payme_merchant_id" value="<?= e($grouped['payments']['payme_merchant_id'] ?? '') ?>"></div>
            <div class="field"><label>Key</label><input type="password" name="payme_key" value="<?= e($grouped['payments']['payme_key'] ?? '') ?>" autocomplete="off"></div>
        </div>
    </div>

    <!-- HUMO -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Humo karta</h2></div>
        <div class="toggle-row">
            <label>Humo orqali to'lovni faollashtirish</label>
            <div class="toggle-switch <?= ($grouped['payments']['payment_humo_active'] ?? '0') === '1' ? 'on' : '' ?>" data-field="payment_humo_active"></div>
            <input type="hidden" name="payment_humo_active" id="toggle_payment_humo_active" value="<?= e($grouped['payments']['payment_humo_active'] ?? '0') ?>">
        </div>
        <div class="field-row" style="margin-top:14px">
            <div class="field"><label>Karta raqami</label><input type="text" name="humo_card_number" value="<?= e($grouped['payments']['humo_card_number'] ?? '') ?>" placeholder="9860 XXXX XXXX XXXX" maxlength="19"></div>
            <div class="field"><label>Karta egasi ismi</label><input type="text" name="humo_card_name" value="<?= e($grouped['payments']['humo_card_name'] ?? '') ?>" placeholder="FAMILIYA ISM"></div>
        </div>
    </div>

    <!-- UZCARD -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Uzcard karta</h2></div>
        <div class="toggle-row">
            <label>Uzcard orqali to'lovni faollashtirish</label>
            <div class="toggle-switch <?= ($grouped['payments']['payment_uzcard_active'] ?? '0') === '1' ? 'on' : '' ?>" data-field="payment_uzcard_active"></div>
            <input type="hidden" name="payment_uzcard_active" id="toggle_payment_uzcard_active" value="<?= e($grouped['payments']['payment_uzcard_active'] ?? '0') ?>">
        </div>
        <div class="field-row" style="margin-top:14px">
            <div class="field"><label>Karta raqami</label><input type="text" name="uzcard_card_number" value="<?= e($grouped['payments']['uzcard_card_number'] ?? '') ?>" placeholder="8600 XXXX XXXX XXXX" maxlength="19"></div>
            <div class="field"><label>Karta egasi ismi</label><input type="text" name="uzcard_card_name" value="<?= e($grouped['payments']['uzcard_card_name'] ?? '') ?>" placeholder="FAMILIYA ISM"></div>
        </div>
    </div>

    <!-- VISA -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Visa karta</h2></div>
        <div class="toggle-row">
            <label>Visa orqali to'lovni faollashtirish</label>
            <div class="toggle-switch <?= ($grouped['payments']['payment_visa_active'] ?? '0') === '1' ? 'on' : '' ?>" data-field="payment_visa_active"></div>
            <input type="hidden" name="payment_visa_active" id="toggle_payment_visa_active" value="<?= e($grouped['payments']['payment_visa_active'] ?? '0') ?>">
        </div>
        <div class="field-row" style="margin-top:14px">
            <div class="field"><label>Karta raqami</label><input type="text" name="visa_card_number" value="<?= e($grouped['payments']['visa_card_number'] ?? '') ?>" placeholder="4XXX XXXX XXXX XXXX" maxlength="19"></div>
            <div class="field"><label>Karta egasi ismi</label><input type="text" name="visa_card_name" value="<?= e($grouped['payments']['visa_card_name'] ?? '') ?>" placeholder="FAMILIYA ISM"></div>
        </div>
    </div>

    <!-- KOMPANIYA HISOBI -->
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Kompaniya hisob raqami (pul o'tkazish)</h2></div>
        <div class="toggle-row">
            <label>Hisob raqamiga o'tkazishni faollashtirish</label>
            <div class="toggle-switch <?= ($grouped['payments']['payment_invoice_active'] ?? '0') === '1' ? 'on' : '' ?>" data-field="payment_invoice_active"></div>
            <input type="hidden" name="payment_invoice_active" id="toggle_payment_invoice_active" value="<?= e($grouped['payments']['payment_invoice_active'] ?? '0') ?>">
        </div>
        <div class="field-row" style="margin-top:14px">
            <div class="field"><label>Kompaniya nomi</label><input type="text" name="company_name" value="<?= e($grouped['company']['company_name'] ?? '') ?>"></div>
            <div class="field"><label>INN</label><input type="text" name="company_inn" value="<?= e($grouped['company']['company_inn'] ?? '') ?>"></div>
        </div>
        <div class="field-row">
            <div class="field"><label>Hisob raqami</label><input type="text" name="company_account" value="<?= e($grouped['company']['company_account'] ?? '') ?>"></div>
            <div class="field"><label>Bank / MFO</label><input type="text" name="company_bank" value="<?= e($grouped['company']['company_bank'] ?? '') ?>"></div>
        </div>
        <div class="field"><label>MFO</label><input type="text" name="company_mfo" value="<?= e($grouped['company']['company_mfo'] ?? '') ?>"></div>
    </div>

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
        if(ts.classList.contains('on')){
            ts.classList.remove('on');
            input.value = '0';
        } else {
            ts.classList.add('on');
            input.value = '1';
        }
    });
});
</script>

</main>
<?php vpy_panel_foot(); ?>
