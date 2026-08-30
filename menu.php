<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/Menu.php';

$menu = new Menu();
$categories = $menu->getCategories();
$selectedCategory = isset($_GET['category']) ? intval($_GET['category']) : null;
$menuItems = $menu->getItems($selectedCategory);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title>Menu - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .btn-buy-now {
            display: block;
            background: transparent;
            border: 1px solid var(--gold, #c9a96e);
            color: var(--gold, #c9a96e);
            padding: 6px 16px;
            font-size: 0.8rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.25s, color 0.25s;
            border-radius: 2px;
        }
        .btn-buy-now:hover {
            background: var(--gold, #c9a96e);
            color: #000;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-luxe fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="menu.php">Menu</a></li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-bag"></i>
                            <span class="badge badge-cart cart-badge" style="display: <?php echo calculateCartTotals()['item_count'] > 0 ? 'inline' : 'none'; ?>">
                                <?php echo calculateCartTotals()['item_count']; ?>
                            </span>
                        </a>
                    </li>
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
                        <li class="nav-item"><a class="btn btn-luxe ms-2" href="register.php">Join</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if ($flash['message']): ?>
    <div class="container mt-5 pt-4">
        <div class="alert alert-<?php echo $flash['type'] ?: 'info'; ?> alert-dismissible fade show">
            <?php echo $flash['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Menu Header -->
    <section class="section-luxe" style="padding-top: 120px;">
        <div class="container">
            <p class="section-subtitle">Our Selection</p>
            <h2 class="section-title">The <span>Menu</span></h2>
            <div class="divider-gold"></div>
            
            <!-- Category Filters -->
            <div class="text-center mt-4 mb-5">
                <a href="menu.php" class="menu-category-btn <?php echo !$selectedCategory ? 'active' : ''; ?>">
                    All
                </a>
                <?php foreach ($categories as $cat): ?>
                <a href="menu.php?category=<?php echo $cat['id']; ?>" class="menu-category-btn <?php echo $selectedCategory == $cat['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Menu Items -->
            <div id="menu-items-container">
                <?php foreach ($menuItems as $item): ?>
                <div class="menu-item-row">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="menu-item-img">
                    <div class="menu-item-info text-start">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="menu-item-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p class="menu-item-desc"><?php echo htmlspecialchars($item['description']); ?></p>
                                <span class="menu-item-price">$<?php echo number_format($item['price'], 2); ?></span>
                            </div>
                            <div class="text-end">
                                <?php if ($item['is_top_priority']): ?>
                                    <span class="badge-top-pick d-inline-block mb-2">Top Pick</span>
                                <?php endif; ?>
                                <div class="qty-control">
                                    <button class="qty-btn qty-minus">-</button>
                                    <span class="qty-value">1</span>
                                    <button class="qty-btn qty-plus">+</button>
                                </div>
                                <button class="btn-add-cart mt-2 w-100" data-id="<?php echo $item['id']; ?>">Add to Cart</button>
                                <button class="btn-buy-now mt-1 w-100" data-id="<?php echo $item['id']; ?>">Buy Now</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-luxe">
        <div class="container">
            <div class="footer-bottom">
                <p>Luxury for everyone. Quality without compromise.</p>
            </div>
        </div>
    </footer>

    <div class="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
    $(document).on('click', '.btn-buy-now', function (e) {
        e.preventDefault();
        const btn = $(this);
        const itemId = btn.data('id');
        const qty = parseInt(btn.closest('.menu-item-row').find('.qty-value').text()) || 1;

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'api/add_to_cart.php',
            type: 'POST',
            data: {
                item_id: itemId,
                qty: qty,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    window.location.href = 'checkout.php';
                } else {
                    showToast(response.message, 'error');
                    btn.prop('disabled', false).html('Buy Now');
                }
            },
            error: function () {
                showToast('Error adding to cart', 'error');
                btn.prop('disabled', false).html('Buy Now');
            }
        });
    });
    </script>
</html>