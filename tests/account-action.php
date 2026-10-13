<?php
if(PHP_SAPI!=='cli') exit(1);
require_once __DIR__.'/../config/db.php';
$fixture=json_decode(file_get_contents(__DIR__.'/../.browser-fixtures.json'),true,512,JSON_THROW_ON_ERROR);
$database=$fixture['database'];if(APP_ENV!=='development' || !preg_match('/^kd_browser_[a-f0-9]{12}$/D',$database)) throw new RuntimeException('Test database only.');
$uid=(int)($argv[1] ?? 0);$action=$argv[2] ?? '';$allowed=[];
foreach($fixture['accounts'] as $accounts) foreach($accounts as $account) if(is_array($account)) $allowed[]=$account['id'];
if(!in_array($uid,$allowed,true) || !in_array($action,['suspend','approve'],true)) throw new RuntimeException('Unknown test account or action.');
$pdo=new PDO('mysql:host='.DB_HOST.';dbname='.$database.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->prepare('UPDATE USER SET status=?,auth_version=auth_version+1 WHERE id=?')->execute([$action==='suspend'?'suspended':'approved',$uid]);
