<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$pageTitle = 'การลา';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $pdo->prepare('DELETE FROM leave_requests WHERE id = ?')->execute([(int) $_POST['cancel_id']]);
    setFlash('success', 'ยกเลิกคำขอลาเรียบร้อย');
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

    if (empty($errors)) {
        $pdo->prepare('INSERT INTO leave_requests (caddy_id, leave_type_id, start_date, end_date, note) VALUES (?, ?, ?, ?, ?)')
            ->execute([$caddyId, $leaveTypeId, $startDate, $endDate, $note !== '' ? $note : null]);
        setFlash('success', 'บันทึกคำขอลาเรียบร้อย');
        header('Location: ' . BASE_URL . '/leave/index.php');
        exit;
    }
}

$caddies = $pdo->query('SELECT id, full_name FROM caddies WHERE is_active = 1 ORDER BY full_name')->fetchAll();
$leaveTypes = $pdo->query('SELECT id, name FROM leave_types ORDER BY id')->fetchAll();

$upcoming = $pdo->query(
    "SELECT lr.id, lr.start_date, lr.end_date, lr.note, c.full_name, lt.name AS type_name
     FROM leave_requests lr
     JOIN caddies c ON c.id = lr.caddy_id
     JOIN leave_types lt ON lt.id = lr.leave_type_id
     WHERE lr.end_date >= CURDATE()
     ORDER BY lr.start_date ASC"
)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>การลา</h1>
    <a href="<?= BASE_URL ?>/leave/report.php" class="btn btn-secondary">รายงานการลารายเดือน</a>
</div>

<div class="card" style="max-width:600px;">
    <h3>บันทึกคำขอลา</h3>
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <div class="form-group">
            <label for="caddy_id">แคดดี้ *</label>
            <select id="caddy_id" name="caddy_id" required>
                <option value="">-- เลือกแคดดี้ --</option>
                <?php foreach ($caddies as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int) ($_POST['caddy_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="leave_type_id">ประเภทการลา *</label>
            <select id="leave_type_id" name="leave_type_id" required>
                <option value="">-- เลือกประเภท --</option>
                <?php foreach ($leaveTypes as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= (int) ($_POST['leave_type_id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="start_date">วันที่เริ่มลา *</label>
                <input type="date" id="start_date" name="start_date" value="<?= e($_POST['start_date'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">วันที่สิ้นสุด *</label>
                <input type="date" id="end_date" name="end_date" value="<?= e($_POST['end_date'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="note">หมายเหตุ</label>
            <textarea id="note" name="note"><?= e($_POST['note'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">บันทึกคำขอลา</button>
    </form>
</div>

<div class="card">
    <h3>รายชื่อแคดดี้ที่แจ้งลาไว้ล่วงหน้า</h3>
    <table>
        <tr>
            <th>แคดดี้</th>
            <th>ประเภท</th>
            <th>ช่วงวันที่ลา</th>
            <th>หมายเหตุ</th>
            <th></th>
        </tr>
        <?php foreach ($upcoming as $u): ?>
        <tr>
            <td><?= e($u['full_name']) ?></td>
            <td><?= e($u['type_name']) ?></td>
            <td class="font-mono"><?= e($u['start_date']) ?> — <?= e($u['end_date']) ?></td>
            <td><?= e($u['note']) ?></td>
            <td>
                <a href="<?= BASE_URL ?>/leave/edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-secondary">แก้ไข</a>
                <form method="post" style="display:inline;" onsubmit="return confirm('ยืนยันยกเลิกคำขอลานี้?');">
                    <input type="hidden" name="cancel_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">ยกเลิก</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
