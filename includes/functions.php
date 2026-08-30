<?php
/**
 * Helper Functions
 * Security, validation, and utility functions
 */

require_once __DIR__ . '/config.php';

/**
 * Generate CSRF token
 */
function generateCSRFToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Clean old CSRF token
 */
function clearCSRFToken(): void {
    unset($_SESSION[CSRF_TOKEN_NAME]);
}

/**
 * Sanitize input string
 */
function sanitize(string $data): string {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Sanitize email
 */
function sanitizeEmail(string $email): string {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Validate email
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 */
function isValidPhone(string $phone): bool {
    return preg_match('/^[\d\s\-\+\(\)]{10,20}$/', $phone) === 1;
}

/**
 * Generate random string
 */
function generateRandomString(int $length = 10): string {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Upload image file
 */
function uploadImage(array $file, string $uploadPath): ?string {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = MAX_UPLOAD_SIZE;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return null;
    }
    
    if ($file['size'] > $maxSize) {
        return null;
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $filepath = $uploadPath . $filename;
    
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return str_replace(__DIR__ . '/../', '', $filepath);
    }
    
    return null;
}

/**
 * Format price
 */
function formatPrice(float $price): string {
    return '$' . number_format($price, 2);
}

/**
 * Send JSON response
 */
function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Redirect with message
 */
function redirect(string $url, string $message = '', string $type = ''): void {
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

/**
 * Get flash message
 */
function getFlashMessage(): array {
    $message = $_SESSION['flash_message'] ?? '';
    $type = $_SESSION['flash_type'] ?? '';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    return ['message' => $message, 'type' => $type];
}

/**
 * Get cart from session
 */
function getCart(): array {
    return $_SESSION['cart'] ?? [];
}

/**
 * Save cart to session
 */
function saveCart(array $cart): void {
    $_SESSION['cart'] = $cart;
}

/**
 * Clear cart
 */
function clearCart(): void {
    unset($_SESSION['cart'], $_SESSION['coupon']);
}

/**
 * Calculate cart totals
 */
function calculateCartTotals(): array {
    $cart = getCart();
    $subtotal = 0;
    $itemCount = 0;
    
    foreach ($cart as $item) {
        $subtotal += $item['price'] * $item['qty'];
        $itemCount += $item['qty'];
    }
    
    $discount = 0;
    $couponCode = $_SESSION['coupon']['code'] ?? null;
    
    if ($couponCode && isset($_SESSION['coupon']['discount'])) {
        $discount = $subtotal * ($_SESSION['coupon']['discount'] / 100);
    }
    
    $total = $subtotal - $discount;
    
    return [
        'subtotal' => $subtotal,
        'discount' => $discount,
        'total' => max(0, $total),
        'item_count' => $itemCount,
        'coupon_code' => $couponCode
    ];
}

/**
 * Get order status badge class
 */
function getStatusBadgeClass(string $status): string {
    $classes = [
        'pending' => 'badge-pending',
        'confirmed' => 'badge-confirmed',
        'preparing' => 'badge-preparing',
        'out_for_delivery' => 'badge-delivery',
        'delivered' => 'badge-delivered',
        'cancelled' => 'badge-cancelled',
        'rejected' => 'badge-rejected'
    ];
    return $classes[$status] ?? 'badge-secondary';
}

/**
 * Get order status label
 */
function getStatusLabel(string $status): string {
    $labels = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'rejected' => 'Rejected'
    ];
    return $labels[$status] ?? ucfirst($status);
}

/**
 * Log activity (admin audit trail)
 */
function logActivity(string $action, string $details = ''): void {
    $logFile = __DIR__ . '/../logs/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $user = $_SESSION['admin_username'] ?? ($_SESSION['user_name'] ?? 'Guest');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $logEntry = "[$timestamp] [$ip] [$user] $action: $details" . PHP_EOL;
    
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
