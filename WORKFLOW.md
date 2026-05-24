# 🔄 KrishiDisha - Complete Workflow & Architecture Guide

This document is a deep-dive explanation of **KrishiDisha's** internal workings. It is designed to help developers, collaborators, and evaluators understand exactly how every function, feature, and database table connects.

---

## 🏗️ 1. Architecture Overview (Frontend ↔ Backend ↔ Database)

KrishiDisha is a **Monolithic Multi-Page Application (MPA)**. It does not use a separate frontend framework (like React) and backend API (like Node.js). Everything is tightly integrated using the classic LAMP/XAMPP stack architecture.

* **Frontend:** HTML5, CSS3 (using custom CSS variables and utility classes), and Vanilla JavaScript for interactivity (modals, auto-scroll chat, calculators).
* **Backend:** PHP 8+. It processes form submissions, handles session management, and executes business logic directly before rendering the HTML.
* **Database Connection:** Uses **PHP Data Objects (PDO)** in `config/db.php`. PDO is used exclusively with **Prepared Statements** (`$pdo->prepare()`) to completely prevent SQL Injection attacks.
* **Flow:** 
  1. User clicks a button or submits a form.
  2. The browser sends an HTTP GET/POST request to the specific `.php` file.
  3. PHP validates the session (`requireAuth()`), connects to MySQL, and runs queries.
  4. PHP dynamically generates the HTML populated with database records and sends it back to the browser.

---

## 👥 2. User Authentication & Role Management Flow

### 🔹 Registration & Login (`/auth/register.php`, `/auth/login.php`)
1. **Registration:** A new user fills out the form. The system hashes the password using PHP's native `password_hash($pass, PASSWORD_DEFAULT)` for maximum security. 
2. **Role Assignment:** The user selects a role (e.g., Farmer, Tourist, Expert). The data is inserted into the master `USER` table. 
   * *Special Cases:* If the user selects Cook, Expert, or Guide, their status is set to `'pending'` until the Admin approves them. Other roles are instantly `'approved'`.
3. **Login:** Compares input with the database using `password_verify()`. If valid, it stores `user_id`, `role`, and `name` in the global `$_SESSION` array.
4. **Routing:** Depending on the `$_SESSION['role']`, the user is automatically redirected to their specific dashboard (e.g., `/farmer/dashboard.php`).

---

## 🛒 3. The Direct-to-Consumer (D2C) Marketplace Flow

This subsystem connects Farmers directly with Consumers.

1. **Listing Produce (Farmer Side - `/farmer/produce.php`):**
   * Farmer fills out a form with Crop Type, Quantity, and Price per KG.
   * **Database:** Inserts a row into the `PRODUCT` table linked via `farmer_id`.
2. **Browsing Marketplace (Consumer Side - `/user/marketplace.php`):**
   * General Users, Tourists, or Dealers visit the marketplace.
   * **Database:** PHP runs a `JOIN` query connecting `PRODUCT` ↔ `CROP` ↔ `FARMER` ↔ `USER` to display available items and seller names.
3. **Placing an Order:**
   * User enters a quantity and clicks "Order".
   * **Database Transaction (Atomic Operation):**
     1. Uses `$pdo->beginTransaction()`.
     2. Inserts record into `ORDER` table.
     3. Updates `quantity_kg` in `PRODUCT` table. If quantity hits `0`, status changes from `'available'` to `'sold'`.
     4. Inserts record into `PAYMENT` table.
     5. Calculates 5% of the total price and inserts it into the `ADMIN_COMMISSION` table.
     6. Commits transaction (`$pdo->commit()`). If any step fails, it rolls back (`$pdo->rollBack()`).

---

## 🚜 4. Agri-Tourism & Food Flow

This subsystem handles farm visits and authentic rural cuisine.

### 🔹 Farm Tours
1. **Hosting (Farmer - `/farmer/farmland.php`):** Farmer lists their land as a tourist spot (`FARM_TOUR` table).
2. **Booking (Tourist - `/tourist/tours.php`):** Tourist browses available lands and selects dates.
3. **Database:** Creates a record in `TOUR_BOOKING`.
4. **Tour Guides (Guide - `/guide/bookings.php`):** Tourists can optionally hire a local guide. Guides manage these requests from their dashboard, changing statuses from `pending` to `confirmed`.

