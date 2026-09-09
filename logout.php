<?php
/* HOMI FURNITURES — Log out: ends the session and returns to the login page. */

session_start();

// Remove all session variables and destroy the session
$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;