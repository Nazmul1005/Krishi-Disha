<?php
if(PHP_SAPI!=='cli') exit(1);
require_once __DIR__.'/../config/db.php';
if(APP_ENV!=='development') throw new RuntimeException('Test cleanup requires development mode.');
$path=__DIR__.'/../.browser-fixtures.json';if(!is_file($path)) exit;
$fixture=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$name=$fixture['database'];
if(!preg_match('/^kd_browser_[a-f0-9]{12}$/D',$name)) throw new RuntimeException('Refusing to remove an unknown database.');
$pdo->exec("DROP DATABASE IF EXISTS `$name`");unlink($path);if(is_file(__DIR__.'/../.browser.env')) unlink(__DIR__.'/../.browser.env');
echo "Temporary browser database and private fixture files removed.\n";
