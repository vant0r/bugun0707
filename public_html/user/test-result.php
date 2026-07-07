<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_login('/login.php');

$u = vpy_user();
$is_bilet50 = vpy_get('type') === 'bilet50';

if ($is_bilet50) {
    // Biletlar 50 - mashq rejimi, session dan o'qiymiz
    $result = $_SESSION['bilet50_result'] ?? null;
    if (!$result) {
        vpy_redirect('/user/testlar.php');
    }
    $result['created_at'] = date('Y-m-d H:i:s');
    $result['user_id'] = (int)$u['id'];
    $result['type'] = 'bilet50';
    $result['answers'] = $result['answers'] ?? [];
    unset($_SESSION['bilet50_result']);
} else {
    $id = (int)vpy_get('id');
    $result = vpy_find('natijalar', 'id', $id);
    if (!$result || (int)$result['user_id'] !== (int)$u['id']) {
        http_response_code(404);
        die('Natija topilmadi');
    }
}

$score = (int)$result['score'];
$total = max(1, (int)$result['total']);
$percent = round($score / $total * 100);
$pass_score = (int)vpy_setting('test_pass_score', 18);
$passed = $score >= $pass_score;

$pdo = vpy_pdo();
$qmap = [];
if ($pdo && !empty($result['answers'])) {
    $qids = array_column($result['answers'], 'question_id');
    if (!empty($qids)) {
        $ph = implode(',', array_fill(0, count($qids), '?'));
        $st = $pdo->prepare("SELECT * FROM test_savollar WHERE id IN ($ph)");
        $st->execute($qids);
        foreach ($st->fetchAll() as $q) $qmap[(int)$q['id']] = $q;
    }
}
$is_cyrl = vpy_lang_code() === 'uz_cyrillic';

