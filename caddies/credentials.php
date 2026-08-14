<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id, full_name FROM caddies WHERE id = ?');
$stmt->execute([$id]);
$caddy = $stmt->fetch();

if (!$caddy) {
    setFlash('error', 'ไม่พบแคดดี้นี้');
    header('Location: ' . BASE_URL . '/caddies/list.php');
    exit;
}

$pageTitle = 'บัญชีพอร์ทัลแคดดี้';
$errors = [];

$stmt = $pdo->prepare('SELECT * FROM caddy_accounts WHERE caddy_id = ?');
$stmt->execute([$id]);
$account = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($email === '') {
        $errors[] = 'กรุณากรอกอีเมล';
    }
    if (!$account && $password === '') {
        $errors[] = 'กรุณากำหนดรหัสผ่านสำหรับบัญชีใหม่';
    }

    if (empty($errors)) {
        try {
            if ($account) {
                if ($password !== '') {
                    $pdo->prepare('UPDATE caddy_accounts SET email = ?, password = ?, is_active = ? WHERE caddy_id = ?')
                        ->execute([$email, password_hash($password, PASSWORD_DEFAULT), $isActive, $id]);
                } else {
                    $pdo->prepare('UPDATE caddy_accounts SET email = ?, is_active = ? WHERE caddy_id = ?')
                        ->execute([$email, $isActive, $id]);
                }
                setFlash('success', 'บันทึกบัญชีพอร์ทัลแคดดี้เรียบร้อย');
            } else {
                $pdo->prepare('INSERT INTO caddy_accounts (caddy_id, email, password, is_active) VALUES (?, ?, ?, 1)')
                    ->execute([$id, $email, password_hash($password, PASSWORD_DEFAULT)]);
                setFlash('success', 'ออกบัญชีพอร์ทัลแคดดี้เรียบร้อย — แจ้งอีเมลและรหัสผ่านนี้ให้แคดดี้ทราบ');
            }
            header('Location: ' . BASE_URL . '/caddies/credentials.php?id=' . $id);
            exit;
        } catch (PDOException $e) {
            $errors[] = $e->getCode() === '23000' ? 'อีเมลนี้ถูกใช้งานแล้ว' : 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }

    if ($account) {
        $account['email'] = $email;
        $account['is_active'] = $isActive;
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>บัญชีพอร์ทัลแคดดี้: <?= e($caddy['full_name']) ?></h1>
    <a href="<?= BASE_URL ?>/caddies/list.php?id=<?= $caddy['id'] ?>" class="btn btn-secondary">กลับไปทะเบียนแคดดี้</a>
</div>

<div class="card" style="max-width:500px;">
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <?php if (!$account): ?>
        <p class="text-muted">แคดดี้คนนี้ยังไม่มีบัญชีเข้าใช้งานพอร์ทัล กำหนดอีเมลและรหัสผ่านเพื่อออกบัญชีใหม่</p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="form-group">
            <label for="email">อีเมล *</label>
            <input type="email" id="email" name="email" value="<?= e($account['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="password"><?= $account ? 'รหัสผ่านใหม่ (เว้นว่างถ้าไม่เปลี่ยน)' : 'รหัสผ่าน *' ?></label>
            <input type="password" id="password" name="password" style="max-width:320px;">
        </div>
        <?php if ($account): ?>
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?= $account['is_active'] ? 'checked' : '' ?> style="width:auto;">
                เปิดใช้งานบัญชีนี้
            </label>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary"><?= $account ? 'บันทึก' : 'ออกบัญชี' ?></button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
