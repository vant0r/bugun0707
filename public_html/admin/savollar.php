<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

$pdo = vpy_pdo();

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf')) && vpy_post('action') === 'delete' && $pdo) {
    $st = $pdo->prepare("DELETE FROM test_savollar WHERE id = :id");
    $st->execute([':id' => (int)vpy_post('id')]);
    vpy_flash_set('success', t('msg_deleted'));
    vpy_redirect('/admin/savollar.php');
}

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf')) && vpy_post('action') === 'delete_bilet' && $pdo) {
    $bid_del = (int)vpy_post('bilet_id_del');
    if ($bid_del > 0) {
        $st = $pdo->prepare("DELETE FROM test_savollar WHERE bilet_id = :b");
        $st->execute([':b' => $bid_del]);
        $cnt_del = $st->rowCount();
        vpy_log('delete_bilet', 'Bilet bo\'yicha savollar o\'chirildi', ['bilet_id' => $bid_del, 'count' => $cnt_del]);
        vpy_flash_set('success', "Bilet #" . sprintf('%02d', $bid_del) . " dagi {$cnt_del} ta savol o'chirildi");
    }
    vpy_redirect('/admin/savollar.php');
}

$q = trim((string)vpy_get('q', ''));
$bilet = (int)vpy_get('bilet', 0);
$mavzu = vpy_get('mavzu', '');
$page = max(1, (int)vpy_get('p', 1));
$per_page = 30;

$rows = [];
$total = 0;
$mavzular = [];
$bilet_counts = [];
if ($pdo) {
    try {
        $mavzular = $pdo->query("SELECT mavzu, COUNT(*) as cnt FROM test_savollar GROUP BY mavzu ORDER BY mavzu")->fetchAll();
        foreach ($pdo->query("SELECT bilet_id, COUNT(*) as cnt FROM test_savollar GROUP BY bilet_id")->fetchAll() as $bc) {
            $bilet_counts[(int)$bc['bilet_id']] = (int)$bc['cnt'];
        }
        $where = [];
        $params = [];
        if ($q) { $where[] = '(savol LIKE :q OR variant_a LIKE :q)'; $params[':q'] = "%$q%"; }
        if ($bilet) { $where[] = 'bilet_id = :b'; $params[':b'] = $bilet; }
        if ($mavzu) { $where[] = 'mavzu = :m'; $params[':m'] = $mavzu; }
        $w = empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);
        $st = $pdo->prepare("SELECT COUNT(*) FROM test_savollar $w");
        $st->execute($params);
        $total = (int)$st->fetchColumn();
        $offset = ($page - 1) * $per_page;
        $st = $pdo->prepare("SELECT * FROM test_savollar $w ORDER BY bilet_id, tartib LIMIT $per_page OFFSET $offset");
        foreach ($params as $k => $v) $st->bindValue($k, $v);
        $st->execute();
        $rows = $st->fetchAll();
    } catch (Exception $e) {}
}
$pages = max(1, (int)ceil($total / $per_page));

vpy_panel_head(t('admin_questions'));
vpy_panel_sidebar('savollar', true);
?>
<main class="main">
<?php vpy_panel_topbar(t('admin_questions'), $total . ' / ' . vpy_test_count(),
    '<a href="/admin/savollar-import.php" class="btn btn-ghost"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>JSON import</a> <a href="/admin/savollar-form.php" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>' . e(t('admin_add')) . '</a>'
); ?>

<?php if (!$pdo): ?>
    <div class="flash error"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="10"/></svg><span>Bazaga ulanib bo'lmadi. Avval install.php ni ishga tushiring.</span></div>
<?php else: ?>

