-- การจองแคดดี้ล่วงหน้า (ตั๋ว 07) — ใช้ตาราง rounds เดิม เพิ่มสถานะ scheduled + เวลานัด
-- caddy_id ผ่อนเป็น NULL ได้ เพราะการจองล่วงหน้าอาจยังไม่ระบุแคดดี้เจาะจง (ตัดสินใจตอนถึงเวลาจริง)
ALTER TABLE rounds
    MODIFY caddy_id INT NULL,
    ADD COLUMN status ENUM('scheduled','in_progress','completed') NOT NULL DEFAULT 'in_progress' AFTER wage_amount,
    ADD COLUMN scheduled_at DATETIME NULL AFTER status;

-- ระยะเวลา (นาที) ก่อนถึงเวลานัดที่จะตัดแคดดี้ที่จองไว้ออกจากคิว FIFO อัตโนมัติ — มีแถวเดียวเสมอ (id = 1)
CREATE TABLE advance_booking_settings (
    id TINYINT PRIMARY KEY DEFAULT 1,
    lead_minutes INT NOT NULL DEFAULT 30
) ENGINE=InnoDB;

INSERT INTO advance_booking_settings (id, lead_minutes) VALUES (1, 30);
