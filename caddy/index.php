<?php
require_once __DIR__ . '/../includes/caddy_auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireCaddyLogin();

$caddyId = currentCaddy()['caddy_id'];
$pageTitle = 'หน้าแรก';

$leadMinutes = getAdvanceBookingLeadMinutes($pdo);
$queue = fetchQueueBoard($pdo, $leadMinutes);
$myQueueRow = null;
$mySequence = null;
$seq = 0;
foreach ($queue as $row) {
    if ($row['status'] === 'ready' && !$row['is_protected']) {
        $seq++;
    }
    if ((int) $row['id'] === $caddyId) {
        $myQueueRow = $row;
        if ($row['status'] === 'ready' && !$row['is_protected']) {
            $mySequence = $seq;
        }
    }
}

$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS rounds, COALESCE(SUM(wage_amount), 0) AS wage
     FROM rounds
     WHERE caddy_id = ? AND status != 'scheduled' AND YEAR(assigned_at) = YEAR(CURDATE())"
);
$stmt->execute([$caddyId]);
$ytd = $stmt->fetch();

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(wage_amount), 0) AS wage
     FROM rounds
     WHERE caddy_id = ? AND status != 'scheduled'
       AND YEAR(assigned_at) = YEAR(CURDATE()) AND MONTH(assigned_at) = MONTH(CURDATE())"
);
$stmt->execute([$caddyId]);
$monthWage = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS rating_count,
            AVG((rr.personality_rating + rr.politeness_rating + rr.knowledge_rating + rr.line_reading_rating
                 + rr.speed_rating + rr.service_rating + rr.satisfaction_rating) / 7) AS avg_rating
     FROM round_ratings rr
     JOIN rounds r ON r.id = rr.round_id
     WHERE r.caddy_id = ?"
);
$stmt->execute([$caddyId]);
$ratingStats = $stmt->fetch();
$ratingCount = (int) $ratingStats['rating_count'];
$avgRating = $ratingCount > 0 ? round((float) $ratingStats['avg_rating'], 1) : null;

$stmt = $pdo->prepare(
    "SELECT r.id, r.holes, r.customer_name, r.caddy_requested, r.wage_amount, r.cart_number, r.assigned_at,
            rr.personality_rating, rr.politeness_rating, rr.knowledge_rating, rr.line_reading_rating,
            rr.speed_rating, rr.service_rating, rr.satisfaction_rating, rr.comment
     FROM rounds r
     LEFT JOIN round_ratings rr ON rr.round_id = r.id
     WHERE r.caddy_id = ? AND r.status != 'scheduled'
     ORDER BY r.assigned_at DESC LIMIT 10"
);
$stmt->execute([$caddyId]);
$recentRounds = $stmt->fetchAll();

$leaveHistory = $pdo->prepare(
    "SELECT lr.start_date, lr.end_date, lr.note, lr.status, lt.name AS type_name
     FROM leave_requests lr
     JOIN leave_types lt ON lt.id = lr.leave_type_id
     WHERE lr.caddy_id = ?
     ORDER BY lr.start_date DESC"
);
$leaveHistory->execute([$caddyId]);
$leaveHistory = $leaveHistory->fetchAll();

require __DIR__ . '/../includes/caddy_header.php';
?>
<div class="page-header">
    <h1>สวัสดี, <?= e(currentCaddy()['full_name']) ?></h1>
</div>

<div class="card">
    <h3>สถานะคิวของคุณ</h3>
    <p>
        สถานะปัจจุบัน:
        <span class="badge <?= e(queueStatusBadgeClass($myQueueRow['status'] ?? null)) ?>"><?= e(queueStatusLabel($myQueueRow['status'] ?? null)) ?></span>
        <?php if ($mySequence !== null): ?>
            — ลำดับที่ <strong><?= $mySequence ?></strong> ในคิวพร้อมออกรอบ
        <?php endif; ?>
    </p>
</div>

<div class="stat-row" style="grid-template-columns: 1fr 1fr 1fr; margin-bottom:16px;">
    <div class="stat-tile">
        <div class="stat-tile-label">รอบปีนี้</div>
        <div class="stat-tile-value"><?= (int) $ytd['rounds'] ?></div>
    </div>
    <div class="stat-tile stat-tile--ready">
        <div class="stat-tile-label">ค่าจ้างเดือนนี้</div>
        <div class="stat-tile-value" style="font-size:18px;"><?= e(number_format($monthWage, 2)) ?></div>
    </div>
    <div class="stat-tile">
        <div class="stat-tile-label">คะแนนเฉลี่ย</div>
        <div class="stat-tile-value" style="font-size:18px;"><?= $avgRating !== null ? e(number_format($avgRating, 1)) . ' ★' : '-' ?></div>
        <?php if ($ratingCount > 0): ?><div class="text-muted" style="font-size:11px;"><?= $ratingCount ?> ครั้ง</div><?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>รอบล่าสุดของคุณ</h3>
    <table>
        <tr>
            <th>เวลา</th>
            <th>ลูกค้า</th>
            <th>หลุม</th>
            <th>เลขรถกอล์ฟ</th>
            <th>ค่าจ้าง</th>
            <th>คะแนน</th>
            <th>ความเห็น</th>
        </tr>
        <?php foreach ($recentRounds as $r): ?>
        <?php $avg = ratingAverage($r); ?>
        <tr>
            <td class="font-mono text-muted"><?= e($r['assigned_at']) ?></td>
            <td><?= e($r['customer_name']) ?></td>
            <td><?= e($r['holes']) ?></td>
            <td class="font-mono"><?= e($r['cart_number']) ?: '-' ?></td>
            <td class="font-mono"><?= e($r['wage_amount']) ?></td>
            <td><?= $avg !== null ? e(number_format($avg, 1)) . ' ★' : '-' ?></td>
            <td><?= e($r['comment']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$recentRounds): ?>
        <tr><td colspan="7" class="text-muted">ยังไม่มีรอบที่ออก</td></tr>
        <?php endif; ?>
    </table>
</div>

<div class="card">
    <h3>ประวัติการลาของคุณ</h3>
    <table>
        <tr>
            <th>ประเภท</th>
            <th>ช่วงวันที่ลา</th>
            <th>หมายเหตุ</th>
            <th>สถานะ</th>
        </tr>
        <?php foreach ($leaveHistory as $l): ?>
        <tr>
            <td><?= e($l['type_name']) ?></td>
            <td class="font-mono"><?= e($l['start_date']) ?> — <?= e($l['end_date']) ?></td>
            <td><?= e($l['note']) ?></td>
            <td><span class="badge <?= leaveStatusBadgeClass($l['status']) ?>"><?= e(leaveStatusLabel($l['status'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$leaveHistory): ?>
        <tr><td colspan="4" class="text-muted">ไม่มีประวัติการลา</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
