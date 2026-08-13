<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$pageTitle = 'แดชบอร์ด';
$user = currentUser();

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <h1>สวัสดี, <?= e($user['full_name']) ?></h1>
</div>

<div class="card">
    <p>เข้าสู่ระบบในบทบาท <strong><?= e(roleLabel($user['role'])) ?></strong></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
