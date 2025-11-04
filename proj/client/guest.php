<?php
// Guest mode: clear all auth session data and reset session so header shows Login button
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// If you need to preserve cart or other data, copy them out before destroying
// $cart = $_SESSION['cart'] ?? null;

// Clear session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000,
    $params['path'], $params['domain'], $params['secure'], $params['httponly']
  );
}
session_destroy();

// Start a fresh guest session
session_start();
// If you preserved cart, restore it here
// if ($cart !== null) { $_SESSION['cart'] = $cart; }

header('Location: ./homepage.php');
exit;
