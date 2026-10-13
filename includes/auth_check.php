<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/db.php';

if (PHP_SAPI !== 'cli') {
    header('Cache-Control: no-store');
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET','POST','HEAD'], true)) { http_response_code(405); exit; }
    foreach (['delete','approve','reject','settle','toggle','suspend','delete_crop','delete_disease','delete_tour','delete_product','approve_product'] as $mutation) {
        if (isset($_GET[$mutation])) { http_response_code(405); die('Use the protected action form.'); }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') verifyCsrf();
    if (isset($_SESSION['user_id'])) {
        $check=$pdo->prepare('SELECT id,name,role,status,email_verified,auth_version FROM USER WHERE id=?');
        $check->execute([$_SESSION['user_id']]); $account=$check->fetch();
        if (!$account || $account['status'] !== 'approved' || !(int)$account['email_verified'] || (int)$account['auth_version'] !== (int)($_SESSION['auth_version'] ?? -1) || time() - (int)($_SESSION['last_seen'] ?? 0) > 1800 || time() - (int)($_SESSION['login_at'] ?? 0) > 43200) {
            destroyLogin();
        } else {
            $_SESSION['role']=$account['role']; $_SESSION['status']=$account['status']; $_SESSION['name']=$account['name']; $_SESSION['last_seen']=time();
        }
    }
}
function establishLogin(array $user): void {
    session_regenerate_id(true);
    $language=$_SESSION['language'] ?? 'bn';
    $_SESSION=['language'=>$language,'user_id'=>(int)$user['id'], 'role'=>$user['role'], 'name'=>$user['name'], 'status'=>$user['status'], 'auth_version'=>(int)$user['auth_version'], 'login_at'=>time(), 'last_seen'=>time()];
}


function requireAuth(array $allowed_roles = []) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /KrishiDisha/auth/login.php');
        exit;
    }
    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        header('Location: /KrishiDisha/index.php?error=unauthorized');
        exit;
    }
    if ($_SESSION['status'] !== 'approved') {
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
