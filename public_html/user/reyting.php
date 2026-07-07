<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_login('/login.php');

$u = vpy_user();
$period = vpy_get('period', 'all');
$cutoff = null;
if ($period === 'today') $cutoff = strtotime('today');
elseif ($period === 'week') $cutoff = strtotime('-7 days');
elseif ($period === 'month') $cutoff = strtotime('-30 days');

$users = vpy_filter('users', fn($x) => ($x['role'] ?? 'user') === 'user' && ($x['status'] ?? 'active') === 'active');
$results = vpy_read_json('natijalar', []);

$score_map = [];
$count_map = [];
foreach ($results as $r) {
    if ($cutoff && strtotime($r['created_at'] ?? '') < $cutoff) continue;
    $uid = (int)$r['user_id'];
    if (!isset($score_map[$uid])) { $score_map[$uid] = 0; $count_map[$uid] = 0; }
    $score_map[$uid] = max($score_map[$uid], (int)$r['score']);
    $count_map[$uid]++;
}

$enriched = [];
foreach ($users as $usr) {
    $uid = (int)$usr['id'];
    $enriched[] = [
        'id' => $uid,
        'name' => $usr['name'],
        'phone' => $usr['phone'],
        'best' => $score_map[$uid] ?? (int)($usr['best_score'] ?? 0),
        'count' => $count_map[$uid] ?? (int)($usr['tests_taken'] ?? 0),
        'created_at' => $usr['created_at'] ?? '',
    ];
}
usort($enriched, fn($a, $b) => $b['best'] <=> $a['best'] ?: $b['count'] <=> $a['count']);

$my_rank = 0;
foreach ($enriched as $i => $e) if ($e['id'] === (int)$u['id']) { $my_rank = $i + 1; break; }

