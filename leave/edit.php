<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$pageTitle = 'แก้ไขคำขอลา';
$errors = [];

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM leave_requests WHERE id = ?');
$stmt->execute([$id]);
$leave = $stmt->fetch();

if (!$leave) {
    setFlash('error', 'ไม่พบคำขอลานี้ หรือถูกยกเลิกไปแล้ว');
    header('Location: ' . BASE_URL . '/leave/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caddyId = (int) ($_POST['caddy_id'] ?? 0);
    $leaveTypeId = (int) ($_POST['leave_type_id'] ?? 0);
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $note = trim($_POST['note'] ?? '');

    $errors = validateLeaveInput($caddyId, $leaveTypeId, $startDate, $endDate);

    $leave['caddy_id'] = $caddyId;
    $leave['leave_type_id'] = $leaveTypeId;
    $leave['start_date'] = $startDate;
    $leave['end_date'] = $endDate;
    $leave['note'] = $note;

    if (empty($errors)) {
        $pdo->prepare(
            'UPDATE leave_requests SET caddy_id = ?, leave_type_id = ?, start_date = ?, end_date = ?, note = ? WHERE id = ?'
        )->execute([$caddyId, $leaveTypeId, $startDate, $endDate, $note !== '' ? $note : null, $id]);
        setFlash('success', 'บันทึกการแก้ไขคำขอลาเรียบร้อย');
        header('Location: ' . BASE_URL . '/leave/index.php');
        exit;
    }
}

$caddies = $pdo->query('SELECT id, full_name FROM caddies WHERE is_active = 1 ORDER BY full_name')->fetchAll();
$leaveTypes = $pdo->query('SELECT id, name FROM leave_types ORDER BY id')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>แก้ไขคำขอลา</h1>
    <a href="<?= BASE_URL ?>/leave/index.php" class="btn btn-secondary">กลับไปหน้าการลา</a>
</div>

<div class="card" style="max-width:600px;">
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="form-group">
            <label for="caddy_id">แคดดี้ *</label>
            <select id="caddy_id" name="caddy_id" required>
                <option value="">-- เลือกแคดดี้ --</option>
                <?php foreach ($caddies as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int) $leave['caddy_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="leave_type_id">ประเภทการลา *</label>
            <select id="leave_type_id" name="leave_type_id" required>
                <option value="">-- เลือกประเภท --</option>
                <?php foreach ($leaveTypes as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= (int) $leave['leave_type_id'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="start_date">วันที่เริ่มลา *</label>
                <input type="date" id="start_date" name="start_date" value="<?= e($leave['start_date']) ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">วันที่สิ้นสุด *</label>
                <input type="date" id="end_date" name="end_date" value="<?= e($leave['end_date']) ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="note">หมายเหตุ</label>
            <textarea id="note" name="note"><?= e($leave['note']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
        <a href="<?= BASE_URL ?>/leave/index.php" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
