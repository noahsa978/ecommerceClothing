<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db_connect.php';
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  if ($email === '' || $password === '') {
    $login_error = 'Please enter email and password.';
  } elseif ($conn instanceof mysqli) {
    $stmt = $conn->prepare('SELECT id, username, email, upassword, role, fullname FROM users WHERE email=? LIMIT 1');
    $stmt->bind_param('s', $email);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      if ($row = $res->fetch_assoc()) {
        if (password_verify($password, $row['upassword'])) {
          if (strcasecmp($row['role'], 'admin') === 0) {
            $_SESSION['user_id'] = (int)$row['id'];
            $_SESSION['user'] = [
              'email' => $row['email'],
              'role'  => $row['role'],
              'fullname' => $row['fullname'],
              'username' => $row['username'],
            ];
            // Ensure admin session keys are also set for admin area compatibility
            $_SESSION['admin_id'] = (int)$row['id'];
            $_SESSION['admin_email'] = $row['email'];
            $_SESSION['admin_name'] = $row['fullname'] ?: $row['username'];
            // Optional general email for broader session fallbacks
            $_SESSION['email'] = $row['email'];
            header('Location: ../admin/users.php');
            exit;
          } else {
            $login_error = 'Not authorized: account is not an admin.';
          }
        }
      }
      if ($login_error === '') {
        $login_error = 'Invalid email or password.';
      }
    } else {
      $login_error = 'Login failed. Please try again.';
    }
    $stmt->close();
  } else {
    $login_error = 'Database connection not available.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login</title>
    <style>
      :root {
        --accent: #7c3aed;
        --accent-600: #6d28d9;
        --bg: #0f172a;
        --card: #111827;
        --muted: #9ca3af;
      }
      * { box-sizing: border-box; }
      html, body { height: 100%; }
      body {
        margin: 0;
        font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
        color: #e5e7eb;
        background: radial-gradient(800px 400px at 10% 10%, rgba(124, 58, 237, 0.15), transparent 60%),
                    radial-gradient(800px 400px at 90% 90%, rgba(59, 130, 246, 0.15), transparent 60%),
                    #0b1220;
      }
      main.auth-page { min-height: 100%; display: grid; place-items: center; padding: 24px }
      .auth-card { width: 100%; max-width: 720px; background: rgba(17,24,39,0.85); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.35) }
      h1 { margin: 0 0 18px; font-size: 24px; line-height: 1.2; letter-spacing: -0.02em; }
      .subtitle { margin: -6px 0 18px; color: var(--muted); font-size: 14px; }
      form { display: grid; gap: 14px }
      label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; }
      input[type="email"], input[type="password"] {
        width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid #1f2937; background: #0b1220; color: #e5e7eb; outline: none; transition: border-color .15s, box-shadow .15s;
      }
      input::placeholder { color: #6b7280; }
      input:focus { border-color: var(--accent); box-shadow: 0 0 0 4px rgba(124,58,237,0.15); }
      .row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 4px; }
      .actions { display: grid; gap: 10px; margin-top: 6px }
      .btn, button {
        appearance: none; border: 0; border-radius: 12px; padding: 12px 16px; background: var(--accent); color: #0b1220; font-weight: 800; letter-spacing: .02em; cursor: pointer; transition: transform .02s ease, background .15s ease, box-shadow .15s ease; box-shadow: 0 8px 20px rgba(124, 58, 237, 0.35);
      }
      .btn:hover, button:hover { background: var(--accent-600); color: #e5e7eb; }
      .btn:active, button:active { transform: translateY(1px); }
      .link { color: #93c5fd; text-decoration: none; font-size: 14px; }
      .link:hover { text-decoration: underline; }
      .logo { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; color: #e5e7eb; }
      .badge { display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: .06em; color: #0b1220; background: linear-gradient(90deg, #7c3aed, #60a5fa); padding: 4px 8px; border-radius: 999px; }
    </style>
  </head>
  <body>
    <main class="auth-page">
      <section class="auth-card">
        <div class="logo">
          <span class="badge">ADMIN</span>
          <h1>Welcome back</h1>
        </div>
        <div style="display:flex; gap:8px; align-items:center; margin:-6px 0 12px; font-size:14px; color:#9ca3af">
          <span>Login type:</span>
          <span style="padding:4px 10px; border-radius:999px; background:rgba(124,58,237,0.15); color:#c4b5fd; font-weight:700;">Admin</span>
          <a class="link" href="./loginc.php" style="padding:4px 10px; border:1px solid #1f2937; border-radius:999px;">Customer</a>
        </div>
        <p class="subtitle">Sign in to manage products, orders, and users.</p>
        <?php if (!empty($login_error)) { ?>
          <div style="background: rgba(239,68,68,0.1); border: 1px solid #ef4444; color: #fecaca; padding: 10px 12px; border-radius: 10px; margin-bottom: 12px;">
            <?= htmlspecialchars($login_error); ?>
          </div>
        <?php } ?>
        <form method="post" action="">
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required placeholder="admin@example.com" />
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="6" placeholder="••••••••" />
          </div>
          <div class="actions">
            <button class="btn" type="submit">Login</button>
          </div>
          <p class="subtitle" style="margin-top:8px">Need customer login? <a class="link" href="./loginc.php">Go to customer login</a></p>
        </form>
      </section>
    </main>
  </body>
</html>
