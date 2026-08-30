<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/Order.php';
require_once __DIR__ . '/../includes/Rider.php';

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$orderId) redirect('orders.php');

$order = new Order();
$rider = new Rider();
$orderData = $order->getById($orderId);

if (!$orderData) redirect('orders.php', 'Order not found', 'error');

$riders = $rider->getAll(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (validateCSRFToken($_POST['csrf_token'] ?? '')) {
        if (isset($_POST['status'])) {
            $order->updateStatus($orderId, sanitize($_POST['status']), sanitize($_POST['notes'] ?? ''));
            redirect("order_detail.php?id=$orderId", 'Status updated', 'success');
        }
        if (isset($_POST['rider_id'])) {
            $order->assignRider($orderId, intval($_POST['rider_id']));
            redirect("order_detail.php?id=$orderId", 'Rider assigned', 'success');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $orderId; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <a href="index.php" class="admin-brand"><?php echo APP_NAME; ?></a>
        <nav>
            <a href="index.php" class="admin-nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="orders.php" class="admin-nav-link active"><i class="fas fa-shopping-bag"></i> Orders</a>
            <a href="menu.php" class="admin-nav-link"><i class="fas fa-utensils"></i> Menu Items</a>
            <a href="categories.php" class="admin-nav-link"><i class="fas fa-tags"></i> Categories</a>
            <a href="riders.php" class="admin-nav-link"><i class="fas fa-motorcycle"></i> Riders</a>
            <a href="coupons.php" class="admin-nav-link"><i class="fas fa-ticket-alt"></i> Coupons</a>
            <a href="users.php" class="admin-nav-link"><i class="fas fa-users"></i> Users</a>
            <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                <a href="logout.php" class="admin-nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1 class="admin-title">Order #<?php echo $orderId; ?></h1>
            <a href="orders.php" class="btn btn-admin-outline">Back to Orders</a>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="admin-card mb-4">
                    <h5 class="mb-4">Items</h5>
                    <?php foreach ($orderData['items'] as $item): ?>
                    <div class="d-flex justify-content-between align-items-center py-3" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <div>
                            <span class="fw-bold"><?php echo htmlspecialchars($item['item_name']); ?></span>
                            <span class="text-muted"> x <?php echo $item['qty']; ?></span>
                        </div>
                        <span class="text-gold">$<?php echo number_format($item['subtotal'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="d-flex justify-content-between mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.1);">
                        <span>Subtotal</span><span>$<?php echo number_format($orderData['total'], 2); ?></span>
                    </div>
                    <?php if ($orderData['discount_amount'] > 0): ?>
                    <div class="d-flex justify-content-between">
                        <span>Discount</span><span class="text-success">-$<?php echo number_format($orderData['discount_amount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between fw-bold mt-2">
                        <span>Total</span><span class="text-gold">$<?php echo number_format($orderData['final_total'], 2); ?></span>
                    </div>
                </div>
                
                <div class="admin-card">
                    <h5 class="mb-4">Status History</h5>
                    <div class="tracking-timeline">
                        <?php foreach ($orderData['history'] as $h): ?>
                        <div class="tracking-item completed">
                            <div class="tracking-status"><?php echo getStatusLabel($h['status']); ?></div>
                            <div class="tracking-time"><?php echo date('M d, Y H:i', strtotime($h['created_at'])); ?></div>
                            <?php if ($h['notes']): ?><div class="text-muted small"><?php echo htmlspecialchars($h['notes']); ?></div><?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="admin-card mb-4">
                    <h5 class="mb-3">Customer</h5>
                    <p class="mb-1 fw-bold"><?php echo htmlspecialchars($orderData['customer_name']); ?></p>
                    <p class="text-muted small mb-1"><i class="fas fa-envelope me-2 text-gold"></i><?php echo htmlspecialchars($orderData['customer_email']); ?></p>
                    <p class="text-muted small mb-1"><i class="fas fa-phone me-2 text-gold"></i><?php echo htmlspecialchars($orderData['customer_phone']); ?></p>
                    <p class="text-muted small"><i class="fas fa-map-marker-alt me-2 text-gold"></i><?php echo nl2br(htmlspecialchars($orderData['customer_address'])); ?></p>
                </div>
                
                <div class="admin-card mb-4">
                    <h5 class="mb-3">Update Status</h5>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <select name="status" class="form-control mb-3" style="background: #0b0b0b; border-color: rgba(255,255,255,0.1); color: white;">
                            <option value="pending" <?php echo $orderData['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $orderData['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="preparing" <?php echo $orderData['status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                            <option value="out_for_delivery" <?php echo $orderData['status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                            <option value="delivered" <?php echo $orderData['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $orderData['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <textarea name="notes" class="form-control mb-3" rows="2" placeholder="Notes (optional)" style="background: #0b0b0b; border-color: rgba(255,255,255,0.1); color: white;"></textarea>
                        <button type="submit" class="btn-admin w-100">Update</button>
                    </form>
                </div>
                
                <div class="admin-card">
                    <h5 class="mb-3">Assign Rider</h5>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <select name="rider_id" class="form-control mb-3" style="background: #0b0b0b; border-color: rgba(255,255,255,0.1); color: white;">
                            <option value="">Select Rider</option>
                            <?php foreach ($riders as $r): ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo $orderData['rider_id'] == $r['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-admin-outline w-100">Assign</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>