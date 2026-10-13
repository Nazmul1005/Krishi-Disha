<?php
require_once __DIR__.'/includes/auth_check.php';
$id=$_GET['id'] ?? '';
if(!is_string($id) || !preg_match('/^[a-f0-9]{32}$/D',$id)) { http_response_code(404);exit; }
$stmt=$pdo->prepare('SELECT * FROM MEDIA WHERE id=?');$stmt->execute([$id]);$media=$stmt->fetch();
if(!$media || ($media['status']!=='approved' && (!isLoggedIn() || ((int)$media['owner_id']!==(int)$_SESSION['user_id'] && currentRole()!=='admin')))) { http_response_code(404);exit; }
$file=STORAGE_PATH.'/media/'.$id.'.jpg';if(!is_file($file)) { http_response_code(404);exit; }
header('Content-Type: image/jpeg');header('X-Content-Type-Options: nosniff');header('Cache-Control: '.($media['status']==='approved'?'public, max-age=86400':'private, no-store'));readfile($file);
