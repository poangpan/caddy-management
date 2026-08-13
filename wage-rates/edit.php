<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$pageTitle = 'อัตราค่าจ้าง';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rate9 = $_POST['rate_9'] ?? '';
    $rate18 = $_POST['rate_18'] ?? '';

    if (!is_numeric($rate9) || (float) $rate9 < 0) {
        $errors[] = 'กรุณากรอกอัตราค่าจ้าง 9 หลุมเป็นตัวเลขไม่ติดลบ';
    }
    if (!is_numeric($rate18) || (float) $rate18 < 0) {
        $errors[] = 'กรุณากรอกอัตราค่าจ้าง 18 หลุมเป็นตัวเลขไม่ติดลบ';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE wage_rates SET rate = ? WHERE holes = ?');
        $stmt->execute([number_format((float) $rate9, 2, '.', ''), '9']);
        $stmt->execute([number_format((float) $rate18, 2, '.', ''), '18']);
        setFlash('success', 'บันทึกอัตราค่าจ้างเรียบร้อย — มีผลกับรอบที่มอบหมายตั้งแต่นี้ไป ไม่กระทบรอบที่ผ่านมาแล้ว');
        header('Location: ' . BASE_URL . '/wage-rates/edit.php');
        exit;
    }
}

$rates = $pdo->query('SELECT holes, rate FROM wage_rates')->fetchAll(PDO::FETCH_KEY_PAIR);

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>อัตราค่าจ้างต่อจำนวนหลุม</h1>
</div>

<div class="card" style="max-width:500px;">
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <div class="form-row">
            <div class="form-group">
                <label for="rate_9">ค่าจ้าง 9 หลุม (บาท)</label>
                <input type="number" id="rate_9" name="rate_9" step="0.01" min="0" value="<?= e($rates['9'] ?? '0.00') ?>" required>
            </div>
            <div class="form-group">
                <label for="rate_18">ค่าจ้าง 18 หลุม (บาท)</label>
                <input type="number" id="rate_18" name="rate_18" step="0.01" min="0" value="<?= e($rates['18'] ?? '0.00') ?>" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">บันทึก</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
