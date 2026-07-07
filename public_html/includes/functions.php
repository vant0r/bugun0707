<?php
if (!defined('VATANPARVAR')) require_once __DIR__ . '/config.php';

function vpy_data_path($name) {
    return VPY_DATA . '/' . preg_replace('/[^a-z0-9_\-]/i', '', $name) . '.json';
}

function vpy_read_json($name, $default = []) {
    $path = vpy_data_path($name);
    if (!is_file($path)) return $default;
    $fp = @fopen($path, 'r');
    if (!$fp) return $default;
    @flock($fp, LOCK_SH);
    $raw = stream_get_contents($fp);
    @flock($fp, LOCK_UN);
    fclose($fp);
    if ($raw === '' || $raw === false) return $default;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}

function vpy_write_json($name, $data) {
    $path = vpy_data_path($name);
    if (!is_dir(VPY_DATA)) @mkdir(VPY_DATA, 0775, true);
    $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return @rename($tmp, $path);
}

function vpy_id_next($name) {
    $rows = vpy_read_json($name, []);
    $max = 0;
    foreach ($rows as $r) if (isset($r['id']) && (int)$r['id'] > $max) $max = (int)$r['id'];
    return $max + 1;
}

function vpy_find($name, $field, $value) {
    foreach (vpy_read_json($name, []) as $r) {
        if (isset($r[$field]) && (string)$r[$field] === (string)$value) return $r;
    }
    return null;
}

function vpy_filter($name, $callback) {
    return array_values(array_filter(vpy_read_json($name, []), $callback));
}

function vpy_upsert($name, $row, $key = 'id') {
    $rows = vpy_read_json($name, []);
    $found = false;
    if (!empty($row[$key])) {
        foreach ($rows as &$r) {
            if (isset($r[$key]) && (string)$r[$key] === (string)$row[$key]) {
                $r = array_merge($r, $row);
                $found = true;
                break;
            }
        }
        unset($r);
    }
    if (!$found) {
        if (empty($row[$key]) && $key === 'id') $row['id'] = vpy_id_next($name);
        if (empty($row['created_at'])) $row['created_at'] = date('Y-m-d H:i:s');
        $rows[] = $row;
    } else {
        $row['updated_at'] = date('Y-m-d H:i:s');
    }
    vpy_write_json($name, $rows);
    return $row[$key] ?? null;
}

function vpy_delete($name, $key, $value) {
    $rows = vpy_read_json($name, []);
    $rows = array_values(array_filter($rows, function ($r) use ($key, $value) {
        return !(isset($r[$key]) && (string)$r[$key] === (string)$value);
    }));
    vpy_write_json($name, $rows);
}

function vpy_setting($key, $default = '') {
    static $cache = null;
    if ($cache === null) {
        $rows = vpy_read_json('sozlamalar', []);
        $cache = [];
        foreach ($rows as $r) if (isset($r['key'])) $cache[$r['key']] = $r['value'] ?? '';
    }
    return $cache[$key] ?? $default;
}

