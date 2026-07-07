<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$installed = vpy_setting('installed', '0') === '1';
$step = (int)vpy_get('step', 1);
$messages = [];
$errors = [];

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    if ($step === 1) {
        $pdo = vpy_pdo();
        if ($pdo) {
            try {
                $sql = file_get_contents(__DIR__ . '/sql/test_savollar.sql');
                $statements = preg_split('/;\s*\n/', $sql);
                $count = 0;
                foreach ($statements as $stmt) {
                    $stmt = trim($stmt);
                    if ($stmt === '' || strpos($stmt, '--') === 0) continue;
                    try { $pdo->exec($stmt); $count++; } catch (Exception $e) {}
                }
                $total = (int)$pdo->query("SELECT COUNT(*) FROM test_savollar")->fetchColumn();
                $messages[] = sprintf('Bazada %d ta savol muvaffaqiyatli yaratildi', $total);
                vpy_redirect('/install.php?step=2');
            } catch (Exception $e) {
                $errors[] = 'SQL xatosi: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Ma\'lumotlar bazasiga ulanib bo\'lmadi. config.php ni tekshiring.';
        }
    } elseif ($step === 2) {
        $name = vpy_post('admin_name', 'Administrator');
        $phone = vpy_post('admin_phone');
        $pwd = vpy_post('admin_password');
        if (mb_strlen($pwd, 'UTF-8') < 6) {
            $errors[] = 'Parol kamida 6 belgi bo\'lishi kerak';
        } else {
            $existing = vpy_find('users', 'phone', vpy_phone_normalize($phone));
            if ($existing) {
                $existing['name'] = $name;
                $existing['password'] = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => VPY_HASH_COST]);
                $existing['role'] = 'admin';
                $existing['status'] = 'active';
                vpy_upsert('users', $existing);
            } else {
                $row = [
                    'id' => vpy_id_next('users'),
                    'name' => $name,
                    'phone' => vpy_phone_normalize($phone),
                    'password' => password_hash($pwd, PASSWORD_BCRYPT, ['cost' => VPY_HASH_COST]),
                    'role' => 'admin',
                    'status' => 'active',
                    'referral_code' => 'ADMIN001',
                    'avatar' => '',
                    'balance' => 0,
                    'tests_taken' => 0,
                    'best_score' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_login' => date('Y-m-d H:i:s')
                ];
                vpy_upsert('users', $row);
            }
            $settings = vpy_read_json('sozlamalar', []);
            $found_installed = false;
            foreach ($settings as &$s) {
                if (($s['key'] ?? '') === 'installed') { $s['value'] = '1'; $found_installed = true; }
                if (($s['key'] ?? '') === 'installed_at') $s['value'] = date('Y-m-d H:i:s');
            }
            unset($s);
            if (!$found_installed) {
                $settings[] = ['key' => 'installed', 'value' => '1', 'group' => 'system'];
                $settings[] = ['key' => 'installed_at', 'value' => date('Y-m-d H:i:s'), 'group' => 'system'];
            }
            vpy_write_json('sozlamalar', $settings);
            vpy_log('install', 'Tizim o\'rnatildi', ['admin_phone' => $phone]);
            vpy_redirect('/install.php?step=3');
        }
    }
}

$lang = vpy_lang_code();
$is_cyrl = $lang === 'uz_cyrillic';

