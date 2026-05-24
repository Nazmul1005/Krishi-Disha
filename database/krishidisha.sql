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
    inventory_id INT NOT NULL,
    quantity_kg DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','delivered','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES USER(id),
    FOREIGN KEY (inventory_id) REFERENCES DEALER_INVENTORY(id)
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
    status ENUM('active','inactive') DEFAULT 'active',
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
    farmer_id INT NOT NULL,
    expert_id INT NOT NULL,
    scheduled_date DATE,
    duration_hours DECIMAL(4,2),
    topic VARCHAR(255),
    notes TEXT,
    fee DECIMAL(10,2),
    status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES FARMER(id),
    FOREIGN KEY (expert_id) REFERENCES EXPERT(id)
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
-- SEED DATA
-- ============================================================

-- Admin (password: Admin@1234)
INSERT INTO USER (name, email, password_hash, phone, role, status) VALUES
('System Admin', 'admin@krishidisha.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01700000001', 'admin', 'approved');

-- Farmers (password: Test@1234)
INSERT INTO USER (name, email, password_hash, phone, role, status) VALUES
('Karim Uddin', 'karim@farmer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01711111111', 'farmer', 'approved'),
('Fatema Begum', 'fatema@farmer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01711111112', 'farmer', 'approved');

INSERT INTO FARMER (user_id, farm_name, farm_location, land_size_acres, soil_type) VALUES
(2, 'Green Valley Farm', 'Sylhet, Bangladesh', 12.50, 'Loamy'),
(3, 'Fatema Organic Farm', 'Mymensingh, Bangladesh', 8.00, 'Clay Loam');

-- Dealers
INSERT INTO USER (name, email, password_hash, phone, role, status) VALUES
('Rahim Traders', 'rahim@dealer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01722222221', 'dealer', 'approved');

INSERT INTO DEALER (user_id, business_name, license_no, business_address) VALUES
(4, 'Rahim Agricultural Traders', 'BD-AGR-2024-001', 'Kawranbazar, Dhaka');

-- Tourist
INSERT INTO USER (name, email, password_hash, phone, role, status) VALUES
('John Smith', 'john@tourist.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01733333331', 'tourist', 'approved');

INSERT INTO TOURIST (user_id, nationality, travel_preferences) VALUES
(5, 'American', 'Eco-tourism, organic farming, cultural experiences');

-- Cook
INSERT INTO USER (name, email, password_hash, phone, role, status) VALUES
('Nasrin Akter', 'nasrin@cook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01744444441', 'cook', 'approved');

INSERT INTO COOK (user_id, specialty, bio, availability) VALUES
(6, 'Traditional Bengali Cuisine', 'Specializing in authentic village recipes using fresh farm produce.', 'available');

-- Expert
INSERT INTO USER (name, email, password_hash, phone, role, status) VALUES
('Dr. Anwar Hossain', 'anwar@expert.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01755555551', 'expert', 'approved');

INSERT INTO EXPERT (user_id, specialization, qualification, hourly_rate, availability) VALUES
(7, 'Soil Science & Pest Management', 'PhD in Agricultural Science, BAU', 800.00, 'available');

-- Guide
INSERT INTO USER (name, email, password_hash, phone, role, status) VALUES
('Rony Ahmed', 'rony@guide.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01766666661', 'guide', 'approved');

INSERT INTO GUIDE (user_id, languages, experience_years, daily_rate, availability) VALUES
(8, 'Bengali, English, Hindi', 5, 1500.00, 'available');

-- General User
INSERT INTO USER (name, email, password_hash, phone, role, status) VALUES
('Sadia Islam', 'sadia@user.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01777777771', 'general', 'approved');

