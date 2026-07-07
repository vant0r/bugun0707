<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payments/invoice.php';

header('Content-Type: application/json; charset=utf-8');

$key = vpy_setting('payme_key', VPY_PAYME_KEY);
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (strpos($auth, 'Basic ') === 0) {
    $decoded = base64_decode(substr($auth, 6));
    $parts = explode(':', $decoded, 2);
    if (count($parts) < 2 || $parts[1] !== $key) {
        echo json_encode(['error' => ['code' => -32504, 'message' => 'Insufficient privileges']]);
        exit;
    }
}

$raw = file_get_contents('php://input');
$req = json_decode($raw, true) ?: [];
$method = $req['method'] ?? '';
$params = $req['params'] ?? [];
$id = $req['id'] ?? null;

function payme_resp($result, $req_id) {
    echo json_encode(['jsonrpc' => '2.0', 'id' => $req_id, 'result' => $result]);
    exit;
}
function payme_err($code, $message, $req_id) {
    echo json_encode(['jsonrpc' => '2.0', 'id' => $req_id, 'error' => ['code' => $code, 'message' => $message]]);
    exit;
}

vpy_log('payme_callback', $method, $params);

if ($method === 'CheckPerformTransaction') {
    $order_id = (int)($params['account']['order_id'] ?? 0);
    $payment = vpy_find('tolovlar', 'id', $order_id);
    if (!$payment) payme_err(-31050, 'Order not found', $id);
    if (abs((float)$payment['amount'] * 100 - (float)($params['amount'] ?? 0)) > 1) payme_err(-31001, 'Incorrect amount', $id);
    payme_resp(['allow' => true], $id);
}

if ($method === 'CreateTransaction') {
    $tx_id = $params['id'] ?? '';
    $order_id = (int)($params['account']['order_id'] ?? 0);
    $payment = vpy_find('tolovlar', 'id', $order_id);
    if (!$payment) payme_err(-31050, 'Order not found', $id);
    $payment['transaction_id'] = 'PAYME-' . $tx_id;
    vpy_upsert('tolovlar', $payment);
    payme_resp(['create_time' => round(microtime(true) * 1000), 'transaction' => (string)$payment['id'], 'state' => 1], $id);
}

if ($method === 'PerformTransaction') {
    $tx_id = $params['id'] ?? '';
    foreach (vpy_read_json('tolovlar', []) as $p) {
        if (($p['transaction_id'] ?? '') === 'PAYME-' . $tx_id) {
            vpy_invoice_complete($p['id'], 'PAYME-' . $tx_id);
            payme_resp(['perform_time' => round(microtime(true) * 1000), 'transaction' => (string)$p['id'], 'state' => 2], $id);
        }
    }
    payme_err(-31003, 'Transaction not found', $id);
}

if ($method === 'CheckTransaction') {
    $tx_id = $params['id'] ?? '';
    foreach (vpy_read_json('tolovlar', []) as $p) {
        if (($p['transaction_id'] ?? '') === 'PAYME-' . $tx_id) {
            $state = ($p['status'] ?? '') === 'success' ? 2 : 1;
            payme_resp(['create_time' => strtotime($p['created_at']) * 1000, 'perform_time' => $state === 2 ? strtotime($p['paid_at']) * 1000 : 0, 'cancel_time' => 0, 'transaction' => (string)$p['id'], 'state' => $state, 'reason' => null], $id);
        }
    }
    payme_err(-31003, 'Transaction not found', $id);
}

payme_err(-32601, 'Method not found', $id);
