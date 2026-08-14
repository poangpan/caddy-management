<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['accounting', 'admin']);

$pageTitle = 'ประวัติการปิดยอดค่าจ้าง';

$periods = $pdo->query(
    "SELECT pp.id, pp.start_date, pp.end_date, pp.closed_at, u.full_name AS closed_by_name,
            COUNT(pi.id) AS caddy_count, COALESCE(SUM(pi.total_wage), 0) AS total_wage
     FROM payroll_periods pp
     JOIN users u ON u.id = pp.closed_by
     LEFT JOIN payroll_items pi ON pi.payroll_period_id = pp.id
     GROUP BY pp.id, pp.start_date, pp.end_date, pp.closed_at, u.full_name
     ORDER BY pp.start_date DESC"
)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>ประวัติการปิดยอดค่าจ้าง</h1>
    <a href="<?= BASE_URL ?>/payroll/index.php" class="btn btn-secondary">กลับไปหน้าปิดยอดค่าจ้าง</a>
</div>

<div class="card">
    <table>
        <tr>
            <th>สัปดาห์</th>
            <th>ปิดยอดเมื่อ</th>
            <th>ปิดยอดโดย</th>
            <th class="text-right">แคดดี้ที่มีรายได้</th>
            <th class="text-right">ยอดค่าจ้างรวม</th>
            <th></th>
        </tr>
        <?php foreach ($periods as $p): ?>
        <tr>
            <td class="font-mono"><?= e($p['start_date']) ?> — <?= e($p['end_date']) ?></td>
            <td class="font-mono text-muted"><?= e($p['closed_at']) ?></td>
            <td><?= e($p['closed_by_name']) ?></td>
            <td class="text-right font-mono"><?= (int) $p['caddy_count'] ?></td>
            <td class="text-right font-mono"><?= e(number_format((float) $p['total_wage'], 2)) ?></td>
            <td>
                <a href="<?= BASE_URL ?>/payroll/view.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-secondary">ดูรายละเอียด</a>
                <a href="<?= BASE_URL ?>/payroll/export.php?period_id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Export CSV</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$periods): ?>
        <tr><td colspan="6" class="text-muted">ยังไม่มีรอบที่ปิดยอด</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
