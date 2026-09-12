<?php
require 'admin_auth.php';

$errors = [];
$edit_product = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please refresh and try again.';
    } else {
        $action = $_POST['form_action'] ?? '';

        try {
            if ($action === 'save') {
                $id          = (int)($_POST['product_id'] ?? 0);
                $name        = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $price       = (float)($_POST['price'] ?? 0);
                $image_url   = trim($_POST['image_url'] ?? '');

                if ($name === '')  $errors[] = 'Product name is required.';
                if ($price <= 0)   $errors[] = 'Price must be greater than 0.';

                if (!$errors) {
                    if ($id > 0) {
                        $stmt = $db->prepare('UPDATE products SET name=?, description=?, price=?, image_url=? WHERE product_id=?');
                        $stmt->bind_param('ssdsi', $name, $description, $price, $image_url, $id);
                    } else {
                        $stmt = $db->prepare('INSERT INTO products (name, description, price, image_url) VALUES (?, ?, ?, ?)');
                        $stmt->bind_param('ssds', $name, $description, $price, $image_url);
                    }
                    $stmt->execute();
                    $stmt->close();
                    header('Location: admin_products.php');
                    exit;
                }
            }

            if ($action === 'delete') {
                $id = (int)($_POST['product_id'] ?? 0);
                $stmt = $db->prepare('DELETE FROM products WHERE product_id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
                header('Location: admin_products.php');
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            error_log('admin_products.php error: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

// Load product to edit, if requested
if (isset($_GET['edit']) && $db) {
    $id = (int) $_GET['edit'];
    $stmt = $db->prepare('SELECT * FROM products WHERE product_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$products = [];
if ($db) {
    $res = $db->query('SELECT * FROM products ORDER BY product_id DESC');
    while ($row = $res->fetch_assoc()) $products[] = $row;
}

$page_title = 'Products';
require 'admin_header.php';
?>

<h1>Products</h1>

<?php foreach ($errors as $err): ?>
  <div class="auth-alert auth-alert-error"><p><?= e($err) ?></p></div>
<?php endforeach; ?>

<div class="admin-card">
  <strong><?= $edit_product ? 'Edit product' : 'Add a new product' ?></strong>
  <form method="post" action="admin_products.php">
    <input type="hidden" name="form_action" value="save">
    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
    <input type="hidden" name="product_id" value="<?= $edit_product ? (int) $edit_product['product_id'] : 0 ?>">
    <div class="admin-form-row">
      <div class="form-field">
        <label>Name</label>
        <input type="text" name="name" value="<?= e($edit_product['name'] ?? '') ?>" required>
      </div>
      <div class="form-field">
        <label>Price (USD)</label>
        <input type="number" step="0.01" min="0.01" name="price" value="<?= e($edit_product['price'] ?? '') ?>" required>
      </div>
      <div class="form-field">
        <label>Image URL</label>
        <input type="text" name="image_url" value="<?= e($edit_product['image_url'] ?? '') ?>">
      </div>
    </div>
    <div class="admin-form-row">
      <div class="form-field" style="flex:2 1 100%;">
        <label>Description</label>
        <textarea name="description" rows="2"><?= e($edit_product['description'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="admin-form-row">
      <button type="submit" class="btn btn-green btn-fit"><?= $edit_product ? 'Save changes' : 'Add product' ?></button>
      <?php if ($edit_product): ?>
        <a class="btn btn-outline btn-fit" href="admin_products.php">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="admin-card">
  <strong>All products (<?= count($products) ?>)</strong>
  <table class="admin-table">
    <tr><th>Image</th><th>Name</th><th>Price</th><th>Description</th><th></th></tr>
    <?php if (!$products): ?>
      <tr><td colspan="5" class="empty-note">No products yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><?php if ($p['image_url']): ?><img src="<?= e($p['image_url']) ?>" style="width:56px;height:56px;object-fit:cover;border-radius:8px;"><?php endif; ?></td>
        <td><?= e($p['name']) ?></td>
        <td>$<?= number_format($p['price'], 2) ?></td>
        <td style="max-width:280px;color:var(--muted);"><?= e(mb_strimwidth($p['description'] ?? '', 0, 90, '…')) ?></td>
        <td style="white-space:nowrap;">
          <a href="admin_products.php?edit=<?= (int) $p['product_id'] ?>"><button type="button" class="btn-sm2 btn-edit">Edit</button></a>
          <form method="post" action="admin_products.php" style="display:inline;" onsubmit="return confirm('Delete this product?');">
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
            <input type="hidden" name="product_id" value="<?= (int) $p['product_id'] ?>">
            <button type="submit" class="btn-sm2 btn-delete">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require 'admin_footer.php'; ?>