<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    $action = vpy_post('action');
    if ($action === 'delete') {
        $id = (int)vpy_post('id');
        if ($id !== (int)vpy_user()['id']) {
            vpy_delete('users', 'id', $id);
            vpy_flash_set('success', t('msg_deleted'));
        }
    } elseif ($action === 'block' || $action === 'unblock') {
        $id = (int)vpy_post('id');
        $u = vpy_find('users', 'id', $id);
        if ($u) {
            $u['status'] = $action === 'block' ? 'blocked' : 'active';
            vpy_upsert('users', $u);
            vpy_flash_set('success', t('msg_updated'));
        }
    }
    vpy_redirect('/admin/users.php');
}

$q = trim((string)vpy_get('q', ''));
$role_filter = vpy_get('role', '');
$users = vpy_read_json('users', []);
usort($users, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

$filtered = array_filter($users, function($u) use ($q, $role_filter) {
    if ($role_filter && ($u['role'] ?? '') !== $role_filter) return false;
    if ($q && stripos($u['name'].$u['phone'], $q) === false) return false;
    return true;
});

$page = max(1, (int)vpy_get('p', 1));
$pag = vpy_paginate(array_values($filtered), 20, $page);

vpy_panel_head(t('admin_users'));
vpy_panel_sidebar('users', true);
?>
<main class="main">
<?php vpy_panel_topbar(t('admin_users'), count($filtered) . ' / ' . count($users),
    '<a href="/admin/users-form.php" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>' . e(t('admin_add')) . '</a>'
); ?>

<div class="card">
    <form method="get" style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap">
        <div class="field" style="flex:1;min-width:200px;margin:0">
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('admin_search')) ?>...">
        </div>
        <select name="role" class="field" style="margin:0;min-width:140px;padding:13px 18px;border-radius:14px;border:1px solid var(--border-strong);background:rgba(255,253,249,0.85)">
            <option value="">Hammasi</option>
            <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>Foydalanuvchi</option>
            <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
        <button type="submit" class="btn btn-dark"><?= e(t('admin_search')) ?></button>
    </form>

    <?php if (empty($pag['items'])): ?>
        <div class="empty"><h3>Foydalanuvchi topilmadi</h3></div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead><tr><th>#</th><th><?= e(t('rating_user')) ?></th><th><?= e(t('auth_phone')) ?></th><th>Rol</th><th><?= e(t('count_tests')) ?></th><th><?= e(t('admin_status_active')) ?></th><th><?= e(t('invoice_date')) ?></th><th></th></tr></thead>
            <tbody>
                <?php foreach ($pag['items'] as $usr):
                    $color = vpy_avatar_color($usr['name']);
                ?>
                <tr>
                    <td>#<?= (int)$usr['id'] ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px">
                            <div style="width:34px;height:34px;border-radius:50%;background:<?= e($color) ?>;color:#fff;display:grid;place-items:center;font-weight:700;font-size:0.78rem"><?= e(vpy_user_initials($usr['name'])) ?></div>
                            <strong style="font-weight:600"><?= e($usr['name']) ?></strong>
                        </div>
                    </td>
                    <td><?= e($usr['phone']) ?></td>
                    <td>
                        <?php if (($usr['role'] ?? '') === 'admin'): ?>
                            <span class="chip chip-warning">Admin</span>
                        <?php else: ?>
                            <span class="chip chip-muted">User</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)($usr['tests_taken'] ?? 0) ?></td>
                    <td>
                        <?php if (($usr['status'] ?? 'active') === 'active'): ?>
                            <span class="chip chip-success"><?= e(t('admin_status_active')) ?></span>
                        <?php else: ?>
                            <span class="chip chip-danger"><?= e(t('admin_status_blocked')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= e(vpy_date($usr['created_at'], 'd.m.Y')) ?></td>
                    <td>
                        <div class="row-actions">
                            <a href="/admin/users-form.php?id=<?= (int)$usr['id'] ?>" title="<?= e(t('admin_edit')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
                            <?php if (($usr['status'] ?? 'active') === 'active'): ?>
                                <form method="post" style="display:inline" onsubmit="return confirm('Bloklash?')">
                                    <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                                    <input type="hidden" name="action" value="block">
                                    <input type="hidden" name="id" value="<?= (int)$usr['id'] ?>">
                                    <button type="submit" class="danger" title="Bloklash"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></button>
                                </form>
                            <?php else: ?>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                                    <input type="hidden" name="action" value="unblock">
                                    <input type="hidden" name="id" value="<?= (int)$usr['id'] ?>">
                                    <button type="submit" title="Faollashtirish"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></button>
                                </form>
                            <?php endif; ?>
                            <?php if ((int)$usr['id'] !== (int)vpy_user()['id']): ?>
                            <form method="post" style="display:inline" onsubmit="return confirm('O\'chirilsinmi?')">
                                <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$usr['id'] ?>">
                                <button type="submit" class="danger" title="<?= e(t('admin_delete')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 01-2 2H9a2 2 0 01-2-2L5 6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pag['pages'] > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $pag['pages']; $i++): ?>
            <?= $i === $page ? '<span class="active">' . $i . '</span>' : '<a href="?p=' . $i . '&q=' . urlencode($q) . '&role=' . urlencode($role_filter) . '">' . $i . '</a>' ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
</main>
<?php vpy_panel_foot(); ?>
