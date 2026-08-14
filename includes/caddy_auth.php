<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Auth ของพอร์ทัลแคดดี้ — แยกจาก includes/auth.php (staff) โดยตั้งใจทั้งหมด: session key ต่างกัน
// ($_SESSION['caddy'] vs $_SESSION['user']), ตารางต่างกัน (caddy_accounts vs users), ไม่มี role ร่วมกัน
// เพื่อไม่ให้ requireRole()/isQueueHr() ฯลฯ ฝั่ง staff เผลอรับรู้ถึง session ของแคดดี้ (หรือกลับกัน)

function currentCaddy(): ?array
{
    return $_SESSION['caddy'] ?? null;
}

function isCaddyLoggedIn(): bool
{
    return isset($_SESSION['caddy']);
}

function requireCaddyLogin(): void
{
    if (!isCaddyLoggedIn()) {
        header('Location: ' . BASE_URL . '/caddy/login.php');
        exit;
    }
}

// ตรวจสอบ email/password กับ caddy_accounts join caddies คืนค่า ['caddy' => array|null, 'error' => string|null]
function verifyCaddyCredentials(PDO $pdo, string $email, string $password): array
{
    $stmt = $pdo->prepare(
        'SELECT ca.id AS account_id, ca.password, ca.is_active, c.id AS caddy_id, c.full_name
         FROM caddy_accounts ca
         JOIN caddies c ON c.id = ca.caddy_id
         WHERE ca.email = ? LIMIT 1'
    );
    $stmt->execute([$email]);
    $account = $stmt->fetch();

    if (!$account || !password_verify($password, $account['password'])) {
        return ['caddy' => null, 'error' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'];
    }
    if ((int) $account['is_active'] !== 1) {
        return ['caddy' => null, 'error' => 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อพนักงาน'];
    }

    return ['caddy' => $account, 'error' => null];
}

function attemptCaddyLogin(PDO $pdo, string $email, string $password): ?string
{
    $result = verifyCaddyCredentials($pdo, $email, $password);
    if ($result['error'] !== null) {
        return $result['error'];
    }

    $_SESSION['caddy'] = [
        'caddy_id' => (int) $result['caddy']['caddy_id'],
        'full_name' => $result['caddy']['full_name'],
    ];

    return null; // no error
}

function caddyLogout(): void
{
    unset($_SESSION['caddy']);
}
