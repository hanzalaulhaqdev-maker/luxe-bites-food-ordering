<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$cart = getCart();
$totals = calculateCartTotals();

if (empty($cart)) {
    redirect('menu.php', 'Your cart is empty', 'info');
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title>Cart - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-luxe fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
                    
                    <li class="nav-item">
                        <a class="nav-link active" href="cart.php">
                            <i class="fas fa-shopping-bag"></i>
                            <span class="badge badge-cart cart-badge"><?php echo $totals['item_count']; ?></span>
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li><a class="dropdown-item" href="my-discounts.php">My Discounts</a></li>
                                <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="btn btn-luxe ms-2" href="register.php">Join</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Cart Section -->
    <section class="section-luxe" style="padding-top: 120px;">
        <div class="container">
            <p class="section-subtitle">Your Selection</p>
            <h2 class="section-title">Shopping <span>Cart</span></h2>
            <div class="divider-gold"></div>
            
            <div class="row mt-4">
                <div class="col-lg-8">
                    <?php foreach ($cart as $itemId => $item): ?>
                    <div class="cart-item" data-id="<?php echo $itemId; ?>">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="" class="cart-item-img">
                        <div class="flex-grow-1 text-start">
                            <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                            <p class="text-muted mb-1">$<?php echo number_format($item['price'], 2); ?> each</p>
                            <div class="qty-control d-inline-flex">
                                <button class="qty-btn qty-minus">-</button>
                                <span class="qty-value"><?php echo $item['qty']; ?></span>
                                <button class="qty-btn qty-plus">+</button>
                            </div>
                        </div>
                        <div class="text-end">
                            <p class="text-gold fw-bold">$<?php echo number_format($item['price'] * $item['qty'], 2); ?></p>
                            <button class="btn btn-sm btn-outline-danger border-0 btn-remove-cart">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h5 class="mb-4">Order Summary</h5>
                        
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="cart-subtotal">$<?php echo number_format($totals['subtotal'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Discount</span>
                            <span id="cart-discount" class="text-success">-$<?php echo number_format($totals['discount'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery</span>
                            <span class="text-success">Free</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span id="cart-total">$<?php echo number_format($totals['total'], 2); ?></span>
                        </div>
                        
                        <a href="checkout.php" class="btn btn-luxe btn-luxe-primary w-100 mt-4">Proceed to Checkout</a>
                        <a href="menu.php" class="btn btn-luxe w-100 mt-2">Continue Shopping</a>
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