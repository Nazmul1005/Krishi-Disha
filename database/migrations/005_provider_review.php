<?php
return function(PDO $pdo): void {
    foreach(['application_details'=>'TEXT NULL','approval_notes'=>'TEXT NULL','approved_by'=>'INT NULL','approved_at'=>'DATETIME NULL'] as $name=>$definition) {
        $stmt=$pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='USER' AND COLUMN_NAME=?");$stmt->execute([$name]);
        if(!$stmt->fetchColumn()) $pdo->exec("ALTER TABLE USER ADD COLUMN `$name` $definition");
    }
};
