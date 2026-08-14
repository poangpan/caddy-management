<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

// "ลงเวลาแล้ว" นับจากวันที่ของ last_ready_at เทียบกับ CURDATE() ของฐานข้อมูล (ไม่ใช้เวลาของ PHP เพื่อกันเวลาไม่ตรงกัน)
// เป็นคนละกลไกกับการปรับสถานะทั่วไปในหน้าคิว (queue/board.php) ซึ่งยังปรับกลับเป็น "พร้อม" ได้ไม่จำกัดตลอดวัน
// (เช่น แคดดี้กลับจากออกรอบ) — ข้อจำกัดนี้คุมเฉพาะปุ่ม "ลงเวลาเข้างาน" ไม่ให้กดซ้ำในวันเดียวกัน
// เลิกงาน (check-out): ลบแถวสถานะคิวออกทั้งแถว — ทำให้ (1) แคดดี้ไม่นับเป็น "พร้อม" ในคิว FIFO อีกต่อไป
// และ (2) last_ready_at หายไปด้วย จึงเปิดให้ลงเวลาเข้างานใหม่ได้ในวันเดียวกันถ้ากลับเข้ากะอีกครั้ง (ต่างจากลงเวลาซ้ำโดยไม่ได้เลิกงานก่อน ซึ่งยังถูกกันอยู่)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_id'])) {
    $pdo->prepare('DELETE FROM caddy_queue_status WHERE caddy_id = ?')->execute([(int) $_POST['checkout_id']]);
    setFlash('success', 'บันทึกเลิกงานเรียบร้อย');
    header('Location: ' . BASE_URL . '/queue/checkin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caddyId = (int) ($_POST['caddy_id'] ?? 0);
    if ($caddyId) {
        $stmt = $pdo->prepare(
            "SELECT 1 FROM caddy_queue_status WHERE caddy_id = ? AND DATE(last_ready_at) = CURDATE()"
        );
        $stmt->execute([$caddyId]);
        if ($stmt->fetchColumn()) {
            setFlash('error', 'แคดดี้คนนี้ลงเวลาเข้างานไปแล้ววันนี้ ลงเวลาซ้ำได้ในวันถัดไป');
        } else {
            $pdo->prepare(
                "INSERT INTO caddy_queue_status (caddy_id, status, last_ready_at) VALUES (?, 'ready', NOW())
                 ON DUPLICATE KEY UPDATE status = 'ready', last_ready_at = NOW()"
            )->execute([$caddyId]);
            setFlash('success', 'ลงเวลาเข้างานเรียบร้อย — เข้าคิวตามเวลานี้');
        }
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
$today = $pdo->query('SELECT CURDATE()')->fetchColumn();

foreach ($queue as &$row) {
    $row['checked_in_today'] = $row['last_ready_at'] !== null && substr($row['last_ready_at'], 0, 10) === $today;
}
unset($row);

$pendingCount = 0;
$checkedInCount = 0;
foreach ($queue as $row) {
    if ($row['checked_in_today']) {
        $checkedInCount++;
    } else {
        $pendingCount++;
    }
}

$rows = array_filter($queue, function ($row) use ($filter) {
    if ($filter === 'pending') {
        return !$row['checked_in_today'];
    }
    if ($filter === 'checked_in') {
        return $row['checked_in_today'];
    }
    return true;
});

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>ลงเวลาเข้างาน</h1>
</div>

<p class="text-muted" style="margin-top:-8px;">กดลงเวลาเมื่อแคดดี้มาถึงที่ทำงาน ระบบจะนำเข้าคิว FIFO ตามเวลานี้ทันที — ลงเวลาได้วันละครั้งต่อคน หากต้องปรับสถานะระหว่างวัน (เช่น กลับจากออกรอบ) ให้ใช้หน้าคิวแคดดี้แทน</p>

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
                <?php if ($row['checked_in_today']): ?>
                    <span class="badge badge-neutral">ลงเวลาแล้ววันนี้</span>
                    <form method="post" style="display:inline;" onsubmit="return confirm('ยืนยันเลิกงานสำหรับ <?= e($row['full_name']) ?>?');">
                        <input type="hidden" name="checkout_id" value="<?= $row['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">เลิกงาน</button>
                    </form>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="caddy_id" value="<?= $row['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-primary">ลงเวลาเข้างาน</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
        <tr><td colspan="4" class="text-muted">ไม่มีแคดดี้ในรายการนี้</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
