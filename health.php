<?php
require_once __DIR__.'/config/db.php';
$pdo->query('SELECT 1');
header('Content-Type: application/json');
echo '{"status":"ok"}';
