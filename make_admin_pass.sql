<?php
$email    = 'rogen@gmail.com';
$password = 'rogen2026123'; // or leave random
$hash     = password_hash($password, PASSWORD_DEFAULT);

echo "UPDATE users SET password = '$hash', role = 'admin' WHERE email = '$email';\n";
