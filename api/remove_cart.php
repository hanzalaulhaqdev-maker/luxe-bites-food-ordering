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

if ($itemId <= 0) {
    jsonResponse(false, 'Invalid item');
}

$cart = getCart();

if (!isset($cart[$itemId])) {
    jsonResponse(false, 'Item not in cart');
}

unset($cart[$itemId]);
saveCart($cart);

$totals = calculateCartTotals();
jsonResponse(true, 'Item removed from cart', [
    'totals' => $totals,
    'item_count' => $totals['item_count']
]);
