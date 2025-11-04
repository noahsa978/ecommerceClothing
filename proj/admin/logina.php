<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and handle admin login
session_start();
require_once __DIR__ . '/../includes/db_connect.php'; // provides $conn (mysqli)

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  $password = isset($_POST['password']) ? $_POST['password'] : '';

  if ($email === '' || $password === '') {
    $error = 'Please provide both email and password.';
  } else if ($conn instanceof mysqli) {
    // Lookup user by email with admin role only
    $stmt = $conn->prepare('SELECT id, username, email, upassword, role, fullname FROM users WHERE email = ? AND role = "admin" LIMIT 1');
    if ($stmt) {
      $stmt->bind_param('s', $email);
      $stmt->execute();
      $result = $stmt->get_result();
      $user = $result ? $result->fetch_assoc() : null;
      $stmt->close();

      if ($user) {
        $stored = $user['upassword'];
        $ok = false;
        // Support hashed passwords (recommended)
        if (is_string($stored) && strlen($stored) >= 20) {
          $ok = password_verify($password, $stored);
        }
        // Fallback to plain match if not hashed
        if (!$ok && hash('sha256', $password) === $stored) {
          $ok = true; // support sha256 stored hash (optional)
        }
        if (!$ok && $password === $stored) {
          $ok = true;
        }

        if ($ok && $user['role'] === 'admin') {
          // Set session for admin
          $_SESSION['admin_id'] = $user['id'];
          $_SESSION['admin_email'] = $user['email'];
          $_SESSION['admin_name'] = $user['fullname'] ?: $user['username'];
          // Also set a general email key for flexible fallbacks
          $_SESSION['email'] = $user['email'];
          // Redirect to admin dashboard
          header('Location: dashboard.php');
          exit;
        } else {
          $error = 'Invalid credentials or not authorized.';
        }
      } else {
        $error = 'Invalid credentials or not authorized.';
      }
    } else {
      $error = 'Database error. Please try again later.';
    }
  } else {
    $error = 'Database connection not available.';
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
        color: #e5e7eb;
        background: linear-gradient(135deg, #0b1220, #111827 60%, #0b1220);
      }
      main {
        min-height: 100%;
        display: grid;
        place-items: center;
        padding: 24px;
      }
      .card {
        width: 100%;
        max-width: 420px;
        background: rgba(17, 24, 39, 0.85);
        backdrop-filter: saturate(140%) blur(6px);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
      }
      h1 {
        margin: 0 0 18px;
        font-size: 24px;
        line-height: 1.2;
        letter-spacing: -0.02em;
      }
      .subtitle {
        margin: -6px 0 18px;
        color: var(--muted);
        font-size: 14px;
      }
      form {
        display: grid;
        gap: 14px;
      }
      label {
        display: block;
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 6px;
      }
      input[type="email"],
      input[type="password"] {
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid #1f2937;
        background: #0b1220;
        color: #e5e7eb;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
      }
      input::placeholder {
        color: #6b7280;
      }
      input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.15);
      }
      .row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 4px;
      }
      .checkbox {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #cbd5e1;
      }
      .actions {
        display: grid;
        gap: 10px;
        margin-top: 6px;
      }
      button {
        appearance: none;
        border: 0;
        border-radius: 12px;
        padding: 12px 16px;
        background: var(--accent);
        color: #fff;
        font-weight: 700;
        letter-spacing: 0.02em;
        cursor: pointer;
        transition: transform 0.02s ease, background 0.15s ease,
          box-shadow 0.15s ease;
        box-shadow: 0 8px 20px rgba(124, 58, 237, 0.35);
      }
      button:hover {
        background: var(--accent-600);
      }
      button:active {
        transform: translateY(1px);
      }
      .link {
        color: #93c5fd;
        text-decoration: none;
        font-size: 14px;
      }
      .link:hover {
        text-decoration: underline;
      }
      .logo {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
        color: #e5e7eb;
      }
      .badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.06em;
        color: #fff;
        background: linear-gradient(90deg, #ef4444, #f59e0b);
        padding: 4px 8px;
        border-radius: 999px;
      }
    </style>
  </head>
  <body>
    <main>
      <section class="card">
        <div class="logo">
          <span class="badge">ADMIN</span>
          <h1>Sign in</h1>
        </div>
        <p class="subtitle">
          Access the dashboard with your administrator credentials.
        </p>
        <?php if (!empty($error)) { ?>
          <div style="margin:10px 0; padding:10px 12px; border:1px solid #7f1d1d; background:#3f1d1d; color:#fecaca; border-radius:10px; font-size:14px;">
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php } ?>
        <form method="post" action="">
          <div>
            <label for="email">Email</label>
            <input
              type="email"
              id="email"
              name="email"
              required
              placeholder="admin@example.com"
            />
          </div>
          <div>
            <label for="password">Password</label>
            <input
              type="password"
              id="password"
              name="password"
              required
              minlength="6"
              placeholder="••••••••"
            />
          </div>
          <div class="row">
            <label class="checkbox">
              <input type="checkbox" name="remember" />
              Remember me
            </label>
            <a class="link" href="#">Forgot password?</a>
          </div>
          <div class="actions">
            <button type="submit">Sign in</button>
          </div>
        </form>
      </section>
    </main>
  </body>
</html>
