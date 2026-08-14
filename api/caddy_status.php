<?php
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireApiToken($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['error' => 'ต้องใช้ POST']);
}

$caddyId = (int) ($_POST['caddy_id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$caddyId) {
    jsonResponse(400, ['error' => 'กรุณาระบุ caddy_id']);
}

$stmt = $pdo->prepare('SELECT 1 FROM caddies WHERE id = ?');
$stmt->execute([$caddyId]);
if (!$stmt->fetchColumn()) {
    jsonResponse(404, ['error' => 'ไม่พบแคดดี้นี้']);
}

if (!setCaddyQueueStatus($pdo, $caddyId, $status)) {
    jsonResponse(400, ['error' => 'สถานะไม่ถูกต้อง ต้องเป็นหนึ่งใน ready, on_round, waiting, leave']);
}

jsonResponse(200, ['message' => 'ปรับสถานะเรียบร้อย']);
