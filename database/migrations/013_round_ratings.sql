-- คะแนนประเมินแคดดี้หลังจบรอบ (ตั๋ว #19) — ระบบนี้ไม่เปิดให้ลูกค้าใช้งานโดยตรง
-- พนักงานหน้างานสอบถามความเห็นลูกค้าด้วยวาจาแล้วกรอกแทน (staff-entered, ตามที่ตกลงไว้)
-- ให้คะแนนได้กับรอบที่เกิดขึ้นจริงแล้วเท่านั้น (status != 'scheduled') — round_id UNIQUE ทำให้บันทึกซ้ำกลายเป็นแก้ไขคะแนนเดิม
CREATE TABLE round_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round_id INT NOT NULL UNIQUE,
    personality_rating TINYINT UNSIGNED NOT NULL,
    politeness_rating TINYINT UNSIGNED NOT NULL,
    knowledge_rating TINYINT UNSIGNED NOT NULL,
    line_reading_rating TINYINT UNSIGNED NOT NULL,
    speed_rating TINYINT UNSIGNED NOT NULL,
    service_rating TINYINT UNSIGNED NOT NULL,
    satisfaction_rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (round_id) REFERENCES rounds(id)
) ENGINE=InnoDB;
