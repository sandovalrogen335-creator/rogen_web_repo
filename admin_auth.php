<?php
/* HOMI FURNITURES — gatekeeper for every admin page.
   require this at the very top of any admin_*.php file, before
   any HTML output. */

session_start();
require 'db_connect.php';
require 'csrf.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

$token       = csrf_token();
$admin_name  = $_SESSION['user_name'] ?? 'Admin';