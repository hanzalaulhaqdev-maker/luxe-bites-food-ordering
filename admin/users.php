<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/User.php';

$user = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $user->delete(intval($_POST['id']));
        redirect('users.php', 'User deleted', 'success');
    }
}

$users = $user->getAll();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Admin - <?php echo APP_NAME; ?></title>
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
            <a href="coupons.php" class="admin-nav-link"><i class="fas fa-ticket-alt"></i> Coupons</a>
            <a href="users.php" class="admin-nav-link active"><i class="fas fa-users"></i> Users</a>
            <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                <a href="logout.php" class="admin-nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1 class="admin-title">Users</h1>
            <span class="text-muted"><?php echo count($users); ?> registered users</span>
        </div>
        
        <?php if ($flash['message']): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show"><?php echo $flash['message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        
        <div class="admin-table-container">
            <table class="admin-table">
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>#<?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['name']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['phone']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-admin-danger" onclick="return confirm('Delete this user?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>