<?php
require_once __DIR__ . '/../includes/caddy_auth.php';

caddyLogout();
header('Location: ' . BASE_URL . '/caddy/login.php');
exit;
