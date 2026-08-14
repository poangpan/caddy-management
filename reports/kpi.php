<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$pageTitle = 'KPI แคดดี้';

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

$sortOptions = [
    'rounds' => 'จำนวนรอบ',
    'wage' => 'ยอดรายได้',
    'requested_rate' => 'อัตราลูกค้าขอชื่อ',
    'vip' => 'งาน VIP',
    'days_worked' => 'วันที่เข้างาน',
    'rating' => 'คะแนนเฉลี่ย',
];
$sort = $_GET['sort'] ?? 'rounds';
if (!isset($sortOptions[$sort])) {
    $sort = 'rounds';
}

// รอบที่เกิดขึ้นจริง (ตัด status = 'scheduled') + ยอดรายได้ + สัดส่วนลูกค้าขอชื่อ + งาน VIP ต่อแคดดี้ในช่วงวันที่นี้
// หมายเหตุ: is_vip ถูกตั้งค่าตอนจองล่วงหน้าเท่านั้น (bookings/create.php) ไม่ได้ถูกส่งต่อมายังรอบจริงตอนมอบหมายออกรอบ
// (rounds/assign.php ไม่ผูก booking เดิมกับรอบที่มอบหมายจริง) ทำให้ "งาน VIP" ในรายงานนี้นับได้เฉพาะกรณีที่ข้อมูลถูกตั้งไว้จริงเท่านั้น
$stmt = $pdo->prepare(
    "SELECT c.id, c.full_name,
            COALESCE(rd.rounds_served, 0) AS rounds_served,
            COALESCE(rd.requested_count, 0) AS requested_count,
            COALESCE(rd.vip_count, 0) AS vip_count,
            COALESCE(rd.total_wage, 0) AS total_wage,
            COALESCE(att.days_worked, 0) AS days_worked,
            rt.avg_rating, COALESCE(rt.rating_count, 0) AS rating_count
     FROM caddies c
     LEFT JOIN (
         SELECT caddy_id,
                COUNT(*) AS rounds_served,
                SUM(CASE WHEN caddy_requested = 1 THEN 1 ELSE 0 END) AS requested_count,
                SUM(CASE WHEN is_vip = 1 THEN 1 ELSE 0 END) AS vip_count,
                COALESCE(SUM(wage_amount), 0) AS total_wage
         FROM rounds
         WHERE status != 'scheduled' AND DATE(assigned_at) BETWEEN ? AND ?
         GROUP BY caddy_id
     ) rd ON rd.caddy_id = c.id
     LEFT JOIN (
         SELECT caddy_id, COUNT(DISTINCT work_date) AS days_worked
         FROM attendance_log
         WHERE work_date BETWEEN ? AND ?
         GROUP BY caddy_id
     ) att ON att.caddy_id = c.id
     LEFT JOIN (
         SELECT r.caddy_id,
                AVG((rr.personality_rating + rr.politeness_rating + rr.knowledge_rating + rr.line_reading_rating
                     + rr.speed_rating + rr.service_rating + rr.satisfaction_rating) / 7) AS avg_rating,
                COUNT(*) AS rating_count
         FROM round_ratings rr
         JOIN rounds r ON r.id = rr.round_id
         WHERE DATE(r.assigned_at) BETWEEN ? AND ?
         GROUP BY r.caddy_id
     ) rt ON rt.caddy_id = c.id
     WHERE c.is_active = 1"
);
$stmt->execute([$startDate, $endDate, $startDate, $endDate, $startDate, $endDate]);
$rows = $stmt->fetchAll();

foreach ($rows as &$row) {
    $row['rounds_served'] = (int) $row['rounds_served'];
    $row['requested_count'] = (int) $row['requested_count'];
    $row['vip_count'] = (int) $row['vip_count'];
    $row['total_wage'] = (float) $row['total_wage'];
    $row['days_worked'] = (int) $row['days_worked'];
    $row['rating_count'] = (int) $row['rating_count'];
    $row['avg_rating'] = $row['rating_count'] > 0 ? round((float) $row['avg_rating'], 1) : null;
    $row['requested_rate'] = $row['rounds_served'] > 0 ? round($row['requested_count'] / $row['rounds_served'] * 100, 1) : null;
}
unset($row);

