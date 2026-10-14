<?php
return function(PDO $pdo): void {
    $pdo->exec('CREATE TABLE IF NOT EXISTS CONSULTATION_READ (consultation_id INT NOT NULL,user_id INT NOT NULL,last_message_id INT NOT NULL DEFAULT 0,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY(consultation_id,user_id),FOREIGN KEY(consultation_id) REFERENCES CONSULTATION(id) ON DELETE CASCADE,FOREIGN KEY(user_id) REFERENCES USER(id) ON DELETE CASCADE)');
    foreach([['CONSULTATION_MESSAGE','idx_chat_unread','consultation_id,sender_id,id'],['PAYMENT','idx_payee_collection','payee_id,status,verified_at'],['PAYMENT','idx_verified_date','verified_at,status'],['PRODUCT','idx_public_listing','status,created_at'],['FARM_TOUR','idx_public_tour','status,created_at']] as [$table,$name,$columns]) {
        $stmt=$pdo->prepare('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?');$stmt->execute([$table,$name]);
        if(!$stmt->fetchColumn()) $pdo->exec("ALTER TABLE `$table` ADD INDEX `$name` ($columns)");
    }
};
