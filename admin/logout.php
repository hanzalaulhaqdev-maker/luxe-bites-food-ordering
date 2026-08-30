<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_name']);
redirect('login.php', 'You have been logged out.', 'success');