### 🔹 Authentic Food
1. **Recipes (Cook - `/cook/recipes.php`):** Cooks post authentic rural dishes into the `RECIPE` table.
2. **Ordering (Tourist - `/tourist/food_orders.php`):** Tourists order these meals during their farm visits.
3. **Fulfillment (Cook - `/cook/orders.php`):** Cook receives the `FOOD_ORDER` record and updates the status to `preparing` or `delivered`.

---

## 💬 5. Unified Consultation & Chat System

A cross-role communication system allowing users to get expert advice.

1. **Booking a Session (`/modules/book_consultation.php`):**
   * Any user (Farmer, Tourist, General User) can select an approved **Expert** or **Guide**.
   * The system calculates the fee based on the provider's `hourly_rate` (or daily rate divided by 8 for guides).
   * **Database:** Inserts into the `CONSULTATION` table with `client_id` and `provider_id`.
2. **Session Management (`/expert/sessions.php` or `/guide/sessions.php`):**
   * Providers accept or mark sessions as complete.
3. **Live Discussion Interface (`/modules/consultation_chat.php`):**
   * A dynamic chat room tied to the `consultation_id`.
   * **Database:** When a user types a message, it is inserted via POST into `CONSULTATION_MESSAGE`. When the page loads, it fetches all messages ordered by `created_at` and displays them in message bubbles (differentiating 'me' vs 'them' based on `sender_id`).

---

## 🧠 6. Intelligence Modules Workflow

These tools are read-heavy applications that process existing database knowledge.

1. **Crop Encyclopedia (`/modules/encyclopedia.php`):**
   * Queries the `CROP` table.
   * **Modal Integration:** When a user clicks "Nutrition Info", it runs a sub-query fetching data from `CROP_VITAMIN` joined with the `VITAMIN` table to display exact mg/mcg values.
2. **Disease Detection (`/modules/disease.php`):**
   * Joins `DISEASE` ↔ `CROP_DISEASE` ↔ `CROP`. Allows users to filter by crop name to see specific threats, symptoms, and organic/chemical solutions.
3. **Crop Recommender (`/modules/recommend.php`):**
   * **Algorithm 1 (Region):** User selects a region (e.g., Sylhet). It queries the `REGION_CROP` table, orders by `suitability_score DESC`, and returns the best crops for that soil type.
   * **Algorithm 2 (Nutrition):** User selects a deficiency (e.g., Vitamin C). It queries `CROP_VITAMIN` to find crops with the highest concentration of that specific vitamin.
4. **Nutrition Retention Calculator (`/modules/nutrition.php`):**
   * **Logic:** Different cooking methods destroy vitamins. The system joins `CROP` ↔ `NUTRIENT_RETENTION` ↔ `COOKING_METHOD`. It takes the base raw vitamin level and applies the retention multiplier (e.g., Boiling retains 60% of Vitamin C, Steaming retains 85%).
5. **Profit Calculator (`/modules/calculator.php`):**
   * A frontend JS module. It pulls current market rates from the backend. The user inputs their land size (acres) and expected yield. JS calculates Estimated Revenue, subtracts generic overhead costs, and outputs Net Profit predictions instantly without a page reload.

---

## 🗄️ 7. Database Core Architecture Explained

The database (`krishidisha.sql`) is highly normalized to eliminate redundancy. 

### Inheritance Strategy
KrishiDisha uses a **"Class Table Inheritance"** structure for users:
* **`USER` Table:** The master table containing shared attributes (`id`, `name`, `email`, `password`, `role`).
* **Child Tables (`FARMER`, `EXPERT`, `GUIDE`, `COOK`, etc.):** Contain role-specific attributes. They map back to the master table via a `user_id` Foreign Key.

### Key Foreign Key Connections
* `PRODUCT.farmer_id` → Links to `FARMER.id` (Who grew it?)
* `ORDER.product_id` → Links to `PRODUCT.id` (What are they buying?)
* `CONSULTATION.client_id` & `CONSULTATION.provider_id` → Both link to `USER.id` (Generic mapping allowing ANY user to consult ANY provider).
* `CONSULTATION_MESSAGE.consultation_id` → Cascading Delete enabled. If a consultation is deleted, the chat history vanishes automatically to save space.

### Security Mechanisms
* **Prepared Statements:** The use of `?` placeholders in queries (e.g., `SELECT * FROM USER WHERE email=?`) ensures user input is never executed as SQL code.
* **Auth Check (`includes/auth_check.php`):** A single file included at the top of every secured page. It verifies the session and forcefully kicks out unauthorized roles.

---
*This workflow provides a complete map of how data travels from a user's click, through PHP business logic, into the MySQL database, and back to the screen.*
