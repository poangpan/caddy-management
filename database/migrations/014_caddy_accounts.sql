-- บัญชีเข้าใช้งานพอร์ทัลของแคดดี้ (ตั๋ว #18 slice แรก: login + ดูข้อมูลตัวเอง)
-- แยกตารางจาก users โดยตั้งใจ — แคดดี้ไม่ใช่พนักงาน ไม่ควรมี role ปนกับ requireRole() ของฝั่ง staff
-- caddy_id UNIQUE: แคดดี้หนึ่งคนมีได้บัญชีเดียว, ออกบัญชีให้โดย staff เท่านั้น (ไม่มีหน้าสมัครเอง)
CREATE TABLE caddy_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caddy_id INT NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (caddy_id) REFERENCES caddies(id)
) ENGINE=InnoDB;
