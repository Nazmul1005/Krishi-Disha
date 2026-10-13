-- KrishiDisha Database Schema
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS krishidisha CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE krishidisha;

-- ============================================================
-- DOMAIN 1: USERS & ROLES
-- ============================================================

CREATE TABLE USER (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin','farmer','dealer','tourist','cook','expert','guide','general') NOT NULL,
    status ENUM('pending','approved','suspended') NOT NULL DEFAULT 'pending',
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    profile_image VARCHAR(255) DEFAULT 'assets/images/default_avatar.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE FARMER (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    farm_name VARCHAR(150),
    farm_location VARCHAR(255),
    land_size_acres DECIMAL(10,2),
    soil_type VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES USER(id) ON DELETE CASCADE
);

CREATE TABLE DEALER (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    business_name VARCHAR(150),
    license_no VARCHAR(100),
    business_address VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES USER(id) ON DELETE CASCADE
);

CREATE TABLE TOURIST (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    nationality VARCHAR(100),
    travel_preferences TEXT,
    FOREIGN KEY (user_id) REFERENCES USER(id) ON DELETE CASCADE
);

CREATE TABLE COOK (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    specialty VARCHAR(200),
    bio TEXT,
    availability ENUM('available','busy') DEFAULT 'available',
    FOREIGN KEY (user_id) REFERENCES USER(id) ON DELETE CASCADE
);

CREATE TABLE EXPERT (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    specialization VARCHAR(200),
    qualification VARCHAR(200),
    hourly_rate DECIMAL(10,2),
    availability ENUM('available','busy') DEFAULT 'available',
    FOREIGN KEY (user_id) REFERENCES USER(id) ON DELETE CASCADE
);

CREATE TABLE GUIDE (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    languages VARCHAR(200),
    experience_years INT,
    daily_rate DECIMAL(10,2),
    availability ENUM('available','busy') DEFAULT 'available',
    FOREIGN KEY (user_id) REFERENCES USER(id) ON DELETE CASCADE
);

-- ============================================================
-- DOMAIN 2: CROP KNOWLEDGE
-- ============================================================

CREATE TABLE CROP (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    scientific_name VARCHAR(200),
    local_name VARCHAR(150),
    origin VARCHAR(200),
    history TEXT,
    image VARCHAR(255) DEFAULT 'assets/images/crops/default.jpg',
    trade_status ENUM('local','export','both') DEFAULT 'local',
    season ENUM('summer','winter','rainy','all') DEFAULT 'all',
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE CROP_VARIETY (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crop_id INT NOT NULL,
    variety_name VARCHAR(150) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    FOREIGN KEY (crop_id) REFERENCES CROP(id) ON DELETE CASCADE
);

CREATE TABLE VITAMIN (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    unit VARCHAR(20) DEFAULT 'mg'
);

CREATE TABLE CROP_VITAMIN (
    crop_id INT NOT NULL,
    vitamin_id INT NOT NULL,
    amount_per_100g DECIMAL(10,4),
    PRIMARY KEY (crop_id, vitamin_id),
    FOREIGN KEY (crop_id) REFERENCES CROP(id) ON DELETE CASCADE,
    FOREIGN KEY (vitamin_id) REFERENCES VITAMIN(id) ON DELETE CASCADE
);

CREATE TABLE DISEASE (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    image VARCHAR(255),
    symptoms TEXT,
    solution TEXT,
    affected_part VARCHAR(100)
);

CREATE TABLE CROP_DISEASE (
    crop_id INT NOT NULL,
    disease_id INT NOT NULL,
    PRIMARY KEY (crop_id, disease_id),
    FOREIGN KEY (crop_id) REFERENCES CROP(id) ON DELETE CASCADE,
    FOREIGN KEY (disease_id) REFERENCES DISEASE(id) ON DELETE CASCADE
);

CREATE TABLE REGION_CROP (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crop_id INT NOT NULL,
    region VARCHAR(150),
    soil_type VARCHAR(100),
    season ENUM('summer','winter','rainy','all'),
    suitability_score TINYINT DEFAULT 5,
    notes TEXT,
    FOREIGN KEY (crop_id) REFERENCES CROP(id) ON DELETE CASCADE
);

-- ============================================================
-- DOMAIN 3: MARKETPLACE
-- ============================================================

CREATE TABLE PRODUCT (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    crop_id INT NOT NULL,
    quantity_kg DECIMAL(10,2) NOT NULL,
    price_per_kg DECIMAL(10,2) NOT NULL,
    description TEXT,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('available','sold','pending') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES FARMER(id) ON DELETE CASCADE,
    FOREIGN KEY (crop_id) REFERENCES CROP(id)
);

CREATE TABLE DEALER_INVENTORY (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dealer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity_purchased DECIMAL(10,2),
    purchase_price DECIMAL(10,2),
    markup_price DECIMAL(10,2),
    stock_remaining DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dealer_id) REFERENCES DEALER(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES PRODUCT(id)
);

CREATE TABLE `ORDER` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    dealer_inventory_id INT NULL,
    quantity_kg DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','delivered','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES USER(id),
    FOREIGN KEY (product_id) REFERENCES PRODUCT(id),
    FOREIGN KEY (dealer_inventory_id) REFERENCES DEALER_INVENTORY(id)
);