<div class="card">
    <form method="get" style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('admin_search')) ?>..." style="flex:1;min-width:200px;padding:13px 18px;border-radius:14px;border:1px solid var(--border-strong);background:rgba(255,253,249,0.85)">
        <select name="bilet" style="padding:13px 18px;border-radius:14px;border:1px solid var(--border-strong);background:rgba(255,253,249,0.85);min-width:140px">
            <option value="">Barcha biletlar</option>
            <?php for ($b = 1; $b <= 62; $b++): ?>
                <option value="<?= $b ?>" <?= $bilet === $b ? 'selected' : '' ?>>Bilet <?= sprintf('%02d', $b) ?></option>
            <?php endfor; ?>
        </select>
        <select name="mavzu" style="padding:13px 18px;border-radius:14px;border:1px solid var(--border-strong);background:rgba(255,253,249,0.85);min-width:160px">
            <option value="">Barcha mavzular</option>
            <?php foreach ($mavzular as $m): ?>
                <option value="<?= e($m['mavzu']) ?>" <?= $mavzu === $m['mavzu'] ? 'selected' : '' ?>><?= e($m['mavzu']) ?> (<?= (int)$m['cnt'] ?>)</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-dark"><?= e(t('admin_search')) ?></button>
    </form>

    <?php if (empty($rows)): ?>
        <div class="empty"><h3><?= e(t('xato_topilmadi')) ?></h3></div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead><tr><th>#</th><th>Bilet</th><th>Savol</th><th>Mavzu</th><th>To'g'ri</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td>#<?= (int)$r['id'] ?></td>
                    <td><span class="chip chip-muted"><?= sprintf('%02d.%02d', (int)$r['bilet_id'], (int)$r['tartib']) ?></span></td>
                    <td style="max-width:420px"><?= e(mb_substr($r['savol'], 0, 90, 'UTF-8')) ?><?= mb_strlen($r['savol'], 'UTF-8') > 90 ? '...' : '' ?></td>
                    <td><span class="chip chip-success"><?= e($r['mavzu']) ?></span></td>
                    <td><strong><?= e($r['togri']) ?></strong></td>
                    <td>
                        <div class="row-actions">
                            <a href="/admin/savollar-form.php?id=<?= (int)$r['id'] ?>" title="<?= e(t('admin_edit')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
                            <form method="post" style="display:inline" onsubmit="return confirm('O\'chirilsinmi?')">
                                <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="danger" title="<?= e(t('admin_delete')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 01-2 2H9a2 2 0 01-2-2L5 6"/></svg></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php
        $start = max(1, $page - 3);
        $end = min($pages, $page + 3);
        for ($i = $start; $i <= $end; $i++):
            $url = "?p=$i&q=" . urlencode($q) . "&bilet=$bilet&mavzu=" . urlencode($mavzu);
        ?>
            <?= $i === $page ? '<span class="active">' . $i . '</span>' : '<a href="' . e($url) . '">' . $i . '</a>' ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<div class="card" style="margin-top:18px;border:1px solid rgba(199,62,54,0.25)">
    <div class="card-head"><h2 style="color:#C73E36">Bilet bo'yicha ommaviy o'chirish</h2></div>
    <p style="font-size:0.85rem;color:var(--muted);margin-bottom:16px">Tanlangan biletdagi <strong>barcha savollar butunlay o'chiriladi</strong>. Bu amalni qaytarib bo'lmaydi — avval ehtiyot nusxa olishni unutmang.</p>
    <form method="post" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end" onsubmit="return confirm('Diqqat! Tanlangan biletdagi BARCHA savollar butunlay o\'chiriladi va qaytarib bo\'lmaydi. Davom etasizmi?')">
        <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
        <input type="hidden" name="action" value="delete_bilet">
        <div class="field" style="margin:0">
            <label>Bilet raqami</label>
            <select name="bilet_id_del" required style="padding:13px 18px;border-radius:14px;border:1px solid var(--border-strong);background:rgba(255,253,249,0.85);min-width:220px">
                <?php for ($b = 1; $b <= 62; $b++): $bc = $bilet_counts[$b] ?? 0; ?>
                    <option value="<?= $b ?>"><?= sprintf('Bilet %02d', $b) ?> — <?= $bc ? "$bc ta savol" : "bo'sh" ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="btn" style="background:#C73E36;color:#fff;border:none">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 01-2 2H9a2 2 0 01-2-2L5 6"/></svg>
            Shu biletdagi barcha savollarni o'chirish
        </button>
    </form>
</div>
<?php endif; ?>
</main>
<?php vpy_panel_foot(); ?>