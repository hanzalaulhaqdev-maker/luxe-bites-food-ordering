<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(false, 'Invalid security token');
}

unset($_SESSION['coupon']);

$totals = calculateCartTotals();
jsonResponse(true, 'Coupon removed', [
    'totals' => $totals
]);
