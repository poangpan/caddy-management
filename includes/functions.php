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
