-- Leave approval workflow (ตั๋ว #20) — คำขอลาต้องผ่านการอนุมัติก่อนจึงจะมีผลตัดออกจากคิว
-- แถวเดิมทั้งหมดถือว่าอนุมัติแล้ว (พฤติกรรมเดิมก่อนตั๋วนี้คือบันทึกแล้วมีผลทันที) เพื่อไม่ให้การลาที่มีผลอยู่แล้วหลุดจากคิวโดยไม่ตั้งใจ
ALTER TABLE leave_requests ADD COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' AFTER note;
UPDATE leave_requests SET status = 'approved';
