<?php
$path=rawurldecode((string)parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH));
if($path==='/') { header('Location: /KrishiDisha/');exit; }
$relative=preg_replace('#^/KrishiDisha/#','',$path);
if($relative==='') $relative='index.php';
if(!preg_match('#^(?:index|health|media)\.php$|^(?:auth|admin|farmer|dealer|tourist|cook|expert|guide|user|modules)/[a-z_]+\.php$#D',$relative)) { http_response_code(404);exit; }
$file=dirname(__DIR__).'/'.$relative;
if(!is_file($file)) { http_response_code(404);exit; }
$_SERVER['PHP_SELF']='/KrishiDisha/'.$relative;
require $file;
