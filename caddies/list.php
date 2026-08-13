<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$pageTitle = 'ทะเบียนแคดดี้';
$caddies = $pdo->query('SELECT * FROM caddies ORDER BY full_name')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>ทะเบียนแคดดี้</h1>
    <a href="<?= BASE_URL ?>/caddies/form.php" class="btn btn-primary">+ เพิ่มแคดดี้</a>
</div>

<div class="card">
    <table>
        <tr>
            <th>ชื่อ-นามสกุล</th>
            <th>เบอร์โทร</th>
            <th>เลขบัตรประชาชน</th>
            <th>เลขบัญชี SCB</th>
            <th>วันที่เริ่มงาน</th>
            <th>สถานะ</th>
            <th></th>
        </tr>
        <?php foreach ($caddies as $c): ?>
        <tr>
            <td><?= e($c['full_name']) ?></td>
            <td class="font-mono"><?= e($c['phone']) ?></td>
            <td class="font-mono"><?= e($c['national_id']) ?></td>
            <td class="font-mono"><?= e($c['bank_account_number']) ?></td>
            <td><?= e($c['start_date']) ?></td>
            <td>
                <?php if ($c['is_active']): ?>
                    <span class="badge badge-success">ทำงานอยู่</span>
                <?php else: ?>
                    <span class="badge badge-danger">พ้นสภาพ</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="<?= BASE_URL ?>/caddies/form.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">แก้ไข</a>
                <a href="<?= BASE_URL ?>/caddies/summary.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">สรุปรายการ</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
