<?php
require 'admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (csrf_verify($_POST['csrf_token'] ?? '') && ($_POST['form_action'] ?? '') === 'toggle_role') {
        $target_id = (int)($_POST['user_id'] ?? 0);

        if ($target_id === (int) $_SESSION['user_id']) {
            // Don't let an admin accidentally demote themselves.
        } else {
            try {
                $stmt = $db->prepare('SELECT role FROM users WHERE user_id = ?');
                $stmt->bind_param('i', $target_id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($row) {
                    $new_role = $row['role'] === 'admin' ? 'customer' : 'admin';
                    $stmt = $db->prepare('UPDATE users SET role = ? WHERE user_id = ?');
                    $stmt->bind_param('si', $new_role, $target_id);
                    $stmt->execute();
                    $stmt->close();
                }
            } catch (mysqli_sql_exception $e) {
                error_log('admin_users.php error: ' . $e->getMessage());
            }
        }
        header('Location: admin_users.php');
        exit;
    }
}

$users = [];
if ($db) {
    $res = $db->query('SELECT user_id, name, email, role, created_at FROM users ORDER BY created_at DESC');
    while ($row = $res->fetch_assoc()) $users[] = $row;
}

$page_title = 'Users';
require 'admin_header.php';
?>

<h1>Users</h1>

<div class="admin-card">
  <strong>All users (<?= count($users) ?>)</strong>
  <table class="admin-table">
    <tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th></th></tr>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= e($u['name']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><span class="pill pill-<?= $u['role'] ?>"><?= e($u['role']) ?></span></td>
        <td style="color:var(--muted);font-size:12.5px;"><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
        <td>
          <?php if ((int) $u['user_id'] !== (int) $_SESSION['user_id']): ?>
            <form method="post" action="admin_users.php" onsubmit="return confirm('Change this user\'s role?');">
              <input type="hidden" name="form_action" value="toggle_role">
              <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
              <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
              <button type="submit" class="btn-sm2 btn-edit"><?= $u['role'] === 'admin' ? 'Make customer' : 'Make admin' ?></button>
            </form>
          <?php else: ?>
            <span class="empty-note" style="font-size:12px;">(you)</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require 'admin_footer.php'; ?>