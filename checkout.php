<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/Order.php';

$cart = getCart();
$totals = calculateCartTotals();

if (empty($cart)) {
    redirect('menu.php', 'Your cart is empty', 'info');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $order = new Order();
    
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
        'customer_name' => sanitize($_POST['name']),
        'customer_email' => sanitizeEmail($_POST['email']),
        'customer_phone' => sanitize($_POST['phone']),
        'customer_address' => sanitize($_POST['address']),
        'total' => $totals['subtotal'],
        'discount_amount' => $totals['discount'],
        'coupon_code' => $totals['coupon_code'],
        'final_total' => $totals['total'],
        'items' => $items
    ];
    
    try {
        $orderId = $order->create($data);
        
        // Increment coupon usage if applied
        if ($totals['coupon_code']) {
            require_once 'includes/Coupon.php';
            $coupon = new Coupon();
            $couponData = $coupon->validate($totals['coupon_code'], $_SESSION['user_id'] ?? null);
            if ($couponData && $couponData['valid']) {
                $coupon->incrementUsage($couponData['coupon']['id']);
            }
        }
        
        clearCart();
        redirect("tracking.php?order=$orderId", 'Order placed successfully!', 'success');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title>Checkout - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-luxe fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo APP_NAME; ?></a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-bag"></i>
                            <span class="badge badge-cart cart-badge"><?php echo $totals['item_count']; ?></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="section-luxe" style="padding-top: 120px;">
        <div class="container">
            <p class="section-subtitle">Almost There</p>
            <h2 class="section-title">Secure <span>Checkout</span></h2>
            <div class="divider-gold"></div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="row mt-4">
                <div class="col-lg-8">
                    <form method="POST" action="" class="form-luxe" id="checkout-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <h5 class="mb-4">Delivery Information</h5>
                        
                        <div class="mb-3">
                            <label class="form-label-luxe">Full Name</label>
                            <input type="text" name="name" class="form-control form-control-luxe" required 
                                value="<?php echo $_SESSION['user_name'] ?? ''; ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-luxe">Email</label>
                                <input type="email" name="email" class="form-control form-control-luxe" required
                                    value="<?php echo $_SESSION['user_email'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-luxe">Phone</label>
                                <input type="tel" name="phone" class="form-control form-control-luxe" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label-luxe">Delivery Address</label>
                            <textarea name="address" class="form-control form-control-luxe" rows="3" required></textarea>
                        </div>
                        
                        <h5 class="mb-4">Order Summary</h5>
                        <?php foreach ($cart as $item): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['qty']; ?></span>
                            <span>$<?php echo number_format($item['price'] * $item['qty'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                        
                        <hr style="border-color: var(--border);">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span id="cart-subtotal">$<?php echo number_format($totals['subtotal'], 2); ?></span>
                        </div>
                        <?php if ($totals['discount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Discount</span>
                            <span id="cart-discount" class="text-success">-$<?php echo number_format($totals['discount'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total</span>
                            <span id="cart-total" class="fw-bold text-gold">$<?php echo number_format($totals['total'], 2); ?></span>
                        </div>
                        
                        <div class="mt-4" id="coupon-display">
                            <?php if ($totals['coupon_code']): ?>
                            <div class="d-flex justify-content-between align-items-center bg-success bg-opacity-10 p-2 rounded">
                                <span class="text-success">Coupon: <strong><?php echo $totals['coupon_code']; ?></strong></span>
                                <button class="btn btn-sm btn-outline-danger border-0" id="remove-coupon">Remove</button>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-3">
                            <label class="form-label-luxe">Have a coupon?</label>
                            <div class="input-group">
                                <input type="text" class="coupon-input form-control form-control-luxe" id="coupon-code" placeholder="Enter coupon code">
                                <button class="btn-apply" id="apply-coupon">Apply</button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-luxe btn-luxe-primary w-100 mt-4">
                            Place Order
                        </button>
                    </form>
                </div>
                
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h5 class="mb-3">We Accept</h5>
                        <div class="d-flex gap-3 mb-4">
                            <i class="fab fa-cc-visa fa-2x text-muted"></i>
                            <i class="fab fa-cc-mastercard fa-2x text-muted"></i>
                            <i class="fab fa-cc-amex fa-2x text-muted"></i>
                            <i class="fab fa-cc-paypal fa-2x text-muted"></i>
                        </div>
                        <p class="text-muted small">
                            <i class="fas fa-lock me-1 text-gold"></i>
                            Your payment information is processed securely.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer-luxe">
        <div class="container">
            <div class="footer-bottom">
                <p>Luxury for everyone. Quality without compromise.</p>
            </div>
        </div>
    </footer>

    <div class="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>