<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_login('/login.php');

$u = vpy_user();
$results = vpy_filter('natijalar', fn($r) => (int)$r['user_id'] === (int)$u['id']);
usort($results, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

$page = max(1, (int)vpy_get('p', 1));
$pag = vpy_paginate($results, 15, $page);
$pass_score = (int)vpy_setting('test_pass_score', 18);

vpy_panel_head(t('user_recent_tests'));
vpy_panel_sidebar('natijalar', false);
?>

<main class="main">
<?php vpy_panel_topbar(t('user_recent_tests'), sprintf('%d ta natija', count($results)),
    '<a href="/user/test.php?type=quick" class="btn btn-primary">' . e(t('user_quick_test')) . '</a>'
); ?>

<div class="card">
    <?php if (empty($pag['items'])): ?>
        <div class="empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
            <h3><?= e(t('user_no_tests')) ?></h3>
            <p style="margin-bottom:20px"><?= e(t('user_quick_test_desc')) ?></p>
            <a href="/user/test.php?type=quick" class="btn btn-primary"><?= e(t('btn_start')) ?></a>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= e(t('admin_dashboard')) ?></th>
                        <th><?= e(t('user_score')) ?></th>
                        <th><?= e(t('test_result_correct')) ?></th>
                        <th><?= e(t('test_result_time')) ?></th>
                        <th><?= e(t('admin_status_active')) ?></th>
                        <th><?= e(t('invoice_date')) ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pag['items'] as $r):
                        $passed = (int)$r['score'] >= $pass_score;
                    ?>
                    <tr>
                        <td>#<?= (int)$r['id'] ?></td>
                        <td>
                            <strong style="font-weight:600">
                                <?= ($r['type'] ?? '') === 'full' ? e(t('user_full_test')) : (($r['type'] ?? '') === 'bilet' ? e(t('ticket_label')) . ' ' . sprintf('%02d', (int)($r['bilet_id'] ?? 0)) : e(t('user_quick_test'))) ?>
                            </strong>
                        </td>
                        <td><strong style="font-family:var(--serif);font-size:1.1rem;color:<?= $passed ? 'var(--primary)' : '#C73E36' ?>"><?= (int)$r['score'] ?>/<?= (int)$r['total'] ?></strong></td>
                        <td><span style="color:var(--primary)"><?= (int)$r['correct'] ?></span> / <span style="color:#C73E36"><?= (int)$r['wrong'] ?></span></td>
                        <td><?= floor((int)$r['duration'] / 60) ?>:<?= sprintf('%02d', (int)$r['duration'] % 60) ?></td>
                        <td>
                            <?php if ($passed): ?>
                                <span class="chip chip-success"><?= e(t('admin_status_success')) ?></span>
                            <?php else: ?>
                                <span class="chip chip-warning"><?= e(t('admin_status_pending')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(vpy_time_ago($r['created_at'])) ?></td>
                        <td>
                            <div class="row-actions">
                                <a href="/user/test-result.php?id=<?= (int)$r['id'] ?>" title="<?= e(t('test_result_review')) ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
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
                <?php if ($i === $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?p=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</main>
<?php vpy_panel_foot(); ?>
