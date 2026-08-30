<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/Rider.php';

$rider = new Rider();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        redirect('riders.php', 'Invalid token', 'error');
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $image = '';
        if (!empty($_FILES['image']['name'])) {
            $image = uploadImage($_FILES['image'], RIDER_UPLOAD_PATH) ?: '';
        }
        $rider->create([
            'name' => $_POST['name'],
            'phone' => $_POST['phone'],
            'email' => $_POST['email'] ?? '',
            'image' => $image,
            'is_active' => true
        ]);
        redirect('riders.php', 'Rider added', 'success');
    } elseif ($action === 'update') {
        $id = intval($_POST['id']);
        $data = ['name' => $_POST['name'], 'phone' => $_POST['phone'], 'email' => $_POST['email'], 'is_active' => !empty($_POST['is_active'])];
        if (!empty($_FILES['image']['name'])) {
            $img = uploadImage($_FILES['image'], RIDER_UPLOAD_PATH);
            if ($img) $data['image'] = $img;
        }
        $rider->update($id, $data);
        redirect('riders.php', 'Rider updated', 'success');
    } elseif ($action === 'delete') {
        $rider->delete(intval($_POST['id']));
        redirect('riders.php', 'Rider deleted', 'success');
    }
}

$riders = $rider->getAll();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riders - Admin - <?php echo APP_NAME; ?></title>
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
            <a href="riders.php" class="admin-nav-link active"><i class="fas fa-motorcycle"></i> Riders</a>
            <a href="coupons.php" class="admin-nav-link"><i class="fas fa-ticket-alt"></i> Coupons</a>
            <a href="users.php" class="admin-nav-link"><i class="fas fa-users"></i> Users</a>
            <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                <a href="logout.php" class="admin-nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1 class="admin-title">Riders</h1>
            <button class="btn-admin" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-2"></i>Add Rider</button>
        </div>
        
        <?php if ($flash['message']): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show"><?php echo $flash['message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        
        <div class="row g-4">
            <?php foreach ($riders as $r): ?>
            <div class="col-md-6 col-lg-3">
                <div class="admin-card text-center">
                    <img src="<?php echo '../' . ($r['image'] ?: 'assets/images/rider1.jpg'); ?>" class="rounded-circle mb-3" style="width: 80px; height: 80px; object-fit: cover; border: 2px solid #d4af37;">
                    <h5 class="mb-1"><?php echo htmlspecialchars($r['name']); ?></h5>
                    <p class="text-muted small mb-1"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($r['phone']); ?></p>
                    <span class="badge-admin <?php echo $r['is_active'] ? 'badge-admin-delivered' : 'badge-admin-cancelled'; ?>"><?php echo $r['is_active'] ? 'Active' : 'Inactive'; ?></span>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $r['id']; ?>"><i class="fas fa-edit"></i></button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-admin-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <div class="modal fade admin-modal" id="createModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Rider</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"><input type="hidden" name="action" value="create">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
                    <button type="submit" class="btn-admin w-100">Add Rider</button>
                </form>
            </div>
        </div></div>
    </div>

    <?php foreach ($riders as $r): ?>
    <div class="modal fade admin-modal" id="editModal<?php echo $r['id']; ?>" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Rider</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($r['name']); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($r['phone']); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($r['email'] ?? ''); ?>"></div>
                    <div class="mb-3"><label class="form-label">New Image (optional)</label><input type="file" name="image" class="form-control" accept="image/*"></div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" class="form-check-input" id="ra<?php echo $r['id']; ?>" <?php echo $r['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ra<?php echo $r['id']; ?>">Active</label>
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