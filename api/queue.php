<?php
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireApiToken($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, ['error' => 'ต้องใช้ GET']);
}

// ใช้ fetchQueueBoard() ตัวเดียวกับที่หน้าเว็บ (queue/board.php, dashboard.php) ใช้ — ข้อมูลตรงกันเสมอโดยไม่ต้องเขียน query ซ้ำ
$leadMinutes = getAdvanceBookingLeadMinutes($pdo);
$queue = fetchQueueBoard($pdo, $leadMinutes);

jsonResponse(200, ['queue' => array_map(function ($row) {
    return [
        'caddy_id' => (int) $row['id'],
        'full_name' => $row['full_name'],
        'status' => $row['status'],
        'raw_status' => $row['raw_status'],
        'last_ready_at' => $row['last_ready_at'],
        'next_booking_at' => $row['next_booking_at'],
        'is_protected' => (bool) $row['is_protected'],
    ];
}, $queue)]);
