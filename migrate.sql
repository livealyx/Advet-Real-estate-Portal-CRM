-- Migration script for live server (Updated v4.0)
-- Includes: SEO Slugs, Gallery, AI Chat, and the new CRM Suite

-- ─────────────────────────────────────────────
-- SECTION 1: Properties & Stories Refinement
-- ─────────────────────────────────────────────

SET @column_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'properties' AND column_name = 'slug' AND table_schema = DATABASE());
SET @query := IF(@column_exists = 0, 'ALTER TABLE properties ADD COLUMN slug VARCHAR(200) NOT NULL UNIQUE AFTER title', 'SELECT "Slug exists in properties"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'stories' AND column_name = 'slug' AND table_schema = DATABASE());
SET @query := IF(@column_exists = 0, 'ALTER TABLE stories ADD COLUMN slug VARCHAR(200) NOT NULL UNIQUE AFTER title', 'SELECT "Slug exists in stories"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ensure Category and Listing Type exist
SET @column_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'properties' AND column_name = 'category' AND table_schema = DATABASE());
SET @query := IF(@column_exists = 0, 'ALTER TABLE properties ADD COLUMN category ENUM("Flat/Apartment", "Plot", "Commercial") NOT NULL DEFAULT "Flat/Apartment" AFTER status', 'SELECT "Category exists"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'properties' AND column_name = 'listing_type' AND table_schema = DATABASE());
SET @query := IF(@column_exists = 0, 'ALTER TABLE properties ADD COLUMN listing_type ENUM("Buy", "Sell", "Rent") NOT NULL DEFAULT "Buy" AFTER category', 'SELECT "Listing Type exists"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ─────────────────────────────────────────────
-- SECTION 2: User Identity & Security
-- ─────────────────────────────────────────────

ALTER TABLE users MODIFY COLUMN role ENUM('admin','agent','member') NOT NULL DEFAULT 'member';

SET @column_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'profile_picture' AND table_schema = DATABASE());
SET @query := IF(@column_exists = 0, 'ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL AFTER email', 'SELECT "Profile picture exists"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS password_resets (
  email VARCHAR(200) NOT NULL,
  token VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (email),
  INDEX (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- SECTION 3: Expansion Modules (Gallery, AI, Content)
-- ─────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS faqs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question TEXT NOT NULL,
  answer TEXT NOT NULL,
  display_order INT DEFAULT 0,
  status ENUM('active', 'draft') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS albums (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  cover_image VARCHAR(300) DEFAULT NULL,
  display_order INT DEFAULT 0,
  status ENUM('active', 'draft') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS album_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  album_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(300) NOT NULL,
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_migrate_album_images FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(100) NOT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  role ENUM('user', 'assistant', 'system') NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_migrate_chat_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- SECTION 4: CRM Core Infrastructure (v4.0)
-- ─────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS crm_contacts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(200) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  whatsapp VARCHAR(50) DEFAULT NULL,
  source VARCHAR(100) DEFAULT 'Direct',
  type ENUM('buyer','seller','investor','agent','tenant') NOT NULL DEFAULT 'buyer',
  budget_min DECIMAL(15,2) DEFAULT 0.00,
  budget_max DECIMAL(15,2) DEFAULT 0.00,
  preferred_loc TEXT,
  notes TEXT,
  property_type VARCHAR(50) DEFAULT NULL,
  assigned_to INT UNSIGNED DEFAULT NULL,
  budget VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_migrate_crm_agent FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_stages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  color VARCHAR(20) DEFAULT '#899178',
  display_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_deals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contact_id INT UNSIGNED NOT NULL,
  property_id INT UNSIGNED DEFAULT NULL,
  stage_id INT UNSIGNED NOT NULL,
  deal_value DECIMAL(15,2) DEFAULT 0.00,
  probability INT UNSIGNED DEFAULT 0,
  expected_close DATE DEFAULT NULL,
  status ENUM('active','won','lost','abandoned') DEFAULT 'active',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_migrate_crm_deal_contact FOREIGN KEY (contact_id) REFERENCES crm_contacts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  agent_id INT UNSIGNED NOT NULL,
  contact_id INT UNSIGNED DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  due_date DATETIME NOT NULL,
  priority ENUM('low','medium','high') DEFAULT 'medium',
  status ENUM('pending','completed','overdue') DEFAULT 'pending',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_migrate_crm_task_agent FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contact_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  file_path VARCHAR(300) NOT NULL,
  doc_type VARCHAR(50) DEFAULT 'Agreement',
  uploaded_by INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_migrate_crm_doc_contact FOREIGN KEY (contact_id) REFERENCES crm_contacts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  deal_id INT UNSIGNED NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  payment_type ENUM('booking', 'milestone', 'registry', 'commission') NOT NULL,
  payment_date DATE NOT NULL,
  ref_number VARCHAR(100) DEFAULT NULL,
  status ENUM('pending', 'verified', 'cancelled') DEFAULT 'pending',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_migrate_crm_transaction_deal FOREIGN KEY (deal_id) REFERENCES crm_deals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_activities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contact_id INT UNSIGNED NOT NULL,
  agent_id INT UNSIGNED NOT NULL,
  activity_type ENUM('call','email','whatsapp','meeting','site_visit','note') NOT NULL,
  details TEXT,
  status ENUM('completed','missed','scheduled') DEFAULT 'completed',
  activity_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_migrate_crm_activity_agent FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_migrate_crm_activity_contact FOREIGN KEY (contact_id) REFERENCES crm_contacts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed CRM Stages if empty
INSERT IGNORE INTO crm_stages (id, name, color, display_order) VALUES
(1, 'Lead Induction', '#899178', 10),
(2, 'Meeting Scheduled', '#B4A28B', 20),
(3, 'KYC Verification', '#7A8C99', 30),
(4, 'Negotiation', '#A67C52', 40),
(5, 'Agreement Signed', '#5C705E', 50),
(6, 'Possession', '#2D3142', 60);

-- ─────────────────────────────────────────────
-- SECTION 5: Telemetry & Analytics
-- ─────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS visitor_logs (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  user_agent TEXT,
  device_type VARCHAR(50),
  os VARCHAR(50),
  browser VARCHAR(50),
  page_url VARCHAR(255),
  visited_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS page_views (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  view_date DATE NOT NULL UNIQUE,
  views INT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- SECTION 6: Project Module (v5.0)
-- ─────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `projects` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `project_type` enum('Apartment','Villa','Society','Plot') NOT NULL DEFAULT 'Apartment',
  `location` varchar(255) NOT NULL,
  `description` text,
  `possession_status` enum('Ready','Under Construction','New Launch') NOT NULL DEFAULT 'Under Construction',
  `price_min` varchar(100) DEFAULT NULL,
  `price_max` varchar(100) DEFAULT NULL,
  `area_min` varchar(100) DEFAULT NULL,
  `area_max` varchar(100) DEFAULT NULL,
  `cover_image` varchar(300) DEFAULT NULL,
  `status` enum('active','draft') NOT NULL DEFAULT 'active',
  `agent_phone` varchar(50) DEFAULT NULL,
  `agent_whatsapp` varchar(50) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int unsigned NOT NULL,
  `image_path` varchar(300) NOT NULL,
  `is_cover` tinyint(1) DEFAULT 0,
  `display_order` int DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_project_images_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_units` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int unsigned NOT NULL,
  `unit_type` varchar(100) NOT NULL,
  `size` varchar(100) DEFAULT NULL,
  `price` varchar(100) DEFAULT NULL,
  `availability` varchar(100) DEFAULT 'Available',
  `display_order` int DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_project_units_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- SECTION 7: Inquiries Expansion (v5.1)
-- ─────────────────────────────────────────────

SET @column_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'inquiries' AND column_name = 'project_id' AND table_schema = DATABASE());
SET @query := IF(@column_exists = 0, 'ALTER TABLE inquiries ADD COLUMN project_id INT UNSIGNED DEFAULT NULL AFTER property_id', 'SELECT "Project_id exists in inquiries"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @constraint_exists := (SELECT COUNT(*) FROM information_schema.key_column_usage WHERE table_name = 'inquiries' AND constraint_name = 'fk_inquiry_project' AND table_schema = DATABASE());
SET @query := IF(@constraint_exists = 0, 'ALTER TABLE inquiries ADD CONSTRAINT fk_inquiry_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL', 'SELECT "Constraint exists"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ensure updated_at exists in crm_deals (Critical for pipeline/leads)
SET @column_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'crm_deals' AND column_name = 'updated_at' AND table_schema = DATABASE());
SET @query := IF(@column_exists = 0, 'ALTER TABLE crm_deals ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at', 'SELECT "updated_at exists in crm_deals"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ─────────────────────────────────────────────
-- SECTION 8: Property Fields Expansion (v5.2)
-- ─────────────────────────────────────────────

-- 1. Update Category ENUM (Rename Home to Flat/Apartment)
-- We expand the ENUM first, then update data, then shrink the ENUM
ALTER TABLE properties MODIFY COLUMN category ENUM('Home', 'Flat/Apartment', 'Plot', 'Commercial') NOT NULL DEFAULT 'Home';
UPDATE properties SET category = 'Flat/Apartment' WHERE category = 'Home';
ALTER TABLE properties MODIFY COLUMN category ENUM('Flat/Apartment', 'Plot', 'Commercial') NOT NULL DEFAULT 'Flat/Apartment';

-- 2. Add Balcony column
SET @column_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'properties' AND column_name = 'balcony' AND table_schema = DATABASE());
SET @query := IF(@column_exists = 0, 'ALTER TABLE properties ADD COLUMN balcony INT DEFAULT 0 AFTER sqft', 'SELECT "Balcony exists"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Add Flat Type column
SET @column_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'properties' AND column_name = 'flat_type' AND table_schema = DATABASE());
SET @query := IF(@column_exists = 0, 'ALTER TABLE properties ADD COLUMN flat_type VARCHAR(50) DEFAULT "Raw" AFTER balcony', 'SELECT "Flat Type exists"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Add Budget column to crm_contacts if missing
SET @column_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'crm_contacts' AND column_name = 'budget' AND table_schema = DATABASE());
SET @query := IF(@column_exists = 0, 'ALTER TABLE crm_contacts ADD COLUMN budget VARCHAR(100) DEFAULT NULL AFTER assigned_to', 'SELECT "Budget exists in crm_contacts"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ─────────────────────────────────────────────
-- SECTION 9: Agent Profile Expansion (v5.3)
-- ─────────────────────────────────────────────

-- 1. Add Phone column to users
SET @column_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'phone' AND table_schema = DATABASE());
SET @query := IF(@column_exists = 0, 'ALTER TABLE users ADD COLUMN phone VARCHAR(50) DEFAULT NULL AFTER profile_picture', 'SELECT "Phone exists in users"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Add Agency Name column to users
SET @column_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'agency_name' AND table_schema = DATABASE());
SET @query := IF(@column_exists = 0, 'ALTER TABLE users ADD COLUMN agency_name VARCHAR(150) DEFAULT NULL AFTER phone', 'SELECT "Agency Name exists in users"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Add Verified Status column to users
SET @column_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'is_verified' AND table_schema = DATABASE());
SET @query := IF(@column_exists = 0, 'ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0 AFTER agency_name', 'SELECT "is_verified exists in users"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ─────────────────────────────────────────────
-- SECTION 10: Property-Project Relationship (v5.4)
-- ─────────────────────────────────────────────

-- 1. Add project_id column to properties
SET @column_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'properties' AND column_name = 'project_id' AND table_schema = DATABASE());
SET @query := IF(@column_exists = 0, 'ALTER TABLE properties ADD COLUMN project_id INT UNSIGNED DEFAULT NULL AFTER agent_id', 'SELECT "project_id exists in properties"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Add Foreign Key constraint
SET @constraint_exists := (SELECT COUNT(*) FROM information_schema.key_column_usage WHERE table_name = 'properties' AND constraint_name = 'fk_property_project' AND table_schema = DATABASE());
SET @query := IF(@constraint_exists = 0, 'ALTER TABLE properties ADD CONSTRAINT fk_property_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL', 'SELECT "Constraint fk_property_project exists"');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

