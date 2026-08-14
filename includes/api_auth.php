<?php
// ต้อง require ไฟล์นี้เป็นอันแรกในทุก endpoint ของ api/ — กัน config.php สั่ง session_start() เพราะ API ใช้ token ไม่ใช้ session cookie
if (!defined('API_REQUEST')) {
    define('API_REQUEST', true);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

function jsonResponse(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// กันไม่ให้ error ที่ไม่คาดคิด (เช่น PDOException จาก constraint) หลุดออกไปเป็น PHP stack trace ดิบๆ
// ทาง REST API — ผู้เรียกควรได้ JSON เสมอไม่ว่าจะสำเร็จหรือพัง
set_exception_handler(function (Throwable $e) {
    error_log('API uncaught exception: ' . $e->getMessage());
    jsonResponse(500, ['error' => 'เกิดข้อผิดพลาดในระบบ']);
});

function generateApiToken(): string
{
    return bin2hex(random_bytes(32));
}

function getBearerToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        return null;
    }
    return trim($matches[1]);
}

// ตรวจ token จาก Authorization: Bearer <token> — ยอมรับเฉพาะ role queue_hr ที่ยังใช้งานอยู่ (ตามที่ตั๋ว #11 ระบุ
// แม้แต่ admin ก็ไม่ผ่าน) ไม่ถูกต้อง -> ตอบ 401 เป็น JSON แล้ว exit ทันที ผู้เรียกไม่ต้องเช็คต่อ
function requireApiToken(PDO $pdo): array
{
    $token = getBearerToken();
    if ($token === null) {
        jsonResponse(401, ['error' => 'ต้องระบุ token ผ่าน Authorization: Bearer <token>']);
    }

    $stmt = $pdo->prepare(
        "SELECT u.* FROM api_tokens t JOIN users u ON u.id = t.user_id
         WHERE t.token = ? AND u.role = 'queue_hr' AND u.is_active = 1"
    );
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(401, ['error' => 'token ไม่ถูกต้องหรือหมดสิทธิ์การใช้งาน']);
    }

    return $user;
}
