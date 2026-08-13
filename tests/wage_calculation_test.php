<?php
// Automated test for wage calculation (ticket 05) — highest financial risk in the system.
// No test framework/composer dependency yet in this project, so this is a small
// self-contained script: run with `php tests/wage_calculation_test.php`.
// Uses an in-memory SQLite DB so it never touches the real MySQL/MariaDB instance.

require_once __DIR__ . '/../includes/wage.php';

$failures = [];

function assertSame($expected, $actual, string $message): void
{
    global $failures;
    if ($expected !== $actual) {
        $failures[] = "{$message}: expected " . var_export($expected, true) . ', got ' . var_export($actual, true);
    }
}

function assertThrows(string $expectedClass, callable $fn, string $message): void
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

function freshPdo(): PDO
{
    $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('CREATE TABLE wage_rates (holes TEXT PRIMARY KEY, rate NUMERIC NOT NULL)');
    $pdo->exec("INSERT INTO wage_rates (holes, rate) VALUES ('9', 300.00), ('18', 500.00)");
    return $pdo;
}

// ค่าจ้างของแต่ละจำนวนหลุมตรงกับอัตราที่ตั้งไว้ ในรูปแบบทศนิยม 2 ตำแหน่งเสมอ
$pdo = freshPdo();
assertSame('300.00', calculateRoundWage($pdo, '9'), '9 holes uses the 9-hole rate');
assertSame('500.00', calculateRoundWage($pdo, '18'), '18 holes uses the 18-hole rate');

// ค่าจ้างที่คำนวณได้สะท้อนอัตรา ณ ตอนที่เรียก ไม่ใช่ค่าตั้งต้นที่ hardcode ไว้
$pdo->exec("UPDATE wage_rates SET rate = 350.00 WHERE holes = '9'");
assertSame('350.00', calculateRoundWage($pdo, '9'), 'picks up the current rate after an admin edit');
assertSame('500.00', calculateRoundWage($pdo, '18'), '18-hole rate is unaffected by editing the 9-hole rate');

// จำนวนหลุมที่ไม่ใช่ 9 หรือ 18 ต้องถูกปฏิเสธ ไม่ใช่ปล่อยให้คำนวณเงินผิดแบบเงียบๆ
assertThrows(InvalidArgumentException::class, fn() => calculateRoundWage($pdo, '27'), 'rejects an invalid hole count');

// ถ้าไม่มีแถวอัตราสำหรับจำนวนหลุมนั้นเลย (ข้อมูลตั้งค่าไม่ครบ) ต้องรายงาน error ไม่ใช่คืนค่า 0 แบบเงียบๆ
$pdo->exec("DELETE FROM wage_rates WHERE holes = '18'");
assertThrows(RuntimeException::class, fn() => calculateRoundWage($pdo, '18'), 'errors when no rate row exists for that hole count');

// จำนวนเงินเป็นจำนวนเต็ม (ไม่มีทศนิยมในอัตราที่ตั้ง) ต้องยังคืนค่าเป็นทศนิยม 2 ตำแหน่งเสมอ เพื่อความสอดคล้องของรูปแบบเงิน
$pdo2 = freshPdo();
$pdo2->exec("UPDATE wage_rates SET rate = 300 WHERE holes = '9'");
assertSame('300.00', calculateRoundWage($pdo2, '9'), 'formats a whole-number rate with 2 decimal places');

if ($failures) {
    echo count($failures) . " FAILURE(S):\n";
    foreach ($failures as $f) {
        echo "  - {$f}\n";
    }
    exit(1);
}

echo "All wage calculation tests passed.\n";
exit(0);
