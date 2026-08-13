-- สถานะปัจจุบันของแคดดี้แต่ละคน + เวลาที่เข้าสถานะ "พร้อม" ล่าสุด (ใช้เรียง FIFO) (ตั๋ว 03)
CREATE TABLE caddy_queue_status (
    caddy_id INT PRIMARY KEY,
    status ENUM('ready','on_round','waiting','leave') NOT NULL,
    last_ready_at DATETIME NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (caddy_id) REFERENCES caddies(id) ON DELETE CASCADE
) ENGINE=InnoDB;
