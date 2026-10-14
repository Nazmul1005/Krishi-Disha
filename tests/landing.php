<?php
require_once __DIR__.'/../includes/landing.php';
$home=landingData($pdo);
check($home['counts']['crops']===0,'Unreviewed crops are absent from landing reference counts');
check(count($home['produce'])<=4 && count($home['tours'])<=3,'Landing features use bounded real listing queries');
$pdo->prepare("UPDATE USER SET status='suspended' WHERE id=?")->execute([$users['farmer']]);
$hidden=landingData($pdo);
check($hidden['counts']['produce']===0 && !$hidden['produce'] && !$hidden['tours'],'Suspended farmers disappear from landing counts and featured records');
$pdo->prepare("UPDATE USER SET status='approved' WHERE id=?")->execute([$users['farmer']]);
$pdo->prepare('UPDATE USER SET email_verified=0 WHERE id=?')->execute([$users['farmer']]);
check(!landingData($pdo)['produce'],'Unverified sellers do not appear in featured produce');
$pdo->prepare('UPDATE USER SET email_verified=1 WHERE id=?')->execute([$users['farmer']]);
check(dashboardUrl('general')==='/KrishiDisha/user/dashboard.php','General account dashboard route maps to user directory');
