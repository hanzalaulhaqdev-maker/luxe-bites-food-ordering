-- ============================================
-- LUXE BITES RESTAURANT - DATABASE SCHEMA
-- Premium Food Ordering Platform
-- ============================================


-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. ADMIN TABLE
-- ============================================
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. CATEGORIES TABLE
-- ============================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. MENU ITEMS TABLE (WITH FEATURED SYSTEM)
-- ============================================
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    category_id INT NOT NULL,
    -- Featured Items System
    is_featured BOOLEAN DEFAULT FALSE,
    is_top_priority BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 999,
    -- Stock/Availability
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_featured (is_featured),
    INDEX idx_priority (is_top_priority),
    INDEX idx_order (display_order),
    INDEX idx_available (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. COUPONS TABLE (ADVANCED SYSTEM)
-- ============================================
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount INT NOT NULL COMMENT 'Percentage discount (1-100)',
    -- Coupon Type System
    type ENUM('public', 'private', 'exclusive') DEFAULT 'public',
    user_id INT NULL COMMENT 'For exclusive coupons - assigned user',
    -- Usage Control
    usage_limit INT DEFAULT NULL COMMENT 'NULL = unlimited',
    used_count INT DEFAULT 0,
    expiry_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type (type),
    INDEX idx_active (is_active),
    INDEX idx_expiry (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. RIDERS TABLE
-- ============================================
CREATE TABLE riders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. ORDERS TABLE
-- ============================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT 'NULL for guest orders',
    -- Customer Info (for both guest and registered)
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_address TEXT NOT NULL,
    -- Order Details
    total DECIMAL(10, 2) NOT NULL,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    coupon_code VARCHAR(50) NULL,
    final_total DECIMAL(10, 2) NOT NULL,
    -- Order Status
    status ENUM('pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled', 'rejected') DEFAULT 'pending',
    -- Rider Assignment
    rider_id INT NULL,
    rider_assigned_at DATETIME NULL,
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. ORDER ITEMS TABLE
-- ============================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    qty INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. ORDER STATUS HISTORY TABLE
-- ============================================
CREATE TABLE order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEED DATA
-- ============================================

-- Default Admin (password: admin123 - change in production!)
-- Hashed with bcrypt
INSERT INTO admin (username, password, name, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@luxebites.com');

-- Categories
INSERT INTO categories (name, slug, display_order) VALUES
('Appetizers', 'appetizers', 1),
('Main Course', 'main-course', 2),
('Steaks & Grills', 'steaks', 3),
('Seafood', 'seafood', 4),
('Pasta', 'pasta', 5),
('Desserts', 'desserts', 6),
('Beverages', 'beverages', 7);

-- Menu Items (will have images generated separately)
INSERT INTO menu_items (name, description, price, category_id, is_featured, is_top_priority, display_order, image) VALUES
-- Appetizers
('Truffle Arancini', 'Crispy risotto balls infused with black truffle, served with garlic aioli', 18.00, 1, TRUE, FALSE, 1, 'assets/images/food1.jpg'),
('Wagyu Beef Carpaccio', 'Thinly sliced wagyu with truffle oil, parmesan shavings, and capers', 32.00, 1, TRUE, FALSE, 2, 'assets/images/food2.jpg'),
('Lobster Bisque', 'Creamy lobster soup with cognac and chive crème fraîche', 24.00, 1, FALSE, FALSE, 3, 'assets/images/food3.jpg'),

-- Main Course
('Duck Confit', 'Slow-cooked duck leg with orange glaze and roasted root vegetables', 42.00, 2, TRUE, FALSE, 1, 'assets/images/food4.jpg'),
('Lamb Rack', 'Herb-crusted New Zealand lamb with mint pesto and potato gratin', 48.00, 2, TRUE, TRUE, 1, 'assets/images/food5.jpg'),
('Veal Osso Buco', 'Braised veal shank with saffron risotto and gremolata', 52.00, 2, FALSE, FALSE, 2, 'assets/images/food6.jpg'),

-- Steaks
('Ribeye Steak', '35-day aged ribeye, bone marrow butter, roasted garlic', 65.00, 3, TRUE, FALSE, 1, 'assets/images/food7.jpg'),
('Filet Mignon', 'Tenderloin with red wine reduction and asparagus', 72.00, 3, TRUE, TRUE, 2, 'assets/images/food8.jpg'),
('Tomahawk', '1.2kg sharing steak, chimichurri, truffle fries', 145.00, 3, TRUE, FALSE, 3, 'assets/images/food9.jpg'),

-- Seafood
('Grilled Octopus', 'Mediterranean style with lemon potatoes and kalamata olives', 38.00, 4, TRUE, FALSE, 1, 'assets/images/food10.jpg'),
('Pan-Seared Scallops', 'Jumbo scallops with cauliflower purée and crispy pancetta', 44.00, 4, TRUE, FALSE, 2, 'assets/images/food11.jpg'),
('Chilean Sea Bass', 'Miso-glazed with bok choy and ginger broth', 56.00, 4, FALSE, FALSE, 3, 'assets/images/food12.jpg'),

-- Pasta
('Lobster Linguine', 'Fresh pasta with whole lobster, cherry tomatoes, and white wine', 52.00, 5, TRUE, FALSE, 1, 'assets/images/food13.jpg'),
('Truffle Tagliatelle', 'Handmade pasta with black truffle cream and aged parmesan', 46.00, 5, TRUE, FALSE, 2, 'assets/images/food14.jpg'),
('Wild Mushroom Risotto', 'Arborio rice with porcini, chanterelle, and truffle oil', 34.00, 5, FALSE, FALSE, 3, 'assets/images/food15.jpg'),

-- Desserts
('Chocolate Fondant', 'Warm dark chocolate cake with vanilla bean ice cream', 18.00, 6, TRUE, FALSE, 1, 'assets/images/food16.jpg'),
('Crème Brûlée', 'Classic vanilla custard with caramelized sugar crust', 16.00, 6, TRUE, FALSE, 2, 'assets/images/food17.jpg'),
('Tiramisu', 'Espresso-soaked ladyfingers with mascarpone and cocoa', 17.00, 6, FALSE, FALSE, 3, 'assets/images/food18.jpg'),

-- Beverages
('Signature Old Fashioned', 'Bourbon, smoked maple syrup, angostura bitters, orange peel', 18.00, 7, FALSE, FALSE, 1, 'assets/images/food19.jpg'),
('Truffle Martini', 'Vodka, dry vermouth, black truffle essence', 22.00, 7, TRUE, FALSE, 2, 'assets/images/food20.jpg'),
('Premium Wine Selection', 'Rotating selection of sommelier-curated wines by the glass', 16.00, 7, FALSE, FALSE, 3, 'assets/images/food21.jpg');

-- Riders
INSERT INTO riders (name, phone, image, email, is_active) VALUES
('James Wilson', '+1 555-0101', 'assets/images/rider1.jpg', 'james@luxebites.com', TRUE),
('Maria Garcia', '+1 555-0102', 'assets/images/rider2.jpg', 'maria@luxebites.com', TRUE),
('David Chen', '+1 555-0103', 'assets/images/rider3.jpg', 'david@luxebites.com', TRUE),
('Sarah Johnson', '+1 555-0104', 'assets/images/rider4.jpg', 'sarah@luxebites.com', TRUE);

-- Coupons
INSERT INTO coupons (code, discount, type, usage_limit, used_count, expiry_date, is_active, description) VALUES
('WELCOME10', 10, 'public', 100, 0, '2026-12-31', TRUE, '10% off your first order'),
('LUXE20', 20, 'public', 50, 0, '2026-06-30', TRUE, '20% off for luxury dining experience'),
('VIP25', 25, 'private', NULL, 0, '2026-12-31', TRUE, 'Secret VIP discount - 25% off'),
('FLASH15', 15, 'public', 200, 0, '2026-05-15', TRUE, 'Limited time flash sale'),
('EXCLUSIVE30', 30, 'exclusive', 1, 0, '2026-12-31', TRUE, 'Exclusive personal discount');

-- ============================================
-- STORED PROCEDURES / VIEWS (Optional helpers)
-- ============================================

-- View for featured items with priority sorting
CREATE VIEW featured_menu AS
SELECT 
    m.*,
    c.name as category_name,
    c.slug as category_slug
FROM menu_items m
JOIN categories c ON m.category_id = c.id
WHERE m.is_featured = TRUE AND m.is_available = TRUE
ORDER BY 
    m.is_top_priority DESC,
    m.display_order ASC,
    m.created_at DESC;

-- View for active public coupons
CREATE VIEW active_public_coupons AS
SELECT * FROM coupons 
WHERE type = 'public' 
  AND is_active = TRUE 
  AND expiry_date >= CURDATE()
  AND (usage_limit IS NULL OR used_count < usage_limit);
