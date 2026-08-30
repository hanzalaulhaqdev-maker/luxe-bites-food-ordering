<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/Menu.php';

$menu = new Menu();
$featuredItems = $menu->getFeaturedItems(8);
$categories = $menu->getCategories();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title><?php echo APP_NAME; ?> - Premium Dining Delivered</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        /* ============================================
           HERO SPLIT SCROLL ANIMATION
           ============================================ */
        #hero-scroll-container {
            position: relative;
        }

        #hero {
            position: relative;
            height: 100vh;
            width: 100%;
            overflow: hidden;
            background: #080808;
            perspective: 1200px;
            perspective-origin: 50% 50%;
        }

        .hero-half {
            position: absolute;
            inset: 0;
            will-change: transform;
            transform-origin: center center;
            transform-style: preserve-3d;
        }
        .hero-half-left   { clip-path: inset(0 50% 0 0); }
        .hero-half-right  { clip-path: inset(0 0 0 50%); }
        .hero-half img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 30%;
            display: block;
        }

        #hero-seam {
            position: absolute;
            top: 0; bottom: 0;
            left: 50%;
            width: 1px;
            transform: translateX(-50%);
            background: rgba(212,175,55,0);
            z-index: 20;
            pointer-events: none;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            z-index: 30;
            display: flex;
            align-items: center;
            pointer-events: none;
        }
        .hero-overlay::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                105deg,
                rgba(0,0,0,0.75) 0%,
                rgba(0,0,0,0.45) 55%,
                rgba(0,0,0,0.05) 100%
            );
        }
        .hero-overlay .container { position: relative; }
        .hero-overlay .btn       { pointer-events: auto; }

        .scroll-indicator {
            position: absolute;
            bottom: 2.5rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            z-index: 35;
        }
        .scroll-indicator span {
            font-size: 0.65rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.45);
        }
        .scroll-line {
            width: 1px;
            height: 44px;
            background: linear-gradient(to bottom, rgba(212,175,55,0.8), transparent);
            animation: scrollPulse 1.6s ease-in-out infinite;
        }
        @keyframes scrollPulse {
            0%, 100% { opacity: 0.35; transform: scaleY(0.9); }
            50%       { opacity: 1;    transform: scaleY(1.1); }
        }

        .hero-particles {
            position: absolute;
            inset: 0;
            z-index: 5;
            pointer-events: none;
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
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
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

    <!-- =============================================
         HERO — tall scroll container + sticky panel
         ============================================= -->
    <div id="hero-scroll-container">
        <section id="hero">

            <!-- Floating particles -->
            <div class="hero-particles">
                <?php for ($i = 0; $i < 20; $i++): ?>
                    <div class="particle" style="left: <?php echo rand(0, 100); ?>%; top: <?php echo rand(0, 100); ?>%;"></div>
                <?php endfor; ?>
            </div>

            <!-- LEFT half of hero image -->
            <div class="hero-half hero-half-left" id="heroHalfTop">
                <img src="assets/images/hero-dish.jpg" alt="Premium Gourmet Dining">
            </div>

            <!-- RIGHT half of hero image -->
            <div class="hero-half hero-half-right" id="heroHalfBottom">
                <img src="assets/images/hero-dish.jpg" alt="Premium Gourmet Dining">
            </div>

            <!-- Gold seam line (appears as image splits) -->
            <div id="hero-seam"></div>

            <!-- Hero text overlay -->
            <div class="hero-overlay" id="heroOverlay">
                <div class="container">
                    <div class="hero-content">
                        <p class="hero-subtitle">Fine Dining. Delivered.</p>
                        <h1 class="hero-title">
                            Taste the<br><span>Extraordinary</span>
                        </h1>
                        <p class="hero-desc">
                            Experience Michelin-starred cuisine from the comfort of your home.
                            Crafted by master chefs, delivered with precision.
                        </p>
                        <a href="menu.php" class="btn btn-luxe btn-luxe-primary">Explore Menu</a>
                    </div>
                </div>
            </div>

            <!-- Scroll indicator -->
            <div class="scroll-indicator" id="scrollIndicator">
                <span>Scroll</span>
                <div class="scroll-line"></div>
            </div>

        </section>
    </div><!-- /hero-scroll-container -->


    <!-- Featured Dishes -->
    <section class="section-luxe dishes-section" id="dishes">
        <div class="container">
            <p class="section-subtitle">Curated Selection</p>
            <h2 class="section-title">Our <span>Signature</span> Dishes</h2>
            <div class="divider-gold"></div>
            <div class="row g-4 mt-4">
                <?php foreach ($featuredItems as $index => $item): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="dish-card" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                        <?php if ($item['is_top_priority']): ?>
                            <span class="badge-top-pick">Top Pick</span>
                        <?php endif; ?>
                        <?php if ($item['is_featured'] && !$item['is_top_priority']): ?>
                            <span class="badge-featured">Featured</span>
                        <?php endif; ?>
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="dish-card-image">
                        <div class="dish-card-body">
                            <h5 class="dish-card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                            <p class="dish-card-desc"><?php echo htmlspecialchars($item['description']); ?></p>
                            <div class="dish-card-footer">
                                <span class="dish-price">$<?php echo number_format($item['price'], 2); ?></span>
                                <button class="btn-add-cart" data-id="<?php echo $item['id']; ?>">Add to Cart</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-5">
                <a href="menu.php" class="btn btn-luxe">View Full Menu</a>
            </div>
        </div>
    </section>


    <!-- Categories -->
    <section class="section-luxe" style="background: var(--bg-secondary);">
        <div class="container">
            <p class="section-subtitle">Browse By</p>
            <h2 class="section-title">Our <span>Categories</span></h2>
            <div class="divider-gold"></div>
            <div class="row g-4 mt-4">
                <?php foreach ($categories as $cat): ?>
                <div class="col-lg-3 col-md-6">
                    <a href="menu.php?category=<?php echo $cat['id']; ?>" class="text-decoration-none">
                        <div class="glass-card text-center">
                            <div class="mb-3"><i class="fas fa-utensils fa-2x text-gold"></i></div>
                            <h5 class="mb-2"><?php echo htmlspecialchars($cat['name']); ?></h5>
                            <p class="text-muted mb-0" style="font-size: 0.85rem;">Explore our selection</p>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>


    <!-- Why Choose Us -->
    <section class="section-luxe">
        <div class="container">
            <p class="section-subtitle">The Experience</p>
            <h2 class="section-title">Why <span>Luxe Bites</span></h2>
            <div class="divider-gold"></div>
            <div class="row g-4 mt-4">
                <div class="col-md-4">
                    <div class="glass-card text-center">
                        <i class="fas fa-star fa-2x text-gold mb-3"></i>
                        <h5>Michelin Quality</h5>
                        <p class="text-muted">Every dish crafted by award-winning chefs using premium ingredients.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card text-center">
                        <i class="fas fa-shipping-fast fa-2x text-gold mb-3"></i>
                        <h5>Swift Delivery</h5>
                        <p class="text-muted">Hot and fresh delivered within 45 minutes by our trained riders.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card text-center">
                        <i class="fas fa-gem fa-2x text-gold mb-3"></i>
                        <h5>Premium Packaging</h5>
                        <p class="text-muted">Elegant, eco-friendly packaging that preserves taste and presentation.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Footer -->
    <footer class="footer-luxe">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h4 class="text-gold mb-3"><?php echo APP_NAME; ?></h4>
                    <p class="text-muted">Redefining luxury dining at home. Every meal is an experience worth savoring.</p>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="footer-title">Quick Links</h5>
                    <a href="index.php" class="footer-link">Home</a>
                    <a href="menu.php" class="footer-link">Menu</a>
                    <a href="offers.php" class="footer-link">Offers</a>
                    <a href="cart.php" class="footer-link">Cart</a>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="footer-title">Account</h5>
                    <a href="login.php" class="footer-link">Login</a>
                    <a href="register.php" class="footer-link">Register</a>
                    <a href="my-discounts.php" class="footer-link">My Discounts</a>
                    <a href="orders.php" class="footer-link">My Orders</a>
                </div>
                <div class="col-lg-4 col-md-4 mb-4">
                    <h5 class="footer-title">Contact</h5>
                    <p class="text-muted"><i class="fas fa-envelope me-2 text-gold"></i> info@luxebites.com</p>
                    <p class="text-muted"><i class="fas fa-phone me-2 text-gold"></i> +1 800-LUXE-BITES</p>
                    <p class="text-muted"><i class="fas fa-map-marker-alt me-2 text-gold"></i> 123 Gourmet Avenue, NYC</p>
                </div>
            </div>
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
    (function () {
        var hero      = document.getElementById('hero');
        var halfLeft  = document.getElementById('heroHalfTop');
        var halfRight = document.getElementById('heroHalfBottom');
        var seam      = document.getElementById('hero-seam');
        var overlay   = document.getElementById('heroOverlay');
        var indicator = document.getElementById('scrollIndicator');

        if (!hero || !halfLeft) return;

        var MAX_X  = 55;
        var MAX_Z  = 140;
        var MAX_RY = 5;
        var ticking = false;

        function update() {
            var scrollY = window.scrollY || window.pageYOffset;
            /* p = 0 at top, p = 1 when hero fully scrolled past */
            var p = Math.max(0, Math.min(1, scrollY / hero.offsetHeight));

            var xPx = p * MAX_X * (window.innerWidth / 100);
            var zPx = p * MAX_Z;
            var ry  = p * MAX_RY;

            halfLeft.style.transform  = 'translateX(-' + xPx + 'px) translateZ(' + zPx + 'px) rotateY(' + ry + 'deg)';
            halfRight.style.transform = 'translateX('  + xPx + 'px) translateZ(' + zPx + 'px) rotateY(-' + ry + 'deg)';

            seam.style.background = 'rgba(212,175,55,' + (p * 0.9) + ')';
            seam.style.boxShadow  = p > 0.05
                ? '0 0 ' + (p * 18) + 'px ' + (p * 6) + 'px rgba(212,175,55,' + (p * 0.35) + ')'
                : 'none';

            var alpha = Math.max(0, 1 - p * 3);
            overlay.style.opacity   = alpha;
            indicator.style.opacity = alpha;

            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (!ticking) { requestAnimationFrame(update); ticking = true; }
        }, { passive: true });

        update();
    })();
    </script>

</body>
</html>