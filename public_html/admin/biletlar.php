<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

$pdo = vpy_pdo();

// Yangi bilet qo'shish (AJAX yoki form)
if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    $action = vpy_post('action');
    
    if ($action === 'add_bilet') {
        // Yangi bilet uchun bilet_id ni topamiz
        $max_bilet = 62;
        if ($pdo) {
            try {
                $row = $pdo->query("SELECT MAX(bilet_id) as max_id FROM test_savollar")->fetch();
                if ($row && (int)$row['max_id'] > $max_bilet) $max_bilet = (int)$row['max_id'];
            } catch (Exception $e) {}
        }
        // faol_biletlar.json dan ham total_bilets ni tekshiramiz
        $ab = vpy_read_json('faol_biletlar', []);
        if (isset($ab['total_bilets']) && (int)$ab['total_bilets'] > $max_bilet) {
            $max_bilet = (int)$ab['total_bilets'];
        }
        $new_bilet_id = $max_bilet + 1;
        
        // Yangi bilet sonini JSON ga saqlaymiz (karta ko'rinishi uchun)
        $ab['total_bilets'] = $new_bilet_id;
        vpy_write_json('faol_biletlar', $ab);
        
        vpy_flash_set('success', 'Bilet #' . sprintf('%02d', $new_bilet_id) . ' qo\'shildi! Savollar qo\'shing.');
        vpy_redirect('/admin/savollar-form.php?bilet_id=' . $new_bilet_id);
    }
    
    if ($action === 'toggle_active') {
        $bilet_id = (int)vpy_post('bilet_id');
        $active_bilets = vpy_read_json('faol_biletlar', ['active' => range(1, 15)]);
        $active_list = $active_bilets['active'] ?? [];
        
        if (in_array($bilet_id, $active_list)) {
            $active_list = array_values(array_diff($active_list, [$bilet_id]));
        } else {
            $active_list[] = $bilet_id;
            sort($active_list);
        }
        
        $active_bilets['active'] = $active_list;
        vpy_write_json('faol_biletlar', $active_bilets);
        vpy_flash_set('success', 'Faol biletlar yangilandi');
        vpy_redirect('/admin/biletlar.php');
    }
    
    if ($action === 'set_active_count') {
        $count = max(1, min(100, (int)vpy_post('active_count')));
        $active_list = range(1, $count);
        $existing = vpy_read_json('faol_biletlar', []);
        $existing['active'] = $active_list;
        vpy_write_json('faol_biletlar', $existing);
        vpy_flash_set('success', 'Dastlabki ' . $count . ' ta bilet faollashtirildi');
        vpy_redirect('/admin/biletlar.php');
    }
}

$bilet_data = [];
if ($pdo) {
    try {
        foreach ($pdo->query("SELECT bilet_id, COUNT(*) as cnt FROM test_savollar GROUP BY bilet_id ORDER BY bilet_id")->fetchAll() as $r) {
            $bilet_data[(int)$r['bilet_id']] = (int)$r['cnt'];
        }
    } catch (Exception $e) {}
}

// Faol biletlar ro'yxatini o'qish
$active_bilets_data = vpy_read_json('faol_biletlar', []);
if (empty($active_bilets_data) || !isset($active_bilets_data['active'])) {
    // Dastlabki 15 biletni faol qilib yozamiz
    $active_bilets_data = ['active' => range(1, 15)];
    vpy_write_json('faol_biletlar', $active_bilets_data);
}
$active_bilets = $active_bilets_data['active'] ?? [];

// total_bilets ni DB dan va JSON dan eng kattasini olamiz
$total_from_db = empty($bilet_data) ? 62 : max(array_keys($bilet_data));
$total_from_json = isset($active_bilets_data['total_bilets']) ? (int)$active_bilets_data['total_bilets'] : 62;
$total_bilets = max(62, $total_from_db, $total_from_json);

