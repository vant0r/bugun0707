<?php
/**
 * Telegram Login Widget callback handler
 * 
 * Agar foydalanuvchi ro'yxatdan o'tgan va telegram_id bog'langan bo'lsa → kabinetga avtomatik kirish
 * Agar ro'yxatdan o'tmagan yoki bog'lanmagan bo'lsa → bosh sahifaga yo'naltirish
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Allaqachon tizimga kirgan bo'lsa
if (vpy_is_logged()) {
    vpy_redirect(vpy_is_admin() ? '/admin/' : '/user/');
}

// Telegram dan kelgan ma'lumotlarni tekshirish
$auth_data = $_GET;
if (empty($auth_data['hash']) || empty($auth_data['id'])) {
    vpy_redirect('/');
}

// Bot token olish
$bot_token = vpy_setting('telegram_bot_token', VPY_TELEGRAM_BOT_TOKEN);
if (!$bot_token) {
    vpy_flash_set('error', 'Telegram bot sozlanmagan');
    vpy_redirect('/login.php');
}

// Telegram ma'lumotlarini tekshirish (HMAC-SHA256)
if (!vpy_telegram_verify($auth_data, $bot_token)) {
    vpy_flash_set('error', 'Telegram ma\'lumotlari noto\'g\'ri yoki muddati o\'tgan');
    vpy_redirect('/login.php');
}

$telegram_id = (int)$auth_data['id'];
$tg_first_name = $auth_data['first_name'] ?? '';
$tg_last_name = $auth_data['last_name'] ?? '';
$tg_username = $auth_data['username'] ?? '';
$tg_photo = $auth_data['photo_url'] ?? '';
$tg_name = trim($tg_first_name . ' ' . $tg_last_name);

// 1. telegram_id bo'yicha foydalanuvchini izlash
$user = vpy_find('users', 'telegram_id', $telegram_id);

if ($user) {
    // Ro'yxatdan o'tgan va bog'langan — avtomatik kirish
    if (($user['status'] ?? 'active') !== 'active') {
        vpy_flash_set('error', 'Hisob bloklangan');
        vpy_redirect('/login.php');
    }
    
    // Telegram ma'lumotlarini yangilash
    $user['telegram_username'] = $tg_username;
    $user['telegram_photo'] = $tg_photo;
    $user['last_login'] = date('Y-m-d H:i:s');
    vpy_upsert('users', $user);
    
    // Tizimga kirish
    vpy_login_set($user);
    vpy_remember_create((int)$user['id']);
    vpy_log('telegram_login', 'Telegram orqali kirish', ['user_id' => $user['id'], 'telegram_id' => $telegram_id]);
    
    // Kabinetga yo'naltirish
    $redirect = $_SESSION['vpy_login_redirect'] ?? null;
    unset($_SESSION['vpy_login_redirect']);
    vpy_redirect(vpy_safe_redirect_target($redirect, ($user['role'] ?? 'user') === 'admin' ? '/admin/' : '/user/'));
}

// 2. Telegram ID topilmadi — bosh sahifaga yo'naltirish
// Telegram ma'lumotlarini sessionga saqlash (keyinchalik bog'lash uchun)
$_SESSION['vpy_telegram_pending'] = [
    'id' => $telegram_id,
    'name' => $tg_name,
    'username' => $tg_username,
    'photo' => $tg_photo,
    'auth_date' => $auth_data['auth_date'] ?? time()
];

vpy_redirect('/');


/**
 * Telegram Login Widget ma'lumotlarini HMAC-SHA256 bilan tekshirish
 */
function vpy_telegram_verify(array $data, string $bot_token): bool {
    $hash = $data['hash'] ?? '';
    unset($data['hash']);
    
    // auth_date ni tekshirish (24 soatdan oshmasligi kerak)
    if (empty($data['auth_date']) || (time() - (int)$data['auth_date']) > 86400) {
        return false;
    }
    
    // Data-check-string yaratish
    $check_arr = [];
    foreach ($data as $key => $value) {
        $check_arr[] = $key . '=' . $value;
    }
    sort($check_arr);
    $check_string = implode("\n", $check_arr);
    
    // HMAC-SHA256 bilan tekshirish
    $secret_key = hash('sha256', $bot_token, true);
    $hmac = hash_hmac('sha256', $check_string, $secret_key);
    
    return hash_equals($hmac, $hash);
}
