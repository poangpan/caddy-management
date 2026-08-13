<?php

// คำนวณค่าจ้างของรอบหนึ่งจากอัตราปัจจุบันใน wage_rates ตามจำนวนหลุม
// คืนค่าเป็น string ทศนิยม 2 ตำแหน่ง (หลีกเลี่ยงปัญหาความละเอียดของ float กับเงิน)
// เรียกครั้งเดียวตอนสร้างรอบเพื่อ snapshot ค่าไว้ — ไม่ query ซ้ำตอนอัตราเปลี่ยนภายหลัง
function calculateRoundWage(PDO $pdo, string $holes): string
{
    if (!in_array($holes, ['9', '18'], true)) {
        throw new InvalidArgumentException("จำนวนหลุมไม่ถูกต้อง: {$holes}");
    }

    $stmt = $pdo->prepare('SELECT rate FROM wage_rates WHERE holes = ?');
    $stmt->execute([$holes]);
    $rate = $stmt->fetchColumn();

    if ($rate === false) {
        throw new RuntimeException("ไม่พบอัตราค่าจ้างสำหรับ {$holes} หลุม");
    }

    return number_format((float) $rate, 2, '.', '');
}
