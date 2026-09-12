<?php
/* HOMI FURNITURES — server-side cart storage, tied to the logged-in user.
   Called via fetch() from script.js so the cart survives page reloads
   and can be shown on profile.php.
   GET  -> list current cart
   POST -> {action: 'add'|'set_qty'|'remove'|'clear', ...} */

session_start();
require 'db_connect.php';
require 'csrf.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'errors' => ['Not signed in.'], 'needsLogin' => true]);
    exit;
}
$user_id = (int) $_SESSION['user_id'];

if (!$db) {
    echo json_encode(['ok' => false, 'errors' => [$db_error]]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    /* ---------- LIST current cart ---------- */
    if ($method === 'GET') {
        $stmt = $db->prepare('SELECT cart_item_id, product_name, price, image_url, quantity FROM cart_items WHERE user_id = ? ORDER BY added_at ASC');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id'    => (int) $row['cart_item_id'],
                'name'  => $row['product_name'],
                'price' => (float) $row['price'],
                'img'   => $row['image_url'],
                'qty'   => (int) $row['quantity'],
            ];
        }
        $stmt->close();
        echo json_encode(['ok' => true, 'items' => $items]);
        exit;
    }

    /* ---------- Everything else is a POST action (JSON body) ---------- */
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        echo json_encode(['ok' => false, 'errors' => ['Invalid request.']]);
        exit;
    }
    if (!csrf_verify($input['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'errors' => ['Your session expired. Please refresh the page and try again.']]);
        exit;
    }

    $action = $input['action'] ?? '';

    if ($action === 'add') {
        $name  = trim((string)($input['name'] ?? ''));
        $price = (float)($input['price'] ?? 0);
        $img   = trim((string)($input['img'] ?? ''));

        if ($name === '') {
            echo json_encode(['ok' => false, 'errors' => ['Missing product name.']]);
            exit;
        }

        $stmt = $db->prepare('SELECT cart_item_id, quantity FROM cart_items WHERE user_id = ? AND product_name = ? LIMIT 1');
        $stmt->bind_param('is', $user_id, $name);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $new_qty = $existing['quantity'] + 1;
            $stmt = $db->prepare('UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?');
            $stmt->bind_param('ii', $new_qty, $existing['cart_item_id']);
            $stmt->execute();
            $stmt->close();
            $id = (int) $existing['cart_item_id'];
        } else {
            $stmt = $db->prepare('INSERT INTO cart_items (user_id, product_name, price, image_url, quantity) VALUES (?, ?, ?, ?, 1)');
            $stmt->bind_param('isds', $user_id, $name, $price, $img);
            $stmt->execute();
            $id = $db->insert_id;
            $stmt->close();
        }

        echo json_encode(['ok' => true, 'id' => $id]);
        exit;
    }

    if ($action === 'set_qty') {
        $id  = (int)($input['id'] ?? 0);
        $qty = (int)($input['qty'] ?? 0);

        if ($qty <= 0) {
            $stmt = $db->prepare('DELETE FROM cart_items WHERE cart_item_id = ? AND user_id = ?');
            $stmt->bind_param('ii', $id, $user_id);
        } else {
            $stmt = $db->prepare('UPDATE cart_items SET quantity = ? WHERE cart_item_id = ? AND user_id = ?');
            $stmt->bind_param('iii', $qty, $id, $user_id);
        }
        $stmt->execute();
        $stmt->close();
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'remove') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM cart_items WHERE cart_item_id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $user_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'clear') {
        $stmt = $db->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => false, 'errors' => ['Unknown action.']]);

} catch (mysqli_sql_exception $e) {
    error_log('cart.php error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'errors' => ['Something went wrong. Please try again.']]);
}