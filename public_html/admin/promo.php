<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

// Delete
if (vpy_get('delete') && vpy_csrf_check(vpy_get('token'))) {
    vpy_delete('promo_kodlar', 'id', (int)vpy_get('delete'));
    vpy_flash_set('success', t('msg_deleted'));
    vpy_redirect('/admin/promo.php');
}

// Create / Edit
if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    $id = (int)vpy_post('id');
    $row = $id ? vpy_find('promo_kodlar', 'id', $id) : [];
    $row['id'] = $id ?: vpy_id_next('promo_kodlar');
    $row['code'] = strtoupper(trim(vpy_post('code')));
    $row['type'] = vpy_post('type', 'discount_percent');
    $row['value'] = (int)vpy_post('value', 0);
    $row['max_uses'] = (int)vpy_post('max_uses', 100);
    $row['used_count'] = $row['used_count'] ?? 0;
    $row['active'] = vpy_post('active') === '1';
    $row['expires_at'] = vpy_post('expires_at', '2026-12-31 23:59:59');
    if (!isset($row['created_at'])) $row['created_at'] = date('Y-m-d H:i:s');
    vpy_upsert('promo_kodlar', $row);
    vpy_flash_set('success', $id ? t('msg_updated') : t('msg_added'));
    vpy_redirect('/admin/promo.php');
}

$promos = vpy_read_json('promo_kodlar', []);
usort($promos, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

$editing = null;
if (vpy_get('edit')) $editing = vpy_find('promo_kodlar', 'id', (int)vpy_get('edit'));

vpy_panel_head('Promo kodlar');
vpy_panel_sidebar('sozlamalar', true);
?>
<main class="main">
<?php vpy_panel_topbar('Promo kodlar', 'Promo kodlarni boshqarish'); ?>

<!-- FORM -->
<div class="card" style="margin-bottom:20px">
    <div class="card-head"><h2><?= $editing ? 'Tahrirlash' : 'Yangi promo kod' ?></h2></div>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>
        <div class="field-row">
            <div class="field"><label>Kod</label><input type="text" name="code" value="<?= e($editing['code'] ?? '') ?>" required placeholder="BEPUL2026" style="text-transform:uppercase"></div>
            <div class="field">
                <label>Turi</label>
                <select name="type">
                    <option value="discount_percent" <?= ($editing['type'] ?? '') === 'discount_percent' ? 'selected' : '' ?>>Chegirma (%)</option>
                    <option value="discount_amount" <?= ($editing['type'] ?? '') === 'discount_amount' ? 'selected' : '' ?>>Chegirma (so'm)</option>
                    <option value="free_ticket" <?= ($editing['type'] ?? '') === 'free_ticket' ? 'selected' : '' ?>>Bepul bilet</option>
                    <option value="free_days" <?= ($editing['type'] ?? '') === 'free_days' ? 'selected' : '' ?>>Bepul kunlar</option>
                </select>
            </div>
        </div>
        <div class="field-row">
            <div class="field"><label>Qiymat</label><input type="number" name="value" value="<?= (int)($editing['value'] ?? 0) ?>" min="0" placeholder="50"></div>
            <div class="field"><label>Max foydalanish</label><input type="number" name="max_uses" value="<?= (int)($editing['max_uses'] ?? 100) ?>" min="1"></div>
        </div>
        <div class="field-row">
            <div class="field"><label>Amal qilish muddati</label><input type="date" name="expires_at" value="<?= e(substr($editing['expires_at'] ?? '2026-12-31', 0, 10)) ?>"></div>
            <div class="field" style="display:flex;align-items:end;padding-bottom:4px">
                <label style="display:flex;gap:8px;align-items:center;font-weight:600"><input type="checkbox" name="active" value="1" <?= !empty($editing['active']) || !$editing ? 'checked' : '' ?>> Faol</label>
            </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:10px">
            <button type="submit" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg><?= e(t('btn_save')) ?></button>
            <?php if ($editing): ?><a href="/admin/promo.php" class="btn btn-ghost"><?= e(t('btn_cancel')) ?></a><?php endif; ?>
        </div>
    </form>
</div>

<!-- TABLE -->
<div class="card">
    <div class="card-head"><h2>Barcha promo kodlar (<?= count($promos) ?>)</h2></div>
    <?php if (empty($promos)): ?>
        <div class="empty"><p>Hali promo kod yo'q</p></div>
    <?php else: ?>
    <table class="tbl">
        <thead><tr><th>Kod</th><th>Turi</th><th>Qiymat</th><th>Foydalanish</th><th>Holat</th><th>Muddat</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($promos as $p): ?>
        <tr>
            <td><strong style="font-family:monospace;letter-spacing:0.05em"><?= e($p['code']) ?></strong></td>
            <td><?php
                $types = ['discount_percent'=>'Chegirma %','discount_amount'=>"Chegirma so'm",'free_ticket'=>'Bepul bilet','free_days'=>'Bepul kun'];
                echo e($types[$p['type']] ?? $p['type']);
            ?></td>
            <td><?= (int)$p['value'] ?><?= $p['type'] === 'discount_percent' ? '%' : '' ?></td>
            <td><?= (int)($p['used_count'] ?? 0) ?>/<?= (int)$p['max_uses'] ?></td>
            <td><?php if (!empty($p['active'])): ?><span class="chip chip-success">Faol</span><?php else: ?><span class="chip chip-muted">Nofaol</span><?php endif; ?></td>
            <td style="font-size:0.82rem"><?= e(substr($p['expires_at'] ?? '', 0, 10)) ?></td>
            <td>
                <div class="row-actions">
                    <a href="?edit=<?= (int)$p['id'] ?>" title="Tahrirlash"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
                    <a href="?delete=<?= (int)$p['id'] ?>&token=<?= e(vpy_csrf()) ?>" class="danger" onclick="return confirm('O\'chirilsinmi?')" title="O'chirish"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14H7L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg></a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</main>
<?php vpy_panel_foot(); ?>
