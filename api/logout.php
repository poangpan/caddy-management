<?php
require_once __DIR__ . '/../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['error' => 'ต้องใช้ POST']);
}

requireApiToken($pdo);
$pdo->prepare('DELETE FROM api_tokens WHERE token = ?')->execute([getBearerToken()]);

jsonResponse(200, ['message' => 'ออกจากระบบเรียบร้อย']);
