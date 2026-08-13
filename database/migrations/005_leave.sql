-- ประเภทการลา (ตั๋ว 06)
CREATE TABLE leave_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO leave_types (name) VALUES ('ป่วย'), ('กิจ'), ('พักผ่อน'), ('อื่นๆ');

-- คำขอลาของแคดดี้ล่วงหน้า — ไม่มีการตัดโควตา
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caddy_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caddy_id) REFERENCES caddies(id),
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id)
) ENGINE=InnoDB;