vpy_panel_head(t('test_result_title'), <<<CSS
.result-hero{padding:48px;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r-lg);text-align:center;margin-bottom:24px;position:relative;overflow:hidden}
.result-hero.passed{background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);color:#fff;border-color:transparent}
.result-hero.failed{background:linear-gradient(135deg,#FFFDF9 0%,#FAF7F2 100%);border:2px solid rgba(232,168,56,0.5)}
.result-hero::before{content:"";position:absolute;top:-50%;right:-30%;width:80%;height:160%;background:radial-gradient(ellipse,rgba(232,168,56,0.18),transparent 60%);pointer-events:none}
.result-circle{width:200px;height:200px;margin:0 auto 24px;position:relative;display:grid;place-items:center}
.result-circle svg{position:absolute;inset:0;transform:rotate(-90deg)}
.result-circle .ring-bg{stroke:rgba(180,160,130,0.18);stroke-width:12;fill:none}
.result-hero.passed .result-circle .ring-bg{stroke:rgba(255,255,255,0.18)}
.result-circle .ring{stroke:var(--accent);stroke-width:12;fill:none;stroke-linecap:round;stroke-dasharray:565;stroke-dashoffset:565;animation:fillRing 1.5s cubic-bezier(0.4,0,0.2,1) forwards}
.result-hero.passed .result-circle .ring{stroke:var(--accent)}
@keyframes fillRing{to{stroke-dashoffset:var(--off)}}
.result-circle-content{position:relative;z-index:2}
.result-percent{font-family:var(--serif);font-size:3.5rem;font-weight:700;line-height:1;letter-spacing:-0.03em}
.result-circle-label{font-size:0.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;font-weight:600;margin-top:6px}
.result-hero.passed .result-circle-label{color:rgba(255,253,249,0.7)}
.result-title{font-family:var(--serif);font-size:clamp(1.6rem,3vw,2.2rem);font-weight:600;line-height:1.1;margin-bottom:8px;letter-spacing:-0.02em;position:relative;z-index:2}
.result-sub{font-size:1rem;color:var(--muted);margin-bottom:28px;position:relative;z-index:2}
.result-hero.passed .result-sub{color:rgba(255,253,249,0.8)}
.result-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:24px;position:relative;z-index:2}
.result-stat{text-align:center;padding:18px;background:rgba(255,255,255,0.5);border-radius:var(--r);backdrop-filter:blur(20px)}
.result-hero.passed .result-stat{background:rgba(255,255,255,0.12)}
.result-stat-num{font-family:var(--serif);font-size:1.8rem;font-weight:700;line-height:1}
.result-stat-label{font-size:0.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-top:6px;font-weight:600}
.result-hero.passed .result-stat-label{color:rgba(255,253,249,0.65)}
.result-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;position:relative;z-index:2}
.review-card{margin-top:18px;padding:24px;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r);transition:var(--t)}
.review-card.correct{border-left:4px solid var(--primary)}
.review-card.wrong{border-left:4px solid #C73E36}
.review-q{font-family:var(--serif);font-size:1.05rem;font-weight:500;line-height:1.4;margin-bottom:14px;color:var(--dark)}
.review-meta{display:flex;align-items:center;gap:14px;font-size:0.82rem;color:var(--muted);margin-bottom:14px;flex-wrap:wrap}
.review-meta .chip{padding:4px 10px;border-radius:var(--pill);font-weight:600;font-size:0.72rem}
.review-meta .chip.correct{background:rgba(13,107,78,0.1);color:var(--primary-dark)}
.review-meta .chip.wrong{background:rgba(255,96,88,0.1);color:#C73E36}
.review-explain{padding:14px 18px;background:rgba(13,107,78,0.04);border-left:3px solid var(--primary);border-radius:0 var(--r-sm) var(--r-sm) 0;font-size:0.88rem;color:var(--dark-soft);line-height:1.5;margin-top:10px}
@media (max-width:640px){.result-hero{padding:30px 22px}.result-stats{gap:10px}.result-stat{padding:12px}}
CSS);
vpy_panel_sidebar('natijalar', false);
?>

<main class="main">
<?php vpy_panel_topbar(t('test_result_title'), e(vpy_date($result['created_at'], 'd.m.Y · H:i'))); ?>

<div class="result-hero <?= $passed ? 'passed' : 'failed' ?>" style="--off:<?= 565 - ($percent / 100 * 565) ?>">
    <div class="result-circle">
        <svg viewBox="0 0 200 200">
            <circle class="ring-bg" cx="100" cy="100" r="90"/>
            <circle class="ring" cx="100" cy="100" r="90"/>
        </svg>
        <div class="result-circle-content">
            <div class="result-percent"><?= $percent ?>%</div>
            <div class="result-circle-label"><?= $score ?> / <?= $total ?></div>
        </div>
    </div>
    <h1 class="result-title"><?= $passed ? e(t('test_result_passed')) : e(t('test_result_failed')) ?></h1>
    <p class="result-sub">
        <?php if ($is_bilet50): ?>
            <span style="background:rgba(232,168,56,0.2);padding:4px 12px;border-radius:20px;font-size:0.85rem;font-weight:600">🎯 Mashq rejimi — natijalar saqlanmaydi</span><br style="margin-bottom:8px">
        <?php endif; ?>
        <?= e(t('test_result_score')) ?>: <?= $score ?> / <?= $total ?> · <?= floor((int)$result['duration'] / 60) ?>:<?= sprintf('%02d', (int)$result['duration'] % 60) ?>
    </p>

    <div class="result-stats">
        <div class="result-stat">
            <div class="result-stat-num" style="color:<?= $passed ? '#fff' : 'var(--primary)' ?>"><?= (int)$result['correct'] ?></div>
            <div class="result-stat-label"><?= e(t('count_correct')) ?></div>
        </div>
        <div class="result-stat">
            <div class="result-stat-num" style="color:<?= $passed ? '#fff' : '#C73E36' ?>"><?= (int)$result['wrong'] ?></div>
            <div class="result-stat-label"><?= e(t('count_wrong')) ?></div>
        </div>
        <div class="result-stat">
            <div class="result-stat-num" style="color:<?= $passed ? '#fff' : 'var(--accent)' ?>"><?= floor((int)$result['duration'] / 60) ?>:<?= sprintf('%02d', (int)$result['duration'] % 60) ?></div>
            <div class="result-stat-label"><?= e(t('test_result_time')) ?></div>
        </div>
    </div>

    <div class="result-actions">
        <?php if ($is_bilet50): ?>
            <a href="/user/test.php?type=bilet50&bilet=<?= (int)($result['bilet_num'] ?? 1) ?>" class="btn btn-primary">Qayta mashq <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 019-9 9.75 9.75 0 016.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg></a>
            <a href="/user/testlar.php#biletlar50" class="btn btn-ghost">Biletlarga qaytish</a>
        <?php else: ?>
            <a href="/user/test.php" class="btn btn-primary"><?= e(t('test_result_retry')) ?> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 019-9 9.75 9.75 0 016.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg></a>
            <a href="/user/" class="btn btn-ghost"><?= e(t('btn_back')) ?></a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($result['answers'])): ?>
<div class="card-head" style="margin:30px 0 14px"><h2><?= e(t('test_result_review')) ?></h2></div>
<?php foreach ($result['answers'] as $i => $ans):
    $q = $qmap[(int)$ans['question_id']] ?? null;
    if (!$q) continue;
    $svol = $is_cyrl && !empty($q['savol_cyrl']) ? $q['savol_cyrl'] : $q['savol'];
    $izoh = $is_cyrl && !empty($q['izoh_cyrl']) ? $q['izoh_cyrl'] : ($q['izoh'] ?? '');
    $is_correct = !empty($ans['is_correct']);
?>
    <div class="review-card <?= $is_correct ? 'correct' : 'wrong' ?>">
        <div class="review-meta">
            <span><?= e(t('test_question')) ?> <?= $i + 1 ?></span>
            <span class="chip <?= $is_correct ? 'correct' : 'wrong' ?>">
                <?= $is_correct ? e(t('count_correct')) : e(t('count_wrong')) ?>
            </span>
            <span>Sizning javob: <strong><?= e($ans['answer']) ?></strong></span>
            <?php if (!$is_correct): ?>
                <span style="color:var(--primary);font-weight:600">To'g'ri javob: <strong><?= e($ans['correct']) ?></strong></span>
            <?php endif; ?>
        </div>
        <div class="review-q"><?= e($svol) ?></div>
        <?php if ($izoh): ?>
            <div class="review-explain"><strong><?= e(t('test_explain')) ?>:</strong> <?= e($izoh) ?></div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
<?php endif; ?>

</main>

<?php vpy_panel_foot(); ?>
