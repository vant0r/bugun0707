<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!vpy_is_logged()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$id = (int)vpy_get('id');
$num = vpy_get('num');
$payment = null;
foreach (vpy_read_json('tolovlar', []) as $p) {
    if ((int)($p['user_id'] ?? 0) !== (int)vpy_user()['id'] && !vpy_is_admin()) continue;
    if (($id && (int)$p['id'] === $id) || ($num && ($p['invoice_number'] ?? '') === $num)) {
        $payment = $p;
        break;
    }
}
if (!$payment) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

echo json_encode([
    'ok' => true,
    'invoice' => [
        'id' => (int)$payment['id'],
        'number' => $payment['invoice_number'],
        'amount' => (float)$payment['amount'],
        'status' => $payment['status'],
        'method' => $payment['method'],
        'tariff_name' => $payment['tariff_name'],
        'paid_at' => $payment['paid_at'] ?? null,
        'expires_at' => $payment['expires_at'] ?? null,
        'created_at' => $payment['created_at'],
    ],
], JSON_UNESCAPED_UNICODE);
