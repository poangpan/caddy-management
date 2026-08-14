<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$pageTitle = 'แดชบอร์ด';
$user = currentUser();

$readyQueue = [];
$onRoundQueue = [];
$waitingQueue = [];

if (isQueueHr() || isAdmin()) {
    $leadMinutes = getAdvanceBookingLeadMinutes($pdo);
    $queue = fetchQueueBoard($pdo, $leadMinutes);
    foreach ($queue as $row) {
        if ($row['status'] === 'ready' && !$row['is_protected']) {
            $readyQueue[] = $row;
        } elseif ($row['status'] === 'on_round') {
            $stmt = $pdo->prepare(
                "SELECT id, customer_name, holes, assigned_at FROM rounds
                 WHERE caddy_id = ? AND status = 'in_progress'
                 ORDER BY assigned_at DESC LIMIT 1"
            );
            $stmt->execute([$row['id']]);
            $row['round'] = $stmt->fetch() ?: null;
            $onRoundQueue[] = $row;
        } elseif ($row['status'] === 'waiting') {
            $waitingQueue[] = $row;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <h1>สวัสดี, <?= e($user['full_name']) ?></h1>
</div>

<?php if (isQueueHr() || isAdmin()): ?>
<div class="dashboard-grid">
    <div class="card">
        <div class="page-header" style="margin-bottom:12px;">
            <h3>คิวแคดดี้ที่พร้อมออกรอบ (<?= count($readyQueue) ?>)</h3>
            <a href="<?= BASE_URL ?>/rounds/assign.php" class="btn btn-primary btn-sm">+ มอบหมายออกรอบ</a>
        </div>
        <table>
            <tr>
                <th>ลำดับ</th>
                <th>ชื่อ-นามสกุล</th>
                <th>เข้าสถานะพร้อมล่าสุด</th>
            </tr>
            <?php $seq = 0; foreach ($readyQueue as $row): ?>
            <tr>
                <td><?= ++$seq ?></td>
                <td><?= e($row['full_name']) ?></td>
                <td class="font-mono text-muted"><?= e($row['last_ready_at']) ?: '-' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$readyQueue): ?>
            <tr><td colspan="3" class="text-muted">ไม่มีแคดดี้พร้อมออกรอบตอนนี้</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="card">
        <h3>แคดดี้ที่กำลังออกรอบ (<?= count($onRoundQueue) ?>)</h3>
        <table>
            <tr>
                <th>ชื่อ-นามสกุล</th>
                <th>ลูกค้า</th>
                <th>หลุม</th>
                <th>เวลาออกรอบ</th>
                <th></th>
            </tr>
            <?php foreach ($onRoundQueue as $row): ?>
            <tr>
                <td><?= e($row['full_name']) ?></td>
                <td><?= $row['round'] ? e($row['round']['customer_name']) : '-' ?></td>
                <td><?= $row['round'] ? e($row['round']['holes']) : '-' ?></td>
                <td class="font-mono text-muted"><?= $row['round'] ? e($row['round']['assigned_at']) : '-' ?></td>
                <td>
                    <?php if ($row['round']): ?>
                        <a href="<?= BASE_URL ?>/rounds/rate.php?round_id=<?= $row['round']['id'] ?>&next_action=checkout" class="btn btn-sm btn-danger">เสร็จแล้ว</a>
                        <a href="<?= BASE_URL ?>/rounds/rate.php?round_id=<?= $row['round']['id'] ?>&next_action=ready" class="btn btn-sm btn-primary">พร้อมออกรอบต่อ</a>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$onRoundQueue): ?>
            <tr><td colspan="5" class="text-muted">ไม่มีแคดดี้ออกรอบอยู่ตอนนี้</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="card">
        <h3>แคดดี้ที่รอตามคิว (<?= count($waitingQueue) ?>)</h3>
        <table>
            <tr>
                <th>ชื่อ-นามสกุล</th>
            </tr>
            <?php foreach ($waitingQueue as $row): ?>
            <tr>
                <td><?= e($row['full_name']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$waitingQueue): ?>
            <tr><td class="text-muted">ไม่มีแคดดี้ที่รออยู่ตอนนี้</td></tr>
            <?php endif; ?>
        </table>
        <a href="<?= BASE_URL ?>/queue/board.php" class="btn btn-secondary btn-sm" style="margin-top:12px;">ไปที่หน้าคิวแคดดี้ทั้งหมด</a>
    </div>
</div>
<?php else: ?>
<div class="card">
    <p>เข้าสู่ระบบในบทบาท <strong><?= e(roleLabel($user['role'])) ?></strong></p>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
