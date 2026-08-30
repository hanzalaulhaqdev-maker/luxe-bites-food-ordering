<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/Coupon.php';

if (!isLoggedIn()) {
    redirect('login.php', 'Please login to view your discounts', 'info');
}

$coupon = new Coupon();
$userCoupons = $coupon->getUserCoupons($_SESSION['user_id']);
$exclusiveCoupons = $coupon->getExclusiveCoupons($_SESSION['user_id']);

$activeCoupons = [];
$expiredCoupons = [];

foreach ($userCoupons as $c) {
    if ($c['expiry_date'] >= date('Y-m-d') && $c['is_active']) {
        $activeCoupons[] = $c;
    } else {
        $expiredCoupons[] = $c;
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
    <title>My Discounts - <?php echo APP_NAME; ?></title>
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
                    <li class="nav-item"><a class="nav-link active" href="my-discounts.php">My Discounts</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-bag"></i>
                            <span class="badge badge-cart cart-badge" style="display: <?php echo calculateCartTotals()['item_count'] > 0 ? 'inline' : 'none'; ?>">
                                <?php echo calculateCartTotals()['item_count']; ?>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="section-luxe" style="padding-top: 120px;">
        <div class="container">
            <p class="section-subtitle">Your Benefits</p>
            <h2 class="section-title">My <span>Discounts</span></h2>
            <div class="divider-gold"></div>
            
            <!-- Exclusive Coupons Section -->
            <?php if (!empty($exclusiveCoupons)): ?>
            <h4 class="mt-5 mb-3"><i class="fas fa-crown text-gold me-2"></i>Exclusive - Only For You</h4>
            <div class="row g-4">
                <?php foreach ($exclusiveCoupons as $c): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="coupon-card exclusive">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge-exclusive">Only For You</span>
                            <span class="coupon-discount"><?php echo $c['discount']; ?>%</span>
                        </div>
                        <div class="coupon-code mb-2"><?php echo $c['code']; ?></div>
                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($c['description']); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                <i class="fas fa-clock me-1"></i>
                                Expires: <?php echo date('M d, Y', strtotime($c['expiry_date'])); ?>
                            </span>
                            <button class="btn btn-sm btn-luxe" onclick="copyCoupon('<?php echo $c['code']; ?>')">Copy</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Active Coupons -->
            <h4 class="mt-5 mb-3"><i class="fas fa-ticket-alt text-gold me-2"></i>Active Coupons</h4>
            <?php if (empty($activeCoupons)): ?>
            <div class="glass-card text-center py-5">
                <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                <p class="text-muted">No active coupons available.</p>
                <a href="offers.php" class="btn btn-luxe mt-2">Browse Offers</a>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($activeCoupons as $c): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="coupon-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge bg-secondary"><?php echo ucfirst($c['type']); ?></span>
                            <span class="coupon-discount"><?php echo $c['discount']; ?>%</span>
                        </div>
                        <div class="coupon-code mb-2"><?php echo $c['code']; ?></div>
                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($c['description']); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                <i class="fas fa-clock me-1"></i>
                                Expires: <?php echo date('M d, Y', strtotime($c['expiry_date'])); ?>
                            </span>
                            <button class="btn btn-sm btn-luxe" onclick="copyCoupon('<?php echo $c['code']; ?>')">Copy</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Expired Coupons -->
            <?php if (!empty($expiredCoupons)): ?>
            <h4 class="mt-5 mb-3"><i class="fas fa-history text-muted me-2"></i>Expired</h4>
            <div class="row g-4">
                <?php foreach ($expiredCoupons as $c): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="coupon-card" style="opacity: 0.6;">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge bg-secondary">Expired</span>
                            <span class="coupon-discount text-muted"><?php echo $c['discount']; ?>%</span>
                        </div>
                        <div class="coupon-code mb-2 text-muted"><?php echo $c['code']; ?></div>
                        <p class="text-muted small mb-0">Expired on: <?php echo date('M d, Y', strtotime($c['expiry_date'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer-luxe">
        <div class="container">
            <div class="footer-bottom">
                <p>Luxury for everyone. Quality without compromise.</p>
            </div>
        </div>
    </footer>

    <script>
    function copyCoupon(code) {
        navigator.clipboard.writeText(code).then(() => {
            alert('Coupon code copied: ' + code);
        });
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</body>
</html>