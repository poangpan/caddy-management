-- บันทึกวันที่แคดดี้ลงเวลาเข้างานจริง สะสมไว้ต่างหาก (ตั๋ว #23)
-- caddy_queue_status.last_ready_at ถูก overwrite ทุกครั้งที่ลงเวลาใหม่ ไม่มีประวัติสะสมให้คำนวณ "จำนวนวันที่เข้างาน" ย้อนหลัง
-- ตารางนี้เริ่มเก็บจากวันที่ติดตั้ง migration นี้เป็นต้นไป ไม่มีข้อมูลย้อนหลังก่อนหน้า (ไม่มีทางสร้างขึ้นใหม่ได้เพราะข้อมูลเดิมถูกทับไปแล้ว)
CREATE TABLE attendance_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caddy_id INT NOT NULL,
    work_date DATE NOT NULL,
    checked_in_at DATETIME NOT NULL,
    UNIQUE KEY caddy_work_date (caddy_id, work_date),
    FOREIGN KEY (caddy_id) REFERENCES caddies(id)
) ENGINE=InnoDB;
