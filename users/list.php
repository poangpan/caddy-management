<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$pageTitle = 'จัดการผู้ใช้งาน';
$users = $pdo->query('SELECT * FROM users ORDER BY full_name')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>จัดการผู้ใช้งาน</h1>
    <a href="<?= BASE_URL ?>/users/form.php" class="btn btn-primary">+ เพิ่มผู้ใช้งาน</a>
</div>

<div class="card">
    <table>
        <tr>
            <th>ชื่อ-นามสกุล</th>
            <th>อีเมล</th>
            <th>สิทธิ์</th>
            <th>สถานะ</th>
            <th></th>
        </tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['full_name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e(roleLabel($u['role'])) ?></td>
            <td>
                <?php if ($u['is_active']): ?>
                    <span class="badge badge-success">ใช้งาน</span>
                <?php else: ?>
                    <span class="badge badge-danger">ระงับ</span>
                <?php endif; ?>
            </td>
            <td><a href="<?= BASE_URL ?>/users/form.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-secondary">แก้ไข</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
