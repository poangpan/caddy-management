<?php
// ต้อง require caddy_auth.php ก่อน include ไฟล์นี้ พร้อมกำหนด $pageTitle — เลย์เอาต์แยกจาก includes/header.php (staff) โดยตั้งใจ
requireCaddyLogin();
$caddy = currentCaddy();
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
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark">⛳</div>
            <div>
                <h1><?= e(APP_NAME) ?></h1>
                <p>พอร์ทัลแคดดี้</p>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a class="is-active" href="<?= BASE_URL ?>/caddy/index.php"><span>หน้าแรก</span></a>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="avatar avatar-sm avatar-placeholder"><?= e(mb_substr($caddy['full_name'], 0, 1)) ?></div>
                <div>
                    <strong><?= e($caddy['full_name']) ?></strong>
                    <span>แคดดี้</span>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/caddy/logout.php" class="btn-logout">ออกจากระบบ</a>
        </div>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-wrapper">
        <header class="content-topbar">
            <button type="button" class="nav-toggle" id="navToggle" aria-label="เปิดเมนู" aria-controls="sidebar" aria-expanded="false">
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
            </button>
            <span class="content-topbar-title"><?= e(APP_NAME) ?></span>
        </header>
        <script>
        (function () {
            var toggle = document.getElementById('navToggle');
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebarOverlay');
            if (!toggle || !sidebar || !overlay) return;
            function close() {
                sidebar.classList.remove('is-open');
                overlay.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
            toggle.addEventListener('click', function () {
                var isOpen = sidebar.classList.toggle('is-open');
                overlay.classList.toggle('is-open', isOpen);
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
            overlay.addEventListener('click', close);
        })();
        </script>
        <main class="container">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
