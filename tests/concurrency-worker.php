<?php
require_once __DIR__.'/../config/db.php';require_once __DIR__.'/../includes/helpers.php';require_once __DIR__.'/../includes/transactions.php';
[$script,$operation,$uid,$source,$key]=$argv;$_SESSION['user_id']=(int)$uid;
try {
    if($operation==='order') $result=orderProduce($pdo,(int)$uid,$key,(int)$source,'80',false,['fulfillment'=>'pickup','delivery_address'=>'Concurrent test','contact_phone'=>'01700000000']);
    else $result=transaction($pdo,fn()=>consumeAuthToken($pdo,$key,'password_reset'));
    echo json_encode(['ok'=>true,'id'=>$result]);
} catch(Throwable $e) {echo json_encode(['ok'=>false,'error'=>get_class($e)]);}
