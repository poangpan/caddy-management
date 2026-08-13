-- ทะเบียนประวัติแคดดี้ (ตั๋ว 02) — แยกจาก `users` ซึ่งเป็นบัญชีพนักงานที่ล็อกอินระบบ
CREATE TABLE caddies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NULL,
    national_id VARCHAR(13) NULL,
    bank_account_number VARCHAR(20) NULL,
    start_date DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
