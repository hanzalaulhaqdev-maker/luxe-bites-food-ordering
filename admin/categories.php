<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/Menu.php';

$menu = new Menu();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        redirect('categories.php', 'Invalid token', 'error');
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $menu->createCategory($_POST['name'], $_POST['slug'], intval($_POST['display_order'] ?? 0));
        redirect('categories.php', 'Category created', 'success');
    } elseif ($action === 'update') {
        $menu->updateCategory(intval($_POST['id']), [
            'name' => $_POST['name'],
            'slug' => $_POST['slug'],
            'display_order' => $_POST['display_order'],
            'is_active' => !empty($_POST['is_active'])
        ]);
        redirect('categories.php', 'Category updated', 'success');
    } elseif ($action === 'delete') {
        $menu->deleteCategory(intval($_POST['id']));
        redirect('categories.php', 'Category deleted', 'success');
    }
}

$categories = $menu->getCategories();
$allCategories = $menu->getCategories(); // For edit modals with full data
// Get raw categories for editing
$db = Database::getInstance();
$stmt = $db->query("SELECT * FROM categories ORDER BY display_order");
$allCategories = $stmt->fetchAll();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Admin - <?php echo APP_NAME; ?></title>
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
            <a href="categories.php" class="admin-nav-link active"><i class="fas fa-tags"></i> Categories</a>
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
            <h1 class="admin-title">Categories</h1>
            <button class="btn-admin" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-2"></i>Add Category</button>
        </div>
        
        <?php if ($flash['message']): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show"><?php echo $flash['message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        
        <div class="admin-table-container">
            <table class="admin-table">
                <thead><tr><th>Name</th><th>Slug</th><th>Order</th><th>Active</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($allCategories as $cat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cat['name']); ?></td>
                        <td><?php echo htmlspecialchars($cat['slug']); ?></td>
                        <td><?php echo $cat['display_order']; ?></td>
                        <td><?php echo $cat['is_active'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'; ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $cat['id']; ?>"><i class="fas fa-edit"></i></button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
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
            <div class="modal-header"><h5 class="modal-title">Add Category</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"><input type="hidden" name="action" value="create">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Display Order</label><input type="number" name="display_order" class="form-control" value="0"></div>
                    <button type="submit" class="btn-admin w-100">Create</button>
                </form>
            </div>
        </div></div>
    </div>

    <?php foreach ($allCategories as $cat): ?>
    <div class="modal fade admin-modal" id="editModal<?php echo $cat['id']; ?>" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Category</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($cat['name']); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($cat['slug']); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Display Order</label><input type="number" name="display_order" class="form-control" value="<?php echo $cat['display_order']; ?>"></div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" class="form-check-input" id="ea<?php echo $cat['id']; ?>" <?php echo $cat['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ea<?php echo $cat['id']; ?>">Active</label>
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