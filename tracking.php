<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/Order.php';

$orderId = isset($_GET['order']) ? intval($_GET['order']) : 0;

if (!$orderId) {
    redirect('index.php', 'Order not found', 'error');
}

$order = new Order();
$orderData = $order->getById($orderId);

if (!$orderData) {
    redirect('index.php', 'Order not found', 'error');
}

// Security check - only order owner or admin can view
$canView = false;
if (isAdminLoggedIn()) {
    $canView = true;
} elseif (isLoggedIn() && $orderData['user_id'] == $_SESSION['user_id']) {
    $canView = true;
} elseif (!isLoggedIn() && isset($_SESSION['last_order']) && $_SESSION['last_order'] == $orderId) {
    $canView = true;
}

if (!$canView) {
    redirect('login.php', 'Please login to view this order', 'info');
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $orderId; ?> - <?php echo APP_NAME; ?></title>
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
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <section class="section-luxe" style="padding-top: 120px;">
        <div class="container">
            <p class="section-subtitle">Order Status</p>
            <h2 class="section-title">Order <span>#<?php echo $orderId; ?></span></h2>
            <div class="divider-gold"></div>
            
            <div class="row mt-4">
                <div class="col-lg-8">
                    <!-- Status Timeline -->
                    <div class="glass-card mb-4">
                        <h5 class="mb-4">Tracking</h5>
                        <div class="tracking-timeline">
                            <?php
                            $statuses = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered'];
                            $currentStatus = $orderData['status'];
                            
                            foreach ($statuses as $status): 
                                $isCompleted = array_search($status, $statuses) < array_search($currentStatus, $statuses);
                                $isActive = $status === $currentStatus;
                                if ($currentStatus === 'cancelled' || $currentStatus === 'rejected') break;
                            ?>
                            <div class="tracking-item <?php echo $isActive ? 'active' : ''; ?> <?php echo $isCompleted ? 'completed' : ''; ?>">
                                <div class="tracking-status"><?php echo getStatusLabel($status); ?></div>
                                <div class="tracking-time">
                                    <?php if ($isActive): ?>
                                        Current Status
                                    <?php elseif ($isCompleted): ?>
                                        Completed
                                    <?php else: ?>
                                        Pending
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if ($currentStatus === 'cancelled' || $currentStatus === 'rejected'): ?>
                            <div class="tracking-item active">
                                <div class="tracking-status text-danger"><?php echo getStatusLabel($currentStatus); ?></div>
                                <div class="tracking-time">Order <?php echo $currentStatus; ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Order Items -->
                    <div class="glass-card">
                        <h5 class="mb-4">Items Ordered</h5>
                        <?php foreach ($orderData['items'] as $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px solid var(--border);">
                            <div>
                                <span class="fw-bold"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                <span class="text-muted"> x <?php echo $item['qty']; ?></span>
                            </div>
                            <span>$<?php echo number_format($item['subtotal'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-between mt-3 pt-3" style="border-top: 1px solid var(--border);">
                            <span class="fw-bold">Total</span>
                            <span class="fw-bold text-gold">$<?php echo number_format($orderData['final_total'], 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Rider Info -->
                    <?php if ($orderData['rider_id']): ?>
                    <div class="rider-card mb-4">
                        <img src="<?php echo $orderData['rider_image'] ?: 'assets/images/rider1.jpg'; ?>" alt="" class="rider-img">
                        <div>
                            <h6 class="mb-1">Your Delivery Partner</h6>
                            <p class="mb-0 fw-bold"><?php echo htmlspecialchars($orderData['rider_name']); ?></p>
                            <p class="mb-0 text-muted small">
                                <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($orderData['rider_phone']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Customer Info -->
                    <div class="glass-card">
                        <h5 class="mb-3">Delivery Details</h5>
                        <p class="mb-2"><strong><?php echo htmlspecialchars($orderData['customer_name']); ?></strong></p>
                        <p class="text-muted mb-2">
                            <i class="fas fa-map-marker-alt me-2 text-gold"></i>
                            <?php echo nl2br(htmlspecialchars($orderData['customer_address'])); ?>
                        </p>
                        <p class="text-muted mb-0">
                            <i class="fas fa-phone me-2 text-gold"></i>
                            <?php echo htmlspecialchars($orderData['customer_phone']); ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>