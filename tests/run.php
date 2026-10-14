<?php
// These tests create and remove only a uniquely named disposable database.
if(PHP_SAPI!=='cli') exit(1);
require_once __DIR__.'/../config/db.php';require_once __DIR__.'/../includes/helpers.php';require_once __DIR__.'/../includes/transactions.php';require_once __DIR__.'/../includes/mfa.php';
$server=$pdo;$database='kd_test_'.bin2hex(random_bytes(6));$checks=0;
function check(bool $condition,string $label): void { global $checks; if(!$condition) throw new RuntimeException('FAIL: '.$label);$checks++;echo "PASS: $label\n"; }
function rejects(callable $work,string $label): void {try {$work();}catch(InvalidArgumentException $e){check(true,$label);return;}throw new RuntimeException('FAIL: '.$label);}
function value(PDO $pdo,string $sql,array $args=[]): mixed {$stmt=$pdo->prepare($sql);$stmt->execute($args);return $stmt->fetchColumn();}
$server->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
try {
    $pdo=new PDO('mysql:host='.DB_HOST.';dbname='.$database.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $schema=file_get_contents(__DIR__.'/../database/schema.sql');$schema=preg_replace('/CREATE DATABASE IF NOT EXISTS krishidisha[^;]+;|USE krishidisha;/','',$schema);
    $pdo->exec($schema);
    foreach(glob(__DIR__.'/../database/migrations/*.php') as $migration) (require $migration)($pdo);
    $users=[];
    foreach(['admin','farmer','dealer','general','tourist','expert','guide','cook'] as $role) {
        $pdo->prepare("INSERT INTO USER(name,email,password_hash,role,status,email_verified) VALUES (?,?,? ,?,'approved',1)")->execute([$role,$role.'@example.test',password_hash('test-strong-password',PASSWORD_DEFAULT),$role]);$users[$role]=(int)$pdo->lastInsertId();
        if(!in_array($role,['admin','general'],true)) $pdo->prepare('INSERT INTO '.strtoupper($role).'(user_id) VALUES (?)')->execute([$users[$role]]);
    }
    $_SESSION['user_id']=$users['general'];
    check((int)value($pdo,'SELECT COUNT(*) FROM VITAMIN')>0,'Reference vocabulary exists without demo users');
    $pdo->exec("INSERT INTO CROP(name,category) VALUES ('Rice','Grain')");
    $farmer=(int)value($pdo,'SELECT id FROM FARMER WHERE user_id=?',[$users['farmer']]);
    $pdo->prepare("INSERT INTO PRODUCT(farmer_id,crop_id,quantity_kg,price_per_kg,status,minimum_order_kg) VALUES (?,1,100,10,'available',1)")->execute([$farmer]);$product=(int)$pdo->lastInsertId();
    $key=bin2hex(random_bytes(16));$procurement=procure($pdo,$users['dealer'],$key,$product,'40','15');
    check((string)value($pdo,'SELECT quantity_kg FROM PRODUCT WHERE id=?',[$product])==='60.00','Dealer purchase deducts farmer stock');
    check(procure($pdo,$users['dealer'],$key,$product,'40','15')===$procurement,'Procurement retry is idempotent');
    $inventory=(int)value($pdo,'SELECT inventory_id FROM PROCUREMENT WHERE id=?',[$procurement]);
    check((string)value($pdo,'SELECT stock_remaining FROM DEALER_INVENTORY WHERE id=?',[$inventory])==='40.00','Dealer stock matches purchased quantity');
    check((int)value($pdo,'SELECT COUNT(*) FROM ADMIN_COMMISSION')===0,'No commission created before collection');
    $delivery=['fulfillment'=>'pickup','delivery_address'=>'Market collection point','contact_phone'=>'01700000000'];
    $key=bin2hex(random_bytes(16));$order=orderProduce($pdo,$users['general'],$key,$inventory,'5',true,$delivery);
    check(orderProduce($pdo,$users['general'],$key,$inventory,'5',true,$delivery)===$order,'Order retry is idempotent');
    rejects(fn()=>orderProduce($pdo,$users['general'],$key,$inventory,'6',true,$delivery),'Changed payload cannot reuse request key');
    check((string)value($pdo,'SELECT stock_remaining FROM DEALER_INVENTORY WHERE id=?',[$inventory])==='35.00','Resale decrements dealer stock');
    rejects(fn()=>orderProduce($pdo,$users['general'],bin2hex(random_bytes(16)),$inventory,'36',true,$delivery),'Oversized order rejected');
    rejects(fn()=>changeState($pdo,$users['farmer'],'order',$order,'confirmed'),'Unrelated seller cannot change order');
    $payment=(int)value($pdo,"SELECT id FROM PAYMENT WHERE ref_type='order' AND ref_id=?",[$order]);
    rejects(fn()=>collectCash($pdo,$users['dealer'],$payment,'75','early'),'Cash completion requires fulfillment');
    changeState($pdo,$users['dealer'],'order',$order,'confirmed');changeState($pdo,$users['dealer'],'order',$order,'delivered');
    rejects(fn()=>collectCash($pdo,$users['dealer'],$payment,'70','wrong-amount'),'Cash amount must match');
    collectCash($pdo,$users['dealer'],$payment,'75','cash-receipt-1');
    check((string)value($pdo,'SELECT commission_amount FROM ADMIN_COMMISSION WHERE payment_id=?',[$payment])==='3.75','Commission follows verified receipt');
    rejects(fn()=>collectCash($pdo,$users['dealer'],$payment,'75','duplicate'),'Cash collection cannot be duplicated');
    rejects(fn()=>changeState($pdo,$users['dealer'],'order',$order,'confirmed'),'Terminal order cannot be reopened');
    paymentOperation($pdo,$users['admin'],$payment,'refund_request','support-case-1');
    $share=(int)value($pdo,'SELECT id FROM PAYMENT_SHARE WHERE payment_id=?',[$payment]);
    rejects(fn()=>paymentOperation($pdo,$users['admin'],$payment,'refund','refund-1'),'Refund requires returned seller proceeds');
    paymentOperation($pdo,$users['admin'],$payment,'recover','return-receipt-1',$share);
    paymentOperation($pdo,$users['admin'],$payment,'refund','refund-receipt-1');
    check((string)value($pdo,'SELECT status FROM PAYMENT WHERE id=?',[$payment])==='refunded','Refund moves verified payment to refunded');
    check((int)value($pdo,'SELECT reversed FROM ADMIN_COMMISSION WHERE payment_id=?',[$payment])===1,'Refund reverses platform commission');
    rejects(fn()=>paymentOperation($pdo,$users['admin'],$payment,'refund','duplicate'),'Refund cannot be duplicated');
    $cancel=orderProduce($pdo,$users['general'],bin2hex(random_bytes(16)),$product,'10',false,$delivery);
    changeState($pdo,$users['general'],'order',$cancel,'cancelled');
    check((string)value($pdo,'SELECT quantity_kg FROM PRODUCT WHERE id=?',[$product])==='60.00','Cancellation restores exact stock');
    rejects(fn()=>changeState($pdo,$users['general'],'order',$cancel,'cancelled'),'Repeated cancellation cannot restock twice');
    // Completed cash cannot be refunded arbitrarily; cancelled service enters recovery/refund workflow.
    $pdo->exec("UPDATE EXPERT SET hourly_rate=1000 WHERE user_id=".$users['expert']);
    $appointment=['provider_id'=>$users['expert'],'scheduled_at'=>date('Y-m-d\TH:i',time()+86400),'duration_hours'=>'1.00','topic'=>'Soil assessment'];
    $consult=bookConsultation($pdo,$users['general'],bin2hex(random_bytes(16)),$appointment);
    rejects(fn()=>bookConsultation($pdo,$users['tourist'],bin2hex(random_bytes(16)),$appointment),'Overlapping consultation rejected');
    rejects(fn()=>bookConsultation($pdo,$users['general'],bin2hex(random_bytes(16)),array_replace($appointment,['provider_id'=>$users['general']])),'Arbitrary provider rejected');
    rejects(fn()=>bookConsultation($pdo,$users['general'],bin2hex(random_bytes(16)),array_replace($appointment,['duration_hours'=>'-1'])),'Negative consultation duration rejected');
    $pdo->prepare("INSERT INTO FARM_TOUR(farmer_id,title,capacity,price_per_day,status) VALUES (?,'Test tour',10,100,'active')")->execute([$farmer]);$tour=(int)$pdo->lastInsertId();
    $booking=['tour_id'=>$tour,'guide_id'=>0,'start_date'=>date('Y-m-d',time()+86400),'end_date'=>date('Y-m-d',time()+86400),'num_visitors'=>8];
    bookTour($pdo,$users['tourist'],bin2hex(random_bytes(16)),$booking);
    rejects(fn()=>bookTour($pdo,$users['tourist'],bin2hex(random_bytes(16)),array_replace($booking,['num_visitors'=>3])),'Daily tour capacity enforced');
    $booking['start_date']=$booking['end_date']=date('Y-m-d',time()+2*86400);bookTour($pdo,$users['tourist'],bin2hex(random_bytes(16)),$booking);check(true,'Disjoint tour dates retain capacity');
    $cook=(int)value($pdo,'SELECT id FROM COOK WHERE user_id=?',[$users['cook']]);
    $pdo->prepare('UPDATE COOK SET daily_capacity=2 WHERE id=?')->execute([$cook]);$pdo->prepare("INSERT INTO RECIPE(cook_id,name,price) VALUES (?,'Test meal',100)")->execute([$cook]);$recipe=(int)$pdo->lastInsertId();
    $food=['recipe_id'=>$recipe,'cook_id'=>$cook,'quantity'=>2,'delivery_date'=>date('Y-m-d',time()+86400),'delivery_address'=>'Dhaka','contact_phone'=>'01700000000'];orderFood($pdo,$users['tourist'],bin2hex(random_bytes(16)),$food);
    rejects(fn()=>orderFood($pdo,$users['tourist'],bin2hex(random_bytes(16)),$food),'Cook daily capacity enforced');
    $token=transaction($pdo,fn()=>createAuthToken($pdo,$users['general'],'password_reset'));
    check(transaction($pdo,fn()=>consumeAuthToken($pdo,$token,'password_reset'))===$users['general'],'Valid recovery token consumed');
    check(transaction($pdo,fn()=>consumeAuthToken($pdo,$token,'password_reset'))===null,'Recovery token cannot be replayed');
    $pdo->prepare('UPDATE USER SET email_verified=0 WHERE id=?')->execute([$users['tourist']]);
    foreach(glob(__DIR__.'/../database/migrations/*.php') as $migration) (require $migration)($pdo);
    check((int)value($pdo,'SELECT email_verified FROM USER WHERE id=?',[$users['tourist']])===0,'Migration rerun never grants verification');
    rejects(fn()=>bookTour($pdo,$users['tourist'],bin2hex(random_bytes(16)),$booking),'Unverified users cannot transact');
    $pdo->prepare("INSERT INTO PRODUCT(farmer_id,crop_id,quantity_kg,price_per_kg,status,minimum_order_kg) VALUES (?,1,100,10,'available',1)")->execute([$farmer]);$concurrentProduct=(int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO USER(name,email,password_hash,role,status,email_verified) VALUES ('second buyer','second@example.test',?,'general','approved',1)")->execute([password_hash('test-strong-password',PASSWORD_DEFAULT)]);$second=(int)$pdo->lastInsertId();
    $parallel=function(array $commands)use($database):array {
        $children=[];$environment=array_merge(getenv(),['DB_NAME'=>$database]);
        foreach($commands as $args) {$pipes=[];$process=proc_open(array_merge([PHP_BINARY,__DIR__.'/concurrency-worker.php'],$args),[['pipe','r'],['pipe','w'],['pipe','w']],$pipes,null,$environment);fclose($pipes[0]);$children[]=[$process,$pipes];}
        $results=[];foreach($children as [$process,$pipes]) {$json=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($process)!==0) throw new RuntimeException($error);$results[]=json_decode($json,true,512,JSON_THROW_ON_ERROR);}return $results;
    };
    $r=$parallel([['order',(string)$users['general'],(string)$concurrentProduct,bin2hex(random_bytes(16))],['order',(string)$second,(string)$concurrentProduct,bin2hex(random_bytes(16))]]);
    check(count(array_filter($r,fn($row)=>$row['ok']))===1,'Concurrent buyers cannot oversell');
    check((string)value($pdo,'SELECT quantity_kg FROM PRODUCT WHERE id=?',[$concurrentProduct])==='20.00','Concurrent stock balance is exact');
    $token=transaction($pdo,fn()=>createAuthToken($pdo,$users['general'],'password_reset'));
    $r=$parallel([['token',(string)$users['general'],'0',$token],['token',(string)$users['general'],'0',$token]]);
    check(count(array_filter($r,fn($row)=>$row['id']!==null))===1,'Concurrent token consumption succeeds once');
    check(totp(base32Encode('12345678901234567890'),1)==='287082','TOTP matches RFC test vector');
    check(decryptSecret(encryptSecret('private-secret'))==='private-secret','MFA secret encryption roundtrip');
    check(e('<img src=x onerror=alert(1)>')==='&lt;img src=x onerror=alert(1)&gt;','Hostile content is escaped');
    check(totalFor('0.25','10.50')==='2.63','Money totals round in integer cents');
    require __DIR__.'/landing.php';
    require __DIR__.'/pagination.php';
    require __DIR__.'/dashboard.php';
    echo "Completed $checks integration checks.\n";
} finally { $pdo=null;$server->exec("DROP DATABASE `$database`"); }
