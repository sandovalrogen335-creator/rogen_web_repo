<?php
/* HOMI FURNITURES — handles the Quote and Contact form submissions
   from index.php's modals. Called via fetch() from script.js. */

session_start();
require 'db_connect.php';
require 'csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'errors' => ['Invalid request method.']]);
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'errors' => ['Your session expired. Please refresh the page and try again.']]);
    exit;
}

if (!$db) {
    echo json_encode(['ok' => false, 'errors' => [$db_error]]);
    exit;
}

$type   = $_POST['type'] ?? '';
$errors = [];

try {
    if ($type === 'quote') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $interest = trim($_POST['interest'] ?? '');
        $message  = trim($_POST['message'] ?? '');

        if ($name === '')                               $errors[] = 'Please enter your full name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Please enter a valid email address.';

        if (!$errors) {
            $stmt = $db->prepare('INSERT INTO quote_requests (name, email, phone, interest, message) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssss', $name, $email, $phone, $interest, $message);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['ok' => true]);
            exit;
        }

    } elseif ($type === 'contact') {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '')                                $errors[] = 'Please enter your name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = 'Please enter a valid email address.';
        if ($message === '')                              $errors[] = 'Please enter a message.';

        if (!$errors) {
            $stmt = $db->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $name, $email, $message);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['ok' => true]);
            exit;
        }

    } else {
        $errors[] = 'Unknown form type.';
    }
} catch (mysqli_sql_exception $e) {
    error_log('submit_form.php error: ' . $e->getMessage());
    $errors[] = 'Something went wrong. Please try again.';
}

echo json_encode(['ok' => false, 'errors' => $errors]);