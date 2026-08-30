<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/Order.php';

if (!isLoggedIn()) {
    redirect('login.php', 'Please login to view your orders', 'info');
}

$order = new Order();
$orders = $order->getByUser($_SESSION['user_id']);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - <?php echo APP_NAME; ?></title>
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
                    <li class="nav-item"><a class="nav-link active" href="orders.php">My Orders</a></li>
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
                            <li><a class="dropdown-item" href="my-discounts.php">My Discounts</a></li>
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
            <p class="section-subtitle">History</p>
            <h2 class="section-title">My <span>Orders</span></h2>
            <div class="divider-gold"></div>
            
            <?php if (empty($orders)): ?>
            <div class="glass-card text-center py-5 mt-4">
                <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                <p class="text-muted">You haven't placed any orders yet.</p>
                <a href="menu.php" class="btn btn-luxe mt-2">Start Ordering</a>
            </div>
            <?php else: ?>
            <div class="mt-4">
                <?php foreach ($orders as $o): ?>
                <div class="glass-card mb-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                        <div>
                            <h5 class="mb-1">Order #<?php echo $o['id']; ?></h5>
                            <p class="text-muted small mb-2"><?php echo date('F d, Y \a\t h:i A', strtotime($o['created_at'])); ?></p>
                            <span class="badge <?php echo getStatusBadgeClass($o['status']); ?>">
                                <?php echo getStatusLabel($o['status']); ?>
                            </span>
                        </div>
                        <div class="text-end mt-2 mt-md-0">
                            <p class="fw-bold text-gold mb-1">$<?php echo number_format($o['final_total'], 2); ?></p>
                            <a href="tracking.php?order=<?php echo $o['id']; ?>" class="btn btn-sm btn-luxe">Track Order</a>
                        </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>