$sortKeyMap = [
    'rounds' => 'rounds_served',
    'wage' => 'total_wage',
    'requested_rate' => 'requested_rate',
    'vip' => 'vip_count',
    'days_worked' => 'days_worked',
    'rating' => 'avg_rating',
];
$sortKey = $sortKeyMap[$sort];
usort($rows, function ($a, $b) use ($sortKey) {
    // ค่า null (เช่น ไม่มีข้อมูลคะแนน) ให้อยู่ท้ายสุดเสมอ ไม่ว่าจะเรียงจากมากไปน้อย
    if ($a[$sortKey] === null && $b[$sortKey] === null) {
        return 0;
    }
    if ($a[$sortKey] === null) {
        return 1;
    }
    if ($b[$sortKey] === null) {
        return -1;
    }
    return $b[$sortKey] <=> $a[$sortKey];
});

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>KPI แคดดี้</h1>
    <a href="<?= BASE_URL ?>/reports/operations.php" class="btn btn-secondary">รายงานการดำเนินงาน</a>
</div>

<div class="card" style="max-width:700px;">
    <form method="get" class="form-row">
        <div class="form-group">
            <label for="start_date">ตั้งแต่วันที่</label>
            <input type="date" id="start_date" name="start_date" value="<?= e($startDate) ?>">
        </div>
        <div class="form-group">
            <label for="end_date">ถึงวันที่</label>
            <input type="date" id="end_date" name="end_date" value="<?= e($endDate) ?>">
        </div>
        <div class="form-group">
            <label for="sort">เรียงตาม</label>
            <select id="sort" name="sort">
                <?php foreach ($sortOptions as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="flex:0;align-self:flex-end;">
            <button type="submit" class="btn btn-primary">ดูรายงาน</button>
        </div>
    </form>
</div>

<p class="text-muted" style="margin-top:-8px;">
    "วันที่เข้างาน" นับจากวันที่เริ่มมีระบบลงเวลาเข้างาน ไม่มีข้อมูลย้อนหลังก่อนหน้านั้น —
    "งาน VIP" นับได้เฉพาะรอบที่ตั้งค่า VIP ไว้ตอนจองล่วงหน้าเท่านั้น
</p>

<div class="card">
    <table>
        <tr>
            <th>อันดับ</th>
            <th>แคดดี้</th>
            <th class="text-right">จำนวนรอบ</th>
            <th class="text-right">วันที่เข้างาน</th>
            <th class="text-right">ยอดรายได้</th>
            <th class="text-right">อัตราลูกค้าขอชื่อ</th>
            <th class="text-right">งาน VIP</th>
            <th class="text-right">คะแนนเฉลี่ย</th>
        </tr>
        <?php $rank = 0; foreach ($rows as $row): ?>
        <tr>
            <td><?= ++$rank ?></td>
            <td><?= e($row['full_name']) ?></td>
            <td class="text-right font-mono"><?= $row['rounds_served'] ?></td>
            <td class="text-right font-mono"><?= $row['days_worked'] ?></td>
            <td class="text-right font-mono"><?= e(number_format($row['total_wage'], 2)) ?></td>
            <td class="text-right font-mono"><?= $row['requested_rate'] !== null ? e(number_format($row['requested_rate'], 1)) . '%' : '-' ?></td>
            <td class="text-right font-mono"><?= $row['vip_count'] ?></td>
            <td class="text-right font-mono"><?= $row['avg_rating'] !== null ? e(number_format($row['avg_rating'], 1)) . ' ★' : '-' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
        <tr><td colspan="8" class="text-muted">ไม่มีข้อมูลในช่วงวันที่นี้</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