$db_status = false;
$db_error = '';
$db_count = 0;
if ($step === 1) {
    $pdo = vpy_pdo();
    if ($pdo) {
        $db_status = true;
        try { $db_count = (int)$pdo->query("SELECT COUNT(*) FROM test_savollar")->fetchColumn(); } catch (Exception $e) {}
    } else {
        $db_error = 'PDO ulanishi qurilmadi';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $is_cyrl ? 'uz-Cyrl' : 'uz' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0D6B4E">
<meta name="robots" content="noindex">
<title><?= e(t('install_title')) ?> — <?= e(t('site_name')) ?></title>
<link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#FAF7F2;--primary:#0D6B4E;--primary-dark:#094D38;--accent:#E8A838;--dark:#1E1B18;--dark-soft:#3B362F;--muted:#7A6F62;--light:#FFFDF9;--glass:rgba(255,252,248,0.7);--glass-strong:rgba(255,252,248,0.88);--border:rgba(180,160,130,0.25);--border-strong:rgba(180,160,130,0.45);--shadow-lg:0 50px 100px rgba(30,27,24,0.18);--r-lg:32px;--pill:100px;--t:0.4s cubic-bezier(0.4,0,0.2,1);--serif:"Playfair Display",Georgia,serif;--sans:"Manrope","Inter",sans-serif}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--sans);font-size:16px;line-height:1.6;color:var(--dark);background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;-webkit-font-smoothing:antialiased;position:relative}
body::before{content:"";position:fixed;inset:0;pointer-events:none;z-index:1;opacity:0.3;mix-blend-mode:multiply;background-image:url("data:image/svg+xml;utf8,<svg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3'/><feColorMatrix values='0 0 0 0 0.12 0 0 0 0 0.10 0 0 0 0 0.08 0 0 0 0.4 0'/></filter><rect width='100%25' height='100%25' filter='url(%23n)' opacity='0.5'/></svg>")}
.bg-mesh{position:fixed;inset:0;z-index:0;overflow:hidden;pointer-events:none}
.blob{position:absolute;border-radius:50%;filter:blur(80px);opacity:0.5}
.blob.b1{width:55vw;height:55vw;background:radial-gradient(circle,#B7C9B3,transparent 70%);top:-20vh;left:-15vw}
.blob.b2{width:50vw;height:50vw;background:radial-gradient(circle,#F5D08A,transparent 70%);bottom:-15vh;right:-15vw;opacity:0.4}
.frame{position:relative;z-index:2;width:100%;max-width:640px;background:var(--glass);backdrop-filter:blur(30px) saturate(180%);-webkit-backdrop-filter:blur(30px) saturate(180%);border:1px solid var(--border);border-radius:var(--r-lg);padding:48px 44px;box-shadow:var(--shadow-lg)}
.brand{display:inline-flex;align-items:center;gap:12px;margin-bottom:30px;font-family:var(--serif);font-weight:700;font-size:1.18rem}
.brand-logo{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;display:grid;place-items:center;box-shadow:0 6px 16px rgba(13,107,78,0.3)}
.brand-logo svg{width:22px;height:22px}
h1{font-family:var(--serif);font-weight:500;font-size:2rem;line-height:1.1;letter-spacing:-0.02em;margin-bottom:10px}
h1 em{font-style:italic;color:var(--primary)}
.sub{color:var(--muted);margin-bottom:30px}
.steps{display:flex;gap:0;margin-bottom:30px;border-radius:14px;overflow:hidden;border:1px solid var(--border)}
.step-item{flex:1;padding:14px 18px;background:rgba(255,253,249,0.5);font-size:0.85rem;font-weight:600;color:var(--muted);position:relative;display:flex;align-items:center;gap:10px}
.step-item.active{background:var(--primary);color:#fff}
.step-item.done{background:rgba(13,107,78,0.1);color:var(--primary)}
.step-num{width:24px;height:24px;border-radius:50%;background:rgba(255,255,255,0.2);display:grid;place-items:center;font-size:0.78rem;flex-shrink:0}
.step-item.done .step-num{background:var(--primary);color:#fff}
.field{margin-bottom:18px}
.field label{display:block;font-size:0.82rem;font-weight:600;color:var(--dark-soft);margin-bottom:8px;letter-spacing:0.02em}
.field input{width:100%;padding:14px 18px;border-radius:14px;border:1px solid var(--border-strong);background:rgba(255,253,249,0.85);color:var(--dark);font-size:0.95rem;font-family:inherit;transition:var(--t)}
.field input:focus{outline:none;border-color:var(--primary);background:var(--light);box-shadow:0 0 0 4px rgba(13,107,78,0.12)}
.btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:18px 28px;border-radius:var(--pill);border:none;font-family:inherit;font-weight:600;font-size:0.95rem;cursor:pointer;background:linear-gradient(135deg,var(--accent),#D88F1A);color:#fff;box-shadow:0 16px 36px rgba(232,168,56,0.4);transition:var(--t)}
.btn:hover{transform:translateY(-2px);box-shadow:0 22px 44px rgba(232,168,56,0.55)}
.alert{padding:14px 18px;border-radius:14px;margin-bottom:18px;display:flex;align-items:flex-start;gap:10px;font-size:0.9rem;line-height:1.5}
.alert.success{background:rgba(13,107,78,0.1);color:var(--primary-dark);border:1px solid rgba(13,107,78,0.2)}
.alert.error{background:rgba(255,96,88,0.1);color:#C73E36;border:1px solid rgba(255,96,88,0.3)}
.alert.info{background:rgba(232,168,56,0.1);color:#A87830;border:1px solid rgba(232,168,56,0.3)}
.alert svg{width:18px;height:18px;flex-shrink:0;margin-top:2px}
.check-list{display:flex;flex-direction:column;gap:14px;margin:24px 0}
.check-item{display:flex;align-items:center;gap:14px;padding:14px 18px;background:rgba(255,253,249,0.5);border:1px solid var(--border);border-radius:14px}
.check-item.ok{background:rgba(13,107,78,0.06);border-color:rgba(13,107,78,0.2)}
.check-item.fail{background:rgba(255,96,88,0.06);border-color:rgba(255,96,88,0.2)}
.check-icon{width:28px;height:28px;border-radius:8px;display:grid;place-items:center;flex-shrink:0}
.check-item.ok .check-icon{background:var(--primary);color:#fff}
.check-item.fail .check-icon{background:#C73E36;color:#fff}
.check-icon svg{width:16px;height:16px}
.check-text{flex:1;font-size:0.92rem}
.check-text strong{display:block;color:var(--dark);margin-bottom:2px}
.check-text span{color:var(--muted);font-size:0.85rem}
.success-icon{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;display:grid;place-items:center;margin:0 auto 24px;box-shadow:0 16px 40px rgba(13,107,78,0.3)}
.success-icon svg{width:40px;height:40px}
@keyframes appear{from{opacity:0;transform:translateY(20px) scale(0.96)}to{opacity:1;transform:translateY(0) scale(1)}}
.frame{animation:appear 0.7s cubic-bezier(0.34,1.56,0.64,1)}
@media (max-width:560px){.frame{padding:30px 24px}h1{font-size:1.6rem}.steps{flex-direction:column;border-radius:14px}.step-item{justify-content:flex-start}}
</style>
</head>
<body>

<div class="bg-mesh">
    <div class="blob b1"></div>
    <div class="blob b2"></div>
</div>

<div class="frame">
    <div class="brand">
        <span class="brand-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l2-2 4 4 8-8 4 4"/></svg>
        </span>
        <?= e(t('site_name')) ?>
    </div>

    <h1><em><?= e(t('install_title')) ?></em></h1>
    <p class="sub"><?= e(t('install_subtitle')) ?></p>

    <div class="steps">
        <div class="step-item <?= $step === 1 ? 'active' : ($step > 1 ? 'done' : '') ?>"><span class="step-num"><?= $step > 1 ? '✓' : '1' ?></span><?= e(t('install_step_db')) ?></div>
        <div class="step-item <?= $step === 2 ? 'active' : ($step > 2 ? 'done' : '') ?>"><span class="step-num"><?= $step > 2 ? '✓' : '2' ?></span><?= e(t('install_step_admin')) ?></div>
        <div class="step-item <?= $step === 3 ? 'active' : '' ?>"><span class="step-num">3</span><?= e(t('install_step_finish')) ?></div>
    </div>

    <?php foreach ($errors as $err): ?>
        <div class="alert error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
            <span><?= e($err) ?></span>
        </div>
    <?php endforeach; ?>

    <?php if ($step === 1): ?>
        <div class="check-list">
            <div class="check-item <?= version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'fail' ?>">
                <div class="check-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="check-text">
                    <strong>PHP versiyasi</strong>
                    <span>Talab: 7.4+ · Sizda: <?= e(PHP_VERSION) ?></span>
                </div>
            </div>
            <div class="check-item <?= extension_loaded('pdo_mysql') ? 'ok' : 'fail' ?>">
                <div class="check-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="check-text">
                    <strong>PDO MySQL</strong>
                    <span><?= extension_loaded('pdo_mysql') ? 'Faol' : 'Yo\'q' ?></span>
                </div>
            </div>
            <div class="check-item <?= is_writable(VPY_DATA) ? 'ok' : 'fail' ?>">
                <div class="check-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="check-text">
                    <strong>data/ papkasi yozish</strong>
                    <span><?= is_writable(VPY_DATA) ? 'Yozish ruxsat etilgan' : 'Yozish ruxsat etilmagan' ?></span>
                </div>
            </div>
            <div class="check-item <?= $db_status ? 'ok' : 'fail' ?>">
                <div class="check-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="check-text">
                    <strong>Ma'lumotlar bazasi (<?= e(VPY_DB_NAME) ?>)</strong>
                    <span><?= $db_status ? "Ulanish muvaffaqiyatli, mavjud savollar: $db_count" : "Ulanib bo'lmadi: $db_error" ?></span>
                </div>
            </div>
        </div>

        <?php if ($db_status): ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
            <button type="submit" class="btn">
                <?= e(t('install_install')) ?> · SQL importi
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </button>
        </form>
        <?php else: ?>
            <div class="alert info">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Avval <code>includes/config.php</code> faylida ma'lumotlar bazasi sozlamalarini to'g'rilang.</span>
            </div>
        <?php endif; ?>

    <?php elseif ($step === 2): ?>
        <p style="margin-bottom:20px;color:var(--muted);font-size:0.95rem">Birinchi administrator hisobini yarating. Bu hisob tizimni boshqarish uchun ishlatiladi.</p>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
            <div class="field">
                <label>Administrator ismi</label>
                <input type="text" name="admin_name" required value="Administrator">
            </div>
            <div class="field">
                <label>Telefon raqami</label>
                <input type="tel" name="admin_phone" required placeholder="+998 90 123 45 67">
            </div>
            <div class="field">
                <label>Parol (kamida 6 belgi)</label>
                <input type="password" name="admin_password" required minlength="6">
            </div>
            <button type="submit" class="btn">
                <?= e(t('btn_next')) ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </button>
        </form>

    <?php else: ?>
        <div style="text-align:center">
            <div class="success-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h2 style="font-family:var(--serif);font-size:1.5rem;font-weight:600;margin-bottom:8px"><?= e(t('install_success')) ?></h2>
            <p style="color:var(--muted);margin-bottom:30px">Endi tizimga administrator sifatida kirib, sozlamalarni yakunlashingiz mumkin.</p>
            <div class="alert info" style="text-align:left">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v3M12 16h.01"/><circle cx="12" cy="12" r="10"/></svg>
                <span><strong>Xavfsizlik:</strong> O'rnatish tugagandan so'ng, <code>install.php</code> faylini server papkangizdan o'chirib tashlang.</span>
            </div>
            <a href="/login.php" class="btn" style="margin-top:18px;text-decoration:none">
                <?= e(t('auth_login_btn')) ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
