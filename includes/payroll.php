<?php

// เริ่ม/สิ้นสุดของสัปดาห์ปฏิทิน (จันทร์–อาทิตย์) ที่ครอบคลุมวันที่ที่กำหนด
function currentPayrollWeek(DateTimeImmutable $today): array
{
    $isoDayOfWeek = (int) $today->format('N'); // 1 = จันทร์ ... 7 = อาทิตย์
    $monday = $today->modify('-' . ($isoDayOfWeek - 1) . ' days');
    $sunday = $monday->modify('+6 days');
    return [$monday->format('Y-m-d'), $sunday->format('Y-m-d')];
}

function findPayrollPeriod(PDO $pdo, string $startDate, string $endDate): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM payroll_periods WHERE start_date = ? AND end_date = ?');
    $stmt->execute([$startDate, $endDate]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

// ปิดยอดค่าจ้างสำหรับช่วงวันที่หนึ่งๆ — สร้าง payroll_periods + payroll_items จาก rounds ที่เกิดขึ้นจริง (ไม่รวมการจองล่วงหน้าที่ยังไม่ถึงเวลา)
// throw หากช่วงวันที่นี้ปิดยอดไปแล้ว — ป้องกันการปิดซ้ำที่จะทำให้ payroll_items ถูกสร้างซ้ำ/ไม่ตรงกับยอดที่โอนจริงไปแล้ว
function closePayrollPeriod(PDO $pdo, string $startDate, string $endDate, int $closedByUserId): int
{
    if (findPayrollPeriod($pdo, $startDate, $endDate) !== null) {
        throw new RuntimeException('รอบวันที่นี้ปิดยอดไปแล้ว ไม่สามารถปิดซ้ำได้');
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO payroll_periods (start_date, end_date, closed_by) VALUES (?, ?, ?)')
            ->execute([$startDate, $endDate, $closedByUserId]);
        $periodId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            "SELECT caddy_id, COUNT(*) AS round_count, SUM(wage_amount) AS total_wage
             FROM rounds
             WHERE status != 'scheduled' AND caddy_id IS NOT NULL
               AND DATE(assigned_at) BETWEEN ? AND ?
             GROUP BY caddy_id"
        );
        $stmt->execute([$startDate, $endDate]);
        $rows = $stmt->fetchAll();

        $insertItem = $pdo->prepare(
            'INSERT INTO payroll_items (payroll_period_id, caddy_id, round_count, total_wage) VALUES (?, ?, ?, ?)'
        );
        foreach ($rows as $row) {
            $insertItem->execute([$periodId, $row['caddy_id'], $row['round_count'], $row['total_wage']]);
        }

        $pdo->commit();
        return $periodId;
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
