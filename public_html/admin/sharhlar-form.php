<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

$id = (int)vpy_get('id');
$review = $id ? vpy_find('sharhlar', 'id', $id) : null;
$is_edit = !empty($review);

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    $row = $review ?: ['id' => vpy_id_next('sharhlar'), 'created_at' => date('Y-m-d H:i:s')];
    $row['name'] = vpy_post('name');
    $row['city'] = vpy_post('city', 'Yaypan');
    $row['rating'] = max(1, min(5, (int)vpy_post('rating', 5)));
    $row['text'] = vpy_post('text');
    $row['text_cyrl'] = vpy_post('text_cyrl');
    $row['status'] = vpy_post('status', 'approved');
    $row['featured'] = vpy_post('featured') === '1';
    vpy_upsert('sharhlar', $row);
    vpy_flash_set('success', $is_edit ? t('msg_updated') : t('msg_added'));
    vpy_redirect('/admin/sharhlar.php');
}

vpy_panel_head($is_edit ? t('admin_edit') : t('admin_add'));
vpy_panel_sidebar('sharhlar', true);
?>
<main class="main">
<?php vpy_panel_topbar($is_edit ? t('admin_edit') : t('admin_add'), t('admin_reviews'),
    '<a href="/admin/sharhlar.php" class="btn btn-ghost">' . e(t('btn_back')) . '</a>'
); ?>
<div class="card" style="max-width:780px">
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
        <div class="field-row">
            <div class="field"><label>Ism</label><input type="text" name="name" value="<?= e($review['name'] ?? '') ?>" required></div>
            <div class="field"><label>Shahar</label><input type="text" name="city" value="<?= e($review['city'] ?? 'Yaypan') ?>"></div>
        </div>
        <div class="field-row">
            <div class="field"><label>Baho (1-5)</label><input type="number" name="rating" min="1" max="5" value="<?= (int)($review['rating'] ?? 5) ?>" required></div>
            <div class="field">
                <label>Status</label>
                <select name="status" style="width:100%;padding:13px 18px;border-radius:14px;border:1px solid var(--border-strong);background:rgba(255,253,249,0.85)">
                    <option value="approved" <?= ($review['status'] ?? 'approved') === 'approved' ? 'selected' : '' ?>>Tasdiqlangan</option>
                    <option value="pending" <?= ($review['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Kutilmoqda</option>
                    <option value="rejected" <?= ($review['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Bekor qilingan</option>
                </select>
            </div>
        </div>
        <div class="field"><label>Sharh matni (lotin)</label><textarea name="text" required rows="4"><?= e($review['text'] ?? '') ?></textarea></div>
        <div class="field"><label>Sharh matni (kirill)</label><textarea name="text_cyrl" rows="4"><?= e($review['text_cyrl'] ?? '') ?></textarea></div>
        <label style="display:flex;gap:8px;align-items:center;font-weight:500;margin:14px 0"><input type="checkbox" name="featured" value="1" <?= !empty($review['featured']) ? 'checked' : '' ?>> Bosh sahifada ko'rsatish</label>
        <div style="display:flex;gap:10px">
            <button type="submit" class="btn btn-primary"><?= e(t('btn_save')) ?></button>
            <a href="/admin/sharhlar.php" class="btn btn-ghost"><?= e(t('btn_cancel')) ?></a>
        </div>
    </form>
</div>
</main>
<?php vpy_panel_foot(); ?>
