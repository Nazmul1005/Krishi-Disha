<?php
require_once __DIR__ . '/../includes/auth_check.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
destroyLogin();
header('Location: /KrishiDisha/auth/login.php');
