<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/Admin.php';
require_once __DIR__ . '/../includes/Order.php';

$admin = new Admin();
$stats = $admin->getDashboardStats();
$recentOrders = $admin->getRecentOrders(10);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <a href="index.php" class="admin-brand"><?php echo APP_NAME; ?></a>
        
        <nav>
            <a href="index.php" class="admin-nav-link active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="orders.php" class="admin-nav-link">
                <i class="fas fa-shopping-bag"></i> Orders
            </a>
            <a href="menu.php" class="admin-nav-link">
                <i class="fas fa-utensils"></i> Menu Items
            </a>
            <a href="categories.php" class="admin-nav-link">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="riders.php" class="admin-nav-link">
                <i class="fas fa-motorcycle"></i> Riders
            </a>
            <a href="coupons.php" class="admin-nav-link">
                <i class="fas fa-ticket-alt"></i> Coupons
            </a>
            <a href="users.php" class="admin-nav-link">
                <i class="fas fa-users"></i> Users
            </a>
            <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                <a href="logout.php" class="admin-nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-header">
            <h1 class="admin-title">Dashboard</h1>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted">Welcome, <?php echo $adminName; ?></span>
                <button id="sidebar-toggle" class="btn btn-sm btn-outline-secondary d-lg-none">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        
        <?php if ($flash['message']): ?>
        <div class="alert alert-<?php echo $flash['type'] ?: 'info'; ?> alert-dismissible fade show">
            <?php echo $flash['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Stats Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-value">$<?php echo number_format($stats['today_revenue'], 2); ?></div>
                    <div class="stat-label">Today's Revenue</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_customers']); ?></div>
                    <div class="stat-label">Total Customers</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['active_riders']); ?></div>
                    <div class="stat-label">Active Riders</div>
                </div>
            </div>
        </div>
        
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['today_orders']); ?></div>
                    <div class="stat-label">Today's Orders</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['pending_orders']); ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_items']); ?></div>
                    <div class="stat-label">Menu Items</div>
                </div>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">Recent Orders</h5>
                <a href="orders.php" class="btn-admin-outline btn-sm">View All</a>
            </div>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td class="text-gold">$<?php echo number_format($order['final_total'], 2); ?></td>
                            <td>
                                <span class="badge-admin badge-admin-<?php echo $order['status']; ?>">
                                    <?php echo getStatusLabel($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-secondary" style="border-color: rgba(255,255,255,0.2); color: #ccc;">
                                    View
                                </a>
                            </td>
                        </tr>
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
    
    $(document).click(function(e) {
        if ($(window).width() <= 991) {
            if (!$(e.target).closest('.admin-sidebar, #sidebar-toggle').length) {
                $('.admin-sidebar').removeClass('open');
            }
        }
    });
    </script>
</body>
</html>