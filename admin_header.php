<?php
/* HOMI FURNITURES — admin layout header.
   admin_auth.php must already be required at the top of the page
   (it starts the session, checks the login, and defines e(), $token, $admin_name).
   Each page sets $page_title BEFORE requiring this file. */

// Safety net: if this file is loaded without admin_auth.php, bail out.
if (!function_exists('e')) {
    header('Location: login.php');
    exit;
}

// Highlight the current page in the sidebar
 $current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title ?? 'Admin') ?> — HOMI Admin</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="admin-layout">

  <aside class="admin-sidebar">
    <div class="admin-brand">
      <div class="name">HOMI</div>
      <div class="sub">ADMIN PANEL</div>
    </div>

    <nav class="admin-nav" aria-label="Admin navigation">
      <a href="admin.php"          class="<?= $current === 'admin.php' ? 'active' : '' ?>">Dashboard</a>
      <a href="admin_products.php" class="<?= $current === 'admin_products.php' ? 'active' : '' ?>">Products</a>
      <a href="admin_orders.php"   class="<?= $current === 'admin_orders.php' ? 'active' : '' ?>">Orders</a>
      <a href="admin_users.php"    class="<?= $current === 'admin_users.php' ? 'active' : '' ?>">Users</a>
      <a href="admin_messages.php" class="<?= $current === 'admin_messages.php' ? 'active' : '' ?>">Messages</a>
    </nav>

    <div class="admin-sidebar-foot">
      <a href="index.php" class="admin-side-link">&larr; View website</a>
      <a href="logout.php" class="admin-side-link">Log out</a>
      <div class="admin-user">
        Signed in as<br><strong><?= e($admin_name) ?></strong>
      </div>
    </div>
  </aside>

  <main class="admin-main">