<?php
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/db.php';
$pdo->exec("CREATE TABLE IF NOT EXISTS SCHEMA_MIGRATION (version VARCHAR(100) PRIMARY KEY,applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
if(!(int)$pdo->query("SELECT GET_LOCK('krishidisha_migrations',60)")->fetchColumn()) throw new RuntimeException('Migration lock unavailable.');
try {
    foreach(glob(__DIR__.'/../database/migrations/*.php') as $file) {
        $version=basename($file);$stmt=$pdo->prepare('SELECT 1 FROM SCHEMA_MIGRATION WHERE version=?');$stmt->execute([$version]);
        if($stmt->fetchColumn()) continue;
        (require $file)($pdo);
        $pdo->prepare('INSERT INTO SCHEMA_MIGRATION(version) VALUES (?)')->execute([$version]);
        echo "Applied $version\n";
    }
    $app=env('DB_APP_USER');
    if($app!=='') {
        if(!preg_match('/^[a-zA-Z0-9_]+$/D',$app) || !preg_match('/^[a-zA-Z0-9_]+$/D',DB_NAME)) throw new RuntimeException('Invalid database identifier.');
        $pdo->exec("REVOKE ALL PRIVILEGES ON `".DB_NAME."`.* FROM '$app'@'%'");
        $pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON `".DB_NAME."`.* TO '$app'@'%'");
    }
} finally { $pdo->query("SELECT RELEASE_LOCK('krishidisha_migrations')"); }
