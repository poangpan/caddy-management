<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$currentUserId = currentUser()['id'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isSelf = $id === $currentUserId;

$formUser = ['full_name' => '', 'email' => '', 'role' => 'queue_hr', 'is_active' => 1, 'photo_path' => null];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        setFlash('error', 'ไม่พบผู้ใช้งานนี้');
        header('Location: ' . BASE_URL . '/users/list.php');
        exit;
    }
    $formUser = $found;
}

$pageTitle = $id ? 'แก้ไขผู้ใช้งาน' : 'เพิ่มผู้ใช้งาน';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formUser['full_name'] = trim($_POST['full_name'] ?? '');
    $formUser['email'] = trim($_POST['email'] ?? '');
    $formUser['role'] = $isSelf ? $formUser['role'] : ($_POST['role'] ?? 'queue_hr');
    $formUser['is_active'] = $isSelf ? 1 : (isset($_POST['is_active']) ? 1 : 0);
    $password = $_POST['password'] ?? '';

    if ($formUser['full_name'] === '' || $formUser['email'] === '') {
        $errors[] = 'กรุณากรอกชื่อและอีเมล';
    }
    if (!$id && $password === '') {
        $errors[] = 'กรุณากำหนดรหัสผ่านสำหรับผู้ใช้ใหม่';
    }
    if (!in_array($formUser['role'], ['queue_hr', 'accounting', 'admin'], true)) {
        $errors[] = 'สิทธิ์ผู้ใช้ไม่ถูกต้อง';
    }

    if (empty($errors)) {
        try {
            $newPhoto = handlePhotoUpload($_FILES, 'photo', 'users', $formUser['photo_path']);
            if ($newPhoto !== null) {
                $formUser['photo_path'] = $newPhoto;
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            if ($id) {
                if ($password !== '') {
                    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, photo_path = ?, email = ?, role = ?, is_active = ?, password = ? WHERE id = ?');
                    $stmt->execute([$formUser['full_name'], $formUser['photo_path'], $formUser['email'], $formUser['role'], $formUser['is_active'], password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, photo_path = ?, email = ?, role = ?, is_active = ? WHERE id = ?');
                    $stmt->execute([$formUser['full_name'], $formUser['photo_path'], $formUser['email'], $formUser['role'], $formUser['is_active'], $id]);
                }
                setFlash('success', 'บันทึกการแก้ไขผู้ใช้งานเรียบร้อย');
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (full_name, photo_path, email, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$formUser['full_name'], $formUser['photo_path'], $formUser['email'], password_hash($password, PASSWORD_DEFAULT), $formUser['role'], $formUser['is_active']]);
                setFlash('success', 'เพิ่มผู้ใช้งานเรียบร้อย');
            }
            header('Location: ' . BASE_URL . '/users/list.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = $e->getCode() === '23000' ? 'อีเมลนี้ถูกใช้งานแล้ว' : 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
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
                <?php if ($formUser['photo_path']): ?>
                    <img class="avatar avatar-md" src="<?= BASE_URL ?>/<?= e($formUser['photo_path']) ?>" alt="">
                <?php else: ?>
                    <div class="avatar avatar-md avatar-placeholder"><?= e($formUser['full_name'] !== '' ? mb_substr($formUser['full_name'], 0, 1) : '?') ?></div>
                <?php endif; ?>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>
        <div class="form-group">
            <label for="full_name">ชื่อ-นามสกุล *</label>
            <input type="text" id="full_name" name="full_name" value="<?= e($formUser['full_name']) ?>" required>
        </div>
        <div class="form-group">
            <label for="email">อีเมล *</label>
            <input type="email" id="email" name="email" value="<?= e($formUser['email']) ?>" required>
        </div>
        <div class="form-group">
            <label for="password"><?= $id ? 'รหัสผ่านใหม่ (เว้นว่างถ้าไม่เปลี่ยน)' : 'รหัสผ่าน *' ?></label>
            <input type="password" id="password" name="password" style="max-width:320px;">
        </div>

        <div class="form-group">
            <label>สิทธิ์การใช้งาน</label>
            <?php if ($isSelf): ?><div><small class="text-muted">ไม่สามารถเปลี่ยนสิทธิ์ของตัวเองได้</small></div><?php endif; ?>
            <div class="role-options">
                <label class="role-option <?= $formUser['role'] === 'queue_hr' ? 'is-checked' : '' ?>">
                    <input type="radio" name="role" value="queue_hr" <?= $formUser['role'] === 'queue_hr' ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>
                    <div>
                        <strong>พนักงานคุมคิว/HR</strong>
                        <p>จัดการคิวแคดดี้รายวัน ทะเบียนแคดดี้ การลา และการจองล่วงหน้า</p>
                    </div>
                </label>
                <label class="role-option <?= $formUser['role'] === 'accounting' ? 'is-checked' : '' ?>">
                    <input type="radio" name="role" value="accounting" <?= $formUser['role'] === 'accounting' ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>
                    <div>
                        <strong>ฝ่ายบัญชี</strong>
                        <p>เข้าถึงการปิดยอดค่าจ้างและรายงานสรุป</p>
                    </div>
                </label>
                <label class="role-option <?= $formUser['role'] === 'admin' ? 'is-checked' : '' ?>">
                    <input type="radio" name="role" value="admin" <?= $formUser['role'] === 'admin' ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>
                    <div>
                        <strong>ผู้ดูแลระบบ</strong>
                        <p>เข้าถึงได้ทุกส่วน รวมถึงจัดการผู้ใช้งานและอัตราค่าจ้าง</p>
                    </div>
                </label>
            </div>
        </div>
        <?php if (!$isSelf): ?>
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?= $formUser['is_active'] ? 'checked' : '' ?> style="width:auto;">
                เปิดใช้งานบัญชีนี้
            </label>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">บันทึก</button>
        <a href="<?= BASE_URL ?>/users/list.php" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