-- ============================================================
-- DOMAIN 4: NUTRITION & FOOD
-- ============================================================

CREATE TABLE COOKING_METHOD (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE NUTRIENT_RETENTION (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crop_id INT NOT NULL,
    vitamin_id INT NOT NULL,
    method_id INT NOT NULL,
    retention_percentage DECIMAL(5,2),
    FOREIGN KEY (crop_id) REFERENCES CROP(id) ON DELETE CASCADE,
    FOREIGN KEY (vitamin_id) REFERENCES VITAMIN(id),
    FOREIGN KEY (method_id) REFERENCES COOKING_METHOD(id)
);

CREATE TABLE RECIPE (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cook_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    prep_time_min INT,
    cook_time_min INT,
    servings INT DEFAULT 2,
    price DECIMAL(10,2) NOT NULL DEFAULT 300.00,
    image VARCHAR(255),
    is_authentic TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cook_id) REFERENCES COOK(id) ON DELETE CASCADE
);

CREATE TABLE RECIPE_CROP (
    recipe_id INT NOT NULL,
    crop_id INT NOT NULL,
    quantity_grams INT,
    PRIMARY KEY (recipe_id, crop_id),
    FOREIGN KEY (recipe_id) REFERENCES RECIPE(id) ON DELETE CASCADE,
    FOREIGN KEY (crop_id) REFERENCES CROP(id)
);

-- ============================================================
-- DOMAIN 5: AGRI-TOURISM
-- ============================================================

CREATE TABLE FARM_TOUR (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    title VARCHAR(200),
    description TEXT,
    location VARCHAR(255),
    capacity INT DEFAULT 10,
    price_per_day DECIMAL(10,2),
    image VARCHAR(255),
    status ENUM('active','inactive','pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES FARMER(id) ON DELETE CASCADE
);

CREATE TABLE TOUR_BOOKING (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tourist_id INT NOT NULL,
    tour_id INT NOT NULL,
    guide_id INT,
    start_date DATE,
    end_date DATE,
    num_visitors INT DEFAULT 1,
    total_price DECIMAL(10,2),
    status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tourist_id) REFERENCES TOURIST(id),
    FOREIGN KEY (tour_id) REFERENCES FARM_TOUR(id),
    FOREIGN KEY (guide_id) REFERENCES GUIDE(id)
);

CREATE TABLE FOOD_ORDER (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tourist_id INT NOT NULL,
    recipe_id INT NOT NULL,
    cook_id INT NOT NULL,
    quantity INT DEFAULT 1,
    total_price DECIMAL(10,2),
    delivery_date DATE,
    status ENUM('pending','preparing','delivered','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tourist_id) REFERENCES TOURIST(id),
    FOREIGN KEY (recipe_id) REFERENCES RECIPE(id),
    FOREIGN KEY (cook_id) REFERENCES COOK(id)
);

-- ============================================================
-- DOMAIN 6: TRANSACTIONS
-- ============================================================

CREATE TABLE CONSULTATION (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    provider_id INT NOT NULL,
    scheduled_date DATE,
    duration_hours DECIMAL(4,2),
    topic VARCHAR(255),
    notes TEXT,
    fee DECIMAL(10,2),
    status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES USER(id),
    FOREIGN KEY (provider_id) REFERENCES USER(id)
);

CREATE TABLE CONSULTATION_MESSAGE (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consultation_id) REFERENCES CONSULTATION(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES USER(id)
);

CREATE TABLE PAYMENT (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payer_id INT NOT NULL,
    ref_type ENUM('order','tour_booking','food_order','consultation') NOT NULL,
    ref_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('cash','mobile_banking','card') DEFAULT 'cash',
    status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payer_id) REFERENCES USER(id)
);

CREATE TABLE ADMIN_COMMISSION (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL UNIQUE,
    commission_rate DECIMAL(5,2) DEFAULT 5.00,
    commission_amount DECIMAL(10,2),
    settled TINYINT(1) DEFAULT 0,
    settled_at TIMESTAMP NULL,
    FOREIGN KEY (payment_id) REFERENCES PAYMENT(id)
);

-- ============================================================
-- DOMAIN 7: CONTENT MODERATION (user-submitted proposals)
-- ============================================================

CREATE TABLE DATA_PROPOSAL (
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

-- ============================================================
-- DOMAIN 8: AUTH TOKENS (password reset + email verification)
-- ============================================================

CREATE TABLE AUTH_TOKEN (
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
