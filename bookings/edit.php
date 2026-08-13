<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$pageTitle = 'แก้ไขการจองล่วงหน้า';
$errors = [];

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM rounds WHERE id = ? AND status = 'scheduled'");
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('error', 'ไม่พบรายการจองนี้ หรือถูกยกเลิก/ดำเนินการไปแล้ว');
    header('Location: ' . BASE_URL . '/bookings/create.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $holes = $_POST['holes'] ?? '';
    $scheduledAtRaw = $_POST['scheduled_at'] ?? '';
    $caddyId = (int) ($_POST['caddy_id'] ?? 0) ?: null;

    $validation = validateBookingInput($pdo, $customerName, $holes, $scheduledAtRaw, $caddyId);
    $errors = $validation['errors'];
    $scheduledAt = $validation['scheduled_at'];

    $booking['customer_name'] = $customerName;
    $booking['holes'] = $holes;
    $booking['scheduled_at'] = $scheduledAtRaw;
    $booking['caddy_id'] = $caddyId;

    if (empty($errors)) {
        $pdo->prepare(
            "UPDATE rounds SET caddy_id = ?, holes = ?, customer_name = ?, caddy_requested = ?, scheduled_at = ?
             WHERE id = ? AND status = 'scheduled'"
        )->execute([$caddyId, $holes, $customerName, $caddyId ? 1 : 0, $scheduledAt, $id]);
        setFlash('success', 'บันทึกการแก้ไขการจองล่วงหน้าเรียบร้อย');
        header('Location: ' . BASE_URL . '/bookings/create.php');
        exit;
    }
} else {
    $booking['scheduled_at'] = str_replace(' ', 'T', substr($booking['scheduled_at'], 0, 16));
}

$caddies = $pdo->query('SELECT id, full_name FROM caddies WHERE is_active = 1 ORDER BY full_name')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>แก้ไขการจองล่วงหน้า</h1>
    <a href="<?= BASE_URL ?>/bookings/create.php" class="btn btn-secondary">กลับไปหน้าจอง</a>
</div>

<div class="card" style="max-width:600px;">
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="customer_name">ชื่อลูกค้า *</label>
                <input type="text" id="customer_name" name="customer_name" value="<?= e($booking['customer_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="holes">จำนวนหลุม *</label>
                <select id="holes" name="holes" required>
                    <option value="9" <?= $booking['holes'] === '9' ? 'selected' : '' ?>>9 หลุม</option>
                    <option value="18" <?= $booking['holes'] === '18' ? 'selected' : '' ?>>18 หลุม</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="scheduled_at">วันที่และเวลานัด *</label>
            <input type="datetime-local" id="scheduled_at" name="scheduled_at" value="<?= e($booking['scheduled_at']) ?>" required>
        </div>
        <div class="form-group">
            <label for="caddy_id">แคดดี้ที่ต้องการ (ถ้ามี)</label>
            <select id="caddy_id" name="caddy_id">
                <option value="">-- ไม่ระบุแคดดี้ --</option>
                <?php foreach ($caddies as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int) $booking['caddy_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
        <a href="<?= BASE_URL ?>/bookings/create.php" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
