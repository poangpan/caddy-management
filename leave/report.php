<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$pageTitle = 'รายงานการลารายเดือน';

$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
if ($month < 1 || $month > 12) {
    $month = (int) date('n');
}

$stmt = $pdo->prepare(
    "SELECT lt.name, COUNT(lr.id) AS total
     FROM leave_types lt
     LEFT JOIN leave_requests lr ON lr.leave_type_id = lt.id
        AND YEAR(lr.start_date) = ? AND MONTH(lr.start_date) = ? AND lr.status != 'rejected'
     GROUP BY lt.id, lt.name
     ORDER BY lt.id"
);
$stmt->execute([$year, $month]);
$summary = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>รายงานการลารายเดือน</h1>
    <a href="<?= BASE_URL ?>/leave/index.php" class="btn btn-secondary">กลับไปหน้าการลา</a>
</div>

<div class="card" style="max-width:500px;">
    <form method="get" class="form-row">
        <div class="form-group">
            <label for="month">เดือน</label>
            <select id="month" name="month">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= $m ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="year">ปี (ค.ศ.)</label>
            <input type="number" id="year" name="year" value="<?= $year ?>">
        </div>
        <div class="form-group" style="flex:0;align-self:flex-end;">
            <button type="submit" class="btn btn-primary">ดูรายงาน</button>
        </div>
    </form>
</div>

<div class="card">
    <table>
        <tr>
            <th>ประเภทการลา</th>
            <th class="text-right">จำนวนครั้ง</th>
        </tr>
        <?php foreach ($summary as $row): ?>
        <tr>
            <td><?= e($row['name']) ?></td>
            <td class="text-right font-mono"><?= (int) $row['total'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
