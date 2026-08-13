<?php

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// สถานะแคดดี้ในคิว — null หมายถึงยังไม่เคยลงคิว (ไม่มีแถวใน caddy_queue_status)
function queueStatusLabel(?string $status): string
{
    return match ($status) {
        'ready' => 'พร้อม',
        'on_round' => 'ออกรอบอยู่',
        'waiting' => 'รอ',
        'leave' => 'ลา',
        default => 'ยังไม่ลงคิว',
    };
}

function queueStatusBadgeClass(?string $status): string
{
    return match ($status) {
        'ready' => 'badge-status-ready',
        'on_round' => 'badge-status-busy',
        'waiting' => 'badge-status-waiting',
        'leave' => 'badge-status-leave',
        default => 'badge-neutral',
    };
}

function getAdvanceBookingLeadMinutes(PDO $pdo): int
{
    return (int) $pdo->query('SELECT lead_minutes FROM advance_booking_settings WHERE id = 1')->fetchColumn();
}

// สถานะที่แสดงผล (status) ตัดแคดดี้ที่มีวันลาครอบคลุมวันนี้ออกจากคิวโดยอัตโนมัติ เว้นแต่พนักงานตั้งสถานะเป็น "พร้อม" เอง (raw_status)
// ซึ่งถือเป็นการดึงกลับเข้าคิวเองตาม AC — คำนวณจากวันที่ปัจจุบันตอน query ทุกครั้ง ไม่ต้องมี background job
// next_booking_at: การจองล่วงหน้าที่ยังไม่ถึงเวลา (แสดงป้ายเสมอ) — is_protected: ใกล้ถึงเวลานัดภายใน lead_minutes แล้ว (ไม่นับเป็นลำดับคิวถึงจะสถานะพร้อม)
function fetchQueueBoard(PDO $pdo, int $leadMinutes): array
{
    $stmt = $pdo->prepare(
        "SELECT c.id, c.full_name, cqs.status AS raw_status, cqs.last_ready_at,
                CASE
                    WHEN lr.caddy_id IS NOT NULL AND (cqs.status IS NULL OR cqs.status != 'ready') THEN 'leave'
                    ELSE cqs.status
                END AS status,
                ab.next_booking_at, ab.is_protected
         FROM caddies c
         LEFT JOIN caddy_queue_status cqs ON cqs.caddy_id = c.id
         LEFT JOIN (
             SELECT DISTINCT caddy_id FROM leave_requests WHERE CURDATE() BETWEEN start_date AND end_date
         ) lr ON lr.caddy_id = c.id
         LEFT JOIN (
             SELECT caddy_id,
                    MIN(scheduled_at) AS next_booking_at,
                    MAX(CASE WHEN NOW() BETWEEN DATE_SUB(scheduled_at, INTERVAL ? MINUTE) AND scheduled_at THEN 1 ELSE 0 END) AS is_protected
             FROM rounds
             WHERE status = 'scheduled' AND caddy_id IS NOT NULL AND scheduled_at >= NOW()
             GROUP BY caddy_id
         ) ab ON ab.caddy_id = c.id
         WHERE c.is_active = 1
         ORDER BY CASE WHEN status = 'ready' THEN 0 ELSE 1 END, cqs.last_ready_at ASC, c.full_name ASC"
    );
    $stmt->execute([$leadMinutes]);
    return $stmt->fetchAll();
}

// ตรวจสอบข้อมูลการจองล่วงหน้า ใช้ร่วมกันทั้งตอนสร้างและแก้ไข
// คืน ['errors' => array, 'scheduled_at' => string|null] — scheduled_at ถูกแปลงจากรูปแบบ datetime-local แล้ว
function validateBookingInput(PDO $pdo, string $customerName, string $holes, string $scheduledAtRaw, ?int $caddyId): array
{
    $errors = [];

    if ($customerName === '') {
        $errors[] = 'กรุณากรอกชื่อลูกค้า';
    }
    if (!in_array($holes, ['9', '18'], true)) {
        $errors[] = 'กรุณาระบุจำนวนหลุม';
    }

    $scheduledAt = str_replace('T', ' ', $scheduledAtRaw);
    if ($scheduledAtRaw === '' || strtotime($scheduledAt) === false) {
        $errors[] = 'กรุณาระบุวันที่และเวลานัด';
    } elseif (strtotime($scheduledAt) <= time()) {
        $errors[] = 'เวลานัดต้องอยู่ในอนาคต';
    }

    if ($caddyId && empty($errors)) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM leave_requests WHERE caddy_id = ? AND ? BETWEEN start_date AND end_date'
        );
        $stmt->execute([$caddyId, substr($scheduledAt, 0, 10)]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'แคดดี้ที่เลือกแจ้งลาไว้ในวันที่นัดหมายนี้ กรุณาเลือกแคดดี้อื่นหรือไม่ระบุแคดดี้';
        }
    }

    return ['errors' => $errors, 'scheduled_at' => $scheduledAt];
}

// รหัสแคดดี้สำหรับแสดงผล (ไม่ใช่ฟิลด์ในฐานข้อมูล) — สร้างจาก id เสมอ
function formatCaddyCode(int $id): string
{
    return sprintf('CDY-%04d', $id);
}

// รับไฟล์รูปโปรไฟล์จาก $_FILES[$fieldName] บันทึกไว้ที่ uploads/{$subdir}/
// คืน path สัมพัทธ์ของรูปใหม่ (เก็บลง DB ได้ตรง ๆ), null ถ้าไม่มีไฟล์แนบมา, หรือโยน RuntimeException ถ้าไฟล์ไม่ผ่านการตรวจสอบ
function handlePhotoUpload(array $files, string $fieldName, string $subdir, ?string $oldPath = null): ?string
{
    if (!isset($files[$fieldName]) || $files[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $files[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('อัปโหลดรูปภาพไม่สำเร็จ');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('ไฟล์รูปภาพต้องมีขนาดไม่เกิน 2MB');
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('รองรับไฟล์รูปภาพชนิด JPEG, PNG หรือ WEBP เท่านั้น');
    }

    $relativeDir = 'uploads/' . $subdir;
    $absoluteDir = __DIR__ . '/../' . $relativeDir;
    if (!is_dir($absoluteDir) || !is_writable($absoluteDir)) {
        throw new RuntimeException('ไม่สามารถบันทึกไฟล์รูปภาพได้ กรุณาติดต่อผู้ดูแลระบบ');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $absoluteDir . '/' . $filename)) {
        throw new RuntimeException('บันทึกไฟล์รูปภาพไม่สำเร็จ');
    }

    if ($oldPath) {
        $oldAbsolute = __DIR__ . '/../' . $oldPath;
        if (is_file($oldAbsolute)) {
            @unlink($oldAbsolute);
        }
    }

    return $relativeDir . '/' . $filename;
}
