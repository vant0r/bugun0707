<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_login('/login.php');

$u = vpy_user();

// Barcha natijalardan xato javoblarni yig'ish
$all_results = vpy_filter('natijalar', fn($r) => (int)$r['user_id'] === (int)$u['id']);
$wrong_questions = [];
foreach ($all_results as $result) {
    if (empty($result['answers'])) continue;
    foreach ($result['answers'] as $ans) {
        if (empty($ans['is_correct'])) {
            $qid = (int)$ans['question_id'];
            if (!isset($wrong_questions[$qid])) {
                $wrong_questions[$qid] = 0;
            }
            $wrong_questions[$qid]++;
        }
    }
}

// Xato savollarni bazadan olish
$pdo = vpy_pdo();
$questions_data = [];
$wrong_count = count($wrong_questions);

if ($pdo && !empty($wrong_questions)) {
    try {
        $ids = array_keys($wrong_questions);
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare("SELECT * FROM test_savollar WHERE id IN ($ph) AND holat='faol' ORDER BY bilet_id ASC, tartib ASC");
        $st->execute($ids);
        $questions_data = $st->fetchAll();
    } catch (Exception $e) {}
}

// 20 talik biletlarga bo'lish
$bilets = [];
$per_bilet = 20;
if (!empty($questions_data)) {
    $chunks = array_chunk($questions_data, $per_bilet);
    foreach ($chunks as $i => $chunk) {
        $bilets[$i + 1] = $chunk;
    }
}
$total_bilets = count($bilets);

vpy_panel_head('Xatolarim', <<<CSS
.bilet-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:16px}
.bilet-card{position:relative;aspect-ratio:1;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r);padding:18px;display:flex;flex-direction:column;justify-content:space-between;transition:transform var(--t),box-shadow var(--t),border-color var(--t);text-decoration:none;color:inherit;overflow:hidden}
.bilet-card:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:var(--primary)}
.bilet-num{font-family:var(--serif);font-size:2.2rem;font-weight:700;line-height:1;letter-spacing:-0.02em;color:var(--primary)}
.bilet-meta{display:flex;flex-direction:column;gap:2px;position:relative;z-index:2}
.bilet-label{font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;font-weight:600}
.bilet-count{font-size:0.92rem;color:var(--dark);font-weight:600}
.error-badge{position:absolute;top:14px;right:14px;padding:4px 10px;border-radius:var(--pill);font-size:0.72rem;font-weight:700;background:rgba(220,53,69,0.12);color:#C73E36}
@media (max-width:640px){.bilet-grid{grid-template-columns:repeat(3,1fr);gap:12px}.bilet-card{padding:14px}.bilet-num{font-size:1.6rem}}
@media (max-width:380px){.bilet-grid{grid-template-columns:repeat(2,1fr)}}
CSS);
vpy_panel_sidebar('xatolarim', false);
?>

<main class="main">
<?php vpy_panel_topbar('Xatolarim', $wrong_count . ' ta savolda xato qilgansiz'); ?>

<?php if (empty($questions_data)): ?>
<div class="card empty">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
    <h3>Xatolar yo'q!</h3>
    <p>Siz hali xato qilmadingiz yoki testlarni yechmagansiz. Biletlarni yechib mashq qiling!</p>
    <a href="/user/testlar20.php" class="btn btn-primary" style="margin-top:16px">Biletlarga o'tish</a>
</div>
<?php else: ?>
<div class="card">
    <div class="card-head">
        <h2>Xato savollar · <?= $wrong_count ?> ta</h2>
        <span class="chip chip-danger"><?= $total_bilets ?> bilet</span>
    </div>
    <p style="color:var(--muted);margin-bottom:18px;font-size:0.9rem;">
        Siz xato qilgan savollar 20 talik biletlarga bo'lingan. Har bir biletni yechib xatolaringizni tuzating!
    </p>
    <div class="bilet-grid">
        <?php for ($i = 1; $i <= $total_bilets; $i++):
            $q_count = count($bilets[$i] ?? []);
        ?>
            <a href="/user/test.php?type=xatolar&bilet=<?= $i ?>" class="bilet-card">
                <span class="error-badge"><?= $q_count ?> savol</span>
                <div>
                    <div class="bilet-label">Xatolar</div>
                    <div class="bilet-num"><?= sprintf('%02d', $i) ?></div>
                </div>
                <div class="bilet-meta">
                    <span class="bilet-count"><?= $q_count ?> <?= e(t('ticket_count')) ?></span>
                </div>
            </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

</main>

<?php vpy_panel_foot(); ?>