<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$pageTitle = 'ให้คะแนนแคดดี้';
$errors = [];
$dimensions = ratingDimensions();

$roundId = (int) ($_GET['round_id'] ?? $_POST['round_id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT r.id, r.caddy_id, r.customer_name, r.holes, r.assigned_at, c.full_name AS caddy_name
     FROM rounds r
     JOIN caddies c ON c.id = r.caddy_id
     WHERE r.id = ? AND r.status != 'scheduled'"
);
$stmt->execute([$roundId]);
$round = $stmt->fetch();

if (!$round) {
    setFlash('error', 'ไม่พบรอบนี้ หรือยังไม่ถึงเวลาออกรอบจริง (ให้คะแนนได้เฉพาะรอบที่เกิดขึ้นจริงแล้ว)');
    header('Location: ' . BASE_URL . '/rounds/assign.php');
    exit;
}

// จากแดชบอร์ด "แคดดี้ที่กำลังออกรอบ" — ให้คะแนนก่อนแล้วค่อยเลิกงาน/กลับเข้าคิว โดยดึง caddy_id จากรอบเอง ไม่รับจากพารามิเตอร์ตรงๆ เพื่อกันความไม่ตรงกัน
$nextAction = $_GET['next_action'] ?? $_POST['next_action'] ?? '';
if (!in_array($nextAction, ['checkout', 'ready'], true)) {
    $nextAction = '';
}

$stmt = $pdo->prepare('SELECT * FROM round_ratings WHERE round_id = ?');
$stmt->execute([$roundId]);
$rating = $stmt->fetch() ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = [];
    foreach ($dimensions as $key => $label) {
        $val = (int) ($_POST[$key] ?? 0);
        if ($val < 1 || $val > 5) {
            $errors[] = 'กรุณาให้คะแนน "' . $label . '" ระหว่าง 1-5 ดาว';
        }
        $values[$key . '_rating'] = $val;
    }
    $comment = trim($_POST['comment'] ?? '');
    $rating = array_merge($rating, $values, ['comment' => $comment]);

    if (empty($errors)) {
        $columns = array_map(fn ($k) => $k . '_rating', array_keys($dimensions));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $updateClause = implode(', ', array_map(fn ($c) => "$c = VALUES($c)", $columns));
        $sql = 'INSERT INTO round_ratings (round_id, ' . implode(', ', $columns) . ', comment) VALUES (?, '
            . $placeholders . ', ?) ON DUPLICATE KEY UPDATE ' . $updateClause . ', comment = VALUES(comment)';
        $params = array_merge([$roundId], array_values($values), [$comment !== '' ? $comment : null]);
        $pdo->prepare($sql)->execute($params);

        if ($nextAction === 'checkout') {
            $pdo->prepare('DELETE FROM caddy_queue_status WHERE caddy_id = ?')->execute([$round['caddy_id']]);
            setFlash('success', 'บันทึกคะแนนและเลิกงานเรียบร้อย');
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }
        if ($nextAction === 'ready') {
            setCaddyQueueStatus($pdo, $round['caddy_id'], 'ready');
            setFlash('success', 'บันทึกคะแนนและปรับสถานะพร้อมออกรอบต่อเรียบร้อย');
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }

        setFlash('success', 'บันทึกคะแนนเรียบร้อย');
        header('Location: ' . BASE_URL . '/rounds/assign.php');
        exit;
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>ให้คะแนนแคดดี้</h1>
    <?php if ($nextAction): ?>
        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-secondary">กลับไปแดชบอร์ด</a>
    <?php else: ?>
        <a href="<?= BASE_URL ?>/rounds/assign.php" class="btn btn-secondary">กลับไปหน้ามอบหมายออกรอบ</a>
    <?php endif; ?>
</div>

<div class="card" style="max-width:600px;">
    <p><strong><?= e($round['caddy_name']) ?></strong> — ลูกค้า <?= e($round['customer_name']) ?> (<?= e($round['holes']) ?> หลุม, <?= e($round['assigned_at']) ?>)</p>
    <?php if ($nextAction === 'checkout'): ?>
        <p class="text-muted">ให้คะแนนก่อน แล้วระบบจะบันทึกเลิกงานให้ทันที</p>
    <?php elseif ($nextAction === 'ready'): ?>
        <p class="text-muted">ให้คะแนนก่อน แล้วระบบจะปรับสถานะพร้อมออกรอบต่อให้ทันที</p>
    <?php endif; ?>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="round_id" value="<?= $roundId ?>">
        <input type="hidden" name="next_action" value="<?= e($nextAction) ?>">
        <?php foreach ($dimensions as $key => $label): ?>
        <div class="form-group">
            <label for="<?= $key ?>"><?= e($label) ?> *</label>
            <select id="<?= $key ?>" name="<?= $key ?>" required>
                <option value="">-- เลือกคะแนน --</option>
                <?php for ($star = 1; $star <= 5; $star++): ?>
                    <option value="<?= $star ?>" <?= (int) ($rating[$key . '_rating'] ?? 0) === $star ? 'selected' : '' ?>><?= $star ?> ดาว</option>
                <?php endfor; ?>
            </select>
        </div>
        <?php endforeach; ?>
        <div class="form-group">
            <label for="comment">ความเห็นเพิ่มเติม</label>
            <textarea id="comment" name="comment"><?= e($rating['comment'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">
            <?php if ($nextAction === 'checkout'): ?>บันทึกคะแนนและเลิกงาน
            <?php elseif ($nextAction === 'ready'): ?>บันทึกคะแนนและพร้อมออกรอบต่อ
            <?php else: ?>บันทึกคะแนน
            <?php endif; ?>
        </button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
