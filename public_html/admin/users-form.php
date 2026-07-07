<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

$id = (int)vpy_get('id');
$user = $id ? vpy_find('users', 'id', $id) : null;
$is_edit = !empty($user);

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    $row = $user ?: ['id' => vpy_id_next('users'), 'created_at' => date('Y-m-d H:i:s'), 'tests_taken' => 0, 'best_score' => 0, 'balance' => 0];
    $row['name'] = vpy_post('name');
    $phone = vpy_phone_normalize(vpy_post('phone'));
    if (!$is_edit && vpy_find('users', 'phone', $phone)) {
        vpy_flash_set('error', t('xato_telefon_band'));
    } else {
        $row['phone'] = $phone;
        $row['role'] = vpy_post('role', 'user');
        $row['status'] = vpy_post('status', 'active');
        $row['referral_code'] = vpy_post('referral_code') ?: ($row['referral_code'] ?? vpy_referral_code(8));
        $pwd = vpy_post('password');
        if ($pwd) $row['password'] = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => VPY_HASH_COST]);
        elseif (empty($row['password'])) $row['password'] = password_hash(vpy_random_string(8), PASSWORD_BCRYPT, ['cost' => VPY_HASH_COST]);
        vpy_upsert('users', $row);
        vpy_flash_set('success', $is_edit ? t('msg_updated') : t('msg_added'));
        vpy_redirect('/admin/users.php');
    }
}

vpy_panel_head($is_edit ? t('admin_edit') . ' · ' . $user['name'] : t('admin_add'));
vpy_panel_sidebar('users', true);
?>
<main class="main">
<?php vpy_panel_topbar(
    $is_edit ? t('admin_edit') : t('admin_add'),
    $is_edit ? '#' . $user['id'] : t('admin_users'),
    '<a href="/admin/users.php" class="btn btn-ghost">' . e(t('btn_back')) . '</a>'
); ?>

<div class="card" style="max-width:780px">
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
        <div class="field-row">
            <div class="field"><label><?= e(t('auth_name')) ?></label><input type="text" name="name" value="<?= e($user['name'] ?? '') ?>" required></div>
            <div class="field"><label><?= e(t('auth_phone')) ?></label><input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>" required></div>
        </div>
        <div class="field-row">
            <div class="field">
                <label>Rol</label>
                <select name="role" class="field-input" style="width:100%;padding:13px 18px;border-radius:14px;border:1px solid var(--border-strong);background:rgba(255,253,249,0.85);font-size:0.94rem">
                    <option value="user" <?= ($user['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>Foydalanuvchi</option>
                    <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrator</option>
                </select>
            </div>
            <div class="field">
                <label><?= e(t('admin_status_active')) ?></label>
                <select name="status" style="width:100%;padding:13px 18px;border-radius:14px;border:1px solid var(--border-strong);background:rgba(255,253,249,0.85);font-size:0.94rem">
                    <option value="active" <?= ($user['status'] ?? 'active') === 'active' ? 'selected' : '' ?>><?= e(t('admin_status_active')) ?></option>
                    <option value="blocked" <?= ($user['status'] ?? '') === 'blocked' ? 'selected' : '' ?>><?= e(t('admin_status_blocked')) ?></option>
                </select>
            </div>
        </div>
        <div class="field-row">
            <div class="field"><label>Referal kod</label><input type="text" name="referral_code" value="<?= e($user['referral_code'] ?? '') ?>" maxlength="12" style="text-transform:uppercase"></div>
            <div class="field"><label><?= $is_edit ? 'Yangi parol (bo\'sh qoldiring agar o\'zgarmasa)' : t('auth_password') ?></label><input type="password" name="password" <?= $is_edit ? '' : 'required' ?> minlength="6"></div>
        </div>
        <div style="display:flex;gap:10px;margin-top:14px">
            <button type="submit" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><?= e(t('btn_save')) ?></button>
            <a href="/admin/users.php" class="btn btn-ghost"><?= e(t('btn_cancel')) ?></a>
        </div>
    </form>
</div>
</main>
<?php vpy_panel_foot(); ?>
