-- บันทึกการมอบหมายแคดดี้ออกรอบแต่ละครั้ง (ตั๋ว 04)
CREATE TABLE rounds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caddy_id INT NOT NULL,
    holes ENUM('9','18') NOT NULL,
    customer_name VARCHAR(150) NOT NULL,
    caddy_requested TINYINT(1) NOT NULL DEFAULT 0,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caddy_id) REFERENCES caddies(id)
) ENGINE=InnoDB;
