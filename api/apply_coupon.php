<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/Coupon.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(false, 'Invalid security token');
}

$code = strtoupper(trim($_POST['code'] ?? ''));

if (empty($code)) {
    jsonResponse(false, 'Please enter a coupon code');
}

$coupon = new Coupon();
$userId = $_SESSION['user_id'] ?? null;
$result = $coupon->validate($code, $userId);

if (!$result) {
    jsonResponse(false, 'Invalid coupon code');
}

if (!$result['valid']) {
    jsonResponse(false, $result['error']);
}

// Apply coupon to session
$_SESSION['coupon'] = [
    'code' => $result['coupon']['code'],
    'discount' => $result['coupon']['discount'],
    'id' => $result['coupon']['id']
];

$totals = calculateCartTotals();
jsonResponse(true, 'Coupon applied successfully!', [
    'totals' => $totals,
    'discount' => $result['coupon']['discount']
]);
