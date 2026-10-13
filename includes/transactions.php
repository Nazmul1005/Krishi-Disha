<?php
require_once __DIR__.'/security.php';

function cents(string $decimal): int { $parts=explode('.',$decimal);return (int)$parts[0]*100+(int)str_pad($parts[1] ?? '',2,'0'); }
function decimal(int $value): string { return intdiv($value,100).'.'.str_pad((string)($value%100),2,'0',STR_PAD_LEFT); }
function totalFor(string $quantity,string $price): string { return decimal(intdiv(cents($quantity)*cents($price)+50,100)); }
function notify(PDO $pdo,int $user,string $message,string $link='/KrishiDisha/modules/orders.php'): void {
    $pdo->prepare('INSERT INTO NOTIFICATION(user_id,message,link) VALUES (?,?,?)')->execute([$user,$message,$link]);
}
function idempotent(PDO $pdo,int $uid,string $key,string $operation,array $payload,callable $work): int {
    if(!preg_match('/^[a-f0-9]{32}$/D',$key)) throw new InvalidArgumentException('Invalid request key.');
    return transaction($pdo,function()use($pdo,$uid,$key,$operation,$payload,$work) {
        $stmt=$pdo->prepare("SELECT id FROM USER WHERE id=? AND status='approved' AND email_verified=1 FOR UPDATE");$stmt->execute([$uid]);
        if(!$stmt->fetchColumn()) throw new InvalidArgumentException('Account is not approved and verified.');
        $hash=hash('sha256',json_encode($payload,JSON_THROW_ON_ERROR));
        $stmt=$pdo->prepare('SELECT * FROM REQUEST_RECORD WHERE user_id=? AND request_key=?');$stmt->execute([$uid,$key]);$old=$stmt->fetch();
        if($old) { if($old['operation']!==$operation || !hash_equals($old['fingerprint'],$hash)) throw new InvalidArgumentException('This form was already used for a different request.');return (int)$old['result_id']; }
        $id=$work();$pdo->prepare('INSERT INTO REQUEST_RECORD(user_id,request_key,operation,fingerprint,result_id) VALUES (?,?,?,?,?)')->execute([$uid,$key,$operation,$hash,$id]);return $id;
    });
}
function pendingPayment(PDO $pdo,int $payer,int $seller,string $ref,int $id,string $amount,array $shares=[]): int {
    if(cents($amount)<=0 || cents($amount)>9999999999) throw new InvalidArgumentException('A positive payment amount is required.');
    $pdo->prepare("INSERT INTO PAYMENT(payer_id,payee_id,ref_type,ref_id,amount,method,status,paid_at) VALUES (?,?,?,?,?,'cash','pending',NULL)")->execute([$payer,$seller,$ref,$id,$amount]);
    $pid=(int)$pdo->lastInsertId();$pdo->prepare('INSERT INTO PAYMENT_REFERENCE(ref_type,ref_id,payment_id) VALUES (?,?,?)')->execute([$ref,$id,$pid]);
    $shares=$shares ?: [$seller=>$amount];
    foreach($shares as $payee=>$gross) {
        $commission=intdiv(cents((string)$gross)*5+50,100);
        $pdo->prepare('INSERT INTO PAYMENT_SHARE(payment_id,payee_id,gross_amount,net_amount) VALUES (?,?,?,?)')->execute([$pid,$payee,$gross,decimal(cents((string)$gross)-$commission)]);
    }
    return $pid;
}
function orderProduce(PDO $pdo,int $uid,string $key,int $source,string $quantity,bool $dealer,array $delivery): int {
    $qty=positiveDecimal($quantity);
    $fulfillment=$delivery['fulfillment'] ?? 'pickup';
    if(!in_array($fulfillment,['pickup','delivery'],true)) throw new InvalidArgumentException('Invalid fulfillment method.');
    $address=textInput($delivery['delivery_address'] ?? '',500);
    $phone=textInput($delivery['contact_phone'] ?? '',20);
    if(!preg_match('/^\+?[0-9 -]{7,20}$/D',$phone)) throw new InvalidArgumentException('Enter a valid contact phone.');
    return idempotent($pdo,$uid,$key,'order',[$source,$qty,$dealer,$fulfillment,$address,$phone],function()use($pdo,$uid,$source,$qty,$dealer,$fulfillment,$address,$phone) {
        if($dealer) {
            $stmt=$pdo->prepare("SELECT di.*,d.user_id AS seller,u.status AS seller_status,p.minimum_order_kg FROM DEALER_INVENTORY di JOIN DEALER d ON di.dealer_id=d.id JOIN USER u ON d.user_id=u.id JOIN PRODUCT p ON di.product_id=p.id WHERE di.id=? FOR UPDATE");$stmt->execute([$source]);$item=$stmt->fetch();
            if(!$item || $item['seller_status']!=='approved' || cents($qty)>cents((string)$item['stock_remaining']) || cents($qty)<cents((string)$item['minimum_order_kg'])) throw new InvalidArgumentException('Quantity unavailable or below the minimum order.');
            $product=(int)$item['product_id'];$price=(string)$item['markup_price'];$inventory=$source;
            $pdo->prepare('UPDATE DEALER_INVENTORY SET stock_remaining=stock_remaining-? WHERE id=? AND stock_remaining>=?')->execute([$qty,$source,$qty]);
        } else {
            $stmt=$pdo->prepare('SELECT p.*,f.user_id AS seller,u.status AS seller_status FROM PRODUCT p JOIN FARMER f ON p.farmer_id=f.id JOIN USER u ON f.user_id=u.id WHERE p.id=? FOR UPDATE');$stmt->execute([$source]);$item=$stmt->fetch();
            if(!$item || $item['seller_status']!=='approved' || $item['status']!=='available' || cents($qty)>cents((string)$item['quantity_kg']) || cents($qty)<cents((string)$item['minimum_order_kg'])) throw new InvalidArgumentException('Quantity unavailable or below the minimum order.');
            $product=$source;$price=(string)$item['price_per_kg'];$inventory=null;
            $pdo->prepare("UPDATE PRODUCT SET quantity_kg=quantity_kg-?,status=IF(quantity_kg=0,'sold',status) WHERE id=? AND quantity_kg>=?")->execute([$qty,$source,$qty]);
        }
        if((int)$item['seller']===$uid) throw new InvalidArgumentException('You cannot buy your own listing.');
        $total=totalFor($qty,$price);
        $pdo->prepare('INSERT INTO `ORDER`(user_id,product_id,dealer_inventory_id,quantity_kg,total_price,fulfillment,delivery_address,contact_phone) VALUES (?,?,?,?,?,?,?,?)')->execute([$uid,$product,$inventory,$qty,$total,$fulfillment,$address,$phone]);
        $id=(int)$pdo->lastInsertId();pendingPayment($pdo,$uid,(int)$item['seller'],'order',$id,$total);
        notify($pdo,(int)$item['seller'],"New produce order #$id");audit($pdo,'order.created','ORDER',$id);return $id;
    });
}
function procure(PDO $pdo,int $uid,string $key,int $product,string $quantity,string $markup): int {
    $qty=positiveDecimal($quantity);$price=positiveDecimal($markup,100000);
    return idempotent($pdo,$uid,$key,'procurement',[$product,$qty,$price],function()use($pdo,$uid,$product,$qty,$price) {
        $stmt=$pdo->prepare('SELECT id FROM DEALER WHERE user_id=?');$stmt->execute([$uid]);$dealer=$stmt->fetchColumn();
        if(!$dealer) throw new InvalidArgumentException('Only approved dealers can procure stock.');
        $stmt=$pdo->prepare('SELECT p.*,f.user_id AS seller,u.status AS seller_status FROM PRODUCT p JOIN FARMER f ON p.farmer_id=f.id JOIN USER u ON f.user_id=u.id WHERE p.id=? FOR UPDATE');$stmt->execute([$product]);$p=$stmt->fetch();
        if(!$p || $p['seller_status']!=='approved' || $p['status']!=='available' || cents($qty)>cents((string)$p['quantity_kg']) || cents($qty)<cents((string)$p['minimum_order_kg'])) throw new InvalidArgumentException('Stock is unavailable.');
        $pdo->prepare("UPDATE PRODUCT SET quantity_kg=quantity_kg-?,status=IF(quantity_kg=0,'sold',status) WHERE id=?")->execute([$qty,$product]);
        $pdo->prepare('INSERT INTO DEALER_INVENTORY(dealer_id,product_id,quantity_purchased,purchase_price,markup_price,stock_remaining) VALUES (?,?,?,?,?,?)')->execute([$dealer,$product,$qty,$p['price_per_kg'],$price,$qty]);$inventory=(int)$pdo->lastInsertId();
        $total=totalFor($qty,(string)$p['price_per_kg']);
        $pdo->prepare('INSERT INTO PROCUREMENT(dealer_id,product_id,inventory_id,quantity_kg,total_price) VALUES (?,?,?,?,?)')->execute([$dealer,$product,$inventory,$qty,$total]);$id=(int)$pdo->lastInsertId();
        pendingPayment($pdo,$uid,(int)$p['seller'],'procurement',$id,$total);notify($pdo,(int)$p['seller'],"Dealer procurement #$id awaits cash collection",'/KrishiDisha/modules/payments.php');audit($pdo,'procurement.created','PROCUREMENT',$id);return $id;
    });
}
function bookTour(PDO $pdo,int $uid,string $key,array $input): int {
    $start=futureDate($input['start_date'] ?? '');$end=futureDate($input['end_date'] ?? '');
    $days=(int)(new DateTimeImmutable($start))->diff(new DateTimeImmutable($end))->format('%r%a')+1;
    $visitors=filter_var($input['num_visitors'] ?? 1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000]]);
    if($days<1 || $days>30 || $visitors===false) throw new InvalidArgumentException('Choose a valid booking of up to 30 days.');
    $tour=(int)($input['tour_id'] ?? 0);$guide=(int)($input['guide_id'] ?? 0);
    return idempotent($pdo,$uid,$key,'tour',[$tour,$guide,$start,$end,$visitors],function()use($pdo,$uid,$tour,$guide,$start,$end,$days,$visitors) {
        $stmt=$pdo->prepare('SELECT id FROM TOURIST WHERE user_id=?');$stmt->execute([$uid]);$tourist=$stmt->fetchColumn();if(!$tourist) throw new InvalidArgumentException('A tourist account is required.');
        $stmt=$pdo->prepare('SELECT ft.*,f.user_id AS seller,u.status AS seller_status FROM FARM_TOUR ft JOIN FARMER f ON ft.farmer_id=f.id JOIN USER u ON f.user_id=u.id WHERE ft.id=? FOR UPDATE');$stmt->execute([$tour]);$ft=$stmt->fetch();
        if(!$ft || $ft['status']!=='active' || $ft['seller_status']!=='approved' || $visitors>(int)$ft['capacity']) throw new InvalidArgumentException('Tour is unavailable or exceeds capacity.');
        // Sum visitors separately for every day, preventing overlap overbooking without penalizing disjoint bookings.
        $capacity=$pdo->prepare("SELECT COALESCE(SUM(num_visitors),0) FROM TOUR_BOOKING WHERE tour_id=? AND status IN ('pending','confirmed','completed') AND start_date<=? AND end_date>=?");
        for($date=new DateTimeImmutable($start);$date->format('Y-m-d')<=$end;$date=$date->modify('+1 day')) { $day=$date->format('Y-m-d');$capacity->execute([$tour,$day,$day]);if((int)$capacity->fetchColumn()+$visitors>(int)$ft['capacity']) throw new InvalidArgumentException('The tour is full on one of these dates.'); }
        $hostAmount=decimal(cents(positiveDecimal((string)$ft['price_per_day']))*$days);$guideAmount='0.00';$shares=[(int)$ft['seller']=>$hostAmount];
        if($guide) {
            $stmt=$pdo->prepare("SELECT g.*,u.status FROM GUIDE g JOIN USER u ON g.user_id=u.id WHERE g.id=? FOR UPDATE");$stmt->execute([$guide]);$g=$stmt->fetch();
            if(!$g || $g['status']!=='approved' || $g['availability']!=='available') throw new InvalidArgumentException('Guide is unavailable.');
            $stmt=$pdo->prepare("SELECT id FROM TOUR_BOOKING WHERE guide_id=? AND status IN ('pending','confirmed','completed') AND start_date<=? AND end_date>=? LIMIT 1");$stmt->execute([$guide,$end,$start]);
            if($stmt->fetchColumn()) throw new InvalidArgumentException('Guide already has a booking for these dates.');
            $conflict=$pdo->prepare("SELECT id FROM CONSULTATION WHERE provider_id=? AND status IN ('pending','confirmed') AND (scheduled_at IS NULL OR (scheduled_at < DATE_ADD(?, INTERVAL 1 DAY) AND TIMESTAMPADD(SECOND,ROUND(duration_hours*3600),scheduled_at)>?)) LIMIT 1");$conflict->execute([$g['user_id'],$end,$start]);if($conflict->fetchColumn()) throw new InvalidArgumentException('Guide has a consultation during the tour dates.');
            $guideAmount=decimal(cents(positiveDecimal((string)$g['daily_rate']))*$days);$shares[(int)$g['user_id']]=$guideAmount;
        }
        $total=decimal(cents($hostAmount)+cents($guideAmount));
        $pdo->prepare('INSERT INTO TOUR_BOOKING(tourist_id,tour_id,guide_id,start_date,end_date,num_visitors,total_price,guide_fee) VALUES (?,?,?,?,?,?,?,?)')->execute([$tourist,$tour,$guide ?: null,$start,$end,$visitors,$total,$guideAmount]);$id=(int)$pdo->lastInsertId();
        pendingPayment($pdo,$uid,(int)$ft['seller'],'tour_booking',$id,$total,$shares);notify($pdo,(int)$ft['seller'],"New tour booking #$id",'/KrishiDisha/farmer/bookings.php');audit($pdo,'tour.booked','TOUR_BOOKING',$id);return $id;
    });
}
function bookConsultation(PDO $pdo,int $uid,string $key,array $input): int {
    $provider=(int)($input['provider_id'] ?? 0);$topic=textInput($input['topic'] ?? '',255);$duration=positiveDecimal($input['duration_hours'] ?? '1',8);
    $raw=$input['scheduled_at'] ?? '';if(!is_string($raw)) throw new InvalidArgumentException('Choose an appointment time.');
    $time=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$raw);
    if(!$time || $time->format('Y-m-d\TH:i')!==$raw || $time<=new DateTimeImmutable() || $time>new DateTimeImmutable('+180 days') || cents($duration)%25!==0) throw new InvalidArgumentException('Choose a future time and a duration in 15-minute steps, up to 8 hours.');
    return idempotent($pdo,$uid,$key,'consultation',[$provider,$topic,$duration,$raw],function()use($pdo,$uid,$provider,$topic,$duration,$time) {
        $stmt=$pdo->prepare("SELECT u.id,u.role,u.status,e.hourly_rate,e.availability AS expert_available,g.daily_rate,g.availability AS guide_available FROM USER u LEFT JOIN EXPERT e ON e.user_id=u.id LEFT JOIN GUIDE g ON g.user_id=u.id WHERE u.id=? FOR UPDATE");$stmt->execute([$provider]);$p=$stmt->fetch();
        if(!$p || $p['status']!=='approved' || !in_array($p['role'],['expert','guide'],true) || ($p['role']==='expert' ? $p['expert_available'] : $p['guide_available'])!=='available' || $provider===$uid) throw new InvalidArgumentException('Choose an available approved expert or guide.');
        $rate=$p['role']==='expert' ? positiveDecimal((string)$p['hourly_rate']) : decimal(intdiv(cents(positiveDecimal((string)$p['daily_rate']))+4,8));
        $start=$time->format('Y-m-d H:i:s');$end=$time->modify('+'.(cents($duration)*36).' seconds')->format('Y-m-d H:i:s');
        $stmt=$pdo->prepare("SELECT id FROM CONSULTATION WHERE provider_id=? AND status IN ('pending','confirmed') AND (scheduled_at IS NULL OR (scheduled_at < ? AND TIMESTAMPADD(SECOND,ROUND(duration_hours*3600),scheduled_at)>?)) LIMIT 1");$stmt->execute([$provider,$end,$start]);if($stmt->fetchColumn()) throw new InvalidArgumentException('Provider has another appointment or an unresolved legacy booking.');
        $stmt=$pdo->prepare("SELECT tb.id FROM TOUR_BOOKING tb JOIN GUIDE g ON tb.guide_id=g.id WHERE g.user_id=? AND tb.status IN ('pending','confirmed','completed') AND tb.start_date<=? AND tb.end_date>=? LIMIT 1");$stmt->execute([$provider,substr($end,0,10),substr($start,0,10)]);if($stmt->fetchColumn()) throw new InvalidArgumentException('Guide is committed to a tour on this date.');
        $fee=totalFor($duration,$rate);$pdo->prepare('INSERT INTO CONSULTATION(client_id,provider_id,scheduled_date,scheduled_at,duration_hours,topic,fee) VALUES (?,?,?,?,?,?,?)')->execute([$uid,$provider,$time->format('Y-m-d'),$start,$duration,$topic,$fee]);$id=(int)$pdo->lastInsertId();
        pendingPayment($pdo,$uid,$provider,'consultation',$id,$fee);notify($pdo,$provider,"New consultation #$id",'/KrishiDisha/'.($p['role']==='expert'?'expert':'guide').'/sessions.php');audit($pdo,'consultation.booked','CONSULTATION',$id);return $id;
    });
}
function orderFood(PDO $pdo,int $uid,string $key,array $input): int {
    $recipe=(int)($input['recipe_id'] ?? 0);$cook=(int)($input['cook_id'] ?? 0);$date=futureDate($input['delivery_date'] ?? '');
    $qty=filter_var($input['quantity'] ?? 0,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>100]]);if($qty===false) throw new InvalidArgumentException('Enter a quantity from 1 to 100.');
    $address=textInput($input['delivery_address'] ?? '',500);$phone=textInput($input['contact_phone'] ?? '',20);
    return idempotent($pdo,$uid,$key,'food',[$recipe,$cook,$date,$qty,$address,$phone],function()use($pdo,$uid,$recipe,$cook,$date,$qty,$address,$phone) {
        $stmt=$pdo->prepare('SELECT id FROM TOURIST WHERE user_id=?');$stmt->execute([$uid]);$tourist=$stmt->fetchColumn();if(!$tourist) throw new InvalidArgumentException('A tourist account is required.');
        $stmt=$pdo->prepare('SELECT c.*,u.status FROM COOK c JOIN USER u ON c.user_id=u.id WHERE c.id=? FOR UPDATE');$stmt->execute([$cook]);$c=$stmt->fetch();
        if(!$c || $c['status']!=='approved' || $c['availability']!=='available') throw new InvalidArgumentException('Cook is unavailable.');
        $stmt=$pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM FOOD_ORDER WHERE cook_id=? AND delivery_date=? AND status IN ('pending','preparing','delivered')");$stmt->execute([$cook,$date]);if((int)$stmt->fetchColumn()+$qty>(int)$c['daily_capacity']) throw new InvalidArgumentException('Cook is fully booked on that date.');
        $stmt=$pdo->prepare('SELECT * FROM RECIPE WHERE id=? AND cook_id=? FOR UPDATE');$stmt->execute([$recipe,$cook]);$r=$stmt->fetch();if(!$r) throw new InvalidArgumentException('Select a recipe belonging to this cook.');
        $total=decimal(cents(positiveDecimal((string)$r['price']))*$qty);$pdo->prepare('INSERT INTO FOOD_ORDER(tourist_id,recipe_id,cook_id,quantity,total_price,delivery_date,delivery_address,contact_phone) VALUES (?,?,?,?,?,?,?,?)')->execute([$tourist,$recipe,$cook,$qty,$total,$date,$address,$phone]);$id=(int)$pdo->lastInsertId();
        pendingPayment($pdo,$uid,(int)$c['user_id'],'food_order',$id,$total);notify($pdo,(int)$c['user_id'],"New food order #$id",'/KrishiDisha/cook/orders.php');audit($pdo,'food.ordered','FOOD_ORDER',$id);return $id;
    });
}
function changeState(PDO $pdo,int $uid,string $ref,int $id,string $next): void {
    transaction($pdo,function()use($pdo,$uid,$ref,$id,$next) {
        $queries=[
            'order'=>['ORDER',"SELECT o.*,COALESCE(d.user_id,f.user_id) AS seller,o.user_id AS buyer FROM `ORDER` o JOIN PRODUCT p ON o.product_id=p.id JOIN FARMER f ON p.farmer_id=f.id LEFT JOIN DEALER_INVENTORY di ON o.dealer_inventory_id=di.id LEFT JOIN DEALER d ON di.dealer_id=d.id WHERE o.id=? FOR UPDATE",['pending'=>['confirmed','cancelled'],'confirmed'=>['delivered','cancelled']]],
            'tour_booking'=>['TOUR_BOOKING',"SELECT b.*,f.user_id AS seller,t.user_id AS buyer,g.user_id AS guide_user FROM TOUR_BOOKING b JOIN FARM_TOUR ft ON b.tour_id=ft.id JOIN FARMER f ON ft.farmer_id=f.id JOIN TOURIST t ON b.tourist_id=t.id LEFT JOIN GUIDE g ON b.guide_id=g.id WHERE b.id=? FOR UPDATE",['pending'=>['confirmed','cancelled'],'confirmed'=>['completed','cancelled']]],
            'food_order'=>['FOOD_ORDER',"SELECT b.*,c.user_id AS seller,t.user_id AS buyer FROM FOOD_ORDER b JOIN COOK c ON b.cook_id=c.id JOIN TOURIST t ON b.tourist_id=t.id WHERE b.id=? FOR UPDATE",['pending'=>['preparing','cancelled'],'preparing'=>['delivered','cancelled']]],
            'consultation'=>['CONSULTATION',"SELECT c.*,provider_id AS seller,client_id AS buyer FROM CONSULTATION c WHERE c.id=? FOR UPDATE",['pending'=>['confirmed','cancelled'],'confirmed'=>['completed','cancelled']]]
        ];
        if(!isset($queries[$ref])) throw new InvalidArgumentException('Invalid transaction type.');
        [$table,$query,$states]=$queries[$ref];$stmt=$pdo->prepare($query);$stmt->execute([$id]);$row=$stmt->fetch();
        if(!$row || !in_array($next,$states[$row['status']] ?? [],true)) throw new InvalidArgumentException('This status change is no longer available.');
        $seller=(int)$row['seller']===$uid;$buyer=(int)$row['buyer']===$uid;
        if(!$seller && !($buyer && $next==='cancelled' && $row['status']==='pending')) throw new InvalidArgumentException('You cannot change this transaction.');
        if($next==='completed' && $ref==='consultation' && (!$row['scheduled_at'] || strtotime($row['scheduled_at'])+round((float)$row['duration_hours']*3600)>time())) throw new InvalidArgumentException('A consultation can complete after its scheduled end.');
        if($next==='completed' && $ref==='tour_booking' && $row['end_date']>date('Y-m-d')) throw new InvalidArgumentException('Tour has not ended.');
        $pdo->prepare("UPDATE `$table` SET status=? WHERE id=?")->execute([$next,$id]);
        if($next==='cancelled') {
            if($ref==='order') {
                if($row['dealer_inventory_id']) $pdo->prepare('UPDATE DEALER_INVENTORY SET stock_remaining=stock_remaining+? WHERE id=?')->execute([$row['quantity_kg'],$row['dealer_inventory_id']]);
                else $pdo->prepare("UPDATE PRODUCT SET quantity_kg=quantity_kg+?,status=IF(status='sold','available',status) WHERE id=?")->execute([$row['quantity_kg'],$row['product_id']]);
            }
            $pdo->prepare("UPDATE PAYMENT SET status=IF(verified_at IS NULL,'failed',status),refund_requested_at=IF(verified_at IS NOT NULL,NOW(),NULL) WHERE ref_type=? AND ref_id=? AND status<>'refunded'")->execute([$ref,$id]);
        }
        notify($pdo,(int)$row['buyer'],"Transaction #$id is now $next");notify($pdo,(int)$row['seller'],"Transaction #$id is now $next");audit($pdo,'transaction.'.$next,$table,$id);
    });
}
function collectCash(PDO $pdo,int $uid,int $payment,string $amount,string $receipt): void {
    $amount=positiveDecimal($amount);$receipt=textInput($receipt,150);
    transaction($pdo,function()use($pdo,$uid,$payment,$amount,$receipt) {
        $stmt=$pdo->prepare('SELECT * FROM PAYMENT WHERE id=? FOR UPDATE');$stmt->execute([$payment]);$p=$stmt->fetch();
        $stmt=$pdo->prepare("SELECT role FROM USER WHERE id=? AND status='approved'");$stmt->execute([$uid]);$admin=$stmt->fetchColumn()==='admin';
        if(!$p || (!$admin && (int)$p['payee_id']!==$uid) || $p['verified_at'] || $p['refund_requested_at'] || !in_array($p['status'],['pending','completed'],true) || cents($amount)!==cents((string)$p['amount'])) throw new InvalidArgumentException('Collection is unavailable or the amount does not match.');
        if($p['ref_type']!=='procurement') {
            $table=['order'=>'ORDER','tour_booking'=>'TOUR_BOOKING','food_order'=>'FOOD_ORDER','consultation'=>'CONSULTATION'][$p['ref_type']];
            $stmt=$pdo->prepare("SELECT status FROM `$table` WHERE id=?");$stmt->execute([$p['ref_id']]);
            if(!in_array($stmt->fetchColumn(),['delivered','completed'],true)) throw new InvalidArgumentException('Complete delivery or the service before collecting cash.');
        }
        $pdo->prepare("UPDATE PAYMENT SET status='completed',method='cash',paid_at=NOW(),verified_at=NOW(),verified_by=?,receipt_reference=? WHERE id=?")->execute([$uid,$receipt,$payment]);
        $stmt=$pdo->prepare('SELECT COUNT(*) AS allocations,COALESCE(SUM(gross_amount-net_amount),0) AS commission FROM PAYMENT_SHARE WHERE payment_id=?');$stmt->execute([$payment]);$allocation=$stmt->fetch();$commission=$allocation['commission'];
        if((int)$allocation['allocations']===0) throw new InvalidArgumentException('This legacy payment needs an allocation review before collection.');
        $pdo->prepare('INSERT INTO ADMIN_COMMISSION(payment_id,commission_rate,commission_amount) VALUES (?,5.00,?) ON DUPLICATE KEY UPDATE commission_amount=VALUES(commission_amount),reversed=0')->execute([$payment,$commission]);
        if(!$admin) $pdo->prepare("UPDATE PAYMENT_SHARE SET paid_at=NOW(),payout_reference=? WHERE payment_id=? AND payee_id=? AND paid_at IS NULL")->execute(['retained cash: '.$receipt,$payment,$uid]);
        notify($pdo,(int)$p['payer_id'],'Cash collection confirmed for payment #'.$payment,'/KrishiDisha/modules/payments.php');audit($pdo,'cash.collected','PAYMENT',$payment,['amount'=>$amount,'receipt'=>$receipt]);
    });
}

