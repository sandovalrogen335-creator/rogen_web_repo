<?php
/* HOMI FURNITURES — checkout endpoint.
   Receives the cart as JSON from script.js and stores a real order. */

session_start();
require 'db_connect.php';
require 'csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'errors' => ['Invalid request method.']]);
    exit;
}

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

if (empty($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'errors' => ['Please sign in before checking out.'], 'needsLogin' => true]);
    exit;
}

$cart = $input['cart'] ?? [];
if (!is_array($cart) || count($cart) === 0) {
    echo json_encode(['ok' => false, 'errors' => ['Your cart is empty.']]);
    exit;
}

if (!$db) {
    echo json_encode(['ok' => false, 'errors' => [$db_error]]);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$total = 0.0;
foreach ($cart as $item) {
    $total += (float)($item['price'] ?? 0) * (int)($item['qty'] ?? 0);
}

try {
    $db->begin_transaction();

    $status = 'pending';
    $stmt = $db->prepare('INSERT INTO orders (user_id, total, status) VALUES (?, ?, ?)');
    $stmt->bind_param('ids', $user_id, $total, $status);
    $stmt->execute();
    $order_id = $db->insert_id;
    $stmt->close();

    $stmt = $db->prepare('INSERT INTO order_items (order_id, product_name, price, quantity) VALUES (?, ?, ?, ?)');
    foreach ($cart as $item) {
        $name  = (string)($item['name'] ?? 'Item');
        $price = (float)($item['price'] ?? 0);
        $qty   = (int)($item['qty'] ?? 1);
        $stmt->bind_param('isdi', $order_id, $name, $price, $qty);
        $stmt->execute();
    }
    $stmt->close();

    $db->commit();

    $clear = $db->prepare('DELETE FROM cart_items WHERE user_id = ?');
    $clear->bind_param('i', $user_id);
    $clear->execute();
    $clear->close();
    
    echo json_encode(['ok' => true, 'order_id' => $order_id]);

} catch (mysqli_sql_exception $e) {
    $db->rollback();
    error_log('checkout.php error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'errors' => ['Something went wrong placing your order. Please try again.']]);
}