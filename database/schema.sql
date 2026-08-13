-- ระบบบริหารจัดการแคดดี้ (Caddy Management System)
-- Database schema (MySQL / MariaDB)

-- สำคัญ: บังคับ charset ของ session นี้เป็น utf8mb4 ก่อนรันคำสั่งถัดไป
-- ป้องกันปัญหาข้อความภาษาไทยเพี้ยน (mojibake) เมื่อ import ผ่านไคลเอนต์ที่ default charset ไม่ใช่ utf8mb4
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS caddy_management CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE caddy_management;

-- ผู้ใช้งานระบบ (บัญชีพนักงาน ไม่ใช่ทะเบียนแคดดี้ — ดูตั๋ว 02 สำหรับ `caddies`)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('queue_hr','accounting','admin') NOT NULL DEFAULT 'queue_hr',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ผู้ใช้งานเริ่มต้น (ผู้ดูแลระบบ) — เปลี่ยนรหัสผ่านทันทีหลังเข้าใช้งานครั้งแรก
INSERT INTO users (full_name, email, password, role) VALUES
('ผู้ดูแลระบบ', 'admin@caddymanagement.local', '$2y$10$TTKSDDNF6ppEjVzGzbofwOhI0juhpADP0ku8o.rhF1LId4p3U1jva', 'admin');