function vpy_csrf() {
    if (empty($_SESSION['vpy_csrf'])) {
        $_SESSION['vpy_csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['vpy_csrf'];
}

function vpy_csrf_check($token) {
    return !empty($token) && !empty($_SESSION['vpy_csrf']) && hash_equals($_SESSION['vpy_csrf'], (string)$token);
}

function vpy_redirect($url) {
    header('Location: ' . $url);
    exit;
}

function vpy_log($type, $message, $meta = []) {
    $rows = vpy_read_json('loglar', []);
    $rows[] = [
        'id' => vpy_id_next('loglar'),
        'type' => $type,
        'message' => $message,
        'meta' => $meta,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'user_id' => $_SESSION['vpy_user_id'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];
    if (count($rows) > 5000) $rows = array_slice($rows, -5000);
    vpy_write_json('loglar', $rows);
}

function vpy_money($v) {
    return number_format((float)$v, 0, '.', ' ') . ' ' . t('valyuta_sum', 'so\'m');
}

function vpy_date($ts, $format = 'd.m.Y H:i') {
    if (is_string($ts)) $ts = strtotime($ts);
    return date($format, (int)$ts);
}

function vpy_time_ago($ts) {
    if (is_string($ts)) $ts = strtotime($ts);
    $diff = time() - (int)$ts;
    if ($diff < 60) return t('vaqt_hozir', 'hozir');
    if ($diff < 3600) return floor($diff / 60) . ' ' . t('vaqt_daqiqa', 'daqiqa oldin');
    if ($diff < 86400) return floor($diff / 3600) . ' ' . t('vaqt_soat', 'soat oldin');
    if ($diff < 604800) return floor($diff / 86400) . ' ' . t('vaqt_kun', 'kun oldin');
    return date('d.m.Y', (int)$ts);
}

function vpy_slug($str) {
    $str = mb_strtolower(trim($str), 'UTF-8');
    $tr = ['а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'j','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'x','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sh','ъ'=>'','ы'=>'i','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya','қ'=>'q','ў'=>'o','ғ'=>'g','ҳ'=>'h',"'"=>'',"’"=>'',"`"=>''];
    $str = strtr($str, $tr);
    $str = preg_replace('/[^a-z0-9]+/u', '-', $str);
    $str = trim($str, '-');
    return $str !== '' ? $str : 'vpy-' . substr(md5(microtime(true)), 0, 8);
}

function vpy_paginate($items, $perPage = 10, $page = 1) {
    $total = count($items);
    $pages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($pages, (int)$page));
    return [
        'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'per_page' => $perPage
    ];
}

function vpy_is_post() {
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function vpy_post($key, $default = '') {
    return isset($_POST[$key]) ? (is_string($_POST[$key]) ? trim($_POST[$key]) : $_POST[$key]) : $default;
}

function vpy_get($key, $default = '') {
    return isset($_GET[$key]) ? (is_string($_GET[$key]) ? trim($_GET[$key]) : $_GET[$key]) : $default;
}

function vpy_referral_code($len = 8) {
    $a = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < $len; $i++) $s .= $a[random_int(0, strlen($a) - 1)];
    return $s;
}

function vpy_user_initials($name) {
    $parts = preg_split('/\s+/u', trim((string)$name));
    $r = '';
    foreach ($parts as $p) if ($p !== '') $r .= mb_strtoupper(mb_substr($p, 0, 1, 'UTF-8'), 'UTF-8');
    return mb_substr($r, 0, 2, 'UTF-8') ?: 'V';
}

function vpy_avatar_color($seed) {
    $colors = ['#0D6B4E', '#E8A838', '#B7C9B3', '#1E1B18', '#094D38', '#A87830', '#5A8068', '#3B362F'];
    return $colors[crc32((string)$seed) % count($colors)];
}

function vpy_flash_set($type, $msg) {
    $_SESSION['vpy_flash'][] = ['type' => $type, 'msg' => $msg];
}

function vpy_flash_get() {
    $f = $_SESSION['vpy_flash'] ?? [];
    unset($_SESSION['vpy_flash']);
    return $f;
}

function vpy_test_questions($limit = 20, $bilet_id = null) {
    $pdo = vpy_pdo();
    if (!$pdo) return [];
    try {
        if ($bilet_id !== null) {
            $st = $pdo->prepare("SELECT * FROM test_savollar WHERE bilet_id = :b ORDER BY tartib ASC, id ASC");
            $st->execute([':b' => (int)$bilet_id]);
        } else {
            $st = $pdo->prepare("SELECT * FROM test_savollar ORDER BY RAND() LIMIT :l");
            $st->bindValue(':l', (int)$limit, PDO::PARAM_INT);
            $st->execute();
        }
        return $st->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Berilgan bilet raqami bo'yicha barcha savollarni tartib bo'yicha qaytaradi.
 * $limit berilsa, natija shu sondan oshmaydi (ixtiyoriy).
 */
function vpy_test_questions_by_bilet($bilet_id, $limit = null) {
    $rows = vpy_test_questions(0, (int)$bilet_id);
    if ($limit !== null && (int)$limit > 0 && count($rows) > (int)$limit) {
        $rows = array_slice($rows, 0, (int)$limit);
    }
    return $rows;
}

function vpy_test_count() {
    $pdo = vpy_pdo();
    if (!$pdo) return 0;
    try {
        return (int)$pdo->query("SELECT COUNT(*) FROM test_savollar")->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function vpy_active_tariff_for_user($user_id) {
    $payments = vpy_filter('tolovlar', function ($p) use ($user_id) {
        return isset($p['user_id'], $p['status']) && (int)$p['user_id'] === (int)$user_id && $p['status'] === 'success';
    });
    $now = time();
    $best = null;
    foreach ($payments as $p) {
        if (!empty($p['expires_at']) && strtotime($p['expires_at']) > $now) {
            if ($best === null || strtotime($p['expires_at']) > strtotime($best['expires_at'])) $best = $p;
        }
    }
    return $best;
}

function vpy_random_string($len = 16) {
    return bin2hex(random_bytes((int)ceil($len / 2)));
}

function vpy_safe_redirect_target($url, $fallback = '/') {
    if (!is_string($url) || $url === '') return $fallback;
    if (strpos($url, '/') !== 0) return $fallback;
    if (strpos($url, '//') === 0) return $fallback;
    return $url;
}

function vpy_phone_normalize($p) {
    $p = preg_replace('/\D+/', '', (string)$p);
    if (strlen($p) === 9) $p = '998' . $p;
    if (strlen($p) === 12 && strpos($p, '998') === 0) return '+' . $p;
    if (strlen($p) === 13 && strpos($p, '998') === 0) return '+' . substr($p, 1);
    return '+' . $p;
}



/**
 * "Biletlar 50" mashq rejimi uchun - barcha faol savollarni id bo'yicha tartiblaydi
 * va 50 talik guruhga bo'lib, berilgan bilet raqamiga mos savollarni qaytaradi.
 * Masalan: bilet_num=1 → savollar 1-50, bilet_num=2 → 51-100, ...
 */
function vpy_test_questions_bilet50($bilet_num) {
    $pdo = vpy_pdo();
    if (!$pdo) return [];
    try {
        $st = $pdo->query("SELECT * FROM test_savollar WHERE holat='faol' ORDER BY id ASC");
        $all = $st->fetchAll();
        $offset = ((int)$bilet_num - 1) * 50;
        return array_slice($all, $offset, 50);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * "Biletlar 50" uchun jami nechta bilet borligini hisoblaydi.
 */
function vpy_test_bilet50_count() {
    $total = vpy_test_count();
    return $total > 0 ? (int)ceil($total / 50) : 0;
}
