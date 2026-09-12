<?php
/**
 * HOMI FURNITURES - CSRF token helpers
 * require this AFTER session_start() on any page with a form or
 * an endpoint that changes data (login, register, checkout, forms).
 */

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify($token) {
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}