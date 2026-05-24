<?php
session_start();

function requireAuth(array $allowed_roles = []) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /KrishiDisha/auth/login.php');
        exit;
    }
    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        header('Location: /KrishiDisha/index.php?error=unauthorized');
        exit;
    }
    if ($_SESSION['status'] !== 'approved' && $_SESSION['role'] !== 'admin') {
        header('Location: /KrishiDisha/auth/login.php?error=pending');
        exit;
    }
}

function redirectToDashboard() {
    $role = $_SESSION['role'] ?? '';
    $map = [
        'admin'   => '/KrishiDisha/admin/dashboard.php',
        'farmer'  => '/KrishiDisha/farmer/dashboard.php',
        'dealer'  => '/KrishiDisha/dealer/dashboard.php',
        'tourist' => '/KrishiDisha/tourist/dashboard.php',
        'cook'    => '/KrishiDisha/cook/dashboard.php',
        'expert'  => '/KrishiDisha/expert/dashboard.php',
        'guide'   => '/KrishiDisha/guide/dashboard.php',
        'general' => '/KrishiDisha/user/dashboard.php',
    ];
    header('Location: ' . ($map[$role] ?? '/KrishiDisha/index.php'));
    exit;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentRole(): string {
    return $_SESSION['role'] ?? '';
}
