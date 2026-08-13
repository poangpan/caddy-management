<?php
// ต้อง require auth.php และ functions.php ก่อน include ไฟล์นี้ พร้อมกำหนด $pageTitle
requireLogin();
$user = currentUser();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? '') ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-brand">
        <a href="<?= BASE_URL ?>/dashboard.php"><?= e(APP_NAME) ?></a>
    </div>
    <button type="button" class="nav-toggle" id="navToggle" aria-label="เปิดเมนู" aria-controls="topbarNav" aria-expanded="false">
        <span class="nav-toggle-bar"></span>
        <span class="nav-toggle-bar"></span>
        <span class="nav-toggle-bar"></span>
    </button>
    <nav class="topbar-nav" id="topbarNav">
        <a href="<?= BASE_URL ?>/dashboard.php">แดชบอร์ด</a>
        <?php if (isQueueHr() || isAdmin()): ?>
            <a href="<?= BASE_URL ?>/queue/board.php">คิวแคดดี้</a>
            <a href="<?= BASE_URL ?>/caddies/list.php">ทะเบียนแคดดี้</a>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>/users/list.php">จัดการผู้ใช้งาน</a>
        <?php endif; ?>
    </nav>
    <div class="topbar-user">
        <span><?= e($user['full_name']) ?> (<?= e(roleLabel($user['role'])) ?>)</span>
        <a href="<?= BASE_URL ?>/logout.php" class="btn-logout">ออกจากระบบ</a>
    </div>
</header>
<script>
(function () {
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('topbarNav');
    if (!toggle || !nav) return;
    toggle.addEventListener('click', function () {
        var isOpen = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
})();
</script>
<main class="container">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
