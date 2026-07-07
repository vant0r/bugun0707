<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_login('/login.php');

$u = vpy_user();

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    $action = vpy_post('action');
    if ($action === 'profile') {
        $u['name'] = vpy_post('name', $u['name']);
        vpy_upsert('users', $u);
        vpy_flash_set('success', t('profile_saved'));
        vpy_redirect('/user/profil.php');
    } elseif ($action === 'password') {
        $r = vpy_password_change($u['id'], vpy_post('old'), vpy_post('new'));
        vpy_flash_set($r['ok'] ? 'success' : 'error', $r['ok'] ? t('msg_updated') : $r['error']);
        vpy_redirect('/user/profil.php');
    }
}
$color = vpy_avatar_color($u['name']);

vpy_panel_head(t('profile_title'), <<<CSS
.profile-grid{display:grid;grid-template-columns:1fr 1.5fr;gap:22px;align-items:start}
.profile-side{display:flex;flex-direction:column;gap:18px}
.profile-card{padding:28px;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r-lg);text-align:center}
.big-avatar{width:120px;height:120px;border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:700;font-size:2.5rem;margin:0 auto 18px;box-shadow:0 18px 40px rgba(0,0,0,0.18);border:4px solid rgba(255,255,255,0.4)}
.profile-name{font-family:var(--serif);font-size:1.4rem;font-weight:600}
.profile-phone{color:var(--muted);font-size:0.92rem;margin-top:4px}
.profile-stats{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:24px;padding-top:24px;border-top:1px solid var(--border)}
.profile-stat{padding:14px}
.profile-stat-num{font-family:var(--serif);font-size:1.4rem;font-weight:700;color:var(--primary);line-height:1}
.profile-stat-label{font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-top:4px;font-weight:600}
@media (max-width:1024px){.profile-grid{grid-template-columns:1fr}}
CSS);
vpy_panel_sidebar('profil', false);
?>

<main class="main">
<?php vpy_panel_topbar(t('profile_title'), t('profile_personal')); ?>

<div class="profile-grid">
    <div class="profile-side">
        <div class="profile-card">
            <div class="big-avatar" style="background:<?= e($color) ?>"><?= e(vpy_user_initials($u['name'])) ?></div>
            <div class="profile-name"><?= e($u['name']) ?></div>
            <div class="profile-phone"><?= e($u['phone']) ?></div>
            <div class="profile-stats">
                <div class="profile-stat">
                    <div class="profile-stat-num"><?= (int)($u['tests_taken'] ?? 0) ?></div>
                    <div class="profile-stat-label"><?= e(t('count_tests')) ?></div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-num"><?= (int)($u['best_score'] ?? 0) ?></div>
                    <div class="profile-stat-label"><?= e(t('user_score')) ?> max</div>
                </div>
            </div>
        </div>

        <div class="profile-card" style="text-align:left">
            <h3 style="font-family:var(--serif);font-size:1.1rem;font-weight:600;margin-bottom:14px"><?= e(t('referral_title')) ?></h3>
            <div style="display:flex;gap:8px;align-items:center;padding:12px 16px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-radius:14px">
                <span style="font-family:var(--serif);font-size:1.2rem;font-weight:700;letter-spacing:0.04em"><?= e($u['referral_code'] ?? '') ?></span>
                <button onclick="navigator.clipboard.writeText('<?= e($u['referral_code'] ?? '') ?>');this.textContent='✓'" style="margin-left:auto;padding:6px 12px;background:rgba(255,255,255,0.18);color:#fff;border-radius:var(--pill);font-size:0.78rem">Nusxalash</button>
            </div>
            <a href="/user/referallar.php" style="margin-top:14px;display:inline-flex;align-items:center;gap:6px;color:var(--primary);font-weight:600;font-size:0.88rem"><?= e(t('btn_more')) ?> →</a>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-head"><h2><?= e(t('profile_personal')) ?></h2></div>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                <input type="hidden" name="action" value="profile">
                <div class="field-row">
                    <div class="field">
                        <label><?= e(t('auth_name')) ?></label>
                        <input type="text" name="name" value="<?= e($u['name']) ?>" required>
                    </div>
                    <div class="field">
                        <label><?= e(t('auth_phone')) ?></label>
                        <input type="text" value="<?= e($u['phone']) ?>" disabled style="opacity:0.7">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><?= e(t('btn_save')) ?> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></button>
            </form>
        </div>

        <div class="card" style="margin-top:18px">
            <div class="card-head"><h2><?= e(t('profile_change_password')) ?></h2></div>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                <input type="hidden" name="action" value="password">
                <div class="field">
                    <label><?= e(t('profile_old_password')) ?></label>
                    <input type="password" name="old" required>
                </div>
                <div class="field">
                    <label><?= e(t('profile_new_password')) ?></label>
                    <input type="password" name="new" required minlength="6">
                </div>
                <button type="submit" class="btn btn-dark"><?= e(t('btn_save')) ?></button>
            </form>
        </div>
    </div>
</div>
</main>
<?php vpy_panel_foot(); ?>
