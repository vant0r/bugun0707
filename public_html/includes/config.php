<?php
if (!defined('VATANPARVAR')) define('VATANPARVAR', true);

date_default_timezone_set('Asia/Tashkent');
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', (string)(60 * 60 * 24 * 30));
    ini_set('session.cookie_lifetime', (string)(60 * 60 * 24 * 30));
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_name('VPYSESS');
    session_start();
}

define('VPY_ROOT', dirname(__DIR__));
define('VPY_DATA', VPY_ROOT . '/data');
define('VPY_LANG', VPY_ROOT . '/lang');
define('VPY_UPLOADS', VPY_ROOT . '/assets/uploads');
define('VPY_VERSION', '1.0.0');
define('VPY_NAME', 'VatanParvar Yaypan');
define('VPY_DOMAIN', 'vatanparvaryaypan.uz');

define('VPY_DB_HOST', 'localhost');
define('VPY_DB_NAME', 'host8873_avto');
define('VPY_DB_USER', 'host8873_avto');
define('VPY_DB_PASS', 'Salom0317!)');
define('VPY_DB_CHARSET', 'utf8mb4');

define('VPY_SECRET', 'vpy_yaypan_2026_emerald_amber_secret_key_change_me');
define('VPY_HASH_COST', 11);

define('VPY_TELEGRAM_BOT_TOKEN', '');
define('VPY_TELEGRAM_CHAT_ID', '');

define('VPY_CLICK_SERVICE_ID', '');
define('VPY_CLICK_MERCHANT_ID', '');
define('VPY_CLICK_SECRET_KEY', '');
define('VPY_CLICK_USER_ID', '');

define('VPY_PAYME_MERCHANT_ID', '');
define('VPY_PAYME_KEY', '');
define('VPY_PAYME_TEST', true);

function vpy_pdo() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    try {
        $dsn = 'mysql:host=' . VPY_DB_HOST . ';dbname=' . VPY_DB_NAME . ';charset=' . VPY_DB_CHARSET;
        $pdo = new PDO($dsn, VPY_DB_USER, VPY_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
        return $pdo;
    } catch (PDOException $e) {
        if (defined('VPY_DEBUG') && VPY_DEBUG) {
            die('DB ulanish xatosi: ' . htmlspecialchars($e->getMessage()));
        }
        $pdo = false;
        return false;
    }
}

function vpy_lang_code() {
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['uz_latin', 'uz_cyrillic'], true)) {
        $_SESSION['vpy_lang'] = $_GET['lang'];
        setcookie('vpy_lang', $_GET['lang'], time() + 31536000, '/');
    }
    if (isset($_SESSION['vpy_lang'])) return $_SESSION['vpy_lang'];
    if (isset($_COOKIE['vpy_lang']) && in_array($_COOKIE['vpy_lang'], ['uz_latin', 'uz_cyrillic'], true)) {
        $_SESSION['vpy_lang'] = $_COOKIE['vpy_lang'];
        return $_COOKIE['vpy_lang'];
    }
    return 'uz_latin';
}

function vpy_lang() {
    static $cache = [];
    $code = vpy_lang_code();
    if (isset($cache[$code])) return $cache[$code];
    $file = VPY_LANG . '/' . $code . '.php';
    if (!is_file($file)) {
        $file = VPY_LANG . '/uz_latin.php';
    }
    $cache[$code] = is_file($file) ? include $file : [];
    return $cache[$code];
}

function t($key, $default = null) {
    $L = vpy_lang();
    if (isset($L[$key])) return $L[$key];
    return $default !== null ? $default : $key;
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}