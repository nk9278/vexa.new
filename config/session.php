<?php
// File: /config/session.php

// Configure session parameters before starting
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Set to 1 if using HTTPS

session_start();

// Basic session hijacking prevention
if (isset($_SESSION['last_ip']) === false) {
    $_SESSION['last_ip'] = $_SERVER['REMOTE_ADDR'];
}
if ($_SESSION['last_ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
