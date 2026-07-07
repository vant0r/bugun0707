<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

$id = (int)vpy_get('id');
$post = $id ? vpy_find('blog', 'id', $id) : null;
$is_edit = !empty($post);

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    $row = $post ?: ['id' => vpy_id_next('blog'), 'created_at' => date('Y-m-d H:i:s'), 'views' => 0];
    $row['title'] = vpy_post('title');
    $row['title_cyrl'] = vpy_post('title_cyrl');
    $row['slug'] = vpy_post('slug') ?: vpy_slug(vpy_post('title'));
    $row['excerpt'] = vpy_post('excerpt');
    $row['excerpt_cyrl'] = vpy_post('excerpt_cyrl');
    $row['content'] = vpy_post('content');
    $row['content_cyrl'] = vpy_post('content_cyrl');
    $row['category'] = vpy_post('category', 'Yangiliklar');
    $row['category_cyrl'] = vpy_post('category_cyrl');
    $row['tags'] = array_values(array_filter(array_map('trim', explode(',', vpy_post('tags')))));
    $row['author'] = vpy_post('author', vpy_user()['name']);
    $row['read_time'] = max(1, (int)vpy_post('read_time', 5));
    $row['status'] = vpy_post('status', 'draft');
    $row['featured'] = vpy_post('featured') === '1';
    vpy_upsert('blog', $row);
    vpy_flash_set('success', $is_edit ? t('msg_updated') : t('msg_added'));
    vpy_redirect('/admin/blog.php');
}

vpy_panel_head($is_edit ? t('admin_edit') : t('admin_add'));
vpy_panel_sidebar('blog', true);
?>
<main class="main">
<?php vpy_panel_topbar($is_edit ? t('admin_edit') : t('admin_add'), t('admin_blog'),
    '<a href="/admin/blog.php" class="btn btn-ghost">' . e(t('btn_back')) . '</a>'
); ?>

<form method="post">
    <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
    <div class="card">
        <div class="card-head"><h2>Sarlavha va URL</h2></div>
        <div class="field-row">
            <div class="field"><label>Sarlavha (lotin)</label><input type="text" name="title" value="<?= e($post['title'] ?? '') ?>" required></div>
            <div class="field"><label>Sarlavha (kirill)</label><input type="text" name="title_cyrl" value="<?= e($post['title_cyrl'] ?? '') ?>"></div>
        </div>
        <div class="field-row">
            <div class="field"><label>Slug (URL)</label><input type="text" name="slug" value="<?= e($post['slug'] ?? '') ?>" placeholder="auto"></div>
            <div class="field"><label>Kategoriya</label><input type="text" name="category" value="<?= e($post['category'] ?? '') ?>"></div>
        </div>
    </div>

    <div class="card" style="margin-top:18px">
        <div class="card-head"><h2>Qisqacha (excerpt)</h2></div>
        <div class="field-row">
            <div class="field"><label>Lotin</label><textarea name="excerpt" rows="3"><?= e($post['excerpt'] ?? '') ?></textarea></div>
            <div class="field"><label>Kirill</label><textarea name="excerpt_cyrl" rows="3"><?= e($post['excerpt_cyrl'] ?? '') ?></textarea></div>
        </div>
    </div>

    <div class="card" style="margin-top:18px">
        <div class="card-head"><h2>Asosiy matn</h2></div>
        <div class="field-row">
            <div class="field"><label>Lotin</label><textarea name="content" rows="10"><?= e($post['content'] ?? '') ?></textarea></div>
            <div class="field"><label>Kirill</label><textarea name="content_cyrl" rows="10"><?= e($post['content_cyrl'] ?? '') ?></textarea></div>
        </div>
    </div>

    <div class="card" style="margin-top:18px">
        <div class="card-head"><h2>Meta</h2></div>
        <div class="field-row">
            <div class="field"><label>Muallif</label><input type="text" name="author" value="<?= e($post['author'] ?? vpy_user()['name']) ?>"></div>
            <div class="field"><label>O'qish vaqti (daqiqa)</label><input type="number" name="read_time" value="<?= (int)($post['read_time'] ?? 5) ?>" min="1"></div>
        </div>
        <div class="field-row">
            <div class="field"><label>Teglar (vergul bilan)</label><input type="text" name="tags" value="<?= e(implode(', ', (array)($post['tags'] ?? []))) ?>"></div>
            <div class="field">
                <label>Status</label>
                <select name="status" style="width:100%;padding:13px 18px;border-radius:14px;border:1px solid var(--border-strong);background:rgba(255,253,249,0.85)">
                    <option value="draft" <?= ($post['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Qoralama</option>
                    <option value="published" <?= ($post['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Chop etilgan</option>
                </select>
            </div>
        </div>
        <label style="display:flex;gap:8px;align-items:center;font-weight:500"><input type="checkbox" name="featured" value="1" <?= !empty($post['featured']) ? 'checked' : '' ?>> Bosh sahifada featured</label>
    </div>

    <div style="display:flex;gap:10px;margin-top:18px">
        <button type="submit" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg><?= e(t('btn_save')) ?></button>
        <a href="/admin/blog.php" class="btn btn-ghost"><?= e(t('btn_cancel')) ?></a>
    </div>
</form>
</main>
<?php vpy_panel_foot(); ?>