function paymentOperation(PDO $pdo,int $uid,int $id,string $action,string $reference,int $shareId=0): void {
    $reference=textInput($reference,150);
    transaction($pdo,function()use($pdo,$uid,$id,$action,$reference,$shareId) {
        $stmt=$pdo->prepare('SELECT * FROM PAYMENT WHERE id=? FOR UPDATE');$stmt->execute([$id]);$p=$stmt->fetch();
        $stmt=$pdo->prepare("SELECT role FROM USER WHERE id=? AND status='approved'");$stmt->execute([$uid]);$admin=$stmt->fetchColumn()==='admin';
        if(!$p || !$p['verified_at']) throw new InvalidArgumentException('Payment is not verified.');
        if($action==='refund_request') {
            if(!$admin || $p['status']!=='completed' || $p['refund_requested_at']) throw new InvalidArgumentException('Refund review is unavailable.');
            $pdo->prepare('UPDATE PAYMENT SET refund_requested_at=NOW() WHERE id=?')->execute([$id]);
        } elseif($action==='payout') {
            if((!$admin && (int)$p['verified_by']!==$uid) || $p['status']!=='completed' || $p['refund_requested_at']) throw new InvalidArgumentException('Payout is unavailable.');
            $stmt=$pdo->prepare('UPDATE PAYMENT_SHARE SET paid_at=NOW(),payout_reference=? WHERE id=? AND payment_id=? AND paid_at IS NULL');$stmt->execute([$reference,$shareId,$id]);
            if($stmt->rowCount()!==1) throw new InvalidArgumentException('Payout already recorded or not found.');
        } elseif($action==='recover') {
            if(!$admin || !$p['refund_requested_at']) throw new InvalidArgumentException('Only an administrator can reconcile returned payout money.');
            $stmt=$pdo->prepare('UPDATE PAYMENT_SHARE SET recovered_at=NOW(),recovery_reference=? WHERE id=? AND payment_id=? AND paid_at IS NOT NULL AND recovered_at IS NULL');$stmt->execute([$reference,$shareId,$id]);
            if($stmt->rowCount()!==1) throw new InvalidArgumentException('Payout recovery already recorded or not found.');
        } elseif($action==='refund') {
            if(!$admin || !$p['refund_requested_at'] || $p['status']!=='completed') throw new InvalidArgumentException('Refund is not available.');
            $stmt=$pdo->prepare('SELECT COUNT(*) FROM PAYMENT_SHARE WHERE payment_id=? AND paid_at IS NOT NULL AND recovered_at IS NULL');$stmt->execute([$id]);
            if((int)$stmt->fetchColumn()>0) throw new InvalidArgumentException('Confirm returned seller payouts before recording the buyer refund.');
            $stmt=$pdo->prepare('SELECT settled FROM ADMIN_COMMISSION WHERE payment_id=?');$stmt->execute([$id]);$settled=(int)$stmt->fetchColumn();
            $pdo->prepare("UPDATE PAYMENT SET status='refunded',refunded_at=NOW(),refund_reference=? WHERE id=?")->execute([$reference,$id]);
            $pdo->prepare('UPDATE ADMIN_COMMISSION SET reversed=1 WHERE payment_id=?')->execute([$id]);
            notify($pdo,(int)$p['payer_id'],'Refund confirmed for payment #'.$id,'/KrishiDisha/modules/payments.php');
        } elseif($action==='commission') {
            if(!$admin || $p['status']!=='completed' || $p['refund_requested_at']) throw new InvalidArgumentException('Commission cannot be settled.');
            $stmt=$pdo->prepare('UPDATE ADMIN_COMMISSION SET settled=1,settled_at=NOW(),settlement_reference=? WHERE payment_id=? AND settled=0 AND reversed=0');$stmt->execute([$reference,$id]);
            if($stmt->rowCount()!==1) throw new InvalidArgumentException('Commission is already settled or unavailable.');
        } else throw new InvalidArgumentException('Unsupported payment action.');
        audit($pdo,'payment.'.$action,'PAYMENT',$id,['reference'=>$reference,'share_id'=>$shareId]);
    });
}
