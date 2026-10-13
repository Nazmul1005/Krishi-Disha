<?php
if(PHP_SAPI!=='cli') exit(1);
require_once __DIR__.'/../config/db.php';
$email=$argv[1] ?? '';
if(!filter_var($email,FILTER_VALIDATE_EMAIL)) {fwrite(STDERR,"Usage: php bin/create-admin.php admin@example.com\n");exit(1);}
$password=bin2hex(random_bytes(18));
$pdo->prepare("INSERT INTO USER(name,email,password_hash,role,status,email_verified) VALUES ('Administrator',?,?,'admin','approved',1)")->execute([$email,password_hash($password,PASSWORD_DEFAULT)]);
fwrite(STDOUT,"Administrator created for $email. Temporary password: $password\nUse an authenticator app to enroll two-factor authentication on first login.\nChange the password after signing in.\n");
