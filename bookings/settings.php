<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$pageTitle = 'ตั้งค่าการจองล่วงหน้า';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leadMinutes = $_POST['lead_minutes'] ?? '';

    if (!ctype_digit((string) $leadMinutes) || (int) $leadMinutes < 1) {
        $errors[] = 'กรุณากรอกจำนวนนาทีเป็นเลขจำนวนเต็มบวก';
    }

    if (empty($errors)) {
        $pdo->prepare('UPDATE advance_booking_settings SET lead_minutes = ? WHERE id = 1')
            ->execute([(int) $leadMinutes]);
        setFlash('success', 'บันทึกค่าตั้งเรียบร้อย');
        header('Location: ' . BASE_URL . '/bookings/settings.php');
        exit;
    }
}

$leadMinutes = getAdvanceBookingLeadMinutes($pdo);

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>ตั้งค่าการจองล่วงหน้า</h1>
    <a href="<?= BASE_URL ?>/bookings/create.php" class="btn btn-secondary">กลับไปหน้าจอง</a>
</div>

<div class="card" style="max-width:500px;">
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <div class="form-group">
            <label for="lead_minutes">ตัดแคดดี้ออกจากคิว FIFO ก่อนถึงเวลานัด (นาที)</label>
            <input type="number" id="lead_minutes" name="lead_minutes" min="1" step="1" value="<?= e((string) $leadMinutes) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">บันทึก</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
