<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// $roles: string เดียว หรือ array ของ role ที่อนุญาต
function requireRole($roles): void
{
    requireLogin();
    $roles = (array) $roles;
    $user = currentUser();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        die('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
    }
}

function isAdmin(): bool
{
    $user = currentUser();
    return $user && $user['role'] === 'admin';
}

function isAccounting(): bool
{
    $user = currentUser();
    return $user && $user['role'] === 'accounting';
}

function isQueueHr(): bool
{
    $user = currentUser();
    return $user && $user['role'] === 'queue_hr';
}

// ตรวจสอบ email/password กับ users table คืนค่า ['user' => array|null, 'error' => string|null]
// ใช้ร่วมกันทั้งฝั่งเว็บ (attemptLogin) และ REST API ของแอป Android ในอนาคต
function verifyCredentials(PDO $pdo, string $email, string $password): array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['user' => null, 'error' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'];
    }

    if ((int) $user['is_active'] !== 1) {
        return ['user' => null, 'error' => 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ'];
    }

    return ['user' => $user, 'error' => null];
}

// เฉพาะฟิลด์ที่ปลอดภัยเปิดเผยได้ของ users row (ไม่มี password hash)
function publicUserFields(array $user): array
{
    return [
        'id' => (int) $user['id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'photo_path' => $user['photo_path'] ?? null,
    ];
}

function attemptLogin(PDO $pdo, string $email, string $password): ?string
{
    $result = verifyCredentials($pdo, $email, $password);
    if ($result['error'] !== null) {
        return $result['error'];
    }

    $_SESSION['user'] = publicUserFields($result['user']);

    $stmt = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
    $stmt->execute([$result['user']['id']]);

    return null; // no error
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

function roleLabel(string $role): string
{
    return match ($role) {
        'admin' => 'ผู้ดูแลระบบ',
        'accounting' => 'ฝ่ายบัญชี',
        'queue_hr' => 'พนักงานคุมคิว/HR',
        default => $role,
    };
}
