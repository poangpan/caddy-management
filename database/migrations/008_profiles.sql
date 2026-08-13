-- รูปโปรไฟล์แคดดี้/ผู้ใช้งาน และเวลาใช้งานล่าสุดของผู้ใช้งาน (ตั๋ว: ปรับหน้าทะเบียนแคดดี้/จัดการผู้ใช้งานตาม ref)
ALTER TABLE caddies ADD COLUMN photo_path VARCHAR(255) NULL AFTER full_name;
ALTER TABLE users ADD COLUMN photo_path VARCHAR(255) NULL AFTER full_name;
ALTER TABLE users ADD COLUMN last_login_at DATETIME NULL AFTER photo_path;
