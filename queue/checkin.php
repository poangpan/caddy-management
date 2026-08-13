<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caddyId = (int) ($_POST['caddy_id'] ?? 0);
    if ($caddyId) {
        $stmt = $pdo->prepare(
            "INSERT INTO caddy_queue_status (caddy_id, status, last_ready_at) VALUES (?, 'ready', NOW())
             ON DUPLICATE KEY UPDATE status = 'ready', last_ready_at = NOW()"
        );
        $stmt->execute([$caddyId]);
        setFlash('success', 'ลงเวลาเข้างานเรียบร้อย — เข้าคิวตามเวลานี้');
    }
    header('Location: ' . BASE_URL . '/queue/checkin.php');
    exit;
}

$pageTitle = 'ลงเวลาเข้างาน';
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'pending', 'checked_in'], true)) {
    $filter = 'all';
}

$leadMinutes = getAdvanceBookingLeadMinutes($pdo);
$queue = fetchQueueBoard($pdo, $leadMinutes);

$pendingCount = 0;
$checkedInCount = 0;
foreach ($queue as $row) {
    if ($row['raw_status'] === 'ready') {
        $checkedInCount++;
    } else {
        $pendingCount++;
    }
}

$rows = array_filter($queue, function ($row) use ($filter) {
    if ($filter === 'pending') {
        return $row['raw_status'] !== 'ready';
    }
    if ($filter === 'checked_in') {
        return $row['raw_status'] === 'ready';
    }
    return true;
});

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>ลงเวลาเข้างาน</h1>
</div>

<p class="text-muted" style="margin-top:-8px;">กดลงเวลาเมื่อแคดดี้มาถึงที่ทำงาน ระบบจะนำเข้าคิว FIFO ตามเวลานี้ทันที (ใช้ได้ไม่ว่าสถานะก่อนหน้าจะเป็นอะไร)</p>

<div class="filter-tabs">
    <a href="<?= BASE_URL ?>/queue/checkin.php?filter=all" class="filter-tab <?= $filter === 'all' ? 'is-active' : '' ?>">ทั้งหมด (<?= count($queue) ?>)</a>
    <a href="<?= BASE_URL ?>/queue/checkin.php?filter=pending" class="filter-tab <?= $filter === 'pending' ? 'is-active' : '' ?>">ยังไม่ลงเวลา (<?= $pendingCount ?>)</a>
    <a href="<?= BASE_URL ?>/queue/checkin.php?filter=checked_in" class="filter-tab <?= $filter === 'checked_in' ? 'is-active' : '' ?>">ลงเวลาแล้ว (<?= $checkedInCount ?>)</a>
</div>

<div class="card">
    <table>
        <tr>
            <th>ชื่อ-นามสกุล</th>
            <th>สถานะปัจจุบัน</th>
            <th>เวลาลงเวลาล่าสุด</th>
            <th></th>
        </tr>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['full_name']) ?></td>
            <td><span class="badge <?= e(queueStatusBadgeClass($row['status'])) ?>"><?= e(queueStatusLabel($row['status'])) ?></span></td>
            <td class="font-mono text-muted"><?= e($row['last_ready_at']) ?: '-' ?></td>
            <td>
                <form method="post">
                    <input type="hidden" name="caddy_id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-primary">ลงเวลาเข้างาน</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
        <tr><td colspan="4" class="text-muted">ไม่มีแคดดี้ในรายการนี้</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
