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
// Clearly named test-only records for public empty/populated states and pagination.
$reviewer=$accounts['desktop']['admin']['id'];
$pdo->prepare("UPDATE CROP SET review_status='reviewed',source_url='https://example.test/reviewed-test-fixture',reviewed_by=?,reviewed_at=NOW() WHERE id=1")->execute([$reviewer]);
$pdo->exec("INSERT INTO CROP(name,category,review_status,source_url,reviewed_at) VALUES ('Z pagination test crop','Grain','reviewed','https://example.test/test-fixture',NOW())");$pagedCrop=(int)$pdo->lastInsertId();
foreach(['desktop','mobile'] as $project) {
    $lookup=$pdo->prepare('SELECT id FROM FARMER WHERE user_id=?');$lookup->execute([$accounts[$project]['farmer']['id']]);$farmer=(int)$lookup->fetchColumn();
    for($i=1;$i<=28;$i++) $pdo->prepare("INSERT INTO PRODUCT(farmer_id,crop_id,quantity_kg,price_per_kg,status,variety,grade,pickup_location,harvest_date,created_at) VALUES (?,?,100,20,'available',?,'Test grade','Test pickup point',CURDATE(),DATE_SUB(NOW(),INTERVAL 1 DAY))")->execute([$farmer,$pagedCrop,'Test listing '.str_pad((string)$i,2,'0',STR_PAD_LEFT)]);
    $pdo->prepare("INSERT INTO FARM_TOUR(farmer_id,title,location,capacity,price_per_day,status) VALUES (?,'Test-only farm visit','Test district',10,500,'active')")->execute([$farmer]);
}
$server->exec("GRANT SELECT,INSERT,UPDATE,DELETE ON `$database`.* TO 'krishidisha_app'@'%'");
$path=dirname(__DIR__).'/.browser-fixtures.json';file_put_contents($path,json_encode(['database'=>$database,'accounts'=>$accounts],JSON_PRETTY_PRINT));chmod($path,0600);
echo "Browser fixtures created in an isolated database.\n";
