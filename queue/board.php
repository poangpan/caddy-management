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
// สถานะที่แสดงผล (status) ตัดแคดดี้ที่มีวันลาครอบคลุมวันนี้ออกจากคิวโดยอัตโนมัติ เว้นแต่พนักงานตั้งสถานะเป็น "พร้อม" เอง (raw_status)
// ซึ่งถือเป็นการดึงกลับเข้าคิวเองตาม AC — คำนวณจากวันที่ปัจจุบันตอน query ทุกครั้ง ไม่ต้องมี background job
$queue = $pdo->query(
    "SELECT c.id, c.full_name, cqs.status AS raw_status, cqs.last_ready_at,
            CASE
                WHEN lr.caddy_id IS NOT NULL AND (cqs.status IS NULL OR cqs.status != 'ready') THEN 'leave'
                ELSE cqs.status
            END AS status
     FROM caddies c
     LEFT JOIN caddy_queue_status cqs ON cqs.caddy_id = c.id
     LEFT JOIN (
         SELECT DISTINCT caddy_id FROM leave_requests WHERE CURDATE() BETWEEN start_date AND end_date
     ) lr ON lr.caddy_id = c.id
     WHERE c.is_active = 1
     ORDER BY CASE WHEN status = 'ready' THEN 0 ELSE 1 END, cqs.last_ready_at ASC, c.full_name ASC"
)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>คิวแคดดี้ (FIFO)</h1>
    <a href="<?= BASE_URL ?>/rounds/assign.php" class="btn btn-primary">+ มอบหมายออกรอบ</a>
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
        <?php foreach ($queue as $i => $row): ?>
        <tr>
            <td><?= $row['status'] === 'ready' ? $i + 1 : '-' ?></td>
            <td><?= e($row['full_name']) ?></td>
            <td><span class="badge <?= e(queueStatusBadgeClass($row['status'])) ?>"><?= e(queueStatusLabel($row['status'])) ?></span></td>
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
