<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payments/invoice.php';

header('Content-Type: application/json; charset=utf-8');

$secret = vpy_setting('click_secret_key', VPY_CLICK_SECRET_KEY);

$input = $_POST + $_GET;
$action = (int)($input['action'] ?? -1);
$click_trans_id = $input['click_trans_id'] ?? '';
$service_id = $input['service_id'] ?? '';
$merchant_trans_id = $input['merchant_trans_id'] ?? '';
$amount = (float)($input['amount'] ?? 0);
$sign_time = $input['sign_time'] ?? '';
$sign_string = $input['sign_string'] ?? '';

$response = function($error = 0, $note = 'OK', $extra = []) use ($click_trans_id, $merchant_trans_id) {
    $r = [
        'click_trans_id' => $click_trans_id,
        'merchant_trans_id' => $merchant_trans_id,
        'merchant_prepare_id' => $merchant_trans_id,
        'merchant_confirm_id' => $merchant_trans_id,
        'error' => $error,
        'error_note' => $note,
    ];
    if ($extra) $r = array_merge($r, $extra);
    echo json_encode($r);
    vpy_log('click_callback', $note, $r);
    exit;
};

if ($secret) {
    $expected = md5($click_trans_id . $service_id . $secret . $merchant_trans_id . $amount . $action . $sign_time);
    if (!hash_equals($expected, (string)$sign_string)) $response(-1, 'SIGN CHECK FAILED');
}

$payment = vpy_find('tolovlar', 'id', (int)$merchant_trans_id);
if (!$payment) $response(-5, 'Order not found');
if (abs((float)$payment['amount'] - $amount) > 0.01) $response(-2, 'Incorrect amount');

if ($action === 0) {
    $response(0, 'Already paid');
} elseif ($action === 1) {
    if (vpy_invoice_complete($payment['id'], 'CLICK-' . $click_trans_id)) {
        $response(0, 'Success');
    }
    $response(-9, 'Failed');
}

$response(-3, 'Unknown action');
