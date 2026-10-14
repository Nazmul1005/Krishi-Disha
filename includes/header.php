<?php
require_once __DIR__.'/navigation.php';
$page_title=$page_title ?? 'KrishiDisha';
$path=(string)parse_url($_SERVER['REQUEST_URI'] ?? '',PHP_URL_PATH);
$roleLayout=isLoggedIn() && !str_starts_with($path,'/KrishiDisha/auth/') && !in_array($path,['/KrishiDisha/','/KrishiDisha/index.php','/'],true) && !in_array(basename($path),['about.php','contact.php','terms.php','privacy.php'],true);
?>
<!DOCTYPE html>
<html lang="<?= e($_SESSION['language'] ?? 'bn') ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?= e($page_description ?? 'Buy and sell produce, explore farm visits and read reviewed agricultural references in Bangladesh.') ?>">
<title><?= e($page_title) ?> | KrishiDisha</title>
<link rel="stylesheet" href="<?= e(assetUrl('assets/vendor/bootstrap/bootstrap.min.css')) ?>">
<link rel="stylesheet" href="<?= e(assetUrl('assets/vendor/fontawesome/css/all.min.css')) ?>">
<link rel="stylesheet" href="<?= e(assetUrl('assets/fonts/fonts.css')) ?>">
<link rel="stylesheet" href="<?= e(assetUrl('assets/css/style.css')) ?>">
</head>
<body class="<?= $roleLayout?'role-layout':'public-layout' ?>">
<a class="skip-link" href="#main-content"><?= t('Skip to content','মূল বিষয়ে যান') ?></a>
<?php include __DIR__.'/public_nav.php'; ?>
