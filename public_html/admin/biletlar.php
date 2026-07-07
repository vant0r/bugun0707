<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

$pdo = vpy_pdo();
$bilet_data = [];
if ($pdo) {
    try {
        foreach ($pdo->query("SELECT bilet_id, COUNT(*) as cnt FROM test_savollar GROUP BY bilet_id ORDER BY bilet_id")->fetchAll() as $r) {
            $bilet_data[(int)$r['bilet_id']] = (int)$r['cnt'];
        }
    } catch (Exception $e) {}
}
$total_bilets = max(62, max(array_keys($bilet_data) + [0]));

vpy_panel_head(t('admin_tickets'), <<<CSS
.bilet-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px}
.b-card{padding:22px;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r);text-align:center;text-decoration:none;color:inherit;transition:var(--t);position:relative;overflow:hidden}
.b-card:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:var(--primary)}
.b-card.ready{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-color:transparent}
.b-num{font-family:var(--serif);font-size:2rem;font-weight:700;line-height:1}
.b-card.ready .b-num{color:#fff}
.b-meta{font-size:0.78rem;color:var(--muted);margin-top:6px;text-transform:uppercase;letter-spacing:0.06em;font-weight:600}
.b-card.ready .b-meta{color:rgba(255,253,249,0.7)}
.b-cnt{font-size:0.92rem;color:var(--dark);font-weight:600;margin-top:8px}
.b-card.ready .b-cnt{color:rgba(255,253,249,0.95)}
@media (max-width:480px){.bilet-grid{grid-template-columns:repeat(2,1fr)}}
CSS);
vpy_panel_sidebar('biletlar', true);
?>
<main class="main">
<?php vpy_panel_topbar(t('admin_tickets'), count($bilet_data) . ' / ' . $total_bilets,
    '<a href="/admin/savollar-form.php" class="btn btn-primary">' . e(t('admin_add')) . ' savol</a>'
); ?>

<div class="card">
    <div class="card-head"><h2>62 ta bilet ¡¤ har biri 20 savol</h2></div>
    <div class="bilet-grid">
        <?php for ($i = 1; $i <= $total_bilets; $i++):
            $cnt = $bilet_data[$i] ?? 0;
            $ready = $cnt >= 20;
        ?>
            <a href="/admin/savollar.php?bilet=<?= $i ?>" class="b-card <?= $ready ? 'ready' : '' ?>">
                <div class="b-meta">Bilet</div>
                <div class="b-num"><?= sprintf('%02d', $i) ?></div>
                <div class="b-cnt"><?= $cnt ?> / 20 savol</div>
            </a>
        <?php endfor; ?>
    </div>
</div>
</main>
<?php vpy_panel_foot(); ?>