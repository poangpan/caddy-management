<?php
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['error' => 'ต้องใช้ POST']);
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    jsonResponse(400, ['error' => 'กรุณาระบุ email และ password']);
}

$result = verifyCredentials($pdo, $email, $password);
if ($result['error'] !== null) {
    jsonResponse(401, ['error' => $result['error']]);
}
$user = $result['user'];

// API นี้ใช้งานได้เฉพาะ queue_hr ตามที่ตั๋ว #11 ระบุ — accounting และ admin ไม่มีเหตุผลต้องใช้แอปนี้
if ($user['role'] !== 'queue_hr') {
    jsonResponse(403, ['error' => 'API นี้ใช้งานได้เฉพาะบัญชีพนักงานคุมคิว/HR เท่านั้น']);
}

$token = generateApiToken();
$pdo->prepare('INSERT INTO api_tokens (user_id, token) VALUES (?, ?)')->execute([$user['id'], $token]);
$pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);

jsonResponse(200, ['token' => $token, 'user' => publicUserFields($user)]);
