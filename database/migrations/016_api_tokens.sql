-- Token auth สำหรับ REST API ของแอป Android (ตั๋ว #11) — จำกัดสิทธิ์เฉพาะ role queue_hr ตามที่ตั๋วระบุ
-- token เป็นค่าสุ่มเก็บตรงๆ ไม่มีวันหมดอายุอัตโนมัติ ยกเลิกได้ผ่าน api/logout.php (ลบแถวนี้)
CREATE TABLE api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;
