<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/Order.php';
require_once __DIR__ . '/../includes/Rider.php';

$order = new Order();
$rider = new Rider();

$statusFilter = $_GET['status'] ?? null;
$orders = $order->getAll($statusFilter);
$riders = $rider->getAll(true);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        redirect('orders.php', 'Invalid token', 'error');
    }
    
    $orderId = intval($_POST['order_id']);
    $action = $_POST['action'];
    
    if ($action === 'update_status') {
        $newStatus = sanitize($_POST['status']);
        $notes = sanitize($_POST['notes'] ?? '');
        $order->updateStatus($orderId, $newStatus, $notes);
        redirect('orders.php', 'Order status updated', 'success');
    } elseif ($action === 'assign_rider') {
        $riderId = intval($_POST['rider_id']);
        $order->assignRider($orderId, $riderId);
        redirect('orders.php', 'Rider assigned', 'success');
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Admin - <?php echo APP_NAME; ?></title>
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
            <h1 class="admin-title">Orders</h1>
            <button id="sidebar-toggle" class="btn btn-sm btn-outline-secondary d-lg-none">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <?php if ($flash['message']): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
            <?php echo $flash['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Filter -->
        <div class="mb-4">
            <a href="orders.php" class="btn btn-sm <?php echo !$statusFilter ? 'btn-admin' : 'btn-admin-outline'; ?>">All</a>
            <a href="?status=pending" class="btn btn-sm <?php echo $statusFilter === 'pending' ? 'btn-admin' : 'btn-admin-outline'; ?>">Pending</a>
            <a href="?status=confirmed" class="btn btn-sm <?php echo $statusFilter === 'confirmed' ? 'btn-admin' : 'btn-admin-outline'; ?>">Confirmed</a>
            <a href="?status=preparing" class="btn btn-sm <?php echo $statusFilter === 'preparing' ? 'btn-admin' : 'btn-admin-outline'; ?>">Preparing</a>
            <a href="?status=out_for_delivery" class="btn btn-sm <?php echo $statusFilter === 'out_for_delivery' ? 'btn-admin' : 'btn-admin-outline'; ?>">Out for Delivery</a>
            <a href="?status=delivered" class="btn btn-sm <?php echo $statusFilter === 'delivered' ? 'btn-admin' : 'btn-admin-outline'; ?>">Delivered</a>
        </div>
        
        <div class="admin-table-container">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Rider</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>#<?php echo $o['id']; ?></td>
                            <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                            <td class="text-gold">$<?php echo number_format($o['final_total'], 2); ?></td>
                            <td>
                                <span class="badge-admin badge-admin-<?php echo $o['status']; ?>">
                                    <?php echo getStatusLabel($o['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $o['rider_name'] ?: '<span class="text-muted">Not assigned</span>'; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($o['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $o['id']; ?>">
                                    Manage
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Order Modal -->
                        <div class="modal fade admin-modal" id="orderModal<?php echo $o['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Manage Order #<?php echo $o['id']; ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Update Status</label>
                                                <select name="status" class="form-control admin-form">
                                                    <option value="pending" <?php echo $o['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="confirmed" <?php echo $o['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="preparing" <?php echo $o['status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                                    <option value="out_for_delivery" <?php echo $o['status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                                    <option value="delivered" <?php echo $o['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                    <option value="cancelled" <?php echo $o['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    <option value="rejected" <?php echo $o['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Notes</label>
                                                <textarea name="notes" class="form-control admin-form" rows="2"></textarea>
                                            </div>
                                            
                                            <button type="submit" name="action" value="update_status" class="btn-admin w-100">Update Status</button>
                                        </form>
                                        
                                        <hr style="border-color: rgba(255,255,255,0.1);">
                                        
                                        <form method="POST" class="mt-3">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Assign Rider</label>
                                                <select name="rider_id" class="form-control admin-form">
                                                    <option value="">Select Rider</option>
                                                    <?php foreach ($riders as $r): ?>
                                                    <option value="<?php echo $r['id']; ?>" <?php echo $o['rider_id'] == $r['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($r['name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <button type="submit" name="action" value="assign_rider" class="btn-admin-outline w-100">Assign Rider</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    $('#sidebar-toggle').click(function() {
        $('.admin-sidebar').toggleClass('open');
    });
    </script>
</body>
</html>