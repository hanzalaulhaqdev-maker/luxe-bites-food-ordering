<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/User.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = sanitizeEmail($_POST['email']);
        $password = $_POST['password'];
        
        $user = new User();
        $userData = $user->login($email, $password);
        
        if ($userData) {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['user_name'] = $userData['name'];
            $_SESSION['user_email'] = $userData['email'];
            redirect('index.php', 'Welcome back, ' . $userData['name'] . '!', 'success');
        } else {
            $error = 'Invalid email or password.';
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
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-luxe fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo APP_NAME; ?></a>
        </div>
    </nav>

    <section class="auth-section">
        <div class="auth-card">
            <h2 class="auth-title">Welcome <span class="text-gold">Back</span></h2>
            <p class="auth-subtitle">Sign in to continue your luxury experience</p>
            
            <?php if ($flash['message']): ?>
            <div class="alert alert-<?php echo $flash['type'] ?: 'info'; ?> alert-dismissible fade show">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="mb-3">
                    <label class="form-label-luxe">Email Address</label>
                    <input type="email" name="email" class="form-control form-control-luxe" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label-luxe">Password</label>
                    <input type="password" name="password" class="form-control form-control-luxe" required>
                </div>
                
                <button type="submit" class="btn btn-luxe btn-luxe-primary w-100 mb-3">Sign In</button>
            </form>
            
            <p class="text-center text-muted mb-0">
                Don't have an account? <a href="register.php" class="text-gold text-decoration-none">Join Now</a>
            </p>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>