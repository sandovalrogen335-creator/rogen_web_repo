<?php
require 'admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (csrf_verify($_POST['csrf_token'] ?? '')) {
        $action = $_POST['form_action'] ?? '';
        try {
            if ($action === 'delete_quote') {
                $stmt = $db->prepare('DELETE FROM quote_requests WHERE quote_id = ?');
                $stmt->bind_param('i', $_POST['id']);
                $stmt->execute();
                $stmt->close();
            } elseif ($action === 'delete_contact') {
                $stmt = $db->prepare('DELETE FROM contact_messages WHERE message_id = ?');
                $stmt->bind_param('i', $_POST['id']);
                $stmt->execute();
                $stmt->close();
            }
        } catch (mysqli_sql_exception $e) {
            error_log('admin_messages.php error: ' . $e->getMessage());
        }
        header('Location: admin_messages.php');
        exit;
    }
}

$quotes = [];
$messages = [];
if ($db) {
    $res = $db->query('SELECT * FROM quote_requests ORDER BY created_at DESC');
    while ($row = $res->fetch_assoc()) $quotes[] = $row;

    $res = $db->query('SELECT * FROM contact_messages ORDER BY created_at DESC');
    while ($row = $res->fetch_assoc()) $messages[] = $row;
}

$page_title = 'Messages';
require 'admin_header.php';
?>

<h1>Messages</h1>

<div class="admin-card">
  <strong>Quote Requests (<?= count($quotes) ?>)</strong>
  <table class="admin-table">
    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Interest</th><th>Message</th><th>Date</th><th></th></tr>
    <?php if (!$quotes): ?><tr><td colspan="7" class="empty-note">None yet.</td></tr><?php endif; ?>
    <?php foreach ($quotes as $q): ?>
      <tr>
        <td><?= e($q['name']) ?></td>
        <td><?= e($q['email']) ?></td>
        <td><?= e($q['phone']) ?></td>
        <td><?= e($q['interest']) ?></td>
        <td style="max-width:220px;color:var(--muted);"><?= e($q['message']) ?></td>
        <td style="color:var(--muted);font-size:12px;white-space:nowrap;"><?= e(date('M j, Y', strtotime($q['created_at']))) ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Delete this request?');">
            <input type="hidden" name="form_action" value="delete_quote">
            <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
            <input type="hidden" name="id" value="<?= (int) $q['quote_id'] ?>">
            <button type="submit" class="btn-sm2 btn-delete">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="admin-card">
  <strong>Contact Messages (<?= count($messages) ?>)</strong>
  <table class="admin-table">
    <tr><th>Name</th><th>Email</th><th>Message</th><th>Date</th><th></th></tr>
    <?php if (!$messages): ?><tr><td colspan="5" class="empty-note">None yet.</td></tr><?php endif; ?>
    <?php foreach ($messages as $m): ?>
      <tr>
        <td><?= e($m['name']) ?></td>
        <td><?= e($m['email']) ?></td>
        <td style="max-width:320px;color:var(--muted);"><?= e($m['message']) ?></td>
        <td style="color:var(--muted);font-size:12px;white-space:nowrap;"><?= e(date('M j, Y', strtotime($m['created_at']))) ?></td>
        <td>    
          <form method="post" onsubmit="return confirm('Delete this message?');">
            <input type="hidden" name="form_action" value="delete_contact">
            <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
            <input type="hidden" name="id" value="<?= (int) $m['message_id'] ?>">
            <button type="submit" class="btn-sm2 btn-delete">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require 'admin_footer.php'; ?>