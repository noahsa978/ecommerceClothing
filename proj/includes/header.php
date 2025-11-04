<?php
// Set default page title if not provided
$__session_started = false;
if (function_exists('session_status')) {
  if (session_status() === PHP_SESSION_NONE) { session_start(); $__session_started = true; }
}
$page_title = isset($page_title) ? $page_title : 'Ecom Clothing';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/client.css">
  </head>
  <body>
    <header class="navbar">
      <div class="container nav-row">
        <div class="brand">
          <a href="./homepage.php">Ecom<span>Clothing</span></a>
        </div>
        <nav class="links">
          <a href="./homepage.php">Home</a>
          <a href="./shop.php">Shop</a>
          <a href="./about.php">About</a>
          <a href="./contact.php">Help</a>
        </nav>
        <div class="nav-actions">
          <a class="btn" href="./cart.php">Cart</a>
          <?php if (!empty($_SESSION['user_id'])) { ?>
            <a href="./profile.php" title="My Account" class="btn primary" style="display:inline-flex; align-items:center; justify-content:center; padding:6px 8px;">
              <img src="../images/Profile_Icon.png" alt="Profile" style="width:24px; height:24px; display:block;" />
            </a>
          <?php } else { ?>
            <a class="btn primary" href="./loginc.php">Login</a>
          <?php } ?>
        </div>
      </div>
    </header>