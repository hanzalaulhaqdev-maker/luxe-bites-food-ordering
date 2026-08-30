<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/Menu.php';

$menu = new Menu();
$categories = $menu->getCategories();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        redirect('menu.php', 'Invalid token', 'error');
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $image = '';
        if (!empty($_FILES['image']['name'])) {
            $image = uploadImage($_FILES['image'], MENU_UPLOAD_PATH) ?: '';
        }
        
        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'category_id' => $_POST['category_id'],
            'image' => $image,
            'is_featured' => !empty($_POST['is_featured']),
            'is_top_priority' => !empty($_POST['is_top_priority']),
            'display_order' => $_POST['display_order'] ?? 999
        ];
        
        $menu->createItem($data);
        redirect('menu.php', 'Item created successfully', 'success');
    } elseif ($action === 'update') {
        $id = intval($_POST['id']);
        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'category_id' => $_POST['category_id'],
            'is_featured' => !empty($_POST['is_featured']),
            'is_top_priority' => !empty($_POST['is_top_priority']),
            'display_order' => $_POST['display_order'] ?? 999,
            'is_available' => !empty($_POST['is_available'])
        ];
        
        if (!empty($_FILES['image']['name'])) {
            $image = uploadImage($_FILES['image'], MENU_UPLOAD_PATH);
            if ($image) $data['image'] = $image;
        }
        
        $menu->updateItem($id, $data);
        redirect('menu.php', 'Item updated successfully', 'success');
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $menu->deleteItem($id);
        redirect('menu.php', 'Item deleted', 'success');
    }
}

$items = $menu->getItems(null, false);
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Items - Admin - <?php echo APP_NAME; ?></title>
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
            <a href="menu.php" class="admin-nav-link active"><i class="fas fa-utensils"></i> Menu Items</a>
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
            <h1 class="admin-title">Menu Items</h1>
            <button class="btn-admin" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-2"></i>Add Item
            </button>
        </div>
        
        <?php if ($flash['message']): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
            <?php echo $flash['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="admin-table-container">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Featured</th>
                            <th>Top Pick</th>
                            <th>Available</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><img src="<?php echo '../' . htmlspecialchars($item['image']); ?>" class="img-preview"></td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td class="text-gold">$<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['is_featured'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'; ?></td>
                            <td><?php echo $item['is_top_priority'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'; ?></td>
                            <td><?php echo $item['is_available'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'; ?></td>
                            <td><?php echo $item['display_order']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $item['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-admin-danger" onclick="return confirm('Delete this item?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Create Modal -->
    <div class="modal fade admin-modal" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add Menu Item</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data" class="admin-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price</label>
                                <input type="number" name="price" class="form-control" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-control" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Display Order</label>
                                <input type="number" name="display_order" class="form-control" value="999">
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="form-check mt-4">
                                    <input type="checkbox" name="is_featured" class="form-check-input" id="cf">
                                    <label class="form-check-label" for="cf">Featured</label>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="form-check mt-4">
                                    <input type="checkbox" name="is_top_priority" class="form-check-input" id="ctp">
                                    <label class="form-check-label" for="ctp">Top Pick</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn-admin w-100">Create Item</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($items as $item): ?>
    <div class="modal fade admin-modal" id="editModal<?php echo $item['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Edit: <?php echo htmlspecialchars($item['name']); ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data" class="admin-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price</label>
                                <input type="number" name="price" class="form-control" step="0.01" value="<?php echo $item['price']; ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-control" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $item['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Image (optional)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Display Order</label>
                                <input type="number" name="display_order" class="form-control" value="<?php echo $item['display_order']; ?>">
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="form-check mt-4">
                                    <input type="checkbox" name="is_featured" class="form-check-input" id="ef<?php echo $item['id']; ?>" <?php echo $item['is_featured'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="ef<?php echo $item['id']; ?>">Featured</label>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="form-check mt-4">
                                    <input type="checkbox" name="is_top_priority" class="form-check-input" id="etp<?php echo $item['id']; ?>" <?php echo $item['is_top_priority'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="etp<?php echo $item['id']; ?>">Top Pick</label>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="form-check mt-4">
                                    <input type="checkbox" name="is_available" class="form-check-input" id="ea<?php echo $item['id']; ?>" <?php echo $item['is_available'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="ea<?php echo $item['id']; ?>">Available</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn-admin w-100">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>