-- CROPS
INSERT INTO CROP (name, scientific_name, local_name, origin, history, trade_status, season, category) VALUES
('Rice', 'Oryza sativa', 'Dhan', 'Southeast Asia', 'Rice has been cultivated in Bangladesh for thousands of years and is the primary staple food. Bangladesh is one of the top rice producing nations in the world.', 'both', 'all', 'Grain'),
('Wheat', 'Triticum aestivum', 'Gom', 'Fertile Crescent', 'Wheat is the second most important cereal crop in Bangladesh, widely grown in winter season (Rabi season) in northern regions.', 'both', 'winter', 'Grain'),
('Potato', 'Solanum tuberosum', 'Alu', 'South America', 'Introduced to Bangladesh during colonial era. Now Bangladesh is a major potato producer, especially in Munshiganj and Rangpur.', 'both', 'winter', 'Vegetable'),
('Tomato', 'Solanum lycopersicum', 'Tometo', 'South America', 'Tomato cultivation in Bangladesh has expanded rapidly, grown in winter season. Rich in vitamins and widely consumed.', 'local', 'winter', 'Vegetable'),
('Mango', 'Mangifera indica', 'Aam', 'South Asia', 'The national fruit of Bangladesh. Rajshahi and Chapainawabganj are famous mango growing regions. Exported to Middle East and Europe.', 'export', 'summer', 'Fruit'),
('Jute', 'Corchorus olitorius', 'Pat', 'South Asia', 'Known as the Golden Fiber of Bangladesh. Major export commodity. Bangladesh produces about 40% of world jute.', 'export', 'rainy', 'Fiber'),
('Mustard', 'Brassica juncea', 'Sorisha', 'Central Asia', 'Important oilseed crop grown in winter. Used for cooking oil and as a spice. Major crops in Jessore and Faridpur.', 'local', 'winter', 'Oilseed'),
('Lentil', 'Lens culinaris', 'Masur Dal', 'Near East', 'High protein legume, essential in Bengali diet. Grown in Rabi season. Rich in iron and folate.', 'both', 'winter', 'Legume'),
('Bitter Gourd', 'Momordica charantia', 'Karela', 'South Asia', 'Popular vegetable in Bangladesh with significant medicinal properties. Used in traditional medicine for diabetes management.', 'local', 'summer', 'Vegetable'),
('Sugarcane', 'Saccharum officinarum', 'Aakh', 'New Guinea', 'Major cash crop in Bangladesh. Used for producing sugar, molasses, and jaggery. Grown in Rajshahi, Natore, and Pabna.', 'local', 'all', 'Cash Crop');

-- VITAMINS
INSERT INTO VITAMIN (name, unit) VALUES
('Vitamin A', 'mcg'), ('Vitamin B1 (Thiamine)', 'mg'), ('Vitamin B2 (Riboflavin)', 'mg'),
('Vitamin B3 (Niacin)', 'mg'), ('Vitamin B6', 'mg'), ('Vitamin B9 (Folate)', 'mcg'),
('Vitamin C', 'mg'), ('Vitamin D', 'mcg'), ('Vitamin E', 'mg'), ('Vitamin K', 'mcg');

-- CROP VITAMINS
INSERT INTO CROP_VITAMIN (crop_id, vitamin_id, amount_per_100g) VALUES
(1,2,0.20),(1,3,0.05),(1,4,1.60),(1,9,0.10),
(2,2,0.30),(2,3,0.12),(2,4,5.46),(2,5,0.30),
(3,7,19.70),(3,6,16.00),(3,2,0.08),(3,9,0.01),
(4,1,833.00),(4,7,23.40),(4,9,0.54),(4,10,7.90),
(5,1,54.00),(5,7,36.40),(5,6,43.00),(5,9,0.90),
(6,7,28.00),(6,2,0.07),(6,6,31.00),
(7,7,3.00),(7,1,2.00),(7,9,0.25),
(8,6,181.00),(8,2,0.17),(8,7,1.50),
(9,7,84.00),(9,1,24.00),(9,6,72.00),(9,9,0.14),
(10,7,7.00),(10,2,0.10);

-- DISEASES
INSERT INTO DISEASE (name, symptoms, solution, affected_part) VALUES
('Rice Blast', 'Diamond-shaped lesions on leaves with gray center and brown border; neck rot causing white panicles.', 'Apply Tricyclazole or Isoprothiolane fungicides. Use resistant varieties. Avoid excess nitrogen.', 'Leaves, Stem, Panicle'),
('Bacterial Leaf Blight', 'Water-soaked to yellowish stripe along leaf margins, wilting of seedlings (kresek).', 'Use resistant varieties. Treat seeds with Streptomycin. Drain fields during kresek stage.', 'Leaves'),
('Potato Late Blight', 'Dark brown lesions on leaves with white fungal growth underneath in humid conditions.', 'Apply Mancozeb or Metalaxyl fungicides. Use certified disease-free seed potatoes.', 'Leaves, Tubers'),
('Tomato Early Blight', 'Dark brown concentric ring lesions on older leaves, yellowing around lesions.', 'Apply Chlorothalonil or Copper-based fungicides. Remove infected plant debris.', 'Leaves, Stems'),
('Mango Anthracnose', 'Dark sunken spots on fruits and flowers; blossom blight reducing fruit set.', 'Apply Carbendazim or Mancozeb during flowering. Post-harvest hot water treatment.', 'Fruits, Flowers'),
('Jute Stem Rot', 'Water-soaked lesions on stems turning dark brown; plant collapse in waterlogged conditions.', 'Improve drainage. Apply Carbendazim. Avoid dense planting.', 'Stems'),
('Wheat Rust', 'Orange-red (stem rust) or yellow (stripe rust) pustules on leaves and stems.', 'Apply Propiconazole or Tebuconazole. Use resistant wheat varieties.', 'Leaves, Stems');

-- CROP_DISEASE links
INSERT INTO CROP_DISEASE (crop_id, disease_id) VALUES
(1,1),(1,2),(2,7),(3,3),(4,4),(5,5),(6,6);

