<?php
require_once __DIR__ . '/includes/auth.php';

redirectIfLoggedIn();

$error = isset($_GET['timeout']) ? 'Your session expired. Please sign in again.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrfValidateForPublicForm();
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (loginAdmin($username, $password)) {
            header('Location: index.php');
            exit;
        }

        $rateKey = 'login:' . twaClientIp() . ':' . strtolower($username);
        $wait = twaRateLimitRemainingSeconds($rateKey, 900);

        if ($wait > 0 && $wait < 900) {
            $mins = max(1, (int) ceil($wait / 60));
            $error = 'Too many failed attempts. Please wait about ' . $mins . ' minute(s) and try again.';
        } else {
            $error = 'Invalid username or password.';
        }
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | The Web Artist</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-brand">
            <div class="brand-icon">TWA</div>
            <h1>Admin Login</h1>
            <p>Sign in to manage enquiries and dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="login-error"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus placeholder="Enter username" value="<?= sanitize($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter password">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; padding:14px;">Sign In</button>
        </form>

       
    </div>
</div>
</body>
</html>
