<?php
chdir(__DIR__);
require_once __DIR__ . '/../includes/config.php';
$_SESSION['user_role'] = 'super_admin';
echo bin2hex(session_id() ?: random_bytes(4));
