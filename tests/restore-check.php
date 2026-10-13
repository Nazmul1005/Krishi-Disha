<?php
if(PHP_SAPI!=='cli') exit(1);
require_once __DIR__.'/../config/db.php';
$files=glob(__DIR__.'/../storage/backups/database-*.sql');sort($files);$file=end($files);
if(!$file) throw new RuntimeException('Create a private local backup first.');
$name='kd_restore_'.bin2hex(random_bytes(6));$server=$pdo;$server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
try {
    $restore=new PDO('mysql:host='.DB_HOST.';dbname='.$name.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $restore->exec(file_get_contents($file));
    foreach(['USER','SCHEMA_MIGRATION','PAYMENT','MEDIA'] as $table) {
        if((int)$server->query("SELECT COUNT(*) FROM `$table`")->fetchColumn()!==(int)$restore->query("SELECT COUNT(*) FROM `$table`")->fetchColumn()) throw new RuntimeException('Restore row count mismatch.');
    }
    echo "Private database backup restored and checked in an isolated database.\n";
} finally {$restore=null;$server->exec("DROP DATABASE `$name`");}
