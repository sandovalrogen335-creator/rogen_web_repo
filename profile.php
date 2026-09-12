<?php
/* HOMI FURNITURES — account page: current cart + order history. */

session_start();
require 'db_connect.php';
require 'csrf.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

$user_id   = (int) $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '';

$cart_items = [];
$orders     = [];

if ($db) {
    try {
        $stmt = $db->prepare('SELECT cart_item_id, product_name, price, image_url, quantity FROM cart_items WHERE user_id = ? ORDER BY added_at ASC');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $cart_items[] = $row;
        $stmt->close();

        $stmt = $db->prepare('SELECT order_id, total, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $order_id = (int) $row['order_id'];

            $items_stmt = $db->prepare('SELECT product_name, price, quantity FROM order_items WHERE order_id = ?');
            $items_stmt->bind_param('i', $order_id);
            $items_stmt->execute();
            $items_res = $items_stmt->get_result();
            $items = [];
            while ($item = $items_res->fetch_assoc()) $items[] = $item;
            $items_stmt->close();

            $row['items'] = $items;
            $orders[] = $row;
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('profile.php error: ' . $e->getMessage());
    }
}

$cart_subtotal = 0;
foreach ($cart_items as $ci) $cart_subtotal += $ci['price'] * $ci['quantity'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account — HOMI Furnitures</title>
<link rel="stylesheet" href="styles.css">
<style>
  :root{ --green:#2f6b4f; --cream:#f3efe6; --brown:#3a2a1a; --muted:#6b6053; --line:#e3ddd0; }
  .acct-wrap{max-width:820px;margin:0 auto;padding:48px 20px 90px;}
  .acct-wrap h1{font-size:28px;}
  .acct-sub{color:var(--muted);margin-top:6px;}
  .acct-sub a{color:var(--green);}
  .acct-section{margin-top:44px;}
  .acct-section h2{font-size:18px;margin-bottom:16px;}
  .acct-card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:18px 20px;margin-bottom:12px;}
  .acct-row{display:flex;justify-content:space-between;align-items:center;gap:16px;}
  .acct-item-info{display:flex;align-items:center;gap:14px;}
  .acct-item-info img{width:60px;height:60px;object-fit:cover;border-radius:8px;}
  .acct-name{font-weight:600;color:var(--brown);}
  .acct-meta{color:var(--muted);font-size:13px;margin-top:2px;}
  .acct-total{font-weight:700;color:var(--brown);}
  .acct-empty{color:var(--muted);}
  .acct-order-head{display:flex;justify-content:space-between;align-items:flex-start;}
  .acct-status{text-transform:uppercase;font-size:11px;letter-spacing:.05em;color:var(--green);font-weight:600;}
  .acct-order-items{margin:12px 0 0;padding-left:18px;color:var(--brown);font-size:14px;}
  .acct-order-items li{margin-bottom:4px;}
  .acct-subtotal{margin-top:14px;font-weight:700;font-size:16px;}
</style>
</head>
<body class="auth-body">

<a class="auth-back" href="index.php">&larr; Back to store</a>

<div class="acct-wrap">
  <h1>My Account</h1>
  <p class="acct-sub">Signed in as <?= e($user_name) ?> &middot; <a href="logout.php">Log out</a></p>

  <!-- ===== Current Cart ===== -->
  <section class="acct-section">
    <h2>Your Cart</h2>
    <?php if (!$cart_items): ?>
      <p class="acct-empty">Your cart is empty. <a href="index.php#bestseller">Browse products</a></p>
    <?php else: ?>
      <?php foreach ($cart_items as $ci): ?>
        <div class="acct-card acct-row">
          <div class="acct-item-info">
            <?php if ($ci['image_url']): ?><img src="<?= e($ci['image_url']) ?>" alt="<?= e($ci['product_name']) ?>"><?php endif; ?>
            <div>
              <div class="acct-name"><?= e($ci['product_name']) ?></div>
              <div class="acct-meta">$<?= number_format($ci['price'], 2) ?> &times; <?= (int) $ci['quantity'] ?></div>
            </div>
          </div>
          <div class="acct-total">$<?= number_format($ci['price'] * $ci['quantity'], 2) ?></div>
        </div>
      <?php endforeach; ?>
      <p class="acct-subtotal">Subtotal: $<?= number_format($cart_subtotal, 2) ?></p>
      <a class="btn btn-green" href="index.php">Go to checkout</a>
    <?php endif; ?>
  </section>

  <!-- ===== Order History ===== -->
  <section class="acct-section">
    <h2>Your Orders</h2>
    <?php if (!$orders): ?>
      <p class="acct-empty">You haven't placed any orders yet.</p>
    <?php else: ?>
      <?php foreach ($orders as $order): ?>
        <div class="acct-card">
          <div class="acct-order-head">
            <div>
              <strong>Order #<?= (int) $order['order_id'] ?></strong>
              <div class="acct-meta"><?= e(date('M j, Y g:ia', strtotime($order['created_at']))) ?></div>
            </div>
            <div style="text-align:right;">
              <div class="acct-status"><?= e($order['status']) ?></div>
              <div class="acct-total">$<?= number_format($order['total'], 2) ?></div>
            </div>
          </div>
          <ul class="acct-order-items">
            <?php foreach ($order['items'] as $item): ?>
              <li><?= e($item['product_name']) ?> &times; <?= (int) $item['quantity'] ?> — $<?= number_format($item['price'] * $item['quantity'], 2) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</div>

</body>
</html>