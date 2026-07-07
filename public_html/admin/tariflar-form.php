<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

$id = (int)vpy_get('id');
$tariff = $id ? vpy_find('tariflar', 'id', $id) : null;
$is_edit = !empty($tariff);

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    $row = $tariff ?: [];
    $row['id'] = $id ?: vpy_id_next('tariflar');
    $row['key'] = vpy_post('key', vpy_slug(vpy_post('name')));
    $row['name'] = vpy_post('name');
    $row['name_cyrl'] = vpy_post('name_cyrl');
    $row['description'] = vpy_post('description');
    $row['description_cyrl'] = vpy_post('description_cyrl');
    $row['price'] = (float)vpy_post('price');
    $row['old_price'] = (float)vpy_post('old_price');
    $row['duration_days'] = (int)vpy_post('duration_days', 30);
    $row['min_days'] = (int)vpy_post('min_days', 1);
    $row['max_days'] = (int)vpy_post('max_days', 100);
    $row['price_per_day'] = (float)vpy_post('price_per_day', 0);
    $row['period_label'] = vpy_post('period_label', '1 oy');
    $row['period_label_cyrl'] = vpy_post('period_label_cyrl', '1 ой');
    $row['active'] = vpy_post('active') === '1';
    $row['popular'] = vpy_post('popular') === '1';
    $row['highlight'] = vpy_post('highlight') === '1';
    $row['sort'] = (int)vpy_post('sort', 1);
    $row['features'] = array_values(array_filter(array_map('trim', explode("\n", vpy_post('features')))));
    $row['features_cyrl'] = array_values(array_filter(array_map('trim', explode("\n", vpy_post('features_cyrl')))));
    if (!isset($row['created_at'])) $row['created_at'] = date('Y-m-d H:i:s');
    vpy_upsert('tariflar', $row);
    vpy_flash_set('success', $is_edit ? t('msg_updated') : t('msg_added'));
    vpy_redirect('/admin/tariflar.php');
}

vpy_panel_head($is_edit ? t('admin_edit') : t('admin_add'));
vpy_panel_sidebar('tariflar', true);
?>
<main class="main">
<?php vpy_panel_topbar(
    $is_edit ? t('admin_edit') . ' · ' . $tariff['name'] : t('admin_add'),
    t('admin_tariffs'),
    '<a href="/admin/tariflar.php" class="btn btn-ghost">' . e(t('btn_back')) . '</a>'
); ?>

<form method="post">
    <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
    <div class="card">
        <div class="card-head"><h2>Asosiy ma'lumot</h2></div>
        <div class="field-row">
            <div class="field"><label>Nomi (lotin)</label><input type="text" name="name" value="<?= e($tariff['name'] ?? '') ?>" required></div>
            <div class="field"><label>Nomi (kirill)</label><input type="text" name="name_cyrl" value="<?= e($tariff['name_cyrl'] ?? '') ?>"></div>
        </div>
        <div class="field-row">
            <div class="field"><label>Tavsif (lotin)</label><textarea name="description"><?= e($tariff['description'] ?? '') ?></textarea></div>
            <div class="field"><label>Tavsif (kirill)</label><textarea name="description_cyrl"><?= e($tariff['description_cyrl'] ?? '') ?></textarea></div>
        </div>
        <div class="field-row">
            <div class="field"><label>Kalit (slug)</label><input type="text" name="key" value="<?= e($tariff['key'] ?? '') ?>" placeholder="basic, standard, premium"></div>
            <div class="field"><label>Tartib</label><input type="number" name="sort" value="<?= (int)($tariff['sort'] ?? 1) ?>" min="0"></div>
        </div>
    </div>

    <div class="card" style="margin-top:18px">
        <div class="card-head"><h2>Narx va muddat</h2></div>
        <div class="field-row">
            <div class="field"><label>Narx (so'm)</label><input type="number" name="price" value="<?= (int)($tariff['price'] ?? 0) ?>" required min="0"></div>
            <div class="field"><label>Eski narx (chizilgan)</label><input type="number" name="old_price" value="<?= (int)($tariff['old_price'] ?? 0) ?>" min="0"></div>
        </div>
        <div class="field-row">
            <div class="field"><label>Muddat (kun)</label><input type="number" name="duration_days" value="<?= (int)($tariff['duration_days'] ?? 30) ?>" required min="1"></div>
            <div class="field"><label>Kunlik narx (so'm)</label><input type="number" name="price_per_day" value="<?= (int)($tariff['price_per_day'] ?? 0) ?>" min="0"></div>
        </div>
        <div class="field-row">
            <div class="field"><label>Min kunlar</label><input type="number" name="min_days" value="<?= (int)($tariff['min_days'] ?? 1) ?>" min="1"></div>
            <div class="field"><label>Max kunlar</label><input type="number" name="max_days" value="<?= (int)($tariff['max_days'] ?? 100) ?>" min="1"></div>
        </div>
        <div class="field-row">
            <div class="field"><label>Davr yorlig'i (lotin)</label><input type="text" name="period_label" value="<?= e($tariff['period_label'] ?? '1 oy') ?>"></div>
            <div class="field"><label>Davr yorlig'i (kirill)</label><input type="text" name="period_label_cyrl" value="<?= e($tariff['period_label_cyrl'] ?? '1 ой') ?>"></div>
        </div>
    </div>

    <div class="card" style="margin-top:18px">
        <div class="card-head"><h2>Imkoniyatlar (har bir qator alohida)</h2></div>
        <div class="field-row">
            <div class="field">
                <label>Lotin</label>
                <textarea name="features" rows="6"><?= e(implode("\n", (array)($tariff['features'] ?? []))) ?></textarea>
            </div>
            <div class="field">
                <label>Kirill</label>
                <textarea name="features_cyrl" rows="6"><?= e(implode("\n", (array)($tariff['features_cyrl'] ?? []))) ?></textarea>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:18px">
        <div class="card-head"><h2>Holat</h2></div>
        <div style="display:flex;gap:24px;flex-wrap:wrap">
            <label style="display:flex;gap:8px;align-items:center;font-weight:500"><input type="checkbox" name="active" value="1" <?= !empty($tariff['active']) || !$is_edit ? 'checked' : '' ?>> Faol</label>
            <label style="display:flex;gap:8px;align-items:center;font-weight:500"><input type="checkbox" name="popular" value="1" <?= !empty($tariff['popular']) ? 'checked' : '' ?>> Mashhur (badge)</label>
            <label style="display:flex;gap:8px;align-items:center;font-weight:500"><input type="checkbox" name="highlight" value="1" <?= !empty($tariff['highlight']) ? 'checked' : '' ?>> Featured (markazda)</label>
        </div>
    </div>

    <div style="display:flex;gap:10px;margin-top:18px">
        <button type="submit" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg><?= e(t('btn_save')) ?></button>
        <a href="/admin/tariflar.php" class="btn btn-ghost"><?= e(t('btn_cancel')) ?></a>
    </div>
</form>
</main>
<?php vpy_panel_foot(); ?>
