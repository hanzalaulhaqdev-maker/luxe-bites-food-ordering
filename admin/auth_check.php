<?php
/**
 * Admin Authentication Check
 * Include at the top of all admin pages
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$adminName = $_SESSION['admin_name'] ?? 'Administrator';
?>