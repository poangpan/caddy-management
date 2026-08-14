<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payroll_export.php';
requireRole(['accounting', 'admin']);

$periodId = (int) ($_GET['period_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM payroll_periods WHERE id = ?');
$stmt->execute([$periodId]);
$period = $stmt->fetch();

if (!$period) {
    setFlash('error', 'ไม่พบรอบการปิดยอดนี้');
    header('Location: ' . BASE_URL . '/payroll/history.php');
    exit;
}

$rows = fetchPayrollExportRows($pdo, $periodId);
$csv = renderPayrollCsv($rows);
$filename = 'payroll_' . $period['start_date'] . '_' . $period['end_date'] . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($csv));
echo $csv;
exit;
