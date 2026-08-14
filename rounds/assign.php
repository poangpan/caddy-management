<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/wage.php';
requireRole(['queue_hr', 'admin']);

$pageTitle = 'มอบหมายออกรอบ';
$errors = [];
$leadMinutes = getAdvanceBookingLeadMinutes($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $holes = $_POST['holes'] ?? '';
    $mode = $_POST['mode'] ?? 'queue';
    $requestedCaddyId = (int) ($_POST['caddy_id'] ?? 0);
    $cartNumber = trim($_POST['cart_number'] ?? '');

    if ($customerName === '') {
        $errors[] = 'กรุณากรอกชื่อลูกค้า';
    }
    if (!in_array($holes, ['9', '18'], true)) {
        $errors[] = 'กรุณาระบุจำนวนหลุม';
    }

    $caddyId = null;
    $caddyRequested = 0;
    $notice = null;

    if (empty($errors)) {
        if ($mode === 'specific') {
            if (!$requestedCaddyId) {
                $errors[] = 'กรุณาเลือกแคดดี้ที่ต้องการระบุ';
            } else {
                $caddyId = $requestedCaddyId;
                $caddyRequested = 1;
                $stmt = $pdo->prepare(
                    "SELECT
                        CASE
                            WHEN lr.caddy_id IS NOT NULL AND (cqs.status IS NULL OR cqs.status != 'ready') THEN 'leave'
                            ELSE cqs.status
                        END AS status,
                        ab.scheduled_at AS booking_scheduled_at
                     FROM caddies c
                     LEFT JOIN caddy_queue_status cqs ON cqs.caddy_id = c.id
                     LEFT JOIN (
                         SELECT DISTINCT caddy_id FROM leave_requests WHERE status = 'approved' AND CURDATE() BETWEEN start_date AND end_date
                     ) lr ON lr.caddy_id = c.id
                     LEFT JOIN (
                         SELECT caddy_id, MIN(scheduled_at) AS scheduled_at
                         FROM rounds
                         WHERE status = 'scheduled' AND caddy_id IS NOT NULL
                           AND NOW() BETWEEN DATE_SUB(scheduled_at, INTERVAL ? MINUTE) AND scheduled_at
                         GROUP BY caddy_id
                     ) ab ON ab.caddy_id = c.id
                     WHERE c.id = ?"
                );
                $stmt->execute([$leadMinutes, $caddyId]);
                $row = $stmt->fetch();
                $status = $row['status'] ?: null;
                if ($row['booking_scheduled_at']) {
                    $notice = 'หมายเหตุ: แคดดี้ที่ระบุมีการจองล่วงหน้าไว้ในเวลาใกล้เคียง (นัด ' . $row['booking_scheduled_at'] . ') — มอบหมายให้ตามที่ระบุ เนื่องจากเป็นการตัดสินใจของพนักงานหน้างาน';
                } elseif ($status !== 'ready') {
                    $notice = 'หมายเหตุ: แคดดี้ที่ระบุไม่อยู่ในสถานะพร้อม (สถานะปัจจุบัน: ' . queueStatusLabel($status) . ') — มอบหมายให้ตามที่ระบุ เนื่องจากเป็นการตัดสินใจของพนักงานหน้างาน';
                }
            }
        } else {
            $stmt = $pdo->prepare(
                "SELECT c.id FROM caddies c
                 JOIN caddy_queue_status cqs ON cqs.caddy_id = c.id
                 WHERE c.is_active = 1 AND cqs.status = 'ready'
                   AND c.id NOT IN (
                       SELECT caddy_id FROM rounds
                       WHERE status = 'scheduled' AND caddy_id IS NOT NULL
                         AND NOW() BETWEEN DATE_SUB(scheduled_at, INTERVAL ? MINUTE) AND scheduled_at
                   )
                 ORDER BY cqs.last_ready_at ASC LIMIT 1"
            );
            $stmt->execute([$leadMinutes]);
            $next = $stmt->fetch();
            if (!$next) {
                $errors[] = 'ไม่มีแคดดี้พร้อมอยู่ในคิว';
            } else {
                $caddyId = (int) $next['id'];
            }
        }
    }

    if (empty($errors) && $caddyId) {
        $wageAmount = calculateRoundWage($pdo, $holes);
        $pdo->prepare('INSERT INTO rounds (caddy_id, holes, customer_name, caddy_requested, wage_amount, cart_number) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$caddyId, $holes, $customerName, $caddyRequested, $wageAmount, $cartNumber ?: null]);

        $pdo->prepare(
            "INSERT INTO caddy_queue_status (caddy_id, status) VALUES (?, 'on_round')
             ON DUPLICATE KEY UPDATE status = 'on_round'"
        )->execute([$caddyId]);

        setFlash($notice ? 'warning' : 'success', $notice ? ('มอบหมายออกรอบเรียบร้อย — ' . $notice) : 'มอบหมายออกรอบเรียบร้อย');
        header('Location: ' . BASE_URL . '/rounds/assign.php');
        exit;
    }
}

$stmt = $pdo->prepare(
    "SELECT c.id, c.full_name,
            CASE
                WHEN lr.caddy_id IS NOT NULL AND (cqs.status IS NULL OR cqs.status != 'ready') THEN 'leave'
                ELSE cqs.status
            END AS status,
            ab.scheduled_at AS booking_scheduled_at
     FROM caddies c
     LEFT JOIN caddy_queue_status cqs ON cqs.caddy_id = c.id
     LEFT JOIN (
         SELECT DISTINCT caddy_id FROM leave_requests WHERE status = 'approved' AND CURDATE() BETWEEN start_date AND end_date
     ) lr ON lr.caddy_id = c.id
     LEFT JOIN (
         SELECT caddy_id, MIN(scheduled_at) AS scheduled_at
         FROM rounds
         WHERE status = 'scheduled' AND caddy_id IS NOT NULL
           AND NOW() BETWEEN DATE_SUB(scheduled_at, INTERVAL ? MINUTE) AND scheduled_at
         GROUP BY caddy_id
     ) ab ON ab.caddy_id = c.id
     WHERE c.is_active = 1
     ORDER BY c.full_name"
);
$stmt->execute([$leadMinutes]);
$caddyOptions = $stmt->fetchAll();

$recentRounds = $pdo->query(
    "SELECT r.holes, r.customer_name, r.caddy_requested, r.wage_amount, r.cart_number, r.assigned_at, c.full_name
     FROM rounds r JOIN caddies c ON c.id = r.caddy_id
     WHERE r.status != 'scheduled'
     ORDER BY r.assigned_at DESC LIMIT 10"
)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>มอบหมายออกรอบ</h1>
    <a href="<?= BASE_URL ?>/queue/board.php" class="btn btn-secondary">กลับไปหน้าคิว</a>
</div>

<div class="card" style="max-width:600px;">
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" id="assignForm">
        <div class="form-row">
            <div class="form-group">
                <label for="customer_name">ชื่อลูกค้า *</label>
                <input type="text" id="customer_name" name="customer_name" value="<?= e($_POST['customer_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="holes">จำนวนหลุม *</label>
                <select id="holes" name="holes" required>
                    <option value="9" <?= ($_POST['holes'] ?? '') === '9' ? 'selected' : '' ?>>9 หลุม</option>
                    <option value="18" <?= ($_POST['holes'] ?? '18') === '18' ? 'selected' : '' ?>>18 หลุม</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>
                <input type="radio" name="mode" value="queue" id="modeQueue" style="width:auto;" <?= ($_POST['mode'] ?? 'queue') === 'queue' ? 'checked' : '' ?>>
                มอบหมายแคดดี้ถัดไปในคิว (ตามลำดับ FIFO)
            </label><br>
            <label>
                <input type="radio" name="mode" value="specific" id="modeSpecific" style="width:auto;" <?= ($_POST['mode'] ?? '') === 'specific' ? 'checked' : '' ?>>
                ลูกค้าขอแคดดี้เป็นการเฉพาะ (caddy request)
            </label>
        </div>
        <div class="form-group" id="caddyPicker">
            <label for="caddy_id">เลือกแคดดี้</label>
            <select id="caddy_id" name="caddy_id">
                <option value="">-- เลือกแคดดี้ --</option>
                <?php foreach ($caddyOptions as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int) ($_POST['caddy_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['full_name']) ?> (<?= e(queueStatusLabel($c['status'])) ?><?= $c['booking_scheduled_at'] ? ', จองไว้ ' . e($c['booking_scheduled_at']) : '' ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="cart_number">เลขรถกอล์ฟ</label>
            <input type="text" id="cart_number" name="cart_number" value="<?= e($_POST['cart_number'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary">มอบหมายออกรอบ</button>
    </form>
</div>

<script>
(function () {
    var queueRadio = document.getElementById('modeQueue');
    var specificRadio = document.getElementById('modeSpecific');
    var picker = document.getElementById('caddyPicker');
    function sync() {
        picker.style.display = specificRadio.checked ? '' : 'none';
    }
    queueRadio.addEventListener('change', sync);
    specificRadio.addEventListener('change', sync);
    sync();
})();
</script>

<div class="card">
    <h3>รอบที่มอบหมายล่าสุด</h3>
    <table>
        <tr>
            <th>เวลา</th>
            <th>แคดดี้</th>
            <th>ลูกค้า</th>
            <th>หลุม</th>
            <th>Caddy request</th>
            <th>เลขรถกอล์ฟ</th>
            <th>ค่าจ้าง</th>
        </tr>
        <?php foreach ($recentRounds as $r): ?>
        <tr>
            <td class="font-mono text-muted"><?= e($r['assigned_at']) ?></td>
            <td><?= e($r['full_name']) ?></td>
            <td><?= e($r['customer_name']) ?></td>
            <td><?= e($r['holes']) ?></td>
            <td><?= $r['caddy_requested'] ? 'ใช่' : '-' ?></td>
            <td class="font-mono"><?= e($r['cart_number']) ?: '-' ?></td>
            <td class="font-mono"><?= e($r['wage_amount']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
