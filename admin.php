<?php
require 'admin_auth.php';

$stats = [
    'users'    => 0,
    'products' => 0,
    'orders'   => 0,
    'revenue'  => 0,
    'quotes'   => 0,
    'messages' => 0,
];

if ($db) {
    $stats['users']    = (int) $db->query('SELECT COUNT(*) c FROM users')->fetch_assoc()['c'];
    $stats['products'] = (int) $db->query('SELECT COUNT(*) c FROM products')->fetch_assoc()['c'];
    $stats['orders']   = (int) $db->query('SELECT COUNT(*) c FROM orders')->fetch_assoc()['c'];
    $stats['revenue']  = (float) ($db->query("SELECT COALESCE(SUM(total),0) s FROM orders WHERE status != 'cancelled'")->fetch_assoc()['s']);
    $stats['quotes']   = (int) $db->query('SELECT COUNT(*) c FROM quote_requests')->fetch_assoc()['c'];
    $stats['messages'] = (int) $db->query('SELECT COUNT(*) c FROM contact_messages')->fetch_assoc()['c'];
}

$page_title = 'Dashboard';
require 'admin_header.php';
?>

<h1>Dashboard</h1>
<p class="empty-note">Overview of the HOMI Furnitures store.</p>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= $stats['users'] ?></div><div class="label">Users</div></div>
  <div class="stat-card"><div class="num"><?= $stats['products'] ?></div><div class="label">Products</div></div>
  <div class="stat-card"><div class="num"><?= $stats['orders'] ?></div><div class="label">Orders</div></div>
  <div class="stat-card"><div class="num">$<?= number_format($stats['revenue'], 2) ?></div><div class="label">Revenue (excl. cancelled)</div></div>
  <div class="stat-card"><div class="num"><?= $stats['quotes'] ?></div><div class="label">Quote Requests</div></div>
  <div class="stat-card"><div class="num"><?= $stats['messages'] ?></div><div class="label">Contact Messages</div></div>
</div>

<div class="admin-card">
  <strong>Quick links</strong>
  <div class="admin-form-row" style="margin-top:14px;">
    <a class="btn btn-green btn-fit" href="admin_products.php">Manage Products</a>
    <a class="btn btn-outline btn-fit" href="admin_orders.php">View Orders</a>
    <a class="btn btn-outline btn-fit" href="admin_users.php">Manage Users</a>
    <a class="btn btn-outline btn-fit" href="admin_messages.php">View Messages</a>
  </div>
</div>

<?php require 'admin_footer.php'; ?>