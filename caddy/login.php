<?php
require_once __DIR__ . '/../includes/caddy_auth.php';

if (isCaddyLoggedIn()) {
    header('Location: ' . BASE_URL . '/caddy/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'กรุณากรอกอีเมลและรหัสผ่าน';
    } else {
        $error = attemptCaddyLogin($pdo, $email, $password);
        if ($error === null) {
            header('Location: ' . BASE_URL . '/caddy/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ (พอร์ทัลแคดดี้) - <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-box">
        <h1><?= htmlspecialchars(APP_NAME) ?></h1>
        <p class="text-muted">พอร์ทัลแคดดี้</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
            <div class="form-group">
                <label for="email">อีเมล</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">เข้าสู่ระบบ</button>
        </form>
    </div>
</body>
</html>
