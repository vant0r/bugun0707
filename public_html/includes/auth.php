<?php
if (!defined('VATANPARVAR')) require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

function vpy_register($name, $phone, $password, $referral = '') {
    $name = trim((string)$name);
    $phone = vpy_phone_normalize($phone);
    if (mb_strlen($name, 'UTF-8') < 2) return ['ok' => false, 'error' => t('xato_ism_qisqa', 'Ism juda qisqa')];
    if (!preg_match('/^\+998\d{9}$/', $phone)) return ['ok' => false, 'error' => t('xato_telefon_xato', 'Telefon raqami noto\'g\'ri')];
    if (strlen((string)$password) < 6) return ['ok' => false, 'error' => t('xato_parol_qisqa', 'Parol kamida 6 belgi')];
    if (vpy_find('users', 'phone', $phone)) return ['ok' => false, 'error' => t('xato_telefon_band', 'Bu telefon allaqachon ro\'yxatdan o\'tgan')];

    $referrer_id = null;
    if (!empty($referral)) {
        $ref = vpy_find('users', 'referral_code', strtoupper(trim($referral)));
        if ($ref) $referrer_id = (int)$ref['id'];
    }

    $row = [
        'id' => vpy_id_next('users'),
        'name' => $name,
        'phone' => $phone,
        'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => VPY_HASH_COST]),
        'role' => 'user',
        'status' => 'active',
        'referral_code' => vpy_referral_code(8),
        'referrer_id' => $referrer_id,
        'avatar' => '',
        'balance' => 0,
        'tests_taken' => 0,
        'best_score' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => date('Y-m-d H:i:s')
    ];
    vpy_upsert('users', $row);
    vpy_log('register', 'Yangi foydalanuvchi', ['user_id' => $row['id'], 'phone' => $phone]);

    if ($referrer_id) {
        $ref = vpy_find('users', 'id', $referrer_id);
        if ($ref) {
            $ref['balance'] = (int)($ref['balance'] ?? 0) + 5000;
            vpy_upsert('users', $ref);
            vpy_log('referral', 'Referral bonus', ['referrer_id' => $referrer_id, 'new_user_id' => $row['id']]);
        }
    }

    vpy_login_set($row);
    vpy_remember_create((int)$row['id']);
    return ['ok' => true, 'user' => $row];
}

function vpy_login($phone, $password, $remember = false) {
    $phone = vpy_phone_normalize($phone);
    $u = vpy_find('users', 'phone', $phone);
    if (!$u) return ['ok' => false, 'error' => t('xato_login_yoq', 'Foydalanuvchi topilmadi')];
    if (($u['status'] ?? 'active') !== 'active') return ['ok' => false, 'error' => t('xato_blok', 'Hisob bloklangan')];
    if (!password_verify((string)$password, (string)($u['password'] ?? ''))) return ['ok' => false, 'error' => t('xato_parol_xato', 'Parol noto\'g\'ri')];
    $u['last_login'] = date('Y-m-d H:i:s');
    vpy_upsert('users', $u);
    vpy_login_set($u);
    if ($remember) vpy_remember_create((int)$u['id']);
    vpy_log('login', 'Tizimga kirish', ['user_id' => $u['id']]);
    return ['ok' => true, 'user' => $u];
}

function vpy_login_set($user) {
    session_regenerate_id(true);
    $_SESSION['vpy_user_id'] = (int)$user['id'];
    $_SESSION['vpy_user_role'] = $user['role'] ?? 'user';
    $_SESSION['vpy_user_name'] = $user['name'] ?? '';
    $_SESSION['vpy_login_at'] = time();
}

/**
 * "Eslab qol" tokenini yaratadi va uzoq muddatli (30 kunlik) cookie qo'yadi.
 * Token bazaga (JSON) faqat hash holida saqlanadi, xom token esa faqat cookie ichida.
 */
