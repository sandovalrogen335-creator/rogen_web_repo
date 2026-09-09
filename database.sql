-- =========================================================
-- HOMI FURNITURES — Database setup
-- How to use (XAMPP):
--   1. Start Apache + MySQL in the XAMPP Control Panel
--   2. Open http://localhost/phpmyadmin
--   3. Go to the "Import" tab and choose this file, then click "Go"
-- (Or from a terminal:  mysql -u root < database.sql)
-- =========================================================

CREATE DATABASE IF NOT EXISTS homi_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE homi_db;

-- Matches the existing `users` table used by login.php:
-- (user_id, name, email, address, age, password, role, created_at)

CREATE TABLE IF NOT EXISTS users (
  user_id    INT           AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100)  NOT NULL,
  email      VARCHAR(150)  NOT NULL UNIQUE,
  address    VARCHAR(255)  DEFAULT NULL,
  age        INT           DEFAULT NULL,
  password   VARCHAR(255)  NOT NULL,
  role       ENUM('customer','admin') DEFAULT 'customer',
  created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);