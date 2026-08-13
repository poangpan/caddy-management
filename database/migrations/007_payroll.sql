-- ปิดยอดค่าจ้างรายสัปดาห์ (ตั๋ว 08) — รอบจะถูกสร้างขึ้น "ตอนปิดยอด" เท่านั้น
-- การมีแถวใน payroll_periods หมายถึงปิดยอดแล้วเสมอ (ไม่มีสถานะ "เปิด" แยก เพราะยังไม่มี workflow ใดต้องใช้)
CREATE TABLE payroll_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    closed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_by INT NOT NULL,
    UNIQUE KEY uniq_period_range (start_date, end_date),
    FOREIGN KEY (closed_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ยอดค่าจ้างต่อแคดดี้ต่อรอบ — สร้างครั้งเดียวตอนปิดยอด ห้ามแก้ไขหลังจากนั้น (ไม่มีหน้าแก้ไขใดๆ เขียนถึงตารางนี้)
CREATE TABLE payroll_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_period_id INT NOT NULL,
    caddy_id INT NOT NULL,
    round_count INT NOT NULL,
    total_wage DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods(id),
    FOREIGN KEY (caddy_id) REFERENCES caddies(id)
) ENGINE=InnoDB;
