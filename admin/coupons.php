<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/Coupon.php';
require_once __DIR__ . '/../includes/User.php';

$coupon = new Coupon();
$user = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        redirect('coupons.php', 'Invalid token', 'error');
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $coupon->create([
            'code' => $_POST['code'],
            'discount' => $_POST['discount'],
            'type' => $_POST['type'],
            'user_id' => $_POST['user_id'] ?: null,
            'usage_limit' => $_POST['usage_limit'] ?: null,
            'expiry_date' => $_POST['expiry_date'],
            'is_active' => !empty($_POST['is_active']),
            'description' => $_POST['description'] ?? ''
        ]);
        redirect('coupons.php', 'Coupon created', 'success');
    } elseif ($action === 'update') {
        $coupon->update(intval($_POST['id']), [
            'code' => $_POST['code'],
            'discount' => $_POST['discount'],
            'type' => $_POST['type'],
            'user_id' => $_POST['user_id'] ?: null,
            'usage_limit' => $_POST['usage_limit'] ?: null,
            'expiry_date' => $_POST['expiry_date'],
            'is_active' => !empty($_POST['is_active']),
            'description' => $_POST['description']
        ]);
        redirect('coupons.php', 'Coupon updated', 'success');
    } elseif ($action === 'delete') {
        $coupon->delete(intval($_POST['id']));
        redirect('coupons.php', 'Coupon deleted', 'success');
    }
}

$coupons = $coupon->getAll();
$users = $user->getAll();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupons - Admin - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <a href="index.php" class="admin-brand"><?php echo APP_NAME; ?></a>
        <nav>
            <a href="index.php" class="admin-nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="orders.php" class="admin-nav-link"><i class="fas fa-shopping-bag"></i> Orders</a>
            <a href="menu.php" class="admin-nav-link"><i class="fas fa-utensils"></i> Menu Items</a>
            <a href="categories.php" class="admin-nav-link"><i class="fas fa-tags"></i> Categories</a>
            <a href="riders.php" class="admin-nav-link"><i class="fas fa-motorcycle"></i> Riders</a>
            <a href="coupons.php" class="admin-nav-link active"><i class="fas fa-ticket-alt"></i> Coupons</a>
            <a href="users.php" class="admin-nav-link"><i class="fas fa-users"></i> Users</a>
            <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                <a href="logout.php" class="admin-nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1 class="admin-title">Coupons</h1>
            <button class="btn-admin" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-2"></i>Add Coupon</button>
        </div>
        
        <?php if ($flash['message']): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show"><?php echo $flash['message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        
        <div class="admin-table-container">
            <table class="admin-table">
                <thead><tr><th>Code</th><th>Discount</th><th>Type</th><th>User</th><th>Usage</th><th>Expiry</th><th>Active</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($coupons as $c): ?>
                    <tr>
                        <td class="text-gold fw-bold"><?php echo htmlspecialchars($c['code']); ?></td>
                        <td><?php echo $c['discount']; ?>%</td>
                        <td><span class="badge bg-secondary"><?php echo ucfirst($c['type']); ?></span></td>
                        <td><?php echo $c['user_name'] ?: '<span class="text-muted">-</span>'; ?></td>
                        <td><?php echo $c['used_count']; ?> / <?php echo $c['usage_limit'] ?: 'Unlimited'; ?></td>
                        <td><?php echo date('M d, Y', strtotime($c['expiry_date'])); ?></td>
                        <td><?php echo $c['is_active'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'; ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $c['id']; ?>"><i class="fas fa-edit"></i></button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-admin-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal fade admin-modal" id="createModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Coupon</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"><input type="hidden" name="action" value="create">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Code</label><input type="text" name="code" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Discount %</label><input type="number" name="discount" class="form-control" min="1" max="100" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-control" id="couponType">
                                <option value="public">Public</option>
                                <option value="private">Private</option>
                                <option value="exclusive">Exclusive</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Assign to User (Exclusive)</label>
                            <select name="user_id" class="form-control">
                                <option value="">None</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Usage Limit</label><input type="number" name="usage_limit" class="form-control" placeholder="Unlimited"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" class="form-control" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="form-check mb-3"><input type="checkbox" name="is_active" class="form-check-input" id="ca" checked><label class="form-check-label" for="ca">Active</label></div>
                    <button type="submit" class="btn-admin w-100">Create Coupon</button>
                </form>
            </div>
        </div></div>
    </div>

    <?php foreach ($coupons as $c): ?>
    <div class="modal fade admin-modal" id="editModal<?php echo $c['id']; ?>" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Coupon</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Code</label><input type="text" name="code" class="form-control" value="<?php echo htmlspecialchars($c['code']); ?>" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Discount %</label><input type="number" name="discount" class="form-control" min="1" max="100" value="<?php echo $c['discount']; ?>" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-control">
                                <option value="public" <?php echo $c['type'] === 'public' ? 'selected' : ''; ?>>Public</option>
                                <option value="private" <?php echo $c['type'] === 'private' ? 'selected' : ''; ?>>Private</option>
                                <option value="exclusive" <?php echo $c['type'] === 'exclusive' ? 'selected' : ''; ?>>Exclusive</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Assign to User</label>
                            <select name="user_id" class="form-control">
                                <option value="">None</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo $c['user_id'] == $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Usage Limit</label><input type="number" name="usage_limit" class="form-control" value="<?php echo $c['usage_limit']; ?>" placeholder="Unlimited"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" class="form-control" value="<?php echo $c['expiry_date']; ?>" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($c['description'] ?? ''); ?></textarea></div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" class="form-check-input" id="cea<?php echo $c['id']; ?>" <?php echo $c['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="cea<?php echo $c['id']; ?>">Active</label>
                    </div>
                    <button type="submit" class="btn-admin w-100">Save Changes</button>
                </form>
            </div>
        </div></div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>