<?php
// File: /config/auth.php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

function loginUser($email, $password, $auto_redirect = true) {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email_address = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['account_status'] !== 'Active') {
            return "Account is " . strtolower($user['account_status']) . ".";
        }

        // Setup session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['agency_id'] = $user['agency_id'];

        if ($auto_redirect) {
            if ($user['force_password_change']) {
                redirect('/force-password-change.php');
            } else {
                redirectUserByRole($user['role_name']);
            }
        }
        return true;
    }
    return "Invalid email or password.";
}

function checkAuth($allowed_roles = []) {
    if (!isset($_SESSION['user_id'])) {
        redirect('/login.php');
    }

    // Protect against skipping force password change
    // Exception: Do not redirect if they are already on the force-password-change.php or logout.php pages
    if (strpos($_SERVER['REQUEST_URI'], 'force-password-change.php') === false && strpos($_SERVER['REQUEST_URI'], 'logout.php') === false) {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT force_password_change FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        if ($stmt->fetchColumn()) {
            redirect('/force-password-change.php');
        }
    }

    if (!empty($allowed_roles) && !in_array($_SESSION['role_name'], $allowed_roles)) {
        // Redirect back to their respective dashboard
        redirectUserByRole($_SESSION['role_name']);
    }
}

function logoutUser() {
    session_unset();
    session_destroy();
    redirect('/login.php');
}
