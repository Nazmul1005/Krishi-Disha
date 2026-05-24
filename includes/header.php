<?php
$page_title = $page_title ?? 'KrishiDisha';
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="KrishiDisha - Bangladesh's premier agricultural intelligence platform connecting farmers, dealers, tourists and experts.">
    <title><?= htmlspecialchars($page_title) ?> | KrishiDisha</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
