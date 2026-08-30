<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/Admin.php';

if (isAdminLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        
        $admin = new Admin();
        $adminData = $admin->login($username, $password);
        
        if ($adminData) {
            $_SESSION['admin_id'] = $adminData['id'];
            $_SESSION['admin_username'] = $adminData['username'];
            $_SESSION['admin_name'] = $adminData['name'];
            redirect('index.php', 'Welcome back, ' . $adminData['name'] . '!', 'success');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body {
            background: #0b0b0b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #1a1a1a;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 3rem;
            width: 100%;
            max-width: 400px;
        }
        .login-title {
            font-family: Georgia, serif;
            color: #d4af37;
            text-align: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 class="login-title"><?php echo APP_NAME; ?> Admin</h2>
        
        <?php if ($flash['message']): ?>
        <div class="alert alert-<?php echo $flash['type'] ?: 'info'; ?> alert-dismissible fade show">
            <?php echo $flash['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="mb-3">
                <label class="form-label text-muted small">Username</label>
                <input type="text" name="username" class="form-control admin-form" style="background: #0b0b0b; border-color: rgba(255,255,255,0.1); color: white;" required>
            </div>
            
            <div class="mb-4">
                <label class="form-label text-muted small">Password</label>
                <input type="password" name="password" class="form-control" style="background: #0b0b0b; border-color: rgba(255,255,255,0.1); color: white;" required>
            </div>
            
            <button type="submit" class="btn-admin w-100">Sign In</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>