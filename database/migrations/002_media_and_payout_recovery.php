<?php
return function(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS MEDIA (id CHAR(32) PRIMARY KEY,owner_id INT NOT NULL,mime VARCHAR(50) NOT NULL,status ENUM('pending','approved') NOT NULL DEFAULT 'pending',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(owner_id) REFERENCES USER(id))");
    foreach(['recovered_at'=>'DATETIME NULL','recovery_reference'=>'VARCHAR(150) NULL'] as $name=>$definition) {
        $stmt=$pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='PAYMENT_SHARE' AND COLUMN_NAME=?");$stmt->execute([$name]);
        if(!$stmt->fetchColumn()) $pdo->exec("ALTER TABLE PAYMENT_SHARE ADD COLUMN `$name` $definition");
    }
};
