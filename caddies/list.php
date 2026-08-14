<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$pageTitle = 'ทะเบียนแคดดี้';

$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'active', 'inactive'], true)) {
    $filter = 'all';
}

$sql = 'SELECT * FROM caddies';
if ($filter === 'active') {
    $sql .= ' WHERE is_active = 1';
} elseif ($filter === 'inactive') {
    $sql .= ' WHERE is_active = 0';
}
$sql .= ' ORDER BY full_name';
$caddies = $pdo->query($sql)->fetchAll();

$totalCount = (int) $pdo->query('SELECT COUNT(*) FROM caddies')->fetchColumn();
$activeCount = (int) $pdo->query('SELECT COUNT(*) FROM caddies WHERE is_active = 1')->fetchColumn();
$inactiveCount = $totalCount - $activeCount;

$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$selected = null;
$ytdRounds = 0;
$monthWage = 0.0;
$recentLeave = null;
$avgRating = null;
$ratingCount = 0;

if ($selectedId) {
    $stmt = $pdo->prepare('SELECT * FROM caddies WHERE id = ?');
    $stmt->execute([$selectedId]);
    $selected = $stmt->fetch() ?: null;
}

if ($selected) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS rounds, COALESCE(SUM(wage_amount), 0) AS wage
         FROM rounds
         WHERE caddy_id = ? AND status != 'scheduled' AND YEAR(assigned_at) = YEAR(CURDATE())"
    );
    $stmt->execute([$selectedId]);
    $ytd = $stmt->fetch();
    $ytdRounds = (int) $ytd['rounds'];

    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(wage_amount), 0) AS wage
         FROM rounds
         WHERE caddy_id = ? AND status != 'scheduled'
           AND YEAR(assigned_at) = YEAR(CURDATE()) AND MONTH(assigned_at) = MONTH(CURDATE())"
    );
    $stmt->execute([$selectedId]);
    $monthWage = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT lr.start_date, lr.end_date, lr.status, lt.name AS type_name
         FROM leave_requests lr
         JOIN leave_types lt ON lt.id = lr.leave_type_id
         WHERE lr.caddy_id = ?
         ORDER BY lr.start_date DESC LIMIT 1"
    );
    $stmt->execute([$selectedId]);
    $recentLeave = $stmt->fetch() ?: null;

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS rating_count,
                AVG((rr.personality_rating + rr.politeness_rating + rr.knowledge_rating + rr.line_reading_rating
                     + rr.speed_rating + rr.service_rating + rr.satisfaction_rating) / 7) AS avg_rating
         FROM round_ratings rr
         JOIN rounds r ON r.id = rr.round_id
         WHERE r.caddy_id = ?"
    );
    $stmt->execute([$selectedId]);
    $ratingStats = $stmt->fetch();
    $ratingCount = (int) $ratingStats['rating_count'];
    $avgRating = $ratingCount > 0 ? round((float) $ratingStats['avg_rating'], 1) : null;
}

function caddyListLink(string $filter, ?int $id = null): string
{
    $qs = 'filter=' . urlencode($filter);
    if ($id) {
        $qs .= '&id=' . $id;
    }
    return BASE_URL . '/caddies/list.php?' . $qs;
}

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>ทะเบียนแคดดี้</h1>
    <a href="<?= BASE_URL ?>/caddies/form.php" class="btn btn-primary">+ เพิ่มแคดดี้</a>
</div>

<div class="filter-tabs">
    <a href="<?= caddyListLink('all', $selectedId) ?>" class="filter-tab <?= $filter === 'all' ? 'is-active' : '' ?>">ทั้งหมด (<?= $totalCount ?>)</a>
    <a href="<?= caddyListLink('active', $selectedId) ?>" class="filter-tab <?= $filter === 'active' ? 'is-active' : '' ?>">ทำงานอยู่ (<?= $activeCount ?>)</a>
    <a href="<?= caddyListLink('inactive', $selectedId) ?>" class="filter-tab <?= $filter === 'inactive' ? 'is-active' : '' ?>">พ้นสภาพ (<?= $inactiveCount ?>)</a>
</div>

