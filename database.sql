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

-- ---------- Users ----------
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

-- ---------- Products (best-seller catalog) ----------
CREATE TABLE IF NOT EXISTS products (
  product_id  INT           AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150)  NOT NULL,
  description TEXT          DEFAULT NULL,
  price       DECIMAL(10,2) NOT NULL,
  image_url   VARCHAR(500)  DEFAULT NULL,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO products (name, description, price, image_url) VALUES
('Living Room Furniture Set', 'HOMI furniture brings comfort and style to the living room through modern and functional design for everyday living.', 3867.00, 'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?q=80&w=600&auto=format&fit=crop'),
('Walling Furniture', 'HOMI walling furniture adds character and organization to the home, combining simple design with practical storage.', 1456.00, 'https://images.unsplash.com/photo-1595428774223-ef52624120d2?q=80&w=600&auto=format&fit=crop'),
('Kitchen Room Furniture', 'HOMI kitchen furniture provides a clean and functional space where style, comfort, and practicality meet.', 1653.00, 'https://images.unsplash.com/photo-1556911220-bff31c812dba?q=80&w=600&auto=format&fit=crop'),
('Home Office Furniture', 'HOMI home office furniture offers a clean, comfortable workspace that keeps you organized and productive.', 4379.00, 'https://images.unsplash.com/photo-1568992687947-868a62a9f521?q=80&w=600&auto=format&fit=crop');

-- ---------- Orders ----------
CREATE TABLE IF NOT EXISTS orders (
  order_id   INT            AUTO_INCREMENT PRIMARY KEY,
  user_id    INT            NOT NULL,
  total      DECIMAL(10,2)  NOT NULL,
  status     ENUM('pending','paid','shipped','completed','cancelled') DEFAULT 'pending',
  created_at TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS order_items (
  order_item_id INT            AUTO_INCREMENT PRIMARY KEY,
  order_id      INT            NOT NULL,
  product_name  VARCHAR(150)   NOT NULL,
  price         DECIMAL(10,2)  NOT NULL,
  quantity      INT            NOT NULL DEFAULT 1,
  FOREIGN KEY (order_id) REFERENCES orders(order_id)
);

-- ---------- Quote requests & contact messages ----------
CREATE TABLE IF NOT EXISTS quote_requests (
  quote_id   INT           AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100)  NOT NULL,
  email      VARCHAR(150)  NOT NULL,
  phone      VARCHAR(50)   DEFAULT NULL,
  interest   VARCHAR(100)  DEFAULT NULL,
  message    TEXT          DEFAULT NULL,
  created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contact_messages (
  message_id INT           AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100)  NOT NULL,
  email      VARCHAR(150)  NOT NULL,
  message    TEXT          NOT NULL,
  created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- ---------- Persistent per-user cart ----------
CREATE TABLE IF NOT EXISTS cart_items (
  cart_item_id INT           AUTO_INCREMENT PRIMARY KEY,
  user_id      INT           NOT NULL,
  product_name VARCHAR(150)  NOT NULL,
  price        DECIMAL(10,2) NOT NULL,
  image_url    VARCHAR(500)  DEFAULT NULL,
  quantity     INT           NOT NULL DEFAULT 1,
  added_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

UPDATE homi_db.users SET role = 'admin' WHERE email = 'rogen@gmail.com';