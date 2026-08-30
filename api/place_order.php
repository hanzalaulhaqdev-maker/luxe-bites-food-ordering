<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/Order.php';
require_once '../includes/Coupon.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(false, 'Invalid security token');
}

$cart = getCart();
if (empty($cart)) {
    jsonResponse(false, 'Your cart is empty');
}

$name = sanitize($_POST['name'] ?? '');
$email = sanitizeEmail($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$address = sanitize($_POST['address'] ?? '');

if (empty($name) || empty($email) || empty($phone) || empty($address)) {
    jsonResponse(false, 'All fields are required');
}

if (!isValidEmail($email)) {
    jsonResponse(false, 'Invalid email address');
}

$totals = calculateCartTotals();

$items = [];
foreach ($cart as $id => $item) {
    $items[] = [
        'id' => $id,
        'name' => $item['name'],
        'price' => $item['price'],
        'qty' => $item['qty']
    ];
}

$data = [
    'user_id' => $_SESSION['user_id'] ?? null,
    'customer_name' => $name,
    'customer_email' => $email,
    'customer_phone' => $phone,
    'customer_address' => $address,
    'total' => $totals['subtotal'],
    'discount_amount' => $totals['discount'],
    'coupon_code' => $totals['coupon_code'],
    'final_total' => $totals['total'],
    'items' => $items
];

try {
    $order = new Order();
    $orderId = $order->create($data);
    
    // Track coupon usage
    if ($totals['coupon_code']) {
        $coupon = new Coupon();
        $couponData = $coupon->validate($totals['coupon_code'], $_SESSION['user_id'] ?? null);
        if ($couponData && $couponData['valid']) {
            $coupon->incrementUsage($couponData['coupon']['id']);
        }
    }
    
    // For guest orders, store in session
    if (!isLoggedIn()) {
        $_SESSION['last_order'] = $orderId;
    }
    
    clearCart();
    
    jsonResponse(true, 'Order placed successfully', [
        'order_id' => $orderId
    ]);
    
} catch (Exception $e) {
    jsonResponse(false, 'Error placing order: ' . $e->getMessage());
}
