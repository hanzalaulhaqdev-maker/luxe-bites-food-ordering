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
        $name = sanitize($_POST['name']);
        $email = sanitizeEmail($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        
        // Validation
        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            $error = 'All fields are required.';
        } elseif (!isValidEmail($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (!isValidPhone($phone)) {
            $error = 'Please enter a valid phone number.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $user = new User();
            
            if ($user->emailExists($email)) {
                $error = 'Email already registered. Please login.';
            } else {
                if ($user->register($name, $email, $phone, $password)) {
                    $userData = $user->login($email, $password);
                    if ($userData) {
                        $_SESSION['user_id'] = $userData['id'];
                        $_SESSION['user_name'] = $userData['name'];
                        $_SESSION['user_email'] = $userData['email'];
                        redirect('index.php', 'Welcome to ' . APP_NAME . '!', 'success');
                    }
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title>Register - <?php echo APP_NAME; ?></title>
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
            <h2 class="auth-title">Join <span class="text-gold">Luxe Bites</span></h2>
            <p class="auth-subtitle">Create your account for exclusive benefits</p>
            
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="mb-3">
                    <label class="form-label-luxe">Full Name</label>
                    <input type="text" name="name" class="form-control form-control-luxe" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label-luxe">Email Address</label>
                    <input type="email" name="email" class="form-control form-control-luxe" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label-luxe">Phone Number</label>
                    <input type="tel" name="phone" class="form-control form-control-luxe" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label-luxe">Password</label>
                    <input type="password" name="password" class="form-control form-control-luxe" required minlength="6">
                </div>
                
                <div class="mb-4">
                    <label class="form-label-luxe">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control form-control-luxe" required>
                </div>
                
                <button type="submit" class="btn btn-luxe btn-luxe-primary w-100 mb-3">Create Account</button>
            </form>
            
            <p class="text-center text-muted mb-0">
                Already have an account? <a href="login.php" class="text-gold text-decoration-none">Sign In</a>
            </p>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>