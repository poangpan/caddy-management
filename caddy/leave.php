<?php
require_once __DIR__ . '/../includes/caddy_auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireCaddyLogin();

$caddyId = currentCaddy()['caddy_id'];
$pageTitle = 'ขอลา';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveTypeId = (int) ($_POST['leave_type_id'] ?? 0);
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $note = trim($_POST['note'] ?? '');

    // caddy_id มาจาก session เท่านั้น ไม่รับจากฟอร์ม — ป้องกันการขอลาแทนแคดดี้คนอื่น
    $errors = validateLeaveInput($caddyId, $leaveTypeId, $startDate, $endDate);

    if (empty($errors)) {
        $pdo->prepare("INSERT INTO leave_requests (caddy_id, leave_type_id, start_date, end_date, note, status) VALUES (?, ?, ?, ?, ?, 'pending')")
            ->execute([$caddyId, $leaveTypeId, $startDate, $endDate, $note !== '' ? $note : null]);
        setFlash('success', 'ส่งคำขอลาเรียบร้อย รอการอนุมัติจากพนักงาน');
        header('Location: ' . BASE_URL . '/caddy/leave.php');
        exit;
    }
}

$leaveTypes = $pdo->query('SELECT id, name FROM leave_types ORDER BY id')->fetchAll();

$stmt = $pdo->prepare(
    "SELECT lr.start_date, lr.end_date, lr.note, lr.status, lt.name AS type_name
     FROM leave_requests lr
     JOIN leave_types lt ON lt.id = lr.leave_type_id
     WHERE lr.caddy_id = ?
     ORDER BY lr.start_date DESC"
);
$stmt->execute([$caddyId]);
$leaveHistory = $stmt->fetchAll();

require __DIR__ . '/../includes/caddy_header.php';
?>
<div class="page-header">
    <h1>ขอลา</h1>
</div>

<div class="card" style="max-width:600px;">
    <h3>ส่งคำขอลาใหม่</h3>
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
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
        <button type="submit" class="btn btn-primary">ส่งคำขอลา</button>
    </form>
</div>

<div class="card">
    <h3>ประวัติคำขอลาของคุณ</h3>
    <table>
        <tr>
            <th>ประเภท</th>
            <th>ช่วงวันที่ลา</th>
            <th>หมายเหตุ</th>
            <th>สถานะ</th>
        </tr>
        <?php foreach ($leaveHistory as $l): ?>
        <tr>
            <td><?= e($l['type_name']) ?></td>
            <td class="font-mono"><?= e($l['start_date']) ?> — <?= e($l['end_date']) ?></td>
            <td><?= e($l['note']) ?></td>
            <td><span class="badge <?= leaveStatusBadgeClass($l['status']) ?>"><?= e(leaveStatusLabel($l['status'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$leaveHistory): ?>
        <tr><td colspan="4" class="text-muted">ยังไม่มีคำขอลา</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
