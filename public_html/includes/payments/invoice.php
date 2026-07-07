<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../notifications.php';

function vpy_invoice_create($user_id, $tariff_id, $method = 'invoice') {
    $tariff = vpy_find('tariflar', 'id', $tariff_id);
    if (!$tariff) return null;
    $payment = [
        'id' => vpy_id_next('tolovlar'),
        'user_id' => (int)$user_id,
        'tariff_id' => (int)$tariff['id'],
        'tariff_name' => $tariff['name'],
        'amount' => (float)$tariff['price'],
        'method' => $method,
        'status' => 'pending',
        'transaction_id' => '',
        'invoice_number' => 'INV-' . date('Y') . '-' . sprintf('%04d', vpy_id_next('tolovlar')),
        'expires_at' => null,
        'paid_at' => null,
        'created_at' => date('Y-m-d H:i:s'),
    ];
    vpy_upsert('tolovlar', $payment);
    return $payment;
}

function vpy_invoice_complete($payment_id, $transaction_id = '') {
    $payment = vpy_find('tolovlar', 'id', $payment_id);
    if (!$payment) return false;
    if (($payment['status'] ?? '') === 'success') return true;
    $tariff = vpy_find('tariflar', 'id', $payment['tariff_id']);
    $payment['status'] = 'success';
    $payment['paid_at'] = date('Y-m-d H:i:s');
    $payment['expires_at'] = date('Y-m-d H:i:s', strtotime('+' . (int)($tariff['duration_days'] ?? 30) . ' days'));
    if ($transaction_id) $payment['transaction_id'] = $transaction_id;
    vpy_upsert('tolovlar', $payment);
    vpy_notify_payment_success($payment['user_id'], $payment['tariff_name'], $payment['amount']);
    vpy_log('payment_success', 'To\'lov muvaffaqiyatli', ['id' => $payment_id, 'method' => $payment['method']]);
    return true;
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $id = (int)vpy_get('id');
    if ($id) vpy_redirect('/invoice.php?id=' . $id);
    vpy_redirect('/');
}
