<?php
function dashboardScalar(PDO $pdo,string $sql,array $params=[]): int {
    $stmt=$pdo->prepare($sql);$stmt->execute($params);return (int)$stmt->fetchColumn();
}
function unreadConsultationMessages(PDO $pdo,int $uid): int {
    return dashboardScalar($pdo,'SELECT COUNT(*) FROM CONSULTATION_MESSAGE m JOIN CONSULTATION c ON m.consultation_id=c.id LEFT JOIN CONSULTATION_READ r ON r.consultation_id=c.id AND r.user_id=? WHERE (c.client_id=? OR c.provider_id=?) AND m.sender_id<>? AND m.id>COALESCE(r.last_message_id,0)',[$uid,$uid,$uid,$uid]);
}
function markConsultationRead(PDO $pdo,int $uid,int $cid,int $visibleId): void {
    $stmt=$pdo->prepare("SELECT c.id FROM CONSULTATION c JOIN USER u ON u.id=? WHERE c.id=? AND (c.client_id=u.id OR c.provider_id=u.id) AND u.status='approved' AND u.email_verified=1");$stmt->execute([$uid,$cid]);
    if(!$stmt->fetchColumn()) throw new InvalidArgumentException('Consultation not found.');
    $stmt=$pdo->prepare('SELECT COALESCE(MAX(id),0) FROM CONSULTATION_MESSAGE WHERE consultation_id=? AND id<=?');$stmt->execute([$cid,max(0,$visibleId)]);$id=(int)$stmt->fetchColumn();
    $pdo->prepare('INSERT INTO CONSULTATION_READ(consultation_id,user_id,last_message_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE last_message_id=GREATEST(last_message_id,VALUES(last_message_id))')->execute([$cid,$uid,$id]);
}
function dashboardData(PDO $pdo,int $uid): array {
    $stmt=$pdo->prepare("SELECT name,role FROM USER WHERE id=? AND status='approved' AND email_verified=1");$stmt->execute([$uid]);$user=$stmt->fetch();
    if(!$user) throw new InvalidArgumentException('Approved account required.');
    $role=$user['role'];$attention=[];
    $add=function(string $title,int $count,string $href,string $symbol)use(&$attention):void{$attention[]=['title'=>$title,'count'=>$count,'href'=>$href,'icon'=>$symbol];};
    $add('Unread notifications',dashboardScalar($pdo,'SELECT COUNT(*) FROM NOTIFICATION WHERE user_id=? AND is_read=0',[$uid]),'/KrishiDisha/modules/notifications.php','fa-bell');
    $messageRoute=match($role){'expert'=>'expert/sessions.php','guide'=>'guide/sessions.php','farmer','general','tourist'=>'modules/book_consultation.php',default=>'modules/notifications.php'};
    $add('Unread messages',unreadConsultationMessages($pdo,$uid),'/KrishiDisha/'.$messageRoute,'fa-comments');
    $fulfillment="(p.ref_type='procurement' OR (p.ref_type='order' AND EXISTS(SELECT 1 FROM `ORDER` o WHERE o.id=p.ref_id AND o.status='delivered')) OR (p.ref_type='tour_booking' AND EXISTS(SELECT 1 FROM TOUR_BOOKING b WHERE b.id=p.ref_id AND b.status='completed')) OR (p.ref_type='food_order' AND EXISTS(SELECT 1 FROM FOOD_ORDER o WHERE o.id=p.ref_id AND o.status='delivered')) OR (p.ref_type='consultation' AND EXISTS(SELECT 1 FROM CONSULTATION c WHERE c.id=p.ref_id AND c.status='completed')))";
    $cash=dashboardScalar($pdo,"SELECT COUNT(*) FROM PAYMENT p WHERE ".($role==='admin'?'1=1':'p.payee_id=?')." AND p.status='pending' AND p.verified_at IS NULL AND p.refund_requested_at IS NULL AND EXISTS(SELECT 1 FROM PAYMENT_SHARE s WHERE s.payment_id=p.id) AND $fulfillment",$role==='admin'?[]:[$uid]);
    if(in_array($role,['admin','farmer','dealer','cook','expert','guide'],true)) $add('Cash receipts to confirm',$cash,'/KrishiDisha/modules/payments.php','fa-receipt');
    // One scoped activity relation per role; all role IDs come from database joins.
    $activity='SELECT o.id,o.created_at,o.status FROM `ORDER` o WHERE o.user_id=?';$params=[$uid];$activityLabel='Produce orders';
    if($role==='admin') {
        $activity='SELECT id,created_at,status FROM `ORDER`';$params=[];
        $add('Provider applications',dashboardScalar($pdo,"SELECT COUNT(*) FROM USER WHERE status='pending'"),'/KrishiDisha/admin/approvals.php','fa-user-check');
        $add('Content proposals',dashboardScalar($pdo,"SELECT COUNT(*) FROM DATA_PROPOSAL WHERE status='pending'"),'/KrishiDisha/admin/proposals.php','fa-book-open');
        $add('Open support requests',dashboardScalar($pdo,"SELECT COUNT(*) FROM SUPPORT_REQUEST WHERE status='open'"),'/KrishiDisha/modules/support.php','fa-life-ring');
    } elseif($role==='farmer') {
        $activity='SELECT o.id,o.created_at,o.status FROM `ORDER` o JOIN PRODUCT p ON o.product_id=p.id JOIN FARMER f ON p.farmer_id=f.id WHERE f.user_id=? AND o.dealer_inventory_id IS NULL';
        $add('Listings awaiting review',dashboardScalar($pdo,"SELECT COUNT(*) FROM PRODUCT p JOIN FARMER f ON p.farmer_id=f.id WHERE f.user_id=? AND p.status='pending'",[$uid]),'/KrishiDisha/farmer/produce.php','fa-seedling');
        $add('Tour requests',dashboardScalar($pdo,"SELECT COUNT(*) FROM TOUR_BOOKING b JOIN FARM_TOUR t ON b.tour_id=t.id JOIN FARMER f ON t.farmer_id=f.id WHERE f.user_id=? AND b.status='pending'",[$uid]),'/KrishiDisha/farmer/bookings.php','fa-calendar');
    } elseif($role==='dealer') $activity='SELECT o.id,o.created_at,o.status FROM `ORDER` o JOIN DEALER_INVENTORY i ON o.dealer_inventory_id=i.id JOIN DEALER d ON i.dealer_id=d.id WHERE d.user_id=?';
    elseif($role==='cook') {$activity='SELECT o.id,o.created_at,o.status FROM FOOD_ORDER o JOIN COOK c ON o.cook_id=c.id WHERE c.user_id=?';$activityLabel='Food orders';}
    elseif($role==='expert') {$activity='SELECT id,created_at,status FROM CONSULTATION WHERE provider_id=?';$activityLabel='Consultations';}
    elseif($role==='guide') {$activity='SELECT b.id,b.created_at,b.status FROM TOUR_BOOKING b JOIN GUIDE g ON b.guide_id=g.id WHERE g.user_id=?';$activityLabel='Tour bookings';}
    elseif($role==='tourist') {$activity='SELECT b.id,b.created_at,b.status FROM TOUR_BOOKING b JOIN TOURIST t ON b.tourist_id=t.id WHERE t.user_id=?';$activityLabel='Tour bookings';}
    $stmt=$pdo->prepare("SELECT COUNT(*) AS total,COALESCE(SUM(status='pending'),0) AS pending FROM ($activity) a");$stmt->execute($params);$activityTotals=$stmt->fetch();
    $routes=['farmer'=>'farmer/orders.php','dealer'=>'dealer/sales.php','cook'=>'cook/orders.php','expert'=>'expert/sessions.php','guide'=>'guide/bookings.php','tourist'=>'tourist/tours.php','general'=>'modules/orders.php','admin'=>'modules/orders.php'];
    $add('Pending '.$activityLabel,(int)$activityTotals['pending'],'/KrishiDisha/'.$routes[$role],'fa-clock');
    $stmt=$pdo->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,COUNT(*) AS value FROM ($activity) a WHERE created_at>=? GROUP BY month ORDER BY month");
    $start=(new DateTimeImmutable('first day of this month'))->modify('-5 months');$stmt->execute(array_merge($params,[$start->format('Y-m-d')]));$activitySeries=$stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $eligible="p.status='completed' AND p.verified_at IS NOT NULL AND p.refund_requested_at IS NULL";
    if($role==='admin') {$money="SELECT p.verified_at,ac.commission_amount AS amount FROM ADMIN_COMMISSION ac JOIN PAYMENT p ON ac.payment_id=p.id WHERE $eligible AND ac.reversed=0";$moneyParams=[];$moneyLabel='Verified commission';}
    elseif(in_array($role,['general','tourist'],true)) {$money="SELECT p.verified_at,p.amount FROM PAYMENT p WHERE p.payer_id=? AND $eligible";$moneyParams=[$uid];$moneyLabel='Cash paid';}
    else {$money="SELECT p.verified_at,s.net_amount AS amount FROM PAYMENT_SHARE s JOIN PAYMENT p ON s.payment_id=p.id WHERE s.payee_id=? AND $eligible";$moneyParams=[$uid];$moneyLabel='Allocated net proceeds';}
    $stmt=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM ($money) m");$stmt->execute($moneyParams);$moneyTotal=$stmt->fetchColumn();
    $stmt=$pdo->prepare("SELECT DATE_FORMAT(verified_at,'%Y-%m') AS month,SUM(amount) AS value FROM ($money) m WHERE verified_at>=? GROUP BY month ORDER BY month");$stmt->execute(array_merge($moneyParams,[$start->format('Y-m-d')]));$moneySeries=$stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $series=[];for($i=0;$i<6;$i++){$month=$start->modify("+$i months")->format('Y-m');$series[]=['month'=>$month,'activity'=>(int)($activitySeries[$month] ?? 0),'money'=>(float)($moneySeries[$month] ?? 0)];}
    return compact('user','attention','activityLabel','activityTotals','moneyLabel','moneyTotal','series');
}