vpy_panel_head(t('rating_title'), <<<CSS
.period-tabs{display:flex;gap:6px;padding:6px;background:var(--glass);backdrop-filter:blur(20px);border:1px solid var(--border);border-radius:var(--pill);margin-bottom:18px;width:fit-content}
.period-tabs a{padding:8px 16px;border-radius:var(--pill);font-size:0.85rem;font-weight:600;color:var(--dark-soft)}
.period-tabs a.active{background:var(--primary);color:#fff}
.podium{display:grid;grid-template-columns:1fr 1.2fr 1fr;gap:18px;align-items:end;margin-bottom:30px}
.podium-place{padding:30px 20px;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r-lg);text-align:center;position:relative;overflow:hidden;transition:transform var(--t)}
.podium-place:hover{transform:translateY(-6px)}
.podium-place.first{padding:40px 20px;background:linear-gradient(135deg,var(--accent),#D88F1A);color:#fff;border-color:transparent;box-shadow:0 24px 48px rgba(232,168,56,0.4)}
.podium-place.second{background:linear-gradient(135deg,#E8E2D4,#C9C0AB);color:var(--dark)}
.podium-place.third{background:linear-gradient(135deg,#D4A574,#B8895A);color:#fff}
.podium-medal{font-size:2.5rem;line-height:1;margin-bottom:10px}
.podium-avatar{width:64px;height:64px;border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:700;font-size:1.3rem;margin:0 auto 14px;box-shadow:0 8px 18px rgba(0,0,0,0.18);border:3px solid rgba(255,255,255,0.4)}
.podium-place.first .podium-avatar{width:80px;height:80px;font-size:1.6rem}
.podium-name{font-family:var(--serif);font-size:1.05rem;font-weight:600;margin-bottom:4px;line-height:1.2}
.podium-place.first .podium-name{font-size:1.2rem}
.podium-score{font-family:var(--serif);font-size:2rem;font-weight:700;line-height:1;letter-spacing:-0.02em}
.podium-place.first .podium-score{font-size:2.5rem}
.rank-row{display:flex;align-items:center;gap:14px;padding:14px 18px;border-radius:14px;border:1px solid var(--border);background:rgba(255,253,249,0.5);margin-bottom:8px;transition:var(--t)}
.rank-row:hover{background:var(--light);border-color:var(--primary)}
.rank-row.you{background:linear-gradient(135deg,rgba(13,107,78,0.08),rgba(232,168,56,0.06));border:1.5px solid var(--primary);box-shadow:0 8px 20px rgba(13,107,78,0.1)}
.rank-num{font-family:var(--serif);font-size:1.3rem;font-weight:700;color:var(--muted);width:50px;text-align:center}
.rank-row.you .rank-num{color:var(--primary)}
.rank-avatar{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:700;font-size:0.85rem;flex-shrink:0}
.rank-name{flex:1;font-weight:600;color:var(--dark)}
.rank-meta{font-size:0.78rem;color:var(--muted);margin-top:2px}
.rank-score{font-family:var(--serif);font-size:1.2rem;font-weight:700;color:var(--primary)}
@media (max-width:640px){.podium{grid-template-columns:1fr}.podium-place.first{order:-1}.rank-num{width:40px;font-size:1.1rem}}
CSS);
vpy_panel_sidebar('reyting', false);
?>

<main class="main">
<?php vpy_panel_topbar(t('rating_title'), $my_rank ? t('rating_you') . ': #' . $my_rank : t('rating_subtitle')); ?>

<div class="period-tabs">
    <a href="?period=today" class="<?= $period === 'today' ? 'active' : '' ?>"><?= e(t('rating_period_today')) ?></a>
    <a href="?period=week" class="<?= $period === 'week' ? 'active' : '' ?>"><?= e(t('rating_period_week')) ?></a>
    <a href="?period=month" class="<?= $period === 'month' ? 'active' : '' ?>"><?= e(t('rating_period_month')) ?></a>
    <a href="?period=all" class="<?= $period === 'all' ? 'active' : '' ?>"><?= e(t('rating_period_all')) ?></a>
</div>

<?php if (count($enriched) >= 3): ?>
<div class="podium">
    <?php foreach ([['second', 1], ['first', 0], ['third', 2]] as $info):
        $place = $info[1];
        $cls = $info[0];
        $u_p = $enriched[$place] ?? null;
        if (!$u_p) continue;
        $color = vpy_avatar_color($u_p['name']);
    ?>
    <div class="podium-place <?= $cls ?>">
        <div class="podium-medal"><?= ['🥇','🥈','🥉'][$place] ?></div>
        <div class="podium-avatar" style="background:<?= e($color) ?>"><?= e(vpy_user_initials($u_p['name'])) ?></div>
        <div class="podium-name"><?= e($u_p['name']) ?></div>
        <div class="podium-score"><?= (int)$u_p['best'] ?>/20</div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <h2><?= e(t('rating_title')) ?> · <?= count($enriched) ?></h2>
    </div>
    <?php if (empty($enriched)): ?>
        <div class="empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="7"/><path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12"/></svg>
            <h3><?= e(t('user_no_tests')) ?></h3>
        </div>
    <?php else: ?>
        <?php foreach (array_slice($enriched, 0, 50) as $i => $usr):
            $is_you = $usr['id'] === (int)$u['id'];
            $color = vpy_avatar_color($usr['name']);
        ?>
        <div class="rank-row <?= $is_you ? 'you' : '' ?>">
            <div class="rank-num">#<?= $i + 1 ?></div>
            <div class="rank-avatar" style="background:<?= e($color) ?>"><?= e(vpy_user_initials($usr['name'])) ?></div>
            <div>
                <div class="rank-name"><?= e($usr['name']) ?> <?php if ($is_you): ?><span class="chip chip-success" style="margin-left:6px;font-size:0.65rem">SIZ</span><?php endif; ?></div>
                <div class="rank-meta"><?= (int)$usr['count'] ?> <?= e(t('count_tests')) ?></div>
            </div>
            <div class="rank-score"><?= (int)$usr['best'] ?>/20</div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</main>
<?php vpy_panel_foot(); ?>
