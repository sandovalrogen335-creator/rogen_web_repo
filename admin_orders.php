<?php
require 'admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (csrf_verify($_POST['csrf_token'] ?? '') && ($_POST['form_action'] ?? '') === 'update_status') {
        $order_id = (int)($_POST['order_id'] ?? 0);
        $status   = $_POST['status'] ?? '';
        $allowed  = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];

        if (in_array($status, $allowed, true)) {
            try {
                $stmt = $db->prepare('UPDATE orders SET status = ? WHERE order_id = ?');
                $stmt->bind_param('si', $status, $order_id);
                $stmt->execute();
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                error_log('admin_orders.php error: ' . $e->getMessage());
            }
        }
        header('Location: admin_orders.php');
        exit;
    }
}

$orders = [];
if ($db) {
    $res = $db->query("
        SELECT o.order_id, o.total, o.status, o.created_at, u.name AS customer_name, u.email AS customer_email
        FROM orders o
        JOIN users u ON u.user_id = o.user_id
        ORDER BY o.created_at DESC
    ");
    while ($row = $res->fetch_assoc()) {
        $items_stmt = $db->prepare('SELECT product_name, price, quantity FROM order_items WHERE order_id = ?');
        $items_stmt->bind_param('i', $row['order_id']);
        $items_stmt->execute();
        $items_res = $items_stmt->get_result();
        $items = [];
        while ($item = $items_res->fetch_assoc()) $items[] = $item;
        $items_stmt->close();
        $row['items'] = $items;
        $orders[] = $row;
    }
}

$page_title = 'Orders';
require 'admin_header.php';
?>

<h1>Orders</h1>

<div class="admin-card">
  <strong>All orders (<?= count($orders) ?>)</strong>
  <table class="admin-table">
    <tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th></tr>
    <?php if (!$orders): ?>
      <tr><td colspan="6" class="empty-note">No orders yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td>#<?= (int) $o['order_id'] ?></td>
        <td><?= e($o['customer_name']) ?><br><span style="color:var(--muted);font-size:12px;"><?= e($o['customer_email']) ?></span></td>
        <td>
          <?php foreach ($o['items'] as $item): ?>
            <div style="font-size:13px;"><?= e($item['product_name']) ?> &times;<?= (int) $item['quantity'] ?></div>
          <?php endforeach; ?>
        </td>
        <td>$<?= number_format($o['total'], 2) ?></td>
        <td>
          <form method="post" action="admin_orders.php" style="display:flex;gap:6px;align-items:center;">
            <input type="hidden" name="form_action" value="update_status">
            <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
            <input type="hidden" name="order_id" value="<?= (int) $o['order_id'] ?>">
            <select name="status" onchange="this.form.submit()">
              <?php foreach (['pending','paid','shipped','completed','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </td>
        <td style="white-space:nowrap;color:var(--muted);font-size:12.5px;"><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require 'admin_footer.php'; ?>