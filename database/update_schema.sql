-- SQL schema update for Admin Approval Workflow & Photos
USE krishidisha;

-- Add image column to PRODUCT table (if not already there)
ALTER TABLE PRODUCT ADD COLUMN image VARCHAR(255) DEFAULT NULL;

-- Add image column to CROP table (if not already there)
ALTER TABLE CROP ADD COLUMN image VARCHAR(255) DEFAULT NULL;

-- Add image column to DISEASE table (if not already there)
ALTER TABLE DISEASE ADD COLUMN image VARCHAR(255) DEFAULT NULL;

-- Add image column to FARM_TOUR table (if not already there)
ALTER TABLE FARM_TOUR ADD COLUMN image VARCHAR(255) DEFAULT NULL;

-- Create DATA_PROPOSAL table for admin reviews of user submissions
CREATE TABLE IF NOT EXISTS DATA_PROPOSAL (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    section ENUM('crop', 'disease', 'nutrition', 'tourism', 'recommender', 'marketplace') NOT NULL,
    action ENUM('create', 'update', 'delete') NOT NULL,
    target_id INT NULL,
    title VARCHAR(255) NOT NULL,
    proposed_data TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    reviewed_by INT NULL,
    FOREIGN KEY (user_id) REFERENCES USER(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES USER(id) ON DELETE SET NULL
);

-- Upload directory note: ensure assets/images/uploads/ exists with write permissions
-- CREATE DIRECTORY assets/images/uploads/{crops,diseases,tours,products}/
