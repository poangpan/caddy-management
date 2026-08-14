-- ขยายทะเบียนแคดดี้ตามข้อมูลใน ref/caddySpecification.txt (ตั๋ว #13): ที่อยู่, ประเภทแคดดี้, ระดับฝีมือ, ภาษา, ใบรับรอง
ALTER TABLE caddies
    ADD COLUMN address VARCHAR(255) NULL AFTER phone,
    ADD COLUMN caddy_type VARCHAR(100) NULL AFTER address,
    ADD COLUMN skill_class ENUM('A','B','C') NULL AFTER caddy_type,
    ADD COLUMN languages VARCHAR(255) NULL AFTER skill_class,
    ADD COLUMN certifications VARCHAR(255) NULL AFTER languages;
