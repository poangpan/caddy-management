<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['accounting', 'admin']);

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT pp.*, u.full_name AS closed_by_name FROM payroll_periods pp JOIN users u ON u.id = pp.closed_by WHERE pp.id = ?'
);
$stmt->execute([$id]);
$period = $stmt->fetch();

if (!$period) {
    setFlash('error', 'ไม่พบรอบการปิดยอดนี้');
    header('Location: ' . BASE_URL . '/payroll/history.php');
    exit;
}

$pageTitle = 'รอบปิดยอด ' . $period['start_date'] . ' — ' . $period['end_date'];

$stmt = $pdo->prepare(
    'SELECT c.full_name, pi.round_count, pi.total_wage
     FROM payroll_items pi JOIN caddies c ON c.id = pi.caddy_id
     WHERE pi.payroll_period_id = ?
     ORDER BY c.full_name'
);
$stmt->execute([$id]);
$rows = $stmt->fetchAll();
$grandTotal = array_sum(array_column($rows, 'total_wage'));
$totalRounds = array_sum(array_column($rows, 'round_count'));

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>รอบปิดยอด <?= e($period['start_date']) ?> — <?= e($period['end_date']) ?></h1>
    <a href="<?= BASE_URL ?>/payroll/history.php" class="btn btn-secondary">กลับไปประวัติการปิดยอด</a>
</div>

<div class="stat-row">
    <div class="stat-tile">
        <div class="stat-tile-label">แคดดี้ที่มีรายได้</div>
        <div class="stat-tile-value"><?= count($rows) ?></div>
    </div>
    <div class="stat-tile">
        <div class="stat-tile-label">จำนวนรอบทั้งหมด</div>
        <div class="stat-tile-value"><?= (int) $totalRounds ?></div>
    </div>
    <div class="stat-tile stat-tile--ready">
        <div class="stat-tile-label">ยอดค่าจ้างรวม</div>
        <div class="stat-tile-value"><?= e(number_format((float) $grandTotal, 2)) ?></div>
    </div>
</div>

<div class="card">
    <div class="page-header" style="margin-bottom:12px;">
        <h3>รายละเอียดต่อแคดดี้</h3>
        <span class="badge badge-neutral">🔒 ปิดยอดแล้ว เมื่อ <?= e($period['closed_at']) ?> โดย <?= e($period['closed_by_name']) ?></span>
    </div>

    <table>
        <tr>
            <th>แคดดี้</th>
            <th class="text-right">จำนวนรอบ</th>
            <th class="text-right">ยอดค่าจ้าง</th>
        </tr>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['full_name']) ?></td>
            <td class="text-right font-mono"><?= (int) $r['round_count'] ?></td>
            <td class="text-right font-mono"><?= e(number_format((float) $r['total_wage'], 2)) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td><strong>รวม</strong></td>
            <td></td>
            <td class="text-right font-mono"><strong><?= e(number_format((float) $grandTotal, 2)) ?></strong></td>
        </tr>
    </table>

    <a href="<?= BASE_URL ?>/payroll/export.php?period_id=<?= $period['id'] ?>" class="btn btn-primary" style="margin-top:12px;">Export CSV</a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
