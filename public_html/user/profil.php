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
    } elseif ($action === 'telegram_unlink') {
        unset($u['telegram_id'], $u['telegram_username'], $u['telegram_photo']);
        vpy_upsert('users', $u);
        vpy_flash_set('success', 'Telegram bog\'lama olib tashlandi');
        vpy_redirect('/user/profil.php');
    }
}

// Telegram bog'lash: agar sessionda pending telegram data bo'lsa
if (!empty($_SESSION['vpy_telegram_pending']) && empty($u['telegram_id'])) {
    $tg = $_SESSION['vpy_telegram_pending'];
    $u['telegram_id'] = (int)$tg['id'];
    $u['telegram_username'] = $tg['username'] ?? '';
    $u['telegram_photo'] = $tg['photo'] ?? '';
    vpy_upsert('users', $u);
    unset($_SESSION['vpy_telegram_pending']);
    vpy_flash_set('success', 'Telegram hisobingiz bog\'landi! Endi Telegram orqali tez kirishingiz mumkin.');
    vpy_redirect('/user/profil.php');
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

        <!-- Telegram bog'lash -->
        <div class="profile-card" style="text-align:left">
            <h3 style="font-family:var(--serif);font-size:1.1rem;font-weight:600;margin-bottom:14px">
                <svg viewBox="0 0 24 24" fill="#0088cc" style="width:20px;height:20px;vertical-align:middle;margin-right:6px"><path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                Telegram
            </h3>
            <?php if (!empty($u['telegram_id'])): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:14px 16px;background:rgba(0,136,204,0.06);border:1px solid rgba(0,136,204,0.15);border-radius:14px">
                    <?php if (!empty($u['telegram_photo'])): ?>
                        <img src="<?= e($u['telegram_photo']) ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover" alt="">
                    <?php else: ?>
                        <div style="width:40px;height:40px;border-radius:50%;background:#0088cc;display:grid;place-items:center;color:#fff;font-weight:700;font-size:1.1rem"><?= e(mb_substr($u['name'], 0, 1, 'UTF-8')) ?></div>
                    <?php endif; ?>
                    <div style="flex:1">
                        <div style="font-weight:700;font-size:0.9rem"><?= e(!empty($u['telegram_username']) ? '@' . $u['telegram_username'] : 'ID: ' . $u['telegram_id']) ?></div>
                        <div style="font-size:0.75rem;color:#10b981;font-weight:600">Bog'langan</div>
                    </div>
                </div>
                <form method="post" style="margin-top:12px">
                    <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                    <input type="hidden" name="action" value="telegram_unlink">
                    <button type="submit" onclick="return confirm('Telegram bog\'lamani olib tashlamoqchimisiz?')" style="padding:8px 16px;border-radius:10px;border:1px solid rgba(239,68,68,0.3);background:rgba(239,68,68,0.05);color:#ef4444;font-size:0.8rem;font-weight:600;cursor:pointer">Bog'lamani olib tashlash</button>
                </form>
            <?php else: ?>
                <?php $bot_username = vpy_setting('telegram_bot_username', ''); ?>
                <?php if ($bot_username): ?>
                    <p style="font-size:0.82rem;color:var(--muted);margin-bottom:12px">Telegram hisobingizni bog'lang va keyingi safar tezkor kirish imkoniga ega bo'ling</p>
                    <script async src="https://telegram.org/js/telegram-widget.js?22"
                        data-telegram-login="<?= e($bot_username) ?>"
                        data-size="large"
                        data-radius="14"
                        data-auth-url="https://<?= e(VPY_DOMAIN) ?>/api/telegram-auth.php"
                        data-request-access="write"></script>
                <?php else: ?>
                    <p style="font-size:0.82rem;color:var(--muted)">Telegram bot sozlanmagan. Admin sozlamalardan bot username kiriting.</p>
                <?php endif; ?>
            <?php endif; ?>
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
