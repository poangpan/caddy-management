<?php

// การแจ้งเตือนพนักงาน (ตั๋ว #21) — ช่องทางอีเมลเท่านั้นในระยะแรก เพราะ LINE OA/Push ต้องมี channel/app
// ที่ลงทะเบียนไว้ก่อนถึงจะใช้งานได้จริง ส่วน "แจ้งเตือนแคดดี้โดยตรง" ทำไม่ได้เพราะไม่มีบัญชี/อีเมลของแคดดี้ในระบบ
// notifyStaff() คือจุดเดียวที่โค้ดส่วนอื่นเรียกใช้งาน — ถ้าเพิ่มช่องทางที่สองภายหลัง (เช่น LINE OA) แก้ไขแค่ในไฟล์นี้
// ไม่ต้องแก้จุดที่เรียกใช้งาน (เช่น leave/index.php)

function smtpConfig(): array
{
    return [
        'host' => getenv('SMTP_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('SMTP_PORT') ?: 1025),
        'user' => getenv('SMTP_USER') ?: '',
        'pass' => getenv('SMTP_PASS') ?: '',
        'from' => getenv('SMTP_FROM') ?: 'noreply@caddymanagement.local',
    ];
}

// ส่งอีเมลผ่าน SMTP ดิบด้วย fsockopen (ไม่ใช้ไลบรารีภายนอก ตามแนวทางไม่ใช้ Composer ของโปรเจกต์นี้)
// คืน true/false เท่านั้น ไม่ throw ออกไปหาผู้เรียก — ผู้เรียก (เช่น การอนุมัติการลา) ต้องไม่ล้มเหลวเพราะส่งอีเมลไม่ได้
function sendEmail(string $to, string $subject, string $body): bool
{
    $config = smtpConfig();
    $socket = @fsockopen($config['host'], $config['port'], $errno, $errstr, 5);
    if (!$socket) {
        error_log("sendEmail: เชื่อมต่อ SMTP ไม่สำเร็จ ({$config['host']}:{$config['port']}) - {$errstr}");
        return false;
    }

    $ok = false;
    try {
        $expect = function (string $expectedCode) use ($socket): bool {
            $line = fgets($socket, 512);
            while ($line !== false && isset($line[3]) && $line[3] === '-') {
                $line = fgets($socket, 512);
            }
            return $line !== false && strncmp($line, $expectedCode, 3) === 0;
        };
        $send = function (string $data) use ($socket): void {
            fwrite($socket, $data . "\r\n");
        };

        if (!$expect('220')) {
            return false;
        }

        $send('EHLO caddy-management');
        if (!$expect('250')) {
            return false;
        }

        if ($config['user'] !== '') {
            $send('AUTH LOGIN');
            if (!$expect('334')) {
                return false;
            }
            $send(base64_encode($config['user']));
            if (!$expect('334')) {
                return false;
            }
            $send(base64_encode($config['pass']));
            if (!$expect('235')) {
                return false;
            }
        }

        $send('MAIL FROM:<' . $config['from'] . '>');
        if (!$expect('250')) {
            return false;
        }

        $send('RCPT TO:<' . $to . '>');
        if (!$expect('250')) {
            return false;
        }

        $send('DATA');
        if (!$expect('354')) {
            return false;
        }

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = "From: {$config['from']}\r\nTo: {$to}\r\nSubject: {$encodedSubject}\r\n"
            . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        // dot-stuffing ตามมาตรฐาน SMTP: บรรทัดที่ขึ้นต้นด้วย "." ต้องเติม "." ซ้ำ ไม่งั้น server จะตีความว่าจบข้อความ
        $escapedBody = str_replace("\n.", "\n..", $body);
        $send($headers . "\r\n" . $escapedBody . "\r\n.");
        $ok = $expect('250');

        $send('QUIT');
    } finally {
        fclose($socket);
    }

    return $ok;
}

// แจ้งเตือนพนักงาน queue_hr/admin ทุกคน ยกเว้นผู้ที่ทำรายการเอง (เช่น คนที่กดอนุมัติ ไม่ต้องแจ้งตัวเอง)
// ส่งล้มเหลวรายคนไม่กระทบรายการอื่น และไม่ throw ออกไปหา caller
function notifyStaff(PDO $pdo, string $subject, string $body, ?int $excludeUserId = null): void
{
    $sql = "SELECT email FROM users WHERE role IN ('queue_hr', 'admin') AND is_active = 1";
    $params = [];
    if ($excludeUserId) {
        $sql .= ' AND id != ?';
        $params[] = $excludeUserId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($recipients as $email) {
        try {
            sendEmail($email, $subject, $body);
        } catch (Throwable $e) {
            error_log('notifyStaff: ส่งอีเมลไม่สำเร็จถึง ' . $email . ' - ' . $e->getMessage());
        }
    }
}

// สร้างเนื้อหาแจ้งเตือนผลอนุมัติ/ไม่อนุมัติคำขอลา แล้วส่งผ่าน notifyStaff()
// เรียกหลัง UPDATE สำเร็จเท่านั้น (rowCount() > 0) กันแจ้งเตือนซ้ำเวลากดคำขอที่ตัดสินใจไปแล้ว
// ครอบ try/catch ทั้งฟังก์ชันไว้เอง เพื่อให้ผู้เรียก (หน้าอนุมัติการลา) ไม่ต้องกังวลเรื่องนี้เลย —
// การแจ้งเตือนล้มเหลวต้องไม่ทำให้การอนุมัติ/ไม่อนุมัติที่บันทึกไปแล้วกลายเป็นข้อผิดพลาดของหน้าเว็บ
function notifyLeaveDecision(PDO $pdo, int $leaveId, string $decision, ?int $actorUserId): void
{
    try {
        $stmt = $pdo->prepare(
            'SELECT lr.start_date, lr.end_date, c.full_name AS caddy_name, lt.name AS type_name
             FROM leave_requests lr
             JOIN caddies c ON c.id = lr.caddy_id
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             WHERE lr.id = ?'
        );
        $stmt->execute([$leaveId]);
        $leave = $stmt->fetch();
        if (!$leave) {
            return;
        }

        $decisionLabel = $decision === 'approved' ? 'อนุมัติ' : 'ไม่อนุมัติ';
        $subject = "คำขอลาของ {$leave['caddy_name']} ถูก{$decisionLabel}แล้ว";
        $body = "คำขอลา{$leave['type_name']}ของ {$leave['caddy_name']} วันที่ {$leave['start_date']} — {$leave['end_date']} ได้รับการ{$decisionLabel}แล้ว";

        notifyStaff($pdo, $subject, $body, $actorUserId);
    } catch (Throwable $e) {
        error_log('notifyLeaveDecision: แจ้งเตือนไม่สำเร็จ (leave_id=' . $leaveId . ') - ' . $e->getMessage());
    }
}
