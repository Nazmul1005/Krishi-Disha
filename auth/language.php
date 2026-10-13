<?php
require_once __DIR__.'/../includes/auth_check.php';
if($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405);exit; }
$language=$_POST['language'] ?? '';if(!in_array($language,['bn','en'],true)) {http_response_code(400);exit;}
$_SESSION['language']=$language;
$back=$_POST['return_to'] ?? '/KrishiDisha/';
if(!is_string($back) || !str_starts_with($back,'/KrishiDisha/') || str_contains($back,'\\') || preg_match('/[\r\n]/',$back)) $back='/KrishiDisha/';
header('Location: '.$back);
