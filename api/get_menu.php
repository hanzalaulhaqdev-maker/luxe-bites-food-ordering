<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/Menu.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Invalid request method');
}

$category = isset($_GET['category']) ? intval($_GET['category']) : null;

$menu = new Menu();
$items = $menu->getItems($category);

// Return HTML for AJAX insertion
$html = '';
foreach ($items as $item) {
    $badge = '';
    if ($item['is_top_priority']) {
        $badge = '<span class="badge-top-pick">Top Pick</span>';
    } elseif ($item['is_featured']) {
        $badge = '<span class="badge-featured">Featured</span>';
    }
    
    $html .= '
    <div class="menu-item-row">
        <img src="' . htmlspecialchars($item['image']) . '" alt="" class="menu-item-img">
        <div class="menu-item-info text-start">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="menu-item-title">' . htmlspecialchars($item['name']) . '</h5>
                    <p class="menu-item-desc">' . htmlspecialchars($item['description']) . '</p>
                    <span class="menu-item-price">$' . number_format($item['price'], 2) . '</span>
                </div>
                <div class="text-end">
                    ' . $badge . '
                    <div class="qty-control">
                        <button class="qty-btn qty-minus">-</button>
                        <span class="qty-value">1</span>
                        <button class="qty-btn qty-plus">+</button>
                    </div>
                    <button class="btn-add-cart mt-2 w-100" data-id="' . $item['id'] . '">Add to Cart</button>
                </div>
            </div>
        </div>
    </div>';
}

echo $html;
