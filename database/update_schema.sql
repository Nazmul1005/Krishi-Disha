-- ============================================================
-- KrishiDisha — Idempotent migration for EXISTING databases
-- ------------------------------------------------------------
-- Fresh installs already get everything from krishidisha.sql.
-- Run this only to upgrade a database created before the
-- photo-upload / proposal / pending-tour features existed.
-- Safe to run multiple times.
-- ============================================================
USE krishidisha;

-- ---- 1. Add PRODUCT.image only if it is missing ------------
DROP PROCEDURE IF EXISTS kd_add_column_if_missing;
DELIMITER //
CREATE PROCEDURE kd_add_column_if_missing(
    IN tbl VARCHAR(64), IN col VARCHAR(64), IN definition VARCHAR(255))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', definition);
        PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

CALL kd_add_column_if_missing('PRODUCT',   'image',               "VARCHAR(255) DEFAULT NULL");
CALL kd_add_column_if_missing('CROP',      'image',               "VARCHAR(255) DEFAULT NULL");
CALL kd_add_column_if_missing('DISEASE',   'image',               "VARCHAR(255) DEFAULT NULL");
CALL kd_add_column_if_missing('FARM_TOUR', 'image',               "VARCHAR(255) DEFAULT NULL");
CALL kd_add_column_if_missing('ORDER',     'dealer_inventory_id', "INT NULL");
CALL kd_add_column_if_missing('USER',      'email_verified',      "TINYINT(1) NOT NULL DEFAULT 0");
CALL kd_add_column_if_missing('RECIPE',    'price',               "DECIMAL(10,2) NOT NULL DEFAULT 300.00");

DROP PROCEDURE IF EXISTS kd_add_column_if_missing;

-- Existing accounts predate email verification: treat them as verified.
UPDATE USER SET email_verified = 1 WHERE email_verified = 0;

-- ---- 2. Allow 'pending' farm tours (approval workflow) -----
ALTER TABLE FARM_TOUR
    MODIFY COLUMN status ENUM('active','inactive','pending') DEFAULT 'active';

-- ---- 3. Create the proposals table if missing --------------
CREATE TABLE IF NOT EXISTS DATA_PROPOSAL (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    section ENUM('crop','disease','nutrition','tourism','recommender','marketplace') NOT NULL,
    action ENUM('create','update','delete') NOT NULL DEFAULT 'create',
    target_id INT NULL,
    title VARCHAR(255) NOT NULL,
    proposed_data TEXT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    admin_notes TEXT DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    reviewed_by INT NULL,
    FOREIGN KEY (user_id) REFERENCES USER(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES USER(id) ON DELETE SET NULL
);

-- ---- 4. Auth tokens (password reset + email verification) --
CREATE TABLE IF NOT EXISTS AUTH_TOKEN (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    type ENUM('password_reset','email_verify') NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES USER(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash)
);

-- Upload directories (create on the web server, not in SQL):
--   assets/images/uploads/{crops,diseases,tours,products}/
