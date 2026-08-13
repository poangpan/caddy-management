<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$statuses = ['ready', 'on_round', 'waiting', 'leave'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caddyId = (int) ($_POST['caddy_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';

    if ($caddyId && in_array($newStatus, $statuses, true)) {
        if ($newStatus === 'ready') {
            $stmt = $pdo->prepare(
                'INSERT INTO caddy_queue_status (caddy_id, status, last_ready_at) VALUES (?, ?, NOW())
                 ON DUPLICATE KEY UPDATE status = ?, last_ready_at = NOW()'
            );
            $stmt->execute([$caddyId, $newStatus, $newStatus]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO caddy_queue_status (caddy_id, status) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE status = ?'
            );
            $stmt->execute([$caddyId, $newStatus, $newStatus]);
        }
        setFlash('success', 'ปรับสถานะแคดดี้เรียบร้อย');
    }
    header('Location: ' . BASE_URL . '/queue/board.php');
    exit;
}

$pageTitle = 'คิวแคดดี้';
$leadMinutes = getAdvanceBookingLeadMinutes($pdo);
$queue = fetchQueueBoard($pdo, $leadMinutes);

$seq = 0;

$statusCounts = ['ready' => 0, 'on_round' => 0, 'waiting' => 0, 'leave' => 0];
foreach ($queue as $row) {
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']]++;
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>คิวแคดดี้ (FIFO)</h1>
    <a href="<?= BASE_URL ?>/rounds/assign.php" class="btn btn-primary">+ มอบหมายออกรอบ</a>
</div>

<div class="stat-row">
    <div class="stat-tile">
        <div class="stat-tile-label">แคดดี้ทั้งหมด</div>
        <div class="stat-tile-value"><?= count($queue) ?></div>
    </div>
    <div class="stat-tile stat-tile--ready">
        <div class="stat-tile-label">พร้อมคิว</div>
        <div class="stat-tile-value"><?= $statusCounts['ready'] ?></div>
    </div>
    <div class="stat-tile stat-tile--busy">
        <div class="stat-tile-label">ออกรอบ</div>
        <div class="stat-tile-value"><?= $statusCounts['on_round'] ?></div>
    </div>
    <div class="stat-tile stat-tile--waiting">
        <div class="stat-tile-label">รอ</div>
        <div class="stat-tile-value"><?= $statusCounts['waiting'] ?></div>
    </div>
    <div class="stat-tile stat-tile--leave">
        <div class="stat-tile-label">ลา</div>
        <div class="stat-tile-value"><?= $statusCounts['leave'] ?></div>
    </div>
</div>

<div class="card">
    <table>
        <tr>
            <th>ลำดับ</th>
            <th>ชื่อ-นามสกุล</th>
            <th>สถานะ</th>
            <th>เข้าสถานะพร้อมล่าสุด</th>
            <th>ปรับสถานะ</th>
        </tr>
        <?php foreach ($queue as $row): ?>
        <?php $eligible = $row['status'] === 'ready' && !$row['is_protected']; ?>
        <tr>
            <td><?= $eligible ? ++$seq : '-' ?></td>
            <td><?= e($row['full_name']) ?></td>
            <td>
                <span class="badge <?= e(queueStatusBadgeClass($row['status'])) ?>"><?= e(queueStatusLabel($row['status'])) ?></span>
                <?php if ($row['next_booking_at']): ?>
                    <span class="badge badge-booking">จองไว้ <?= e($row['next_booking_at']) ?></span>
                <?php endif; ?>
            </td>
            <td class="font-mono text-muted"><?= e($row['last_ready_at']) ?: '-' ?></td>
            <td>
                <form method="post" class="status-form">
                    <input type="hidden" name="caddy_id" value="<?= $row['id'] ?>">
                    <select name="status">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= e($s) ?>" <?= $row['raw_status'] === $s ? 'selected' : '' ?>><?= e(queueStatusLabel($s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-secondary">ปรับสถานะ</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
