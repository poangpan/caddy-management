<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$pageTitle = 'สรุปรายการแคดดี้';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$caddy = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM caddies WHERE id = ?');
    $stmt->execute([$id]);
    $caddy = $stmt->fetch();
}

$caddyList = $pdo->query('SELECT id, full_name FROM caddies ORDER BY full_name')->fetchAll();

if ($caddy) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS round_count, COALESCE(SUM(wage_amount), 0) AS total_wage
         FROM rounds WHERE caddy_id = ? AND status != 'scheduled'"
    );
    $stmt->execute([$id]);
    $stats = $stmt->fetch();

    $stmt = $pdo->prepare(
        "SELECT lr.start_date, lr.end_date, lr.note, lr.status, lt.name AS type_name
         FROM leave_requests lr
         JOIN leave_types lt ON lt.id = lr.leave_type_id
         WHERE lr.caddy_id = ?
         ORDER BY lr.start_date DESC"
    );
    $stmt->execute([$id]);
    $leaveHistory = $stmt->fetchAll();
}

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header no-print">
    <h1>สรุปรายการแคดดี้</h1>
    <a href="<?= BASE_URL ?>/caddies/list.php" class="btn btn-secondary">กลับไปทะเบียนแคดดี้</a>
</div>

<div class="card no-print" style="max-width:500px;">
    <form method="get">
        <div class="form-group">
            <label for="id">เลือกแคดดี้</label>
            <select id="id" name="id" onchange="this.form.submit()">
                <option value="">-- เลือกแคดดี้ --</option>
                <?php foreach ($caddyList as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $id === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($caddy): ?>
<div class="card">
    <div class="page-header no-print">
        <h3><?= e($caddy['full_name']) ?></h3>
        <button type="button" class="btn btn-secondary" onclick="window.print()">พิมพ์</button>
    </div>
    <h2 class="print-only" style="display:none;"><?= e($caddy['full_name']) ?></h2>

    <table class="meta-table">
        <tr>
            <td width="50%"><strong>เบอร์โทร:</strong> <?= e($caddy['phone']) ?: '-' ?></td>
            <td width="50%"><strong>วันที่เริ่มงาน:</strong> <?= e($caddy['start_date']) ?: '-' ?></td>
        </tr>
        <tr>
            <td><strong>สถานะ:</strong> <?= $caddy['is_active'] ? 'ทำงานอยู่' : 'พ้นสภาพ' ?></td>
            <td></td>
        </tr>
    </table>
</div>

<div class="card">
    <h3>สรุปยอด</h3>
    <table>
        <tr>
            <th>จำนวนรอบที่ออกทั้งหมด</th>
            <th>ยอดค่าจ้างสะสมทั้งหมด</th>
        </tr>
        <tr>
            <td class="font-mono"><?= (int) $stats['round_count'] ?></td>
            <td class="font-mono"><?= e(number_format((float) $stats['total_wage'], 2)) ?></td>
        </tr>
    </table>
</div>

<div class="card">
    <h3>ประวัติการลา</h3>
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
        <tr><td colspan="4" class="text-muted">ไม่มีประวัติการลา</td></tr>
        <?php endif; ?>
    </table>
</div>
<?php elseif ($id): ?>
<div class="alert alert-error">ไม่พบแคดดี้นี้</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
