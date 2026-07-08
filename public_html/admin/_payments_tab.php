<?php
// To'lov usullari - Professional UI
$pay_methods = [
    ['key' => 'payment_click_active', 'name' => 'Click', 'desc' => 'Onlayn to\'lov tizimi', 'icon' => 'click', 'color' => '#00b4ff', 'type' => 'gateway'],
    ['key' => 'payment_payme_active', 'name' => 'Payme', 'desc' => 'Mobil to\'lov platformasi', 'icon' => 'payme', 'color' => '#00cccc', 'type' => 'gateway'],
    ['key' => 'payment_humo_active', 'name' => 'Humo', 'desc' => 'Plastik karta orqali', 'icon' => 'humo', 'color' => '#ff6b35', 'type' => 'card'],
    ['key' => 'payment_uzcard_active', 'name' => 'Uzcard', 'desc' => 'Milliy karta tizimi', 'icon' => 'uzcard', 'color' => '#0066cc', 'type' => 'card'],
    ['key' => 'payment_visa_active', 'name' => 'Visa', 'desc' => 'Xalqaro karta', 'icon' => 'visa', 'color' => '#1a1f71', 'type' => 'card'],
    ['key' => 'payment_invoice_active', 'name' => 'Bank o\'tkazma', 'desc' => 'Kompaniya hisobiga', 'icon' => 'bank', 'color' => '#6366f1', 'type' => 'invoice'],
];
$active_count = 0;
foreach ($pay_methods as $pm) {
    if (($grouped['payments'][$pm['key']] ?? '0') === '1') $active_count++;
}
?>

<!-- Umumiy holat -->
<div class="pay-summary">
    <div class="pay-summary-item">
        <div class="pay-summary-icon" style="background:rgba(16,185,129,0.1);color:#10b981">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div>
            <div class="pay-summary-num"><?= $active_count ?></div>
            <div class="pay-summary-label">Faol usul</div>
        </div>
    </div>
    <div class="pay-summary-item">
        <div class="pay-summary-icon" style="background:rgba(107,114,128,0.1);color:#6b7280">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        </div>
        <div>
            <div class="pay-summary-num"><?= count($pay_methods) - $active_count ?></div>
            <div class="pay-summary-label">O'chirilgan</div>
        </div>
    </div>
    <div class="pay-summary-item">
        <div class="pay-summary-icon" style="background:rgba(99,102,241,0.1);color:#6366f1">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        </div>
        <div>
            <div class="pay-summary-num"><?= count($pay_methods) ?></div>
            <div class="pay-summary-label">Jami usul</div>
        </div>
    </div>
</div>

<!-- ONLINE TO'LOV TIZIMLARI -->
<div class="pay-section-title">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    Onlayn to'lov tizimlari
</div>

<!-- CLICK -->
<?php $is_on = ($grouped['payments']['payment_click_active'] ?? '0') === '1'; ?>
<div class="card pay-card-pro <?= $is_on ? 'pcp-active' : 'pcp-inactive' ?>" data-pay-card="payment_click_active">
    <div class="pcp-header">
        <div class="pcp-icon" style="background:rgba(0,180,255,0.08);color:#00b4ff">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </div>
        <div class="pcp-info">
            <h3 class="pcp-name">Click</h3>
            <p class="pcp-desc">Onlayn to'lov tizimi</p>
        </div>
        <div class="pcp-right">
            <div class="pay-status-badge <?= $is_on ? 'badge-on' : 'badge-off' ?>" id="badge_payment_click_active"><?= $is_on ? 'Faol' : "O'chiq" ?></div>
            <div class="toggle-switch <?= $is_on ? 'on' : '' ?>" data-field="payment_click_active"></div>
            <input type="hidden" name="payment_click_active" id="toggle_payment_click_active" value="<?= $is_on ? '1' : '0' ?>">
        </div>
    </div>
    <div class="pay-fields <?= $is_on ? '' : 'pay-fields-hidden' ?>" id="fields_payment_click_active">
        <div class="pcp-body">
            <div class="field-row">
                <div class="field"><label>Service ID</label><input type="text" name="click_service_id" value="<?= e($grouped['payments']['click_service_id'] ?? '') ?>" placeholder="Kiriting..."></div>
                <div class="field"><label>Merchant ID</label><input type="text" name="click_merchant_id" value="<?= e($grouped['payments']['click_merchant_id'] ?? '') ?>" placeholder="Kiriting..."></div>
            </div>
            <div class="field"><label>Secret Key</label><input type="password" name="click_secret_key" value="<?= e($grouped['payments']['click_secret_key'] ?? '') ?>" autocomplete="off" placeholder="••••••••"></div>
        </div>
    </div>
</div>

