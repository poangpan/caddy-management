<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payroll.php';
requireRole(['accounting', 'admin']);

$pageTitle = 'ปิดยอดค่าจ้างรายสัปดาห์';

[$startDate, $endDate] = currentPayrollWeek(new DateTimeImmutable('today'));
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        closePayrollPeriod($pdo, $startDate, $endDate, currentUser()['id']);
        setFlash('success', 'ปิดยอดค่าจ้างสำหรับรอบนี้เรียบร้อย');
    } catch (RuntimeException $e) {
        setFlash('error', $e->getMessage());
    }
    header('Location: ' . BASE_URL . '/payroll/index.php');
    exit;
}

$period = findPayrollPeriod($pdo, $startDate, $endDate);

if ($period) {
    $stmt = $pdo->prepare(
        'SELECT c.full_name, pi.round_count, pi.total_wage
         FROM payroll_items pi JOIN caddies c ON c.id = pi.caddy_id
         WHERE pi.payroll_period_id = ?
         ORDER BY c.full_name'
    );
    $stmt->execute([$period['id']]);
    $rows = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare(
        "SELECT c.full_name, COUNT(r.id) AS round_count, SUM(r.wage_amount) AS total_wage
         FROM caddies c
         JOIN rounds r ON r.caddy_id = c.id
         WHERE r.status != 'scheduled' AND DATE(r.assigned_at) BETWEEN ? AND ?
         GROUP BY c.id, c.full_name
         ORDER BY c.full_name"
    );
    $stmt->execute([$startDate, $endDate]);
    $rows = $stmt->fetchAll();
}

$grandTotal = array_sum(array_column($rows, 'total_wage'));
$totalRounds = array_sum(array_column($rows, 'round_count'));

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>ปิดยอดค่าจ้างรายสัปดาห์</h1>
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

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card <?= $period ? 'payroll-locked' : '' ?>">
    <div class="page-header" style="margin-bottom:12px;">
        <h3>สัปดาห์ <?= e($startDate) ?> — <?= e($endDate) ?></h3>
        <?php if ($period): ?>
            <span class="badge badge-neutral">🔒 ปิดยอดแล้ว เมื่อ <?= e($period['closed_at']) ?></span>
        <?php else: ?>
            <form method="post" onsubmit="return confirm('ยืนยันปิดยอดสัปดาห์นี้? หลังปิดยอดแล้วจะไม่สามารถแก้ไขได้');">
                <button type="submit" class="btn btn-primary">ปิดยอด</button>
            </form>
        <?php endif; ?>
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
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