vpy_panel_head(t('admin_tickets'), <<<CSS
.bilet-toolbar{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:18px;padding:14px 18px;background:var(--glass);backdrop-filter:blur(16px);border:1px solid var(--border);border-radius:14px}
.bilet-toolbar .btn{white-space:nowrap}
.active-badge{position:absolute;top:8px;right:8px;width:10px;height:10px;border-radius:50%;background:#10b981;box-shadow:0 0 6px rgba(16,185,129,0.5);z-index:2}
.inactive-badge{position:absolute;top:8px;right:8px;width:10px;height:10px;border-radius:50%;background:var(--muted);opacity:0.4;z-index:2}
.bilet-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px}
.b-card{padding:22px;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r);text-align:center;text-decoration:none;color:inherit;transition:var(--t);position:relative;overflow:hidden}
.b-card:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:var(--primary)}
.b-card.ready{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-color:transparent}
.b-card.active-bilet{border-color:#10b981;box-shadow:0 0 0 1px #10b981}
.b-card.ready.active-bilet{box-shadow:0 4px 16px rgba(16,185,129,0.3)}
.b-num{font-family:var(--serif);font-size:2rem;font-weight:700;line-height:1}
.b-card.ready .b-num{color:#fff}
.b-meta{font-size:0.78rem;color:var(--muted);margin-top:6px;text-transform:uppercase;letter-spacing:0.06em;font-weight:600}
.b-card.ready .b-meta{color:rgba(255,253,249,0.7)}
.b-cnt{font-size:0.92rem;color:var(--dark);font-weight:600;margin-top:8px}
.b-card.ready .b-cnt{color:rgba(255,253,249,0.95)}
.toggle-active-btn{position:absolute;top:6px;left:6px;width:26px;height:26px;border-radius:8px;background:rgba(0,0,0,0.05);border:1px solid var(--border);display:grid;place-items:center;cursor:pointer;opacity:0;transition:var(--t);z-index:3}
.b-card:hover .toggle-active-btn{opacity:1}
.toggle-active-btn:hover{background:rgba(16,185,129,0.15);border-color:#10b981}
.toggle-active-btn svg{width:14px;height:14px}
.add-bilet-card{padding:22px;background:var(--surface);border:2px dashed var(--border-strong);border-radius:var(--r);text-align:center;cursor:pointer;transition:var(--t);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;min-height:120px}
.add-bilet-card:hover{border-color:var(--primary);background:var(--blue-soft);transform:translateY(-4px)}
.add-bilet-card svg{width:32px;height:32px;color:var(--primary)}
.add-bilet-card span{font-size:0.82rem;font-weight:700;color:var(--primary)}
@media (max-width:480px){.bilet-grid{grid-template-columns:repeat(2,1fr)}}
CSS);
vpy_panel_sidebar('biletlar', true);
?>
<main class="main">
<?php vpy_panel_topbar(t('admin_tickets'), count($bilet_data) . ' / ' . $total_bilets . ' bilet',
    '<a href="/admin/savollar-form.php" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>' . e(t('admin_add')) . ' savol</a>'
); ?>

<!-- Toolbar -->
<div class="bilet-toolbar">
    <form method="post" style="display:flex;gap:8px;align-items:center">
        <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
        <input type="hidden" name="action" value="set_active_count">
        <label style="font-size:0.85rem;font-weight:600;white-space:nowrap">Faol biletlar soni:</label>
        <input type="number" name="active_count" value="<?= count($active_bilets) ?>" min="1" max="<?= $total_bilets ?>" style="width:70px;padding:8px 12px;border-radius:10px;border:1px solid var(--border-strong);font-weight:700;text-align:center">
        <button type="submit" class="btn btn-primary" style="padding:8px 16px;font-size:0.82rem">Belgilash</button>
    </form>
    <div style="flex:1"></div>
    <span style="font-size:0.78rem;color:var(--muted)"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981;margin-right:4px"></span>Faol: <?= count($active_bilets) ?> ta</span>
    <form method="post" style="display:inline">
        <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
        <input type="hidden" name="action" value="add_bilet">
        <button type="submit" class="btn btn-primary" style="padding:8px 16px;font-size:0.82rem">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" style="width:14px;height:14px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Yangi bilet
        </button>
    </form>
</div>

<div class="card">
    <div class="card-head"><h2><?= $total_bilets ?> ta bilet · har biri 20 savol</h2></div>
    <div class="bilet-grid">
        <?php for ($i = 1; $i <= $total_bilets; $i++):
            $cnt = $bilet_data[$i] ?? 0;
            $ready = $cnt >= 20;
            $is_active = in_array($i, $active_bilets);
        ?>
            <a href="/admin/savollar.php?bilet=<?= $i ?>" class="b-card <?= $ready ? 'ready' : '' ?> <?= $is_active ? 'active-bilet' : '' ?>">
                <?php if ($is_active): ?>
                    <div class="active-badge" title="Faol"></div>
                <?php else: ?>
                    <div class="inactive-badge" title="Nofaol"></div>
                <?php endif; ?>
                <form method="post" class="toggle-active-btn" onclick="event.preventDefault();event.stopPropagation();this.submit();" title="<?= $is_active ? 'Nofaol qilish' : 'Faollashtirish' ?>">
                    <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="bilet_id" value="<?= $i ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="<?= $is_active ? '#10b981' : 'currentColor' ?>" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg>
                </form>
                <div class="b-meta">Bilet</div>
                <div class="b-num"><?= sprintf('%02d', $i) ?></div>
                <div class="b-cnt"><?= $cnt ?> / 20 savol</div>
            </a>
        <?php endfor; ?>

        <!-- Yangi bilet qo'shish tugmasi -->
        <form method="post" class="add-bilet-card" onclick="this.submit()">
            <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
            <input type="hidden" name="action" value="add_bilet">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Yangi bilet qo'shish</span>
        </form>
    </div>
</div>
</main>
<?php vpy_panel_foot(); ?>
