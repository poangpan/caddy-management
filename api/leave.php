<?php
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireApiToken($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['error' => 'ต้องใช้ POST']);
}

$caddyId = (int) ($_POST['caddy_id'] ?? 0);
$leaveTypeId = (int) ($_POST['leave_type_id'] ?? 0);
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';
$note = trim($_POST['note'] ?? '');

// validateLeaveInput() ตัวเดียวกับ leave/index.php และ caddy/leave.php — เข้า workflow อนุมัติเดียวกันทุกช่องทาง (ตั๋ว #20)
$errors = validateLeaveInput($caddyId, $leaveTypeId, $startDate, $endDate);
if (!empty($errors)) {
    jsonResponse(400, ['errors' => $errors]);
}

$pdo->prepare("INSERT INTO leave_requests (caddy_id, leave_type_id, start_date, end_date, note, status) VALUES (?, ?, ?, ?, ?, 'pending')")
    ->execute([$caddyId, $leaveTypeId, $startDate, $endDate, $note !== '' ? $note : null]);

jsonResponse(201, ['message' => 'บันทึกคำขอลาเรียบร้อย รอการอนุมัติ', 'id' => (int) $pdo->lastInsertId()]);
