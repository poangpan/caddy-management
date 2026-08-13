<?php
// ต้อง require auth.php และ functions.php ก่อน include ไฟล์นี้ พร้อมกำหนด $pageTitle
requireLogin();
$user = currentUser();
$flash = getFlash();

$navIcons = [
    'dashboard' => '<path d="M4 13h6V4H4v9Zm0 7h6v-5H4v5Zm10 0h6V11h-6v9Zm0-16v5h6V4h-6Z"/>',
    'queue'     => '<path d="M4 6h16M4 12h16M4 18h10" stroke-linecap="round"/>',
    'caddies'   => '<path d="M17 20v-1.5a4 4 0 0 0-4-4h-2a4 4 0 0 0-4 4V20M15 6.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>',
    'leave'     => '<path d="M7 3v3M17 3v3M4 9h16M5 6h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z"/><path d="M9 14l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/>',
    'booking'   => '<path d="M7 3v3M17 3v3M4 9h16M5 6h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z"/>',
    'payroll'   => '<path d="M3 8h18v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8Z"/><path d="M3 8l2-4h14l2 4M12 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>',
    'wage'      => '<path d="M12 3v18M8 7h5.5a2.5 2.5 0 0 1 0 5H9a2.5 2.5 0 0 0 0 5H16" stroke-linecap="round"/>',
    'users'     => '<path d="M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5 9v-1.2A4.8 4.8 0 0 1 8.8 14h.4A4.8 4.8 0 0 1 14 18.8V20M17 8a2 2 0 1 0 0-4M16 14a3.5 3.5 0 0 1 4 3.46V20" stroke-linecap="round"/>',
];

function navIcon(array $icons, string $key): string
{
    return '<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">' . ($icons[$key] ?? '') . '</svg>';
}

$currentPath = $_SERVER['SCRIPT_NAME'] ?? '';
function navActive(string $path, string $needle): string
{
    return strpos($path, $needle) !== false ? ' is-active' : '';
}
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
                <p>Caddy Operations</p>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a class="<?= navActive($currentPath, '/dashboard.php') ?>" href="<?= BASE_URL ?>/dashboard.php"><?= navIcon($navIcons, 'dashboard') ?><span>แดชบอร์ด</span></a>
            <?php if (isQueueHr() || isAdmin()): ?>
                <a class="<?= navActive($currentPath, '/queue/') ?>" href="<?= BASE_URL ?>/queue/board.php"><?= navIcon($navIcons, 'queue') ?><span>คิวแคดดี้</span></a>
                <a class="<?= navActive($currentPath, '/caddies/') ?>" href="<?= BASE_URL ?>/caddies/list.php"><?= navIcon($navIcons, 'caddies') ?><span>ทะเบียนแคดดี้</span></a>
                <a class="<?= navActive($currentPath, '/leave/') ?>" href="<?= BASE_URL ?>/leave/index.php"><?= navIcon($navIcons, 'leave') ?><span>การลา</span></a>
                <a class="<?= navActive($currentPath, '/bookings/') ?>" href="<?= BASE_URL ?>/bookings/create.php"><?= navIcon($navIcons, 'booking') ?><span>จองแคดดี้ล่วงหน้า</span></a>
            <?php endif; ?>
            <?php if (isAccounting() || isAdmin()): ?>
                <a class="<?= navActive($currentPath, '/payroll/') ?>" href="<?= BASE_URL ?>/payroll/index.php"><?= navIcon($navIcons, 'payroll') ?><span>ปิดยอดค่าจ้าง</span></a>
            <?php endif; ?>
            <?php if (isAdmin()): ?>
                <a class="<?= navActive($currentPath, '/wage-rates/') ?>" href="<?= BASE_URL ?>/wage-rates/edit.php"><?= navIcon($navIcons, 'wage') ?><span>อัตราค่าจ้าง</span></a>
                <a class="<?= navActive($currentPath, '/users/') ?>" href="<?= BASE_URL ?>/users/list.php"><?= navIcon($navIcons, 'users') ?><span>จัดการผู้ใช้งาน</span></a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <?php if (!empty($user['photo_path'])): ?>
                    <img class="avatar avatar-sm" src="<?= BASE_URL ?>/<?= e($user['photo_path']) ?>" alt="">
                <?php else: ?>
                    <div class="avatar avatar-sm avatar-placeholder"><?= e(mb_substr($user['full_name'], 0, 1)) ?></div>
                <?php endif; ?>
                <div>
                    <strong><?= e($user['full_name']) ?></strong>
                    <span><?= e(roleLabel($user['role'])) ?></span>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/logout.php" class="btn-logout">ออกจากระบบ</a>
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
