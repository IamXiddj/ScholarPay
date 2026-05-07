<?php
// includes/auth.php - Authentication helpers
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function isStudent() {
    return isLoggedIn() && $_SESSION['role'] === 'student';
}

function requireLogin($redirectTo = '/scholarpay/index.php') {
    if (!isLoggedIn()) {
        header("Location: $redirectTo");
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: /scholarpay/student/dashboard.php");
        exit;
    }
}

function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header("Location: /scholarpay/admin/dashboard.php");
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['name'],
        'email' => $_SESSION['email'],
        'role'  => $_SESSION['role'],
    ];
}

function login($email, $password) {
    require_once __DIR__ . '/db.php';
    // logActivity is defined in db.php — already loaded above
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']         = $user['id'];
        $_SESSION['name']            = $user['name'];
        $_SESSION['email']           = $user['email'];
        $_SESSION['role']            = $user['role'];
        $_SESSION['stellar_address'] = $user['stellar_address'];
        logActivity($user['id'], 'LOGIN', 'User logged in');
        return $user;
    }
    return false;
}

function logout() {
    if (isLoggedIn()) {
        require_once __DIR__ . '/db.php';
        logActivity($_SESSION['user_id'], 'LOGOUT', 'User logged out');
    }
    session_destroy();
    header("Location: /scholarpay/index.php");
    exit;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}
