-- เลขรถกอล์ฟที่ใช้ในรอบ ตามข้อมูลใน ref/caddySpecification.txt (ตั๋ว #16)
ALTER TABLE rounds ADD COLUMN cart_number VARCHAR(20) NULL AFTER wage_amount;
