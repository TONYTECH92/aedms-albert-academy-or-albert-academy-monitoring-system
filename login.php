<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . dashboard_url_for($_SESSION['role']));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } elseif (attempt_login($pdo, $email, $password)) {
        header('Location: ' . dashboard_url_for($_SESSION['role']));
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log in · AEDMS</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="brand-mark" style="width:46px;height:46px;font-size:16px;">AA</div>
        <h1>Albert Academy AEDMS</h1>
        <p class="sub">Integrated Digital Education Monitoring System</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/login.php">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn" style="width:100%;">Log In</button>
        </form>
        <p class="muted" style="text-align:center;margin-top:16px;">Forgot your password? Contact the system administrator.</p>
    </div>
</div>
</body>
</html>
