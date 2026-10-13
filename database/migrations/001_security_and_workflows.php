<?php
return function(PDO $pdo): void {
    $column=function(string $table,string $name,string $definition) use($pdo): void {
        $stmt=$pdo->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');$stmt->execute([$table,$name]);
        if(!$stmt->fetchColumn()) $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$name` $definition");
    };
    foreach(['image'=>'VARCHAR(255) NULL'] as $name=>$definition) foreach(['PRODUCT','CROP','DISEASE','FARM_TOUR'] as $table) $column($table,$name,$definition);
    $column('USER','email_verified','TINYINT(1) NOT NULL DEFAULT 0');
    $column('USER','auth_version','INT NOT NULL DEFAULT 1');
    $column('USER','mfa_secret','TEXT NULL');
    $column('USER','mfa_last_counter','BIGINT NOT NULL DEFAULT -1');
    $column('USER','mfa_recovery','JSON NULL');
    $column('ORDER','dealer_inventory_id','INT NULL');
    $column('ORDER','delivery_address',"VARCHAR(500) NOT NULL DEFAULT ''");
    $column('ORDER','contact_phone',"VARCHAR(20) NOT NULL DEFAULT ''");
    $column('ORDER','fulfillment',"ENUM('pickup','delivery') NOT NULL DEFAULT 'pickup'");
    $column('RECIPE','price','DECIMAL(10,2) NOT NULL DEFAULT 300.00');
    $column('RECIPE','ingredients','TEXT NULL');
    $column('RECIPE','allergens','VARCHAR(255) NULL');
    $column('COOK','daily_capacity','INT NOT NULL DEFAULT 20');
    $column('PRODUCT','variety','VARCHAR(150) NULL');
    $column('PRODUCT','grade','VARCHAR(100) NULL');
    $column('PRODUCT','harvest_date','DATE NULL');
    $column('PRODUCT','pickup_location','VARCHAR(255) NULL');
    $column('PRODUCT','minimum_order_kg','DECIMAL(10,2) NOT NULL DEFAULT 0.50');
    $column('FARM_TOUR','safety_notes','TEXT NULL');
    $column('FARM_TOUR','cancellation_policy','TEXT NULL');
    $column('CONSULTATION','scheduled_at','DATETIME NULL');
    $column('FOOD_ORDER','delivery_address',"VARCHAR(500) NOT NULL DEFAULT ''");
    $column('FOOD_ORDER','contact_phone',"VARCHAR(20) NOT NULL DEFAULT ''");
    $column('TOUR_BOOKING','guide_fee','DECIMAL(10,2) NOT NULL DEFAULT 0.00');
    $pdo->exec("ALTER TABLE PRODUCT ALTER status SET DEFAULT 'pending'");
    $pdo->exec("ALTER TABLE FARM_TOUR MODIFY status ENUM('active','inactive','pending') DEFAULT 'pending'");
    $pdo->exec("ALTER TABLE PAYMENT MODIFY ref_type ENUM('order','procurement','tour_booking','food_order','consultation') NOT NULL");
    $pdo->exec('ALTER TABLE PAYMENT MODIFY paid_at TIMESTAMP NULL DEFAULT NULL');
    $column('PAYMENT','payee_id','INT NULL');
    $column('PAYMENT','verified_at','DATETIME NULL');
    $column('PAYMENT','verified_by','INT NULL');
    $column('PAYMENT','receipt_reference','VARCHAR(150) NULL');
    $column('PAYMENT','refund_requested_at','DATETIME NULL');
    $column('PAYMENT','refunded_at','DATETIME NULL');
    $column('PAYMENT','refund_reference','VARCHAR(150) NULL');
    $column('ADMIN_COMMISSION','reversed','TINYINT(1) NOT NULL DEFAULT 0');
    $column('ADMIN_COMMISSION','settlement_reference','VARCHAR(150) NULL');
    foreach(['CROP','DISEASE','REGION_CROP','CROP_VITAMIN','NUTRIENT_RETENTION'] as $table) {
        $column($table,'source_url','VARCHAR(500) NULL');
        $column($table,'reviewed_by','INT NULL');
        $column($table,'reviewed_at','DATETIME NULL');
        $column($table,'review_status',"ENUM('unreviewed','reviewed') NOT NULL DEFAULT 'unreviewed'");
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS MEDIA (id CHAR(32) PRIMARY KEY,owner_id INT NOT NULL,mime VARCHAR(50) NOT NULL,status ENUM('pending','approved') NOT NULL DEFAULT 'pending',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(owner_id) REFERENCES USER(id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS RATE_LIMIT (bucket CHAR(64) PRIMARY KEY,attempts INT NOT NULL,window_start DATETIME NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS AUDIT_LOG (id BIGINT AUTO_INCREMENT PRIMARY KEY,actor_id INT NULL,action VARCHAR(100) NOT NULL,entity VARCHAR(100) NOT NULL,entity_id INT NOT NULL,details JSON NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(entity,entity_id),INDEX(created_at))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS REQUEST_RECORD (user_id INT NOT NULL,request_key CHAR(32) NOT NULL,operation VARCHAR(50) NOT NULL,fingerprint CHAR(64) NOT NULL,result_id INT NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(user_id,request_key))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS PROCUREMENT (id INT AUTO_INCREMENT PRIMARY KEY,dealer_id INT NOT NULL,product_id INT NOT NULL,inventory_id INT NOT NULL,quantity_kg DECIMAL(10,2) NOT NULL,total_price DECIMAL(10,2) NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(dealer_id) REFERENCES DEALER(id),FOREIGN KEY(product_id) REFERENCES PRODUCT(id),FOREIGN KEY(inventory_id) REFERENCES DEALER_INVENTORY(id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS PAYMENT_SHARE (id INT AUTO_INCREMENT PRIMARY KEY,payment_id INT NOT NULL,payee_id INT NOT NULL,gross_amount DECIMAL(10,2) NOT NULL,net_amount DECIMAL(10,2) NOT NULL,paid_at DATETIME NULL,payout_reference VARCHAR(150) NULL,recovered_at DATETIME NULL,recovery_reference VARCHAR(150) NULL,UNIQUE(payment_id,payee_id),FOREIGN KEY(payment_id) REFERENCES PAYMENT(id),FOREIGN KEY(payee_id) REFERENCES USER(id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS SUPPORT_REQUEST (id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,payment_id INT NULL,subject VARCHAR(200) NOT NULL,message TEXT NOT NULL,status ENUM('open','resolved') DEFAULT 'open',admin_reply TEXT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES USER(id),FOREIGN KEY(payment_id) REFERENCES PAYMENT(id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS NOTIFICATION (id BIGINT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,message VARCHAR(500) NOT NULL,link VARCHAR(255) NOT NULL,is_read TINYINT NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(user_id,is_read),FOREIGN KEY(user_id) REFERENCES USER(id))");
    $index=function(string $table,string $name,string $columns)use($pdo):void {
        $stmt=$pdo->prepare('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?');$stmt->execute([$table,$name]);
        if(!$stmt->fetchColumn()) $pdo->exec("ALTER TABLE `$table` ADD INDEX `$name` ($columns)");
    };
    $index('TOUR_BOOKING','idx_tour_dates','tour_id,status,start_date,end_date');
    $index('CONSULTATION','idx_provider_time','provider_id,status,scheduled_at');
    $index('FOOD_ORDER','idx_cook_date','cook_id,status,delivery_date');
    $index('ORDER','idx_buyer_status','user_id,status,created_at');
    // Keep legacy duplicate payments auditable: a separate unique reference registry governs new workflows.
    $pdo->exec("CREATE TABLE IF NOT EXISTS PAYMENT_REFERENCE (ref_type VARCHAR(30) NOT NULL,ref_id INT NOT NULL,payment_id INT NOT NULL,PRIMARY KEY(ref_type,ref_id),FOREIGN KEY(payment_id) REFERENCES PAYMENT(id))");
    $stmt=$pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ORDER' AND CONSTRAINT_TYPE='FOREIGN KEY'");
    $fk=$pdo->query("SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ORDER' AND COLUMN_NAME='dealer_inventory_id' AND REFERENCED_TABLE_NAME='DEALER_INVENTORY'")->fetchColumn();
    if(!$fk) $pdo->exec('ALTER TABLE `ORDER` ADD FOREIGN KEY (dealer_inventory_id) REFERENCES DEALER_INVENTORY(id)');
    // CHECK creation fails closed on inconsistent legacy data; the upgrade guide explains remediation.
    foreach([['PRODUCT','kd_product_stock','quantity_kg >= 0 AND price_per_kg > 0 AND minimum_order_kg > 0'],['DEALER_INVENTORY','kd_inventory_stock','stock_remaining >= 0 AND quantity_purchased > 0 AND markup_price > 0'],['ORDER','kd_order_positive','quantity_kg > 0 AND total_price > 0'],['REGION_CROP','kd_suitability','suitability_score BETWEEN 1 AND 10'],['NUTRIENT_RETENTION','kd_retention','retention_percentage BETWEEN 0 AND 100'],['TOUR_BOOKING','kd_tour_range','num_visitors > 0 AND start_date <= end_date'],['PAYMENT','kd_payment_positive','amount > 0']] as [$table,$name,$condition]) {
        $stmt=$pdo->prepare('SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME=?');$stmt->execute([$table,$name]);
        if(!$stmt->fetchColumn()) $pdo->exec("ALTER TABLE `$table` ADD CONSTRAINT `$name` CHECK ($condition)");
    }
};
