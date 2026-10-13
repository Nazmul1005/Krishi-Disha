<?php
return function(PDO $pdo): void {
    // Only the public, unchanged demonstration hash is disabled; real passwords are preserved.
    $pdo->prepare("UPDATE USER SET status='suspended',auth_version=auth_version+1 WHERE password_hash=?")->execute(['$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi']);
};
