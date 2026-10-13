<?php
$page_title = $page_title ?? 'KrishiDisha';
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="<?= e($_SESSION['language'] ?? 'bn') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="KrishiDisha - Bangladesh's premier agricultural intelligence platform connecting farmers, dealers, tourists and experts.">
    <title><?= htmlspecialchars($page_title) ?> | KrishiDisha</title>
    <link rel="stylesheet" href="/KrishiDisha/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/KrishiDisha/assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/KrishiDisha/assets/css/style.css">
</head>
<?php
// Define 8 distinct color themes for the 8 roles
$role_themes = [
    'admin'   => ['p' => '#dc2626', 'pl' => '#f87171', 'pd' => '#991b1b'], // Red
    'farmer'  => ['p' => '#2d6a4f', 'pl' => '#52b788', 'pd' => '#1b4332'], // Green
    'dealer'  => ['p' => '#2563eb', 'pl' => '#60a5fa', 'pd' => '#1e3a8a'], // Blue
    'tourist' => ['p' => '#0891b2', 'pl' => '#22d3ee', 'pd' => '#164e63'], // Cyan
    'cook'    => ['p' => '#ea580c', 'pl' => '#fb923c', 'pd' => '#9a3412'], // Orange
    'expert'  => ['p' => '#7c3aed', 'pl' => '#a78bfa', 'pd' => '#4c1d95'], // Purple
    'guide'   => ['p' => '#d97706', 'pl' => '#fbbf24', 'pd' => '#92400e'], // Amber
    'general' => ['p' => '#475569', 'pl' => '#94a3b8', 'pd' => '#1e293b'], // Slate
];

$session_role = $_GET['role'] ?? $_SESSION['role'] ?? '';
$theme_style = "";
if (isset($role_themes[$session_role])) {
    $t = $role_themes[$session_role];
    $theme_style = "style=\"--primary: {$t['p']}; --primary-light: {$t['pl']}; --primary-dark: {$t['pd']};\"";
}
?>
<body <?= $theme_style ?>>
<a class="skip-link" href="#main-content">মূল বিষয়ে যান / Skip to content</a>
<form method="POST" action="/KrishiDisha/auth/language.php" class="language-switch"><?= csrfField() ?><input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI']) ?>"><label for="language">Language / ভাষা</label><select id="language" name="language"><option value="bn" <?= ($_SESSION['language'] ?? 'bn')==='bn'?'selected':'' ?>>বাংলা</option><option value="en" <?= ($_SESSION['language'] ?? '')==='en'?'selected':'' ?>>English</option></select><button type="submit">Apply / চালু</button></form>
