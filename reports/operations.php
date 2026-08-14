<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$pageTitle = 'รายงานการดำเนินงาน';

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
if (strtotime($startDate) === false) {
    $startDate = date('Y-m-01');
}
if (strtotime($endDate) === false) {
    $endDate = date('Y-m-d');
}
if ($endDate < $startDate) {
    [$startDate, $endDate] = [$endDate, $startDate];
}

// นับเฉพาะรอบที่เกิดขึ้นจริง (ตัด status = 'scheduled' ซึ่งเป็นการจองล่วงหน้าที่ยังไม่ถึงเวลา) ใช้ assigned_at เป็นวันที่อ้างอิง เหมือนหน้าปิดยอดค่าจ้าง
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rounds WHERE status != 'scheduled' AND DATE(assigned_at) BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$totalRounds = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT DATE(assigned_at) AS day, COUNT(*) AS total
     FROM rounds
     WHERE status != 'scheduled' AND DATE(assigned_at) BETWEEN ? AND ?
     GROUP BY DATE(assigned_at)
     ORDER BY day ASC"
);
$stmt->execute([$startDate, $endDate]);
$byDay = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT c.full_name,
            SUM(CASE WHEN r.caddy_requested = 1 THEN 1 ELSE 0 END) AS requested_count,
            SUM(CASE WHEN r.caddy_requested = 0 THEN 1 ELSE 0 END) AS queue_count,
            COUNT(r.id) AS total_rounds
     FROM rounds r
     JOIN caddies c ON c.id = r.caddy_id
     WHERE r.status != 'scheduled' AND DATE(r.assigned_at) BETWEEN ? AND ?
     GROUP BY c.id, c.full_name
     ORDER BY total_rounds DESC"
);
$stmt->execute([$startDate, $endDate]);
$byCaddy = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>รายงานการดำเนินงาน</h1>
</div>

<div class="card" style="max-width:500px;">
    <form method="get" class="form-row">
        <div class="form-group">
            <label for="start_date">ตั้งแต่วันที่</label>
            <input type="date" id="start_date" name="start_date" value="<?= e($startDate) ?>">
        </div>
        <div class="form-group">
            <label for="end_date">ถึงวันที่</label>
            <input type="date" id="end_date" name="end_date" value="<?= e($endDate) ?>">
        </div>
        <div class="form-group" style="flex:0;align-self:flex-end;">
            <button type="submit" class="btn btn-primary">ดูรายงาน</button>
        </div>
    </form>
</div>

<div class="stat-row" style="grid-template-columns: 1fr;">
    <div class="stat-tile">
        <div class="stat-tile-label">จำนวนรอบทั้งหมดในช่วงนี้</div>
        <div class="stat-tile-value"><?= $totalRounds ?></div>
    </div>
</div>

<div class="card">
    <h3>จำนวนรอบต่อวัน</h3>
    <table>
        <tr>
            <th>วันที่</th>
            <th class="text-right">จำนวนรอบ</th>
        </tr>
        <?php foreach ($byDay as $row): ?>
        <tr>
            <td class="font-mono"><?= e($row['day']) ?></td>
            <td class="text-right font-mono"><?= (int) $row['total'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$byDay): ?>
        <tr><td colspan="2" class="text-muted">ไม่มีรอบในช่วงวันที่นี้</td></tr>
        <?php endif; ?>
    </table>
</div>

<div class="card">
    <h3>อันดับแคดดี้ตามจำนวนรอบ</h3>
    <table>
        <tr>
            <th>แคดดี้</th>
            <th class="text-right">ลูกค้าขอชื่อ</th>
            <th class="text-right">มอบหมายตามคิว</th>
            <th class="text-right">รวม</th>
        </tr>
        <?php foreach ($byCaddy as $row): ?>
        <tr>
            <td><?= e($row['full_name']) ?></td>
            <td class="text-right font-mono"><?= (int) $row['requested_count'] ?></td>
            <td class="text-right font-mono"><?= (int) $row['queue_count'] ?></td>
            <td class="text-right font-mono"><strong><?= (int) $row['total_rounds'] ?></strong></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$byCaddy): ?>
        <tr><td colspan="4" class="text-muted">ไม่มีรอบในช่วงวันที่นี้</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
