<?php
// Catalog state changes are confined to the explicitly generated disposable test database.
if(PHP_SAPI!=='cli') exit(1);
require_once __DIR__.'/../config/db.php';
$fixture=json_decode(file_get_contents(__DIR__.'/../.browser-fixtures.json'),true,512,JSON_THROW_ON_ERROR);
$database=$fixture['database'];
if(APP_ENV!=='development' || !preg_match('/^kd_browser_[a-f0-9]{12}$/D',$database)) throw new RuntimeException('Test database only.');
$pdo=new PDO('mysql:host='.DB_HOST.';dbname='.$database.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$operation=$argv[1] ?? '';$tables=['PRODUCT'=>['pending','available','sold'],'FARM_TOUR'=>['pending','active','inactive']];
if($operation==='reset_limits') {
    $pdo->exec('DELETE FROM RATE_LIMIT');
} elseif($operation==='hide_catalog') {
    $state=[];foreach($tables as $table=>$statuses)$state[$table]=$pdo->query("SELECT id,status FROM `$table`")->fetchAll();
    $pdo->exec("UPDATE PRODUCT SET status='pending'");$pdo->exec("UPDATE FARM_TOUR SET status='inactive'");
    echo json_encode($state,JSON_THROW_ON_ERROR);
} elseif($operation==='restore_catalog') {
    $state=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
    foreach($tables as $table=>$statuses){$stmt=$pdo->prepare("UPDATE `$table` SET status=? WHERE id=?");foreach($state[$table] ?? [] as $row){if(!in_array($row['status'],$statuses,true)) throw new RuntimeException('Invalid test status.');$stmt->execute([$row['status'],(int)$row['id']]);}}
} else throw new RuntimeException('Unknown test operation.');
