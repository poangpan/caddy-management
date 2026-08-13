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
