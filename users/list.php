<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$pageTitle = 'จัดการผู้ใช้งาน';
$users = $pdo->query('SELECT * FROM users ORDER BY full_name')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>จัดการผู้ใช้งาน</h1>
    <a href="<?= BASE_URL ?>/users/form.php" class="btn btn-primary">+ เพิ่มผู้ใช้งาน</a>
</div>

<div class="two-col">
    <div class="card">
        <table>
            <tr>
                <th></th>
                <th>ชื่อ-นามสกุล</th>
                <th>อีเมล</th>
                <th>สิทธิ์</th>
                <th>สถานะ</th>
                <th>ใช้งานล่าสุด</th>
                <th></th>
            </tr>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <?php if ($u['photo_path']): ?>
                        <img class="avatar avatar-sm" src="<?= BASE_URL ?>/<?= e($u['photo_path']) ?>" alt="">
                    <?php else: ?>
                        <div class="avatar avatar-sm avatar-placeholder"><?= e(mb_substr($u['full_name'], 0, 1)) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= e($u['full_name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><span class="badge badge-info"><?= e(roleLabel($u['role'])) ?></span></td>
                <td>
                    <?php if ($u['is_active']): ?>
                        <span class="badge badge-success">ใช้งาน</span>
                    <?php else: ?>
                        <span class="badge badge-danger">ระงับ</span>
                    <?php endif; ?>
                </td>
                <td class="font-mono text-muted"><?= $u['last_login_at'] ? e($u['last_login_at']) : 'ยังไม่เคยเข้าใช้งาน' ?></td>
                <td><a href="<?= BASE_URL ?>/users/form.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-secondary">แก้ไข</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <h3>สิทธิ์การเข้าถึงตามบทบาท</h3>

        <div class="permission-role">
            <div class="permission-role-title"><span class="role-dot role-dot-queue_hr"></span>พนักงานคุมคิว/HR</div>
            <ul class="permission-list">
                <li class="is-allowed">คิวแคดดี้ ทะเบียนแคดดี้ และการลา</li>
                <li class="is-allowed">จองแคดดี้ล่วงหน้าและมอบหมายออกรอบ</li>
                <li class="is-denied">ไม่มีสิทธิ์เข้าถึงค่าจ้าง/ปิดยอด</li>
            </ul>
        </div>

        <div class="permission-role">
            <div class="permission-role-title"><span class="role-dot role-dot-accounting"></span>ฝ่ายบัญชี</div>
            <ul class="permission-list">
                <li class="is-allowed">ปิดยอดค่าจ้างรายสัปดาห์และดูสรุปยอด</li>
                <li class="is-denied">ไม่มีสิทธิ์จัดการคิว/ทะเบียนแคดดี้/การลา</li>
            </ul>
        </div>

        <div class="permission-role">
            <div class="permission-role-title"><span class="role-dot role-dot-admin"></span>ผู้ดูแลระบบ</div>
            <ul class="permission-list">
                <li class="is-allowed">เข้าถึงได้ทุกส่วนของระบบ</li>
                <li class="is-allowed">จัดการผู้ใช้งานและอัตราค่าจ้าง</li>
            </ul>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
