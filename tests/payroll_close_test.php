<?php
// Automated test for payroll period close-out (ticket 08) — highest financial risk
// alongside wage calculation. No test framework/composer dependency yet in this
// project, so this is a small self-contained script: run with
// `php tests/payroll_close_test.php`. Uses an in-memory SQLite DB.

require_once __DIR__ . '/../includes/payroll.php';

$failures = [];

function assertSame2($expected, $actual, string $message): void
{
    global $failures;
    if ($expected !== $actual) {
        $failures[] = "{$message}: expected " . var_export($expected, true) . ', got ' . var_export($actual, true);
    }
}

function assertThrows2(string $expectedClass, callable $fn, string $message): void
{
    global $failures;
    try {
        $fn();
        $failures[] = "{$message}: expected {$expectedClass} to be thrown, but nothing was thrown";
    } catch (\Throwable $e) {
        if (!($e instanceof $expectedClass)) {
            $failures[] = "{$message}: expected {$expectedClass}, got " . get_class($e);
        }
    }
}

function freshPayrollPdo(): PDO
{
    $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('CREATE TABLE caddies (id INTEGER PRIMARY KEY, full_name TEXT)');
    $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, full_name TEXT)');
    $pdo->exec(
        'CREATE TABLE rounds (
            id INTEGER PRIMARY KEY,
            caddy_id INTEGER,
            status TEXT NOT NULL,
            wage_amount NUMERIC,
            assigned_at TEXT NOT NULL
        )'
    );
    $pdo->exec(
        'CREATE TABLE payroll_periods (
            id INTEGER PRIMARY KEY,
            start_date TEXT NOT NULL,
            end_date TEXT NOT NULL,
            closed_at TEXT,
            closed_by INTEGER,
            UNIQUE (start_date, end_date)
        )'
    );
    $pdo->exec(
        'CREATE TABLE payroll_items (
            id INTEGER PRIMARY KEY,
            payroll_period_id INTEGER,
            caddy_id INTEGER,
            round_count INTEGER,
            total_wage NUMERIC
        )'
    );

    $pdo->exec("INSERT INTO users (id, full_name) VALUES (1, 'Accounting Tester')");
    $pdo->exec("INSERT INTO caddies (id, full_name) VALUES (1, 'Caddy A'), (2, 'Caddy B')");

    // สัปดาห์ที่จะปิดยอด: 2026-08-10 (จันทร์) ถึง 2026-08-16 (อาทิตย์)
    $rounds = [
        // ภายในสัปดาห์ นับรวม
        [1, 1, 'in_progress', 500.00, '2026-08-10 09:00:00'],
        [2, 1, 'completed', 300.00, '2026-08-12 14:00:00'],
        [3, 2, 'in_progress', 500.00, '2026-08-16 10:00:00'],
        // นอกสัปดาห์ ไม่นับ
        [4, 1, 'in_progress', 500.00, '2026-08-17 09:00:00'],
        [5, 2, 'in_progress', 500.00, '2026-08-09 09:00:00'],
        // จองล่วงหน้าที่ยังไม่ถึงเวลา (ยังไม่เกิดขึ้นจริง) ไม่นับ ถึงจะอยู่ในช่วงวันที่
        [6, 1, 'scheduled', null, '2026-08-11 09:00:00'],
    ];
    $insert = $pdo->prepare('INSERT INTO rounds (id, caddy_id, status, wage_amount, assigned_at) VALUES (?, ?, ?, ?, ?)');
    foreach ($rounds as $r) {
        $insert->execute($r);
    }

    return $pdo;
}

// ปิดยอดสัปดาห์ 2026-08-10..2026-08-16 ต้องรวมเฉพาะรอบที่เกิดขึ้นจริงในช่วงวันที่นั้น
$pdo = freshPayrollPdo();
$periodId = closePayrollPeriod($pdo, '2026-08-10', '2026-08-16', 1);

$items = $pdo->query("SELECT caddy_id, round_count, total_wage FROM payroll_items WHERE payroll_period_id = {$periodId} ORDER BY caddy_id")->fetchAll();

assertSame2(2, count($items), 'creates one payroll item per caddy who worked that week');
assertSame2(1, (int) $items[0]['caddy_id'], 'first item is for caddy A');
assertSame2(2, (int) $items[0]['round_count'], 'caddy A: 2 rounds counted (excludes out-of-range and scheduled rounds)');
assertSame2('800', (string) $items[0]['total_wage'], 'caddy A: wage sums 500 + 300 = 800');
assertSame2(2, (int) $items[1]['caddy_id'], 'second item is for caddy B');
assertSame2(1, (int) $items[1]['round_count'], 'caddy B: 1 round counted (excludes the out-of-range round)');
assertSame2('500', (string) $items[1]['total_wage'], 'caddy B: wage is 500');

// ปิดยอดซ้ำสำหรับช่วงวันที่เดียวกันต้องถูกปฏิเสธ (locking behavior) และห้ามสร้าง payroll_items ซ้ำ
assertThrows2(RuntimeException::class, fn() => closePayrollPeriod($pdo, '2026-08-10', '2026-08-16', 1), 'refuses to close an already-closed period twice');

$itemCountAfterRetry = (int) $pdo->query('SELECT COUNT(*) FROM payroll_items')->fetchColumn();
assertSame2(2, $itemCountAfterRetry, 'a rejected re-close does not create duplicate payroll items');

$periodCountAfterRetry = (int) $pdo->query('SELECT COUNT(*) FROM payroll_periods')->fetchColumn();
assertSame2(1, $periodCountAfterRetry, 'a rejected re-close does not create a duplicate period row');

// ปิดยอดสัปดาห์อื่นที่ไม่มีรอบเลยต้องไม่สร้าง payroll_items แต่ยังสร้าง period ได้ (สัปดาห์เงียบ)
$emptyPeriodId = closePayrollPeriod($pdo, '2026-09-01', '2026-09-07', 1);
$emptyItems = (int) $pdo->query("SELECT COUNT(*) FROM payroll_items WHERE payroll_period_id = {$emptyPeriodId}")->fetchColumn();
assertSame2(0, $emptyItems, 'closing a week with no rounds creates zero payroll items, not an error');

if ($failures) {
    echo count($failures) . " FAILURE(S):\n";
    foreach ($failures as $f) {
        echo "  - {$f}\n";
    }
    exit(1);
}

echo "All payroll close-out tests passed.\n";
exit(0);