function vpy_remember_create($user_id) {
    $raw = bin2hex(random_bytes(32));
    $hash = hash('sha256', $raw);
    $ttl = 60 * 60 * 24 * 30;
    $rows = vpy_read_json('remember_tokens', []);
    $rows = array_values(array_filter($rows, function ($r) use ($user_id) {
        return (int)($r['user_id'] ?? 0) !== (int)$user_id;
    }));
    $rows[] = [
        'user_id' => (int)$user_id,
        'token_hash' => $hash,
        'expires_at' => date('Y-m-d H:i:s', time() + $ttl),
        'created_at' => date('Y-m-d H:i:s')
    ];
    vpy_write_json('remember_tokens', $rows);
    setcookie('vpy_remember', $user_id . '.' . $raw, [
        'expires' => time() + $ttl,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * Berilgan foydalanuvchining (yoki joriy cookie'ning) "eslab qol" tokenini bekor qiladi.
 */
function vpy_remember_clear($user_id = null) {
    if ($user_id !== null) {
        $rows = vpy_read_json('remember_tokens', []);
        $rows = array_values(array_filter($rows, function ($r) use ($user_id) {
            return (int)($r['user_id'] ?? 0) !== (int)$user_id;
        }));
        vpy_write_json('remember_tokens', $rows);
    }
    if (isset($_COOKIE['vpy_remember'])) {
        setcookie('vpy_remember', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        unset($_COOKIE['vpy_remember']);
    }
}

/**
 * Sessiya tugagan bo'lsa, "eslab qol" cookie'si orqali foydalanuvchini avtomatik
 * qaytadan tizimga kiritadi. Har bir sahifa yuklanganda (auth.php orqali) ishlaydi.
 */
function vpy_remember_check() {
    if (vpy_is_logged()) return;
    if (empty($_COOKIE['vpy_remember'])) return;

    $parts = explode('.', (string)$_COOKIE['vpy_remember'], 2);
    if (count($parts) !== 2) { vpy_remember_clear(); return; }
    [$uid, $raw] = $parts;
    $uid = (int)$uid;
    if ($uid <= 0 || $raw === '') { vpy_remember_clear(); return; }

    $hash = hash('sha256', $raw);
    $rows = vpy_read_json('remember_tokens', []);
    $matched = null;
    foreach ($rows as $r) {
        if ((int)($r['user_id'] ?? 0) === $uid && hash_equals((string)($r['token_hash'] ?? ''), $hash)) {
            $matched = $r;
            break;
        }
    }
    if (!$matched || strtotime($matched['expires_at'] ?? 'now') <= time()) {
        vpy_remember_clear($uid);
        return;
    }

    $user = vpy_find('users', 'id', $uid);
    if (!$user || ($user['status'] ?? 'active') !== 'active') {
        vpy_remember_clear($uid);
        return;
    }

    vpy_login_set($user);
    vpy_remember_create($uid); // token rotatsiyasi — xavfsizlik uchun har safar yangilanadi
    vpy_log('remember_login', 'Eslab qolish orqali avtomatik kirish', ['user_id' => $uid]);
}

function vpy_logout() {
    if (!empty($_SESSION['vpy_user_id'])) {
        vpy_remember_clear((int)$_SESSION['vpy_user_id']);
        vpy_log('logout', 'Tizimdan chiqish', ['user_id' => $_SESSION['vpy_user_id']]);
    } else {
        vpy_remember_clear();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function vpy_user() {
    if (empty($_SESSION['vpy_user_id'])) return null;
    static $cache = null;
    if ($cache !== null && (int)$cache['id'] === (int)$_SESSION['vpy_user_id']) return $cache;
    $cache = vpy_find('users', 'id', $_SESSION['vpy_user_id']);
    return $cache;
}

function vpy_is_logged() {
    return !empty($_SESSION['vpy_user_id']);
}

function vpy_is_admin() {
    return vpy_is_logged() && (($_SESSION['vpy_user_role'] ?? '') === 'admin');
}

function vpy_require_login($redirect = '/login.php') {
    if (!vpy_is_logged()) {
        $_SESSION['vpy_login_redirect'] = $_SERVER['REQUEST_URI'] ?? '/';
        vpy_redirect($redirect);
    }
}

function vpy_require_admin($redirect = '/login.php') {
    vpy_require_login($redirect);
    if (!vpy_is_admin()) {
        http_response_code(403);
        die('<h1 style="font-family:system-ui;text-align:center;margin-top:20vh;color:#0D6B4E">403 — ' . e(t('xato_ruxsat_yoq', 'Ruxsat berilmagan')) . '</h1>');
    }
}

function vpy_password_change($user_id, $old, $new) {
    $u = vpy_find('users', 'id', $user_id);
    if (!$u) return ['ok' => false, 'error' => t('xato_login_yoq', 'Foydalanuvchi topilmadi')];
    if (!password_verify((string)$old, (string)$u['password'])) return ['ok' => false, 'error' => t('xato_eski_parol', 'Eski parol noto\'g\'ri')];
    if (strlen((string)$new) < 6) return ['ok' => false, 'error' => t('xato_parol_qisqa', 'Parol kamida 6 belgi')];
    $u['password'] = password_hash($new, PASSWORD_BCRYPT, ['cost' => VPY_HASH_COST]);
    vpy_upsert('users', $u);
    vpy_log('password_change', 'Parol o\'zgartirildi', ['user_id' => $user_id]);
    return ['ok' => true];
}

// Har bir sahifa yuklanganda (bu fayl require qilingan joyda) sessiya tugagan bo'lsa,
// "eslab qol" cookie'si orqali foydalanuvchini avtomatik tizimga kiritishga harakat qiladi.
vpy_remember_check();