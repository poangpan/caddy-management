<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

$caddy = ['full_name' => '', 'phone' => '', 'national_id' => '', 'bank_account_number' => '', 'start_date' => '', 'is_active' => 1, 'photo_path' => null];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM caddies WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        setFlash('error', 'ไม่พบแคดดี้นี้');
        header('Location: ' . BASE_URL . '/caddies/list.php');
        exit;
    }
    $caddy = $found;
}

$pageTitle = $id ? 'แก้ไขข้อมูลแคดดี้' : 'เพิ่มแคดดี้';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caddy['full_name'] = trim($_POST['full_name'] ?? '');
    $caddy['phone'] = trim($_POST['phone'] ?? '');
    $caddy['national_id'] = trim($_POST['national_id'] ?? '');
    $caddy['bank_account_number'] = trim($_POST['bank_account_number'] ?? '');
    $caddy['start_date'] = $_POST['start_date'] ?? '' ?: null;
    // สถานะทำงาน/พ้นสภาพ ปรับได้เฉพาะผู้ดูแลระบบ; แคดดี้ใหม่เริ่มเป็นสถานะทำงานอยู่เสมอ
    $caddy['is_active'] = isAdmin() ? (isset($_POST['is_active']) ? 1 : 0) : 1;

    if ($caddy['full_name'] === '') {
        $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
    }

    if (empty($errors)) {
        try {
            $newPhoto = handlePhotoUpload($_FILES, 'photo', 'caddies', $caddy['photo_path']);
            if ($newPhoto !== null) {
                $caddy['photo_path'] = $newPhoto;
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE caddies SET full_name = ?, photo_path = ?, phone = ?, national_id = ?, bank_account_number = ?, start_date = ?, is_active = ? WHERE id = ?');
            $stmt->execute([$caddy['full_name'], $caddy['photo_path'], $caddy['phone'], $caddy['national_id'], $caddy['bank_account_number'], $caddy['start_date'], $caddy['is_active'], $id]);
            setFlash('success', 'บันทึกการแก้ไขข้อมูลแคดดี้เรียบร้อย');
        } else {
            $stmt = $pdo->prepare('INSERT INTO caddies (full_name, photo_path, phone, national_id, bank_account_number, start_date, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
            $stmt->execute([$caddy['full_name'], $caddy['photo_path'], $caddy['phone'], $caddy['national_id'], $caddy['bank_account_number'], $caddy['start_date']]);
            setFlash('success', 'เพิ่มแคดดี้เรียบร้อย');
        }
        header('Location: ' . BASE_URL . '/caddies/list.php');
        exit;
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1><?= e($pageTitle) ?></h1>
</div>

<div class="card" style="max-width:600px;">
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>รูปโปรไฟล์</label>
            <div style="display:flex; align-items:center; gap:14px;">
                <?php if ($caddy['photo_path']): ?>
                    <img class="avatar avatar-md" src="<?= BASE_URL ?>/<?= e($caddy['photo_path']) ?>" alt="">
                <?php else: ?>
                    <div class="avatar avatar-md avatar-placeholder"><?= e($caddy['full_name'] !== '' ? mb_substr($caddy['full_name'], 0, 1) : '?') ?></div>
                <?php endif; ?>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>
        <div class="form-group">
            <label for="full_name">ชื่อ-นามสกุล *</label>
            <input type="text" id="full_name" name="full_name" value="<?= e($caddy['full_name']) ?>" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="phone">เบอร์โทร</label>
                <input type="text" id="phone" name="phone" value="<?= e($caddy['phone']) ?>">
            </div>
            <div class="form-group">
                <label for="national_id">เลขบัตรประชาชน</label>
                <input type="text" id="national_id" name="national_id" value="<?= e($caddy['national_id']) ?>" maxlength="13">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="bank_account_number">เลขบัญชี SCB</label>
                <input type="text" id="bank_account_number" name="bank_account_number" value="<?= e($caddy['bank_account_number']) ?>">
            </div>
            <div class="form-group">
                <label for="start_date">วันที่เริ่มงาน</label>
                <input type="date" id="start_date" name="start_date" value="<?= e($caddy['start_date']) ?>">
            </div>
        </div>
        <?php if ($id): ?>
            <div class="form-group">
                <?php if (isAdmin()): ?>
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?= $caddy['is_active'] ? 'checked' : '' ?> style="width:auto;">
                        ยังทำงานอยู่ (ยกเลิกติ๊กถ้าลาออก/พ้นสภาพ)
                    </label>
                <?php else: ?>
                    <label>สถานะ</label>
                    <?php if ($caddy['is_active']): ?>
                        <span class="badge badge-success">ทำงานอยู่</span>
                    <?php else: ?>
                        <span class="badge badge-danger">พ้นสภาพ</span>
                    <?php endif; ?>
                    <div><small class="text-muted">เฉพาะผู้ดูแลระบบที่ปรับสถานะนี้ได้</small></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">บันทึก</button>
        <a href="<?= BASE_URL ?>/caddies/list.php" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
