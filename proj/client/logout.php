<?php
// Logout: destroy session and redirect to shop
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Clear all session data
$_SESSION = [];

// Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Redirect to shop or homepage
header('Location: ./shop.php');
exit;