-- REGION CROP
INSERT INTO REGION_CROP (crop_id, region, soil_type, season, suitability_score, notes) VALUES
(1,'Sylhet','Clay Loam','rainy',10,'Excellent conditions for Aman rice'),
(1,'Rajshahi','Sandy Loam','winter',8,'Suitable for Boro rice with irrigation'),
(2,'Rangpur','Loamy','winter',9,'Best wheat growing belt of Bangladesh'),
(3,'Munshiganj','Alluvial','winter',10,'Famous potato growing district'),
(4,'Bogura','Sandy Loam','winter',9,'Major tomato producing region'),
(5,'Rajshahi','Sandy Loam','summer',10,'National mango hub - Chapainawabganj'),
(6,'Faridpur','Alluvial','rainy',10,'Jute growing heartland'),
(7,'Jessore','Sandy Loam','winter',9,'Major mustard growing area'),
(8,'Dinajpur','Loamy','winter',8,'Good lentil growing conditions'),
(10,'Natore','Clay Loam','all',9,'Major sugarcane growing region');

-- COOKING METHODS
INSERT INTO COOKING_METHOD (name, description) VALUES
('Raw/Fresh', 'No cooking applied, consumed as is'),
('Boiling', 'Cooking in water at 100°C'),
('Steaming', 'Cooking using steam, retains more nutrients than boiling'),
('Frying', 'Cooking in oil at high temperature'),
('Roasting', 'Dry heat cooking in oven or open fire'),
('Microwaving', 'Rapid heating using microwave radiation');

-- NUTRIENT RETENTION
INSERT INTO NUTRIENT_RETENTION (crop_id, vitamin_id, method_id, retention_percentage) VALUES
(3,7,1,100.00),(3,7,2,65.00),(3,7,3,85.00),(3,7,4,70.00),
(4,1,1,100.00),(4,1,2,75.00),(4,1,3,90.00),(4,1,4,55.00),
(4,7,1,100.00),(4,7,2,60.00),(4,7,3,80.00),(4,7,4,50.00),
(5,7,1,100.00),(5,7,2,70.00),(5,7,3,88.00);

-- FARM TOURS
INSERT INTO FARM_TOUR (farmer_id, title, description, location, capacity, price_per_day) VALUES
(1,'Green Valley Eco-Tour','Experience authentic farm life at our 12-acre organic farm. Includes rice paddies, fruit orchards, and pond fishing.','Sylhet, Bangladesh',15,2500.00),
(2,'Fatema Organic Farm Visit','Learn about organic farming practices, composting, and sustainable agriculture at our certified organic farm.','Mymensingh, Bangladesh',10,2000.00);

-- RECIPES
INSERT INTO RECIPE (cook_id, name, description, prep_time_min, cook_time_min, servings, is_authentic) VALUES
(1,'Panta Bhat','Traditional fermented rice soaked overnight in water, served with mustard paste, dried fish, and green chilies. A Bengali cultural heritage dish.',15,480,4,1),
(1,'Shorshe Ilish','Hilsa fish cooked in mustard paste with turmeric and green chilies. The quintessential Bengali recipe.',20,30,4,1),
(1,'Aloo Bhorta','Mashed potato with mustard oil, green onion, green chilies, and dried red chilies. Simple and delicious.',10,20,2,1);

INSERT INTO RECIPE_CROP (recipe_id, crop_id, quantity_grams) VALUES
(1,1,200),(1,7,10),(3,3,300);

-- CONSULTATIONS
INSERT INTO CONSULTATION (farmer_id, expert_id, scheduled_date, duration_hours, topic, fee, status) VALUES
(1,1,'2024-12-20',2.00,'Soil quality improvement and organic fertilizer recommendations for rice cultivation',1600.00,'completed'),
(2,1,'2024-12-25',1.50,'Pest management for winter vegetables',1200.00,'confirmed');

-- PRODUCTS
INSERT INTO PRODUCT (farmer_id, crop_id, quantity_kg, price_per_kg, description, status) VALUES
(1,1,500.00,45.00,'Fresh Aman rice, freshly harvested. No pesticides used.','available'),
(1,5,200.00,150.00,'Langra mango variety, fully ripe, ready for export packaging.','available'),
(2,3,300.00,30.00,'Diamant variety potato, cleaned and graded, suitable for wholesale.','available'),
(2,4,150.00,60.00,'Hybrid tomato, firm and bright red, fresh harvest.','available');

-- DEALER INVENTORY
INSERT INTO DEALER_INVENTORY (dealer_id, product_id, quantity_purchased, purchase_price, markup_price, stock_remaining) VALUES
(1,1,200.00,45.00,55.00,180.00),
(1,3,150.00,30.00,38.00,120.00);

-- PAYMENTS
INSERT INTO PAYMENT (payer_id, ref_type, ref_id, amount, method, status) VALUES
(9,'order',1,990.00,'mobile_banking','completed');

-- ADMIN COMMISSION
INSERT INTO ADMIN_COMMISSION (payment_id, commission_rate, commission_amount, settled) VALUES
(1,5.00,49.50,0);
