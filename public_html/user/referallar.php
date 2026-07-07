<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_login('/login.php');

$u = vpy_user();
$invited = vpy_filter('users', fn($x) => (int)($x['referrer_id'] ?? 0) === (int)$u['id']);
$bonus_per = (int)vpy_setting('referral_bonus', 5000);
$earned = count($invited) * $bonus_per;
$ref_link = 'https://' . VPY_DOMAIN . '/register.php?ref=' . urlencode($u['referral_code'] ?? '');

vpy_panel_head(t('referral_title'), <<<CSS
.ref-hero{padding:48px;background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);color:#fff;border-radius:var(--r-lg);margin-bottom:24px;position:relative;overflow:hidden}
.ref-hero::before{content:"";position:absolute;top:-50%;right:-30%;width:80%;height:160%;background:radial-gradient(ellipse,rgba(232,168,56,0.22),transparent 60%);pointer-events:none}
.ref-hero h2{font-family:var(--serif);font-size:clamp(1.6rem,3vw,2.2rem);font-weight:500;line-height:1.1;letter-spacing:-0.02em;margin-bottom:10px;position:relative;z-index:2}
.ref-hero p{color:rgba(255,253,249,0.8);font-size:1rem;max-width:60ch;position:relative;z-index:2;margin-bottom:30px}
.ref-codes{display:grid;grid-template-columns:1fr 1fr;gap:14px;position:relative;z-index:2}
.ref-code-box{padding:18px 22px;background:rgba(255,255,255,0.12);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.18);border-radius:14px}
.ref-code-label{font-size:0.72rem;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:rgba(255,253,249,0.65);margin-bottom:6px}
.ref-code-val{font-family:var(--serif);font-size:1.3rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:10px;word-break:break-all}
.ref-code-val .copy{margin-left:auto;padding:6px 14px;background:rgba(255,255,255,0.18);border-radius:var(--pill);color:#fff;font-size:0.78rem;font-weight:600;cursor:pointer;flex-shrink:0;font-family:var(--sans);border:none;transition:var(--t)}
.ref-code-val .copy:hover{background:rgba(255,255,255,0.3)}
.ref-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:24px}
.ref-stat{padding:24px;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r);text-align:center}
.ref-stat-num{font-family:var(--serif);font-size:2rem;font-weight:700;color:var(--primary);line-height:1}
.ref-stat-label{font-size:0.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-top:6px;font-weight:600}
@media (max-width:768px){.ref-codes{grid-template-columns:1fr}.ref-hero{padding:30px 24px}.ref-stats{grid-template-columns:1fr}}
CSS);
vpy_panel_sidebar('referallar', false);
?>

<main class="main">
<?php vpy_panel_topbar(t('referral_title'), t('referral_subtitle')); ?>

<div class="ref-hero">
    <h2><em>Do'stlaringizni taklif qiling</em>, har biri uchun <?= number_format($bonus_per, 0, '.', ' ') ?> <?= e(t('valyuta_sum')) ?></h2>
    <p>Sizning kodingiz orqali ro'yxatdan o'tgan har bir foydalanuvchidan keyin sizga avtomatik bonus qo'shiladi. Bonusni hisobingizdan tariflarga sarflashingiz mumkin.</p>

    <div class="ref-codes">
        <div class="ref-code-box">
            <div class="ref-code-label"><?= e(t('referral_code_label')) ?></div>
            <div class="ref-code-val">
                <?= e($u['referral_code'] ?? '') ?>
                <button class="copy" onclick="copyText('<?= e($u['referral_code'] ?? '') ?>', this)"><?= e(t('referral_copy')) ?></button>
            </div>
        </div>
        <div class="ref-code-box">
            <div class="ref-code-label"><?= e(t('referral_link_label')) ?></div>
            <div class="ref-code-val" style="font-size:0.92rem;font-family:var(--sans);font-weight:500">
                <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;min-width:0"><?= e($ref_link) ?></span>
                <button class="copy" onclick="copyText('<?= e($ref_link) ?>', this)"><?= e(t('referral_copy')) ?></button>
            </div>
        </div>
    </div>
</div>

<div class="ref-stats">
    <div class="ref-stat">
        <div class="ref-stat-num"><?= count($invited) ?></div>
        <div class="ref-stat-label"><?= e(t('referral_invited')) ?></div>
    </div>
    <div class="ref-stat">
        <div class="ref-stat-num"><?= number_format($earned, 0, '.', ' ') ?></div>
        <div class="ref-stat-label"><?= e(t('referral_earned')) ?> <?= e(t('valyuta_sum')) ?></div>
    </div>
    <div class="ref-stat">
        <div class="ref-stat-num"><?= number_format((int)($u['balance'] ?? 0), 0, '.', ' ') ?></div>
        <div class="ref-stat-label"><?= e(t('user_balance')) ?> <?= e(t('valyuta_sum')) ?></div>
    </div>
</div>

<div class="card">
    <div class="card-head"><h2><?= e(t('referral_invited')) ?> · <?= count($invited) ?></h2></div>
    <?php if (empty($invited)): ?>
        <div class="empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            <h3>Hali hech kim taklif qilinmagan</h3>
            <p>Yuqoridagi kod yoki havolani do'stlaringizga ulashing</p>
        </div>
    <?php else: ?>
        <table class="tbl">
            <thead><tr><th>#</th><th><?= e(t('rating_user')) ?></th><th><?= e(t('auth_phone')) ?></th><th><?= e(t('invoice_date')) ?></th><th>Bonus</th></tr></thead>
            <tbody>
                <?php foreach ($invited as $i => $iv):
                    $color = vpy_avatar_color($iv['name']);
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px">
                            <div style="width:34px;height:34px;border-radius:50%;background:<?= e($color) ?>;color:#fff;display:grid;place-items:center;font-weight:700;font-size:0.78rem"><?= e(vpy_user_initials($iv['name'])) ?></div>
                            <strong style="font-weight:600"><?= e($iv['name']) ?></strong>
                        </div>
                    </td>
                    <td><?= e($iv['phone']) ?></td>
                    <td><?= e(vpy_time_ago($iv['created_at'])) ?></td>
                    <td><span class="chip chip-success">+<?= number_format($bonus_per, 0, '.', ' ') ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function copyText(text, btn){
    navigator.clipboard.writeText(text).then(function(){
        var orig = btn.textContent;
        btn.textContent = '<?= e(t('referral_copied')) ?>';
        setTimeout(function(){ btn.textContent = orig; }, 1800);
    }).catch(function(){
        alert(text);
    });
}
</script>

</main>
<?php vpy_panel_foot(); ?>
