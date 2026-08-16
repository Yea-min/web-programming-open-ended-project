-- =========================================================
-- Peer-to-Peer Campus Tool & Equipment Library
-- Database Schema (MySQL / MariaDB)
-- CSE 3120 - Web Programming | Open-Ended Lab
-- =========================================================

DROP DATABASE IF EXISTS campus_tool_library;
CREATE DATABASE campus_tool_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campus_tool_library;

-- ---------------------------------------------------------
-- USERS
-- Every student / staff member who registers on the platform
-- ---------------------------------------------------------
CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  NOT NULL UNIQUE,
    student_id      VARCHAR(30)   NOT NULL,
    password_hash   VARCHAR(255)  NOT NULL,
    phone           VARCHAR(30)   DEFAULT NULL,
    department      VARCHAR(100)  DEFAULT NULL,
    avatar_initials VARCHAR(4)    DEFAULT NULL,
    reputation_pts  INT           NOT NULL DEFAULT 0,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- CATEGORIES
-- Fixed taxonomy so browsing / filtering stays consistent
-- ---------------------------------------------------------
CREATE TABLE categories (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(60) NOT NULL UNIQUE,
    icon      VARCHAR(10) DEFAULT '🔧'
) ENGINE=InnoDB;

INSERT INTO categories (name, icon) VALUES
('Electronics Kits', '🔌'),
('Cameras & Photography', '📷'),
('Lab Instruments', '🧪'),
('Power Tools', '🛠️'),
('Textbooks & Manuals', '📚'),
('Sports & Outdoor', '🏸'),
('Computing & Accessories', '💻'),
('Miscellaneous', '📦');

-- ---------------------------------------------------------
-- ITEMS
-- The listings that owners post for others to borrow
-- ---------------------------------------------------------
CREATE TABLE items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    owner_id        INT NOT NULL,
    category_id     INT NOT NULL,
    title           VARCHAR(120) NOT NULL,
    description     TEXT NOT NULL,
    item_condition  ENUM('New','Like New','Good','Fair','Worn') NOT NULL DEFAULT 'Good',
    photo_path      VARCHAR(255) DEFAULT NULL,
    max_loan_days   INT NOT NULL DEFAULT 7,
    deposit_note    VARCHAR(255) DEFAULT NULL,
    status          ENUM('available','borrowed','unavailable') NOT NULL DEFAULT 'available',
    asset_code      VARCHAR(12) NOT NULL UNIQUE,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id)    REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_items_status   ON items(status);
CREATE INDEX idx_items_category ON items(category_id);

-- ---------------------------------------------------------
-- BORROW REQUESTS
-- A request from a borrower to an owner for a specific item
-- ---------------------------------------------------------
CREATE TABLE borrow_requests (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    item_id         INT NOT NULL,
    borrower_id     INT NOT NULL,
    start_date      DATE NOT NULL,
    end_date        DATE NOT NULL,
    message         VARCHAR(500) DEFAULT NULL,
    status          ENUM('pending','approved','rejected','returned','cancelled')
                    NOT NULL DEFAULT 'pending',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id)     REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (borrower_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_requests_status ON borrow_requests(status);

-- ---------------------------------------------------------
-- IMPACT LOG (optional analytics table)
-- Every time a loan is completed we log an "item saved from
-- being newly manufactured / purchased" event -> powers the
-- sustainability counters on the homepage.
-- ---------------------------------------------------------
CREATE TABLE impact_log (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    request_id      INT NOT NULL,
    est_money_saved DECIMAL(8,2) NOT NULL DEFAULT 0,
    logged_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES borrow_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Sample seed data (optional - safe to remove)
-- ---------------------------------------------------------
-- Password for BOTH demo users below is: Passw0rd!
INSERT INTO users (full_name, email, student_id, password_hash, department, avatar_initials)
VALUES
('Ayesha Rahman', 'ayesha@ulab.edu.bd', '213001001', '$2y$10$k0vvd.DMK3JCa/CoNhq5BO0tIn0.RDnT8QNLZbmtovLk1ML9ijR0a', 'CSE', 'AR'),
('Tanvir Hasan',  'tanvir@ulab.edu.bd', '213001002', '$2y$10$k0vvd.DMK3JCa/CoNhq5BO0tIn0.RDnT8QNLZbmtovLk1ML9ijR0a', 'EEE', 'TH');
-- Tip: you can always just use the Sign Up page instead of these seed accounts.

INSERT INTO items (owner_id, category_id, title, description, item_condition, max_loan_days, deposit_note, asset_code)
VALUES
(1, 2, 'Canon EOS 200D DSLR Camera',
 'Entry-level DSLR with an 18-55mm kit lens. Great for club event coverage or photography assignments. Comes with a 32GB SD card and spare battery.',
 'Like New', 5, 'Meet at CSE building reception', 'TL-A1B2'),
(1, 1, 'Arduino Uno Starter Kit',
 'Full electronics kit with breadboard, jumper wires, sensors, and an Arduino Uno R3. Ideal for embedded systems coursework.',
 'Good', 10, NULL, 'TL-C3D4'),
(2, 4, 'Cordless Drill (18V)',
 'Lightly used cordless drill with two batteries and a charger. Comes with a small bit set. Please return with a full charge.',
 'Good', 3, 'Deposit: student ID at pickup', 'TL-E5F6'),
(2, 3, 'Digital Multimeter',
 'Basic digital multimeter for voltage, current, and resistance measurement. Useful for circuits lab.',
 'Fair', 7, NULL, 'TL-G7H8');