<!-- PAYME -->
<?php $is_on = ($grouped['payments']['payment_payme_active'] ?? '0') === '1'; ?>
<div class="card pay-card-pro <?= $is_on ? 'pcp-active' : 'pcp-inactive' ?>" data-pay-card="payment_payme_active">
    <div class="pcp-header">
        <div class="pcp-icon" style="background:rgba(0,204,204,0.08);color:#00cccc">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
        </div>
        <div class="pcp-info">
            <h3 class="pcp-name">Payme</h3>
            <p class="pcp-desc">Mobil to'lov platformasi</p>
        </div>
        <div class="pcp-right">
            <div class="pay-status-badge <?= $is_on ? 'badge-on' : 'badge-off' ?>" id="badge_payment_payme_active"><?= $is_on ? 'Faol' : "O'chiq" ?></div>
            <div class="toggle-switch <?= $is_on ? 'on' : '' ?>" data-field="payment_payme_active"></div>
            <input type="hidden" name="payment_payme_active" id="toggle_payment_payme_active" value="<?= $is_on ? '1' : '0' ?>">
        </div>
    </div>
    <div class="pay-fields <?= $is_on ? '' : 'pay-fields-hidden' ?>" id="fields_payment_payme_active">
        <div class="pcp-body">
            <div class="field-row">
                <div class="field"><label>Merchant ID</label><input type="text" name="payme_merchant_id" value="<?= e($grouped['payments']['payme_merchant_id'] ?? '') ?>" placeholder="Kiriting..."></div>
                <div class="field"><label>Key</label><input type="password" name="payme_key" value="<?= e($grouped['payments']['payme_key'] ?? '') ?>" autocomplete="off" placeholder="••••••••"></div>
            </div>
        </div>
    </div>
</div>

<!-- PLASTIK KARTALAR -->
<div class="pay-section-title">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
    Plastik karta orqali to'lov
</div>

<!-- HUMO -->
<?php $is_on = ($grouped['payments']['payment_humo_active'] ?? '0') === '1'; ?>
<div class="card pay-card-pro <?= $is_on ? 'pcp-active' : 'pcp-inactive' ?>" data-pay-card="payment_humo_active">
    <div class="pcp-header">
        <div class="pcp-icon" style="background:rgba(255,107,53,0.08);color:#ff6b35">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/><circle cx="18" cy="16" r="1.5" fill="currentColor"/></svg>
        </div>
        <div class="pcp-info">
            <h3 class="pcp-name">Humo</h3>
            <p class="pcp-desc">Plastik karta orqali o'tkazma</p>
        </div>
        <div class="pcp-right">
            <div class="pay-status-badge <?= $is_on ? 'badge-on' : 'badge-off' ?>" id="badge_payment_humo_active"><?= $is_on ? 'Faol' : "O'chiq" ?></div>
            <div class="toggle-switch <?= $is_on ? 'on' : '' ?>" data-field="payment_humo_active"></div>
            <input type="hidden" name="payment_humo_active" id="toggle_payment_humo_active" value="<?= $is_on ? '1' : '0' ?>">
        </div>
    </div>
    <div class="pay-fields <?= $is_on ? '' : 'pay-fields-hidden' ?>" id="fields_payment_humo_active">
        <div class="pcp-body">
            <div class="field-row">
                <div class="field"><label>Karta raqami</label><input type="text" name="humo_card_number" value="<?= e($grouped['payments']['humo_card_number'] ?? '') ?>" placeholder="9860 XXXX XXXX XXXX" maxlength="19"></div>
                <div class="field"><label>Karta egasi</label><input type="text" name="humo_card_name" value="<?= e($grouped['payments']['humo_card_name'] ?? '') ?>" placeholder="FAMILIYA ISM"></div>
            </div>
        </div>
    </div>
</div>

<!-- UZCARD -->
<?php $is_on = ($grouped['payments']['payment_uzcard_active'] ?? '0') === '1'; ?>
<div class="card pay-card-pro <?= $is_on ? 'pcp-active' : 'pcp-inactive' ?>" data-pay-card="payment_uzcard_active">
    <div class="pcp-header">
        <div class="pcp-icon" style="background:rgba(0,102,204,0.08);color:#0066cc">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/><path d="M6 15h4"/></svg>
        </div>
        <div class="pcp-info">
            <h3 class="pcp-name">Uzcard</h3>
            <p class="pcp-desc">Milliy karta tizimi</p>
        </div>
        <div class="pcp-right">
            <div class="pay-status-badge <?= $is_on ? 'badge-on' : 'badge-off' ?>" id="badge_payment_uzcard_active"><?= $is_on ? 'Faol' : "O'chiq" ?></div>
            <div class="toggle-switch <?= $is_on ? 'on' : '' ?>" data-field="payment_uzcard_active"></div>
            <input type="hidden" name="payment_uzcard_active" id="toggle_payment_uzcard_active" value="<?= $is_on ? '1' : '0' ?>">
        </div>
    </div>
    <div class="pay-fields <?= $is_on ? '' : 'pay-fields-hidden' ?>" id="fields_payment_uzcard_active">
        <div class="pcp-body">
            <div class="field-row">
                <div class="field"><label>Karta raqami</label><input type="text" name="uzcard_card_number" value="<?= e($grouped['payments']['uzcard_card_number'] ?? '') ?>" placeholder="8600 XXXX XXXX XXXX" maxlength="19"></div>
                <div class="field"><label>Karta egasi</label><input type="text" name="uzcard_card_name" value="<?= e($grouped['payments']['uzcard_card_name'] ?? '') ?>" placeholder="FAMILIYA ISM"></div>
            </div>
        </div>
    </div>
