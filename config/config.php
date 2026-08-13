<?php
// ค่าคงที่ของแอปพลิเคชัน
define('APP_NAME', 'ระบบบริหารจัดการแคดดี้');
define('BASE_URL', ''); // ถ้าติดตั้งใน subfolder ให้ใส่ path ตรงนี้

date_default_timezone_set('Asia/Bangkok');

// API request (token auth, ไม่ใช้ session cookie) — สำหรับตั๋ว Android REST API ในอนาคต
if (!defined('API_REQUEST') && session_status() === PHP_SESSION_NONE) {
    session_start();
}
