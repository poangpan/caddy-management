-- อัตราค่าจ้างต่อจำนวนหลุม (ตั๋ว 05) — ผู้ดูแลระบบแก้ไขได้
-- ค่าเริ่มต้นเป็นค่าประมาณ (placeholder) ต้องยืนยัน/แก้ไขจริงกับฝ่ายบัญชีก่อนใช้งานจริง
CREATE TABLE wage_rates (
    holes ENUM('9','18') PRIMARY KEY,
    rate DECIMAL(10,2) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO wage_rates (holes, rate) VALUES ('9', 300.00), ('18', 500.00);

-- บันทึกค่าจ้างที่คำนวณได้ ณ ตอนมอบหมายรอบ — เป็น snapshot ที่ไม่เปลี่ยนตามอัตราที่แก้ไขในอนาคต
ALTER TABLE rounds ADD COLUMN wage_amount DECIMAL(10,2) NULL AFTER caddy_requested;
