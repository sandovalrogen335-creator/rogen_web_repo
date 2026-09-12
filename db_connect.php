<?php
/**
 * HOMI FURNITURES - MySQL database connection
 * XAMPP defaults: host=localhost, user=root, password=empty.
 *
 * Include this file (require 'db_connect.php';) at the top of any
 * page that needs the database. It exposes a $db mysqli object,
 * or sets $db = null and $db_error on failure.
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'homi_db';

$db = null;
$db_error = '';

try {
    $db = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
    $db->set_charset('utf8mb4');

    $db->query("CREATE DATABASE IF NOT EXISTS `homi_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db->select_db($DB_NAME);

    $db->query("CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        address VARCHAR(255) DEFAULT NULL,
        age INT DEFAULT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('customer','admin') DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

} catch (mysqli_sql_exception $e) {
    // Never show raw DB errors to visitors — log them, show a generic message.
    error_log('DB connection error: ' . $e->getMessage());
    $db = null;
    $db_error = 'We could not reach the database right now. Please try again shortly.';
}