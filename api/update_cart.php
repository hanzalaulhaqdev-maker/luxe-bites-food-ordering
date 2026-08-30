<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(false, 'Invalid security token');
}

$itemId = intval($_POST['item_id'] ?? 0);
$qty = intval($_POST['qty'] ?? 1);

if ($itemId <= 0 || $qty < 1) {
    jsonResponse(false, 'Invalid item or quantity');
}

$cart = getCart();

if (!isset($cart[$itemId])) {
    jsonResponse(false, 'Item not in cart');
}

$cart[$itemId]['qty'] = $qty;
saveCart($cart);

$totals = calculateCartTotals();
jsonResponse(true, 'Cart updated', [
    'totals' => $totals,
    'item_count' => $totals['item_count']
]);
