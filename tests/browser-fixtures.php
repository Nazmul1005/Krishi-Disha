<?php
if(PHP_SAPI!=='cli') exit(1);
require_once __DIR__.'/../config/db.php';require_once __DIR__.'/../includes/helpers.php';require_once __DIR__.'/../includes/mfa.php';
if(APP_ENV!=='development') throw new RuntimeException('Browser fixtures require development mode.');
$database='kd_browser_'.bin2hex(random_bytes(6));$server=$pdo;$server->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo=new PDO('mysql:host='.DB_HOST.';dbname='.$database.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
$schema=preg_replace('/CREATE DATABASE IF NOT EXISTS krishidisha[^;]+;|USE krishidisha;/','',file_get_contents(__DIR__.'/../database/schema.sql'));$pdo->exec($schema);
foreach(glob(__DIR__.'/../database/migrations/*.php') as $migration) (require $migration)($pdo);
$accounts=[];
foreach(['desktop','mobile'] as $project) foreach(['admin','farmer','dealer','general','tourist','expert','guide','cook'] as $role) {
    $password=bin2hex(random_bytes(16));$email="$project-$role@example.test";
    $pdo->prepare("INSERT INTO USER(name,email,password_hash,role,status,email_verified) VALUES (?,?,?,?,'approved',1)")->execute(["$project $role",$email,password_hash($password,PASSWORD_DEFAULT),$role]);$id=(int)$pdo->lastInsertId();
    $accounts[$project][$role]=['email'=>$email,'password'=>$password,'id'=>$id];
    if($role==='admin') {$secret=base32Encode(random_bytes(20));$pdo->prepare('UPDATE USER SET mfa_secret=? WHERE id=?')->execute([encryptSecret($secret),$id]);$accounts[$project][$role]['totp']=$secret;}
    elseif($role!=='general') $pdo->prepare('INSERT INTO '.strtoupper($role).'(user_id) VALUES (?)')->execute([$id]);
}
$pdo->exec("INSERT INTO CROP(name,category,local_name) VALUES ('Test rice','Grain','পরীক্ষার ধান')");
foreach(['desktop','mobile'] as $project) {
    $farmer=(int)(function()use($pdo,$accounts,$project){$s=$pdo->prepare('SELECT id FROM FARMER WHERE user_id=?');$s->execute([$accounts[$project]['farmer']['id']]);return $s->fetchColumn();})();
    $pdo->prepare("INSERT INTO PRODUCT(farmer_id,crop_id,quantity_kg,price_per_kg,status,pickup_location,variety,grade,harvest_date) VALUES (?,1,100,20,'available','Local test market','Test variety','Test grade',CURDATE())")->execute([$farmer]);$accounts[$project]['product']=(int)$pdo->lastInsertId();
}
$server->exec("GRANT SELECT,INSERT,UPDATE,DELETE ON `$database`.* TO 'krishidisha_app'@'%'");
$path=dirname(__DIR__).'/.browser-fixtures.json';file_put_contents($path,json_encode(['database'=>$database,'accounts'=>$accounts],JSON_PRETTY_PRINT));chmod($path,0600);
echo "Browser fixtures created in an isolated database.\n";
