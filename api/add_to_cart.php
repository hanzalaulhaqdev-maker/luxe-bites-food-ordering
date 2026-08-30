<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/Menu.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(false, 'Invalid security token');
}

$itemId = intval($_POST['item_id'] ?? 0);
$qty = intval($_POST['qty'] ?? 1);

if ($itemId <= 0 || $qty <= 0) {
    jsonResponse(false, 'Invalid item or quantity');
}

$menu = new Menu();
$item = $menu->getItemById($itemId);

if (!$item) {
    jsonResponse(false, 'Item not found');
}

$cart = getCart();

if (isset($cart[$itemId])) {
    $cart[$itemId]['qty'] += $qty;
} else {
    $cart[$itemId] = [
        'id' => $itemId,
        'name' => $item['name'],
        'price' => $item['price'],
        'image' => $item['image'],
        'qty' => $qty
    ];
}

saveCart($cart);
$totals = calculateCartTotals();

jsonResponse(true, 'Item added to cart', [
    'item_count' => $totals['item_count'],
    'cart_total' => $totals['total']
]);