</div>

<!-- VISA -->
<?php $is_on = ($grouped['payments']['payment_visa_active'] ?? '0') === '1'; ?>
<div class="card pay-card-pro <?= $is_on ? 'pcp-active' : 'pcp-inactive' ?>" data-pay-card="payment_visa_active">
    <div class="pcp-header">
        <div class="pcp-icon" style="background:rgba(26,31,113,0.08);color:#1a1f71">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/><path d="M15 15h3"/></svg>
        </div>
        <div class="pcp-info">
            <h3 class="pcp-name">Visa</h3>
            <p class="pcp-desc">Xalqaro karta tizimi</p>
        </div>
        <div class="pcp-right">
            <div class="pay-status-badge <?= $is_on ? 'badge-on' : 'badge-off' ?>" id="badge_payment_visa_active"><?= $is_on ? 'Faol' : "O'chiq" ?></div>
            <div class="toggle-switch <?= $is_on ? 'on' : '' ?>" data-field="payment_visa_active"></div>
            <input type="hidden" name="payment_visa_active" id="toggle_payment_visa_active" value="<?= $is_on ? '1' : '0' ?>">
        </div>
    </div>
    <div class="pay-fields <?= $is_on ? '' : 'pay-fields-hidden' ?>" id="fields_payment_visa_active">
        <div class="pcp-body">
            <div class="field-row">
                <div class="field"><label>Karta raqami</label><input type="text" name="visa_card_number" value="<?= e($grouped['payments']['visa_card_number'] ?? '') ?>" placeholder="4XXX XXXX XXXX XXXX" maxlength="19"></div>
                <div class="field"><label>Karta egasi</label><input type="text" name="visa_card_name" value="<?= e($grouped['payments']['visa_card_name'] ?? '') ?>" placeholder="FAMILIYA ISM"></div>
            </div>
        </div>
    </div>
</div>

<!-- BANK O'TKAZMA -->
<div class="pay-section-title">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
    Bank o'tkazmasi
</div>

<!-- KOMPANIYA HISOBI -->
<?php $is_on = ($grouped['payments']['payment_invoice_active'] ?? '0') === '1'; ?>
<div class="card pay-card-pro <?= $is_on ? 'pcp-active' : 'pcp-inactive' ?>" data-pay-card="payment_invoice_active">
    <div class="pcp-header">
        <div class="pcp-icon" style="background:rgba(99,102,241,0.08);color:#6366f1">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
        </div>
        <div class="pcp-info">
            <h3 class="pcp-name">Bank o'tkazma</h3>
            <p class="pcp-desc">Kompaniya hisob raqamiga pul o'tkazish</p>
        </div>
        <div class="pcp-right">
            <div class="pay-status-badge <?= $is_on ? 'badge-on' : 'badge-off' ?>" id="badge_payment_invoice_active"><?= $is_on ? 'Faol' : "O'chiq" ?></div>
            <div class="toggle-switch <?= $is_on ? 'on' : '' ?>" data-field="payment_invoice_active"></div>
            <input type="hidden" name="payment_invoice_active" id="toggle_payment_invoice_active" value="<?= $is_on ? '1' : '0' ?>">
        </div>
    </div>
    <div class="pay-fields <?= $is_on ? '' : 'pay-fields-hidden' ?>" id="fields_payment_invoice_active">
        <div class="pcp-body">
            <div class="field-row">
                <div class="field"><label>Kompaniya nomi</label><input type="text" name="company_name" value="<?= e($grouped['company']['company_name'] ?? '') ?>" placeholder="OOO VATANPARVAR"></div>
                <div class="field"><label>INN</label><input type="text" name="company_inn" value="<?= e($grouped['company']['company_inn'] ?? '') ?>" placeholder="300123456"></div>
            </div>
            <div class="field-row">
                <div class="field"><label>Hisob raqami</label><input type="text" name="company_account" value="<?= e($grouped['company']['company_account'] ?? '') ?>" placeholder="20208000900123456789"></div>
                <div class="field"><label>Bank nomi</label><input type="text" name="company_bank" value="<?= e($grouped['company']['company_bank'] ?? '') ?>" placeholder="Hamkorbank filiali"></div>
            </div>
            <div class="field"><label>MFO</label><input type="text" name="company_mfo" value="<?= e($grouped['company']['company_mfo'] ?? '') ?>" placeholder="00427"></div>
        </div>
    </div>
</div>
