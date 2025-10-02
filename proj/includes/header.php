<?php
// Set default page title if not provided
$page_title = isset($page_title) ? $page_title : 'Ecom Clothing';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style>
      :root {
        --bg: #0b1220;
        --text: #e5e7eb;
        --muted: #9ca3af;
        --card: #111827;
        --accent: #7c3aed;
        --accent-600: #6d28d9;
        --accent-2: #10b981;
        --border: rgba(255, 255, 255, 0.08);
      }
      * {
        box-sizing: border-box;
      }
      html,
      body {
        height: 100%;
      }
      body {
        margin: 0;
        font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
        color: var(--text);
        background: radial-gradient(
            900px 400px at 0% 0%,
            rgba(124, 58, 237, 0.12),
            transparent 60%
          ),
          radial-gradient(
            900px 400px at 100% 100%,
            rgba(16, 185, 129, 0.12),
            transparent 60%
          ),
          var(--bg);
      }
      a {
        color: inherit;
        text-decoration: none;
      }
      .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 16px;
      }

      /* Navbar */
      .navbar {
        position: sticky;
        top: 0;
        z-index: 50;
        backdrop-filter: saturate(140%) blur(6px);
        background: rgba(11, 18, 32, 0.7);
        border-bottom: 1px solid var(--border);
      }
      .nav-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 0;
      }
      .brand {
        font-weight: 800;
        letter-spacing: 0.02em;
      }
      .brand span {
        color: var(--accent);
      }
      .links {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
      }
      .links a {
        padding: 8px 10px;
        border-radius: 10px;
        color: #cbd5e1;
      }
      .links a:hover {
        background: rgba(124, 58, 237, 0.12);
        color: #fff;
      }
      .nav-actions {
        display: flex;
        align-items: center;
        gap: 10px;
      }
      .search {
        position: relative;
      }
      .search input {
        width: 220px;
        padding: 10px 12px;
        padding-left: 34px;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: #0b1220;
        color: var(--text);
        outline: none;
      }
      .search input::placeholder {
        color: #748094;
      }
      .search .icon {
        position: absolute;
        top: 50%;
        left: 10px;
        transform: translateY(-50%);
        opacity: 0.7;
        font-size: 14px;
      }
      .btn {
        padding: 10px 12px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #0b1220;
        color: var(--text);
      }
      .btn.primary {
        background: var(--accent);
        border-color: transparent;
        box-shadow: 0 8px 20px rgba(124, 58, 237, 0.35);
        transition: transform 0.02s ease, background 0.15s ease,
          box-shadow 0.15s ease;
      }
      .btn.primary:hover {
        background: var(--accent-600);
        transform: translateY(-1px);
        box-shadow: 0 12px 25px rgba(124, 58, 237, 0.45);
      }
      .btn.primary:active {
        transform: translateY(0px);
        box-shadow: 0 6px 15px rgba(124, 58, 237, 0.3);
      }

      @media (max-width: 900px) {
        .search input {
          width: 160px;
        }
      }
      @media (max-width: 600px) {
        .links {
          display: none;
        }
      }
    </style>
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
          <a href="#">Sale</a>
          <a href="#">About</a>
        </nav>
        <div class="nav-actions">
          <div class="search">
            <span class="icon">🔍</span>
            <input type="search" placeholder="Search products" />
          </div>
          <a class="btn" href="./cart.php">Cart</a>
          <a class="btn primary" href="./loginc.php">Login</a>
        </div>
      </div>
    </header>