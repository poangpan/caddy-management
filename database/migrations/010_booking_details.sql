-- ขยายการจองล่วงหน้าตามข้อมูลใน ref/caddySpecification.txt (ตั๋ว #15): flight, จำนวนผู้เล่น, VIP
ALTER TABLE rounds
    ADD COLUMN flight VARCHAR(50) NULL AFTER scheduled_at,
    ADD COLUMN player_count INT NOT NULL DEFAULT 1 AFTER flight,
    ADD COLUMN is_vip TINYINT(1) NOT NULL DEFAULT 0 AFTER player_count;