<div class="two-col">
    <div class="card">
        <table>
            <tr>
                <th></th>
                <th>ชื่อ-นามสกุล</th>
                <th>เบอร์โทร</th>
                <th>วันที่เริ่มงาน</th>
                <th>ระดับฝีมือ</th>
                <th>สถานะ</th>
                <th></th>
            </tr>
            <?php foreach ($caddies as $c): ?>
            <tr class="<?= $selectedId === (int) $c['id'] ? 'is-selected-row' : '' ?>">
                <td>
                    <?php if ($c['photo_path']): ?>
                        <img class="avatar avatar-sm" src="<?= BASE_URL ?>/<?= e($c['photo_path']) ?>" alt="">
                    <?php else: ?>
                        <div class="avatar avatar-sm avatar-placeholder"><?= e(mb_substr($c['full_name'], 0, 1)) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= caddyListLink($filter, (int) $c['id']) ?>"><strong><?= e($c['full_name']) ?></strong></a>
                    <div class="text-muted font-mono" style="font-size:12px;">ID: <?= e(formatCaddyCode((int) $c['id'])) ?></div>
                </td>
                <td class="font-mono"><?= e($c['phone']) ?: '-' ?></td>
                <td><?= e($c['start_date']) ?: '-' ?></td>
                <td>
                    <?php if ($c['skill_class']): ?>
                        <span class="badge badge-info"><?= e($c['skill_class']) ?></span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($c['is_active']): ?>
                        <span class="badge badge-success">ทำงานอยู่</span>
                    <?php else: ?>
                        <span class="badge badge-danger">พ้นสภาพ</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= BASE_URL ?>/caddies/form.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">แก้ไข</a>
                    <a href="<?= BASE_URL ?>/caddies/summary.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">สรุปรายการ</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$caddies): ?>
            <tr><td colspan="7" class="text-muted">ไม่มีแคดดี้ในรายการนี้</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <?php if ($selected): ?>
    <div class="card detail-panel">
        <div class="detail-panel-photo">
            <?php if ($selected['photo_path']): ?>
                <img class="avatar avatar-lg" src="<?= BASE_URL ?>/<?= e($selected['photo_path']) ?>" alt="">
            <?php else: ?>
                <div class="avatar avatar-lg avatar-placeholder"><?= e(mb_substr($selected['full_name'], 0, 1)) ?></div>
            <?php endif; ?>
        </div>
        <h3 style="text-align:center; margin-bottom:2px;"><?= e($selected['full_name']) ?></h3>
        <p class="text-muted font-mono" style="text-align:center; margin-top:0;"><?= e(formatCaddyCode((int) $selected['id'])) ?></p>

        <div class="stat-row" style="grid-template-columns: 1fr 1fr 1fr; margin-bottom:16px;">
            <div class="stat-tile">
                <div class="stat-tile-label">รอบปีนี้</div>
                <div class="stat-tile-value"><?= $ytdRounds ?></div>
            </div>
            <div class="stat-tile stat-tile--ready">
                <div class="stat-tile-label">ค่าจ้างเดือนนี้</div>
                <div class="stat-tile-value" style="font-size:18px;"><?= e(number_format($monthWage, 2)) ?></div>
            </div>
            <div class="stat-tile">
                <div class="stat-tile-label">คะแนนเฉลี่ย</div>
                <div class="stat-tile-value" style="font-size:18px;"><?= $avgRating !== null ? e(number_format($avgRating, 1)) . ' ★' : '-' ?></div>
                <?php if ($ratingCount > 0): ?><div class="text-muted" style="font-size:11px;"><?= $ratingCount ?> ครั้ง</div><?php endif; ?>
            </div>
        </div>

        <table class="meta-table" style="margin-bottom:16px;">
            <tr>
                <td width="50%"><strong>ประเภทแคดดี้:</strong> <?= e($selected['caddy_type']) ?: '-' ?></td>
                <td width="50%"><strong>ระดับฝีมือ:</strong> <?= e($selected['skill_class']) ?: '-' ?></td>
            </tr>
            <tr>
                <td><strong>ภาษา:</strong> <?= e($selected['languages']) ?: '-' ?></td>
                <td><strong>ใบรับรอง:</strong> <?= e($selected['certifications']) ?: '-' ?></td>
            </tr>
            <tr>
                <td colspan="2"><strong>ที่อยู่:</strong> <?= e($selected['address']) ?: '-' ?></td>
            </tr>
        </table>

        <p class="text-muted" style="text-transform:uppercase; font-size:12px; margin-bottom:6px;">การลาล่าสุด</p>
        <?php if ($recentLeave): ?>
            <div class="card" style="margin-bottom:0; padding:12px 14px;">
                <strong><?= e($recentLeave['type_name']) ?></strong>
                <span class="badge <?= leaveStatusBadgeClass($recentLeave['status']) ?>"><?= e(leaveStatusLabel($recentLeave['status'])) ?></span>
                <div class="font-mono text-muted" style="font-size:13px;"><?= e($recentLeave['start_date']) ?> — <?= e($recentLeave['end_date']) ?></div>
            </div>
        <?php else: ?>
            <p class="text-muted">ไม่มีประวัติการลา</p>
        <?php endif; ?>

        <div class="form-row" style="margin-top:16px;">
            <a href="<?= BASE_URL ?>/caddies/form.php?id=<?= $selected['id'] ?>" class="btn btn-secondary btn-block">แก้ไขข้อมูล</a>
            <a href="<?= BASE_URL ?>/caddies/summary.php?id=<?= $selected['id'] ?>" class="btn btn-primary btn-block">ดูสรุปรายการทั้งหมด</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
