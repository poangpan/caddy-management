<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['queue_hr', 'admin']);

$pageTitle = 'ให้คะแนนแคดดี้';
$errors = [];
$dimensions = ratingDimensions();

$roundId = (int) ($_GET['round_id'] ?? $_POST['round_id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT r.id, r.customer_name, r.holes, r.assigned_at, c.full_name AS caddy_name
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
        setFlash('success', 'บันทึกคะแนนเรียบร้อย');
        header('Location: ' . BASE_URL . '/rounds/assign.php');
        exit;
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>ให้คะแนนแคดดี้</h1>
    <a href="<?= BASE_URL ?>/rounds/assign.php" class="btn btn-secondary">กลับไปหน้ามอบหมายออกรอบ</a>
</div>

<div class="card" style="max-width:600px;">
    <p><strong><?= e($round['caddy_name']) ?></strong> — ลูกค้า <?= e($round['customer_name']) ?> (<?= e($round['holes']) ?> หลุม, <?= e($round['assigned_at']) ?>)</p>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="round_id" value="<?= $roundId ?>">
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
        <button type="submit" class="btn btn-primary">บันทึกคะแนน</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
