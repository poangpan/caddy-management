<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$pageTitle = 'จองแคดดี้ล่วงหน้า';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $holes = $_POST['holes'] ?? '';
    $scheduledAtRaw = $_POST['scheduled_at'] ?? '';
    $caddyId = (int) ($_POST['caddy_id'] ?? 0) ?: null;

    if ($customerName === '') {
        $errors[] = 'กรุณากรอกชื่อลูกค้า';
    }
    if (!in_array($holes, ['9', '18'], true)) {
        $errors[] = 'กรุณาระบุจำนวนหลุม';
    }
    $scheduledAt = str_replace('T', ' ', $scheduledAtRaw);
    if ($scheduledAtRaw === '' || strtotime($scheduledAt) === false) {
        $errors[] = 'กรุณาระบุวันที่และเวลานัด';
    } elseif (strtotime($scheduledAt) <= time()) {
        $errors[] = 'เวลานัดต้องอยู่ในอนาคต';
    }

    if (empty($errors)) {
        $pdo->prepare(
            "INSERT INTO rounds (caddy_id, holes, customer_name, caddy_requested, status, scheduled_at)
             VALUES (?, ?, ?, ?, 'scheduled', ?)"
        )->execute([$caddyId, $holes, $customerName, $caddyId ? 1 : 0, $scheduledAt]);
        setFlash('success', 'บันทึกการจองล่วงหน้าเรียบร้อย');
        header('Location: ' . BASE_URL . '/bookings/create.php');
        exit;
    }
}

$caddies = $pdo->query('SELECT id, full_name FROM caddies WHERE is_active = 1 ORDER BY full_name')->fetchAll();

$leadMinutes = getAdvanceBookingLeadMinutes($pdo);

$upcoming = $pdo->query(
    "SELECT r.scheduled_at, r.customer_name, r.holes, c.full_name
     FROM rounds r
     LEFT JOIN caddies c ON c.id = r.caddy_id
     WHERE r.status = 'scheduled' AND r.scheduled_at >= NOW()
     ORDER BY r.scheduled_at ASC"
)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>จองแคดดี้ล่วงหน้า</h1>
    <?php if (isAdmin()): ?>
        <a href="<?= BASE_URL ?>/bookings/settings.php" class="btn btn-secondary">ตั้งค่าระยะเวลาป้องกันคิว</a>
    <?php endif; ?>
</div>

<div class="card" style="max-width:600px;">
    <p class="text-muted">แคดดี้ที่ถูกจองไว้จะถูกตัดออกจากคิว FIFO อัตโนมัติ <?= (int) $leadMinutes ?> นาทีก่อนถึงเวลานัด</p>
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
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
            <label for="scheduled_at">วันที่และเวลานัด *</label>
            <input type="datetime-local" id="scheduled_at" name="scheduled_at" value="<?= e($_POST['scheduled_at'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="caddy_id">แคดดี้ที่ต้องการ (ถ้ามี)</label>
            <select id="caddy_id" name="caddy_id">
                <option value="">-- ไม่ระบุแคดดี้ --</option>
                <?php foreach ($caddies as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int) ($_POST['caddy_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">บันทึกการจอง</button>
    </form>
</div>

<div class="card">
    <h3>รายการจองล่วงหน้าที่กำลังจะถึง</h3>
    <table>
        <tr>
            <th>วันเวลานัด</th>
            <th>ลูกค้า</th>
            <th>หลุม</th>
            <th>แคดดี้</th>
        </tr>
        <?php foreach ($upcoming as $b): ?>
        <tr>
            <td class="font-mono"><?= e($b['scheduled_at']) ?></td>
            <td><?= e($b['customer_name']) ?></td>
            <td><?= e($b['holes']) ?></td>
            <td><?= $b['full_name'] ? e($b['full_name']) : '-- ไม่ระบุ --' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
