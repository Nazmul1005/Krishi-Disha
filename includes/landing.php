<?php
/** Only published records from approved, verified providers are public discovery. */
function landingData(PDO $pdo): array {
    $counts=$pdo->query("SELECT (SELECT COUNT(*) FROM PRODUCT p JOIN FARMER f ON p.farmer_id=f.id JOIN USER u ON f.user_id=u.id WHERE p.status='available' AND p.quantity_kg>0 AND u.status='approved' AND u.email_verified=1) AS produce, (SELECT COUNT(*) FROM FARM_TOUR ft JOIN FARMER f ON ft.farmer_id=f.id JOIN USER u ON f.user_id=u.id WHERE ft.status='active' AND u.status='approved' AND u.email_verified=1) AS tours, (SELECT COUNT(*) FROM CROP WHERE review_status='reviewed') AS crops, (SELECT COUNT(*) FROM EXPERT e JOIN USER u ON e.user_id=u.id WHERE u.status='approved' AND u.email_verified=1 AND e.availability='available') AS experts")->fetch();
    $produce=$pdo->query("SELECT p.*,c.name AS crop_name,u.name AS seller FROM PRODUCT p JOIN CROP c ON p.crop_id=c.id JOIN FARMER f ON p.farmer_id=f.id JOIN USER u ON f.user_id=u.id WHERE p.status='available' AND p.quantity_kg>0 AND u.status='approved' AND u.email_verified=1 ORDER BY p.created_at DESC,p.id DESC LIMIT 4")->fetchAll();
    $tours=$pdo->query("SELECT ft.*,u.name AS host FROM FARM_TOUR ft JOIN FARMER f ON ft.farmer_id=f.id JOIN USER u ON f.user_id=u.id WHERE ft.status='active' AND u.status='approved' AND u.email_verified=1 ORDER BY ft.created_at DESC,ft.id DESC LIMIT 3")->fetchAll();
    return ['counts'=>array_map('intval',$counts),'produce'=>$produce,'tours'=>$tours];
}
