<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Clear all user session data
unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);

redirect('index.php', 'You have been logged out.', 'success');
