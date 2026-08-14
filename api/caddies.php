<?php
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireApiToken($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = (int) ($_GET['id'] ?? 0);

    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM caddies WHERE id = ?');
        $stmt->execute([$id]);
        $caddy = $stmt->fetch();
        if (!$caddy) {
            jsonResponse(404, ['error' => 'ไม่พบแคดดี้นี้']);
        }
        jsonResponse(200, ['caddy' => $caddy]);
    }

    $caddies = $pdo->query('SELECT * FROM caddies ORDER BY full_name')->fetchAll();
    jsonResponse(200, ['caddies' => $caddies]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $nationalId = trim($_POST['national_id'] ?? '');
    $bankAccountNumber = trim($_POST['bank_account_number'] ?? '');
    $startDate = ($_POST['start_date'] ?? '') ?: null;
    $address = trim($_POST['address'] ?? '');
    $caddyType = trim($_POST['caddy_type'] ?? '');
    $skillClass = in_array($_POST['skill_class'] ?? '', ['A', 'B', 'C'], true) ? $_POST['skill_class'] : null;
    $languages = trim($_POST['languages'] ?? '');
    $certifications = trim($_POST['certifications'] ?? '');

    if ($fullName === '') {
        jsonResponse(400, ['error' => 'กรุณาระบุ full_name']);
    }

    if ($id) {
        $stmt = $pdo->prepare('SELECT id FROM caddies WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetchColumn()) {
            jsonResponse(404, ['error' => 'ไม่พบแคดดี้นี้']);
        }

        $pdo->prepare(
            'UPDATE caddies SET full_name = ?, phone = ?, national_id = ?, bank_account_number = ?, start_date = ?,
             address = ?, caddy_type = ?, skill_class = ?, languages = ?, certifications = ? WHERE id = ?'
        )->execute([
            $fullName, $phone, $nationalId, $bankAccountNumber, $startDate,
            $address ?: null, $caddyType ?: null, $skillClass, $languages ?: null, $certifications ?: null, $id,
        ]);

        jsonResponse(200, ['message' => 'บันทึกการแก้ไขเรียบร้อย', 'id' => $id]);
    }

    $pdo->prepare(
        'INSERT INTO caddies (full_name, phone, national_id, bank_account_number, start_date, address, caddy_type, skill_class, languages, certifications, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
    )->execute([
        $fullName, $phone, $nationalId, $bankAccountNumber, $startDate,
        $address ?: null, $caddyType ?: null, $skillClass, $languages ?: null, $certifications ?: null,
    ]);

    jsonResponse(201, ['message' => 'เพิ่มแคดดี้เรียบร้อย', 'id' => (int) $pdo->lastInsertId()]);
}

jsonResponse(405, ['error' => 'ต้องใช้ GET หรือ POST']);
