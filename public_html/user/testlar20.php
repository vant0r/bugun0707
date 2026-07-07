<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_login('/login.php');

$u = vpy_user();
$pdo = vpy_pdo();
$bilet_data = [];

if ($pdo) {
    try {
        $st = $pdo->query("SELECT bilet_id, COUNT(*) as cnt FROM test_savollar WHERE holat='faol' GROUP BY bilet_id ORDER BY bilet_id");
        foreach ($st->fetchAll() as $row) $bilet_data[(int)$row['bilet_id']] = (int)$row['cnt'];
    } catch (Exception $e) {}
}

$total_bilets = max(62, max(array_keys($bilet_data) + [0]));

$user_results = vpy_filter('natijalar', fn($r) => (int)$r['user_id'] === (int)$u['id'] && ($r['type'] ?? '') === 'bilet');
$bilet_done = [];
foreach ($user_results as $r) {
    $bid = (int)($r['bilet_id'] ?? 0);
    if (!$bid) continue;
    if (!isset($bilet_done[$bid]) || ((int)$r['score'] > (int)$bilet_done[$bid]['score'])) $bilet_done[$bid] = $r;
}

vpy_panel_head(t('tickets_title'), <<<CSS
.bilet-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:16px}
.bilet-card{position:relative;aspect-ratio:1;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r);padding:18px;display:flex;flex-direction:column;justify-content:space-between;transition:transform var(--t),box-shadow var(--t),border-color var(--t);text-decoration:none;color:inherit;overflow:hidden}
.bilet-card:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:var(--primary)}
.bilet-card.done{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-color:transparent}
.bilet-card.done::before{content:"";position:absolute;top:-50%;right:-30%;width:80%;height:160%;background:radial-gradient(ellipse,rgba(232,168,56,0.18),transparent 60%);pointer-events:none}
.bilet-num{font-family:var(--serif);font-size:2.2rem;font-weight:700;line-height:1;letter-spacing:-0.02em;color:var(--primary)}
.bilet-card.done .bilet-num{color:#fff}
.bilet-meta{display:flex;flex-direction:column;gap:2px;position:relative;z-index:2}
.bilet-label{font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;font-weight:600}
.bilet-card.done .bilet-label{color:rgba(255,253,249,0.65)}
.bilet-count{font-size:0.92rem;color:var(--dark);font-weight:600}
.bilet-card.done .bilet-count{color:rgba(255,253,249,0.95)}
.bilet-score{position:absolute;top:14px;right:14px;padding:4px 10px;border-radius:var(--pill);font-size:0.72rem;font-weight:700;background:rgba(232,168,56,0.18);color:#A87830}
.bilet-card.done .bilet-score{background:var(--accent);color:var(--dark)}
.bilet-empty{opacity:0.55;pointer-events:none}
.bilet-empty .bilet-num{color:var(--muted)}
@media (max-width:640px){.bilet-grid{grid-template-columns:repeat(3,1fr);gap:12px}.bilet-card{padding:14px}.bilet-num{font-size:1.6rem}}
@media (max-width:380px){.bilet-grid{grid-template-columns:repeat(2,1fr)}}
CSS);
vpy_panel_sidebar('testlar20', false);
?>

<main class="main">
<?php vpy_panel_topbar(t('tickets_title'), t('tickets_subtitle'),
    '<a href="/user/testlar50.php" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>Biletlar 50 · Mashq</a>'
); ?>

<div class="card">
    <div class="card-head">
        <h2><?= e(t('tickets_title')) ?> · <?= count($bilet_data) ?>/<?= $total_bilets ?></h2>
        <span class="chip chip-success"><?= count($bilet_done) ?> <?= e(t('ticket_done')) ?></span>
    </div>

    <div class="bilet-grid">
        <?php for ($i = 1; $i <= $total_bilets; $i++):
            $count = $bilet_data[$i] ?? 0;
            $done = $bilet_done[$i] ?? null;
            $cls = $done ? 'done' : ($count === 0 ? 'bilet-empty' : '');
        ?>
            <a href="<?= $count ? '/user/test.php?bilet=' . $i : '#' ?>" class="bilet-card <?= $cls ?>">
                <?php if ($done): ?>
                    <span class="bilet-score"><?= (int)$done['score'] ?>/<?= (int)$done['total'] ?></span>
                <?php endif; ?>
                <div>
                    <div class="bilet-label"><?= e(t('ticket_label')) ?></div>
                    <div class="bilet-num"><?= sprintf('%02d', $i) ?></div>
                </div>
                <div class="bilet-meta">
                    <span class="bilet-count"><?= $count ?: '—' ?> <?= e(t('ticket_count')) ?></span>
                </div>
            </a>
        <?php endfor; ?>
    </div>
</div>

</main>

<?php vpy_panel_foot(); ?>