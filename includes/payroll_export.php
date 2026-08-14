<?php

// จุดต่อขยายสำหรับการ export ยอดจ่ายค่าจ้าง (ตั๋ว #9) — ตอนนี้มีแค่ CSV ทั่วไป
// fetchPayrollExportRows() แยกออกจาก renderPayrollCsv() โดยตั้งใจ: ถ้าอนาคตต้องทำไฟล์ตามสเปกของ SCB
// (bulk-transfer format เฉพาะของธนาคาร) แค่เพิ่ม renderPayrollScbFormat($rows) แล้วเรียก fetch เดิม
// ไม่ต้องแก้ query หรือจุดที่เรียกใช้งานเดิมเลย

function fetchPayrollExportRows(PDO $pdo, int $periodId): array
{
    $stmt = $pdo->prepare(
        'SELECT c.full_name AS caddy_name, c.bank_account_number, pi.total_wage AS amount
         FROM payroll_items pi
         JOIN caddies c ON c.id = pi.caddy_id
         WHERE pi.payroll_period_id = ?
         ORDER BY c.full_name'
    );
    $stmt->execute([$periodId]);
    return $stmt->fetchAll();
}

// BOM นำหน้าให้ Excel เปิดไฟล์เป็น UTF-8 ถูกต้อง ไม่งั้นชื่อภาษาไทยจะเพี้ยนเป็นอักขระ ANSI เมื่อเปิดบน Windows
function renderPayrollCsv(array $rows): string
{
    $handle = fopen('php://memory', 'w+');
    fwrite($handle, "\xEF\xBB\xBF");
    fputcsv($handle, ['ชื่อแคดดี้', 'เลขบัญชีธนาคาร (SCB)', 'จำนวนเงิน']);
    foreach ($rows as $row) {
        fputcsv($handle, [
            $row['caddy_name'],
            $row['bank_account_number'] ?? '',
            number_format((float) $row['amount'], 2, '.', ''),
        ]);
    }
    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);
    return $csv;
}
