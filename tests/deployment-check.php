<?php
if(PHP_SAPI!=='cli') exit(1);
require_once __DIR__.'/../config/db.php';
if(DB_USER==='root' || env('MYSQL_ROOT_PASSWORD')!=='') throw new RuntimeException('Web runtime must not receive database root access.');
$grants=$pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN);
foreach($grants as $grant) if(preg_match('/\b(ALL PRIVILEGES|CREATE|ALTER|DROP|FILE|SUPER|GRANT OPTION)\b/',$grant)) throw new RuntimeException('Runtime privileges exceed application needs.');
$count=$pdo->query('SELECT COUNT(*) FROM SCHEMA_MIGRATION')->fetchColumn();if((int)$count<5) throw new RuntimeException('Missing schema migrations.');
echo "Runtime credentials, limited database grants and applied migrations verified.\n";
