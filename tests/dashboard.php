<?php
require_once __DIR__.'/../includes/dashboard.php';
$order=orderProduce($pdo,$users['general'],bin2hex(random_bytes(16)),$concurrentProduct,'5',false,$delivery);
changeState($pdo,$users['farmer'],'order',$order,'confirmed');changeState($pdo,$users['farmer'],'order',$order,'delivered');
$payment=(int)value($pdo,"SELECT id FROM PAYMENT WHERE ref_type='order' AND ref_id=?",[$order]);
$farmerOverview=dashboardData($pdo,$users['farmer']);
check((float)$farmerOverview['moneyTotal']===0.0,'Dashboard excludes unverified cash from proceeds');
check(array_sum(array_column(array_filter($farmerOverview['attention'],fn($item)=>$item['title']==='Cash receipts to confirm'),'count'))>=1,'Delivered orders create actionable cash attention counts');
collectCash($pdo,$users['farmer'],$payment,'50','dashboard-fixture-receipt');
check((float)dashboardData($pdo,$users['farmer'])['moneyTotal']===47.5,'Farmer chart uses verified net proceeds after commission');
check((float)dashboardData($pdo,$users['general'])['moneyTotal']===50.0,'Buyer chart uses actual verified cash paid');
check((float)dashboardData($pdo,$second)['moneyTotal']===0.0,'Dashboard financial records are scoped to the requesting account');
paymentOperation($pdo,$users['admin'],$payment,'refund_request','dashboard-refund-review');
check((float)dashboardData($pdo,$users['farmer'])['moneyTotal']===0.0,'Refund review is excluded from dashboard cash totals');
$message=$pdo->prepare('INSERT INTO CONSULTATION_MESSAGE(consultation_id,sender_id,message) VALUES (?,?,?)');
$message->execute([$consult,$users['expert'],'Test provider message']);$visible=(int)$pdo->lastInsertId();
$message->execute([$consult,$users['general'],'Test client message']);
check(unreadConsultationMessages($pdo,$users['general'])===1 && unreadConsultationMessages($pdo,$users['expert'])===1,'Unread messages exclude own messages and count per participant');
check(unreadConsultationMessages($pdo,$second)===0,'Unrelated accounts cannot discover consultation message counts');
rejects(fn()=>markConsultationRead($pdo,$second,$consult,$visible),'Unrelated accounts cannot mark consultation messages read');
markConsultationRead($pdo,$users['general'],$consult,$visible);markConsultationRead($pdo,$users['general'],$consult,0);
check(unreadConsultationMessages($pdo,$users['general'])===0 && unreadConsultationMessages($pdo,$users['expert'])===1,'Read positions are monotonic and do not change the other participant');
$pdo->prepare('UPDATE USER SET email_verified=1 WHERE id=?')->execute([$users['tourist']]);
foreach($users as $role=>$uid){$data=dashboardData($pdo,$uid);check(count($data['series'])===6 && $data['user']['role']===$role,"$role dashboard has scoped six-month activity and cash series");}
$pdo->prepare("UPDATE USER SET status='suspended' WHERE id=?")->execute([$second]);
rejects(fn()=>dashboardData($pdo,$second),'Suspended accounts cannot query dashboard services');
