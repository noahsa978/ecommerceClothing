<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Handle registration submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first = trim($_POST['first_name'] ?? '');
  $last = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $city = trim($_POST['city'] ?? '');
  $delivery = trim($_POST['delivery_location'] ?? '');

  if ($first === '') $errors[] = 'First name is required';
  if ($last === '') $errors[] = 'Last name is required';
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
  if ($password === '' || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
  if ($phone === '') $errors[] = 'Phone is required';
  if ($city === '') $errors[] = 'City is required';
  if ($delivery === '') $errors[] = 'Delivery location is required';

  if (!$errors) {
    // In a real app, insert into DB then set session based on inserted user
    $_SESSION['user_id'] = $_SESSION['user_id'] ?? rand(1000,9999);
    $_SESSION['user'] = [
      'first_name' => $first,
      'last_name'  => $last,
      'email'      => $email,
      'phone'      => $phone,
    ];
    $_SESSION['shipping'] = [
      'first_name' => $first,
      'last_name'  => $last,
      'phone'      => $phone,
      'city'       => $city,
      'delivery_location' => $delivery,
    ];

    header('Location: ./profile.php');
    exit;
  }
}

$page_title = 'Register — Ecom clothing';

?>

<style>
  :root { --accent:#10b981; --accent-600:#059669; --bg:#0f172a; --muted:#9ca3af }
  body { background: radial-gradient(800px 400px at 10% 10%, rgba(16,185,129,0.15), transparent 60%), radial-gradient(800px 400px at 90% 90%, rgba(59,130,246,0.15), transparent 60%), #0b1220 }
  .auth-page { padding: 28px 0; min-height: 100dvh; display: grid; place-items: center }
  .auth-card { width: 100%; max-width: 720px; background: rgba(17,24,39,0.85); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.35) }
  .auth-card h1 { margin: 0 0 16px; font-size: 24px; color: #e5e7eb }
  .subtitle { margin: -6px 0 18px; color: var(--muted); font-size: 14px }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px }
  .form-group { margin-bottom: 12px }
  .form-group label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: #e5e7eb }
  .form-group input, .form-group select { width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid #1f2937; background: #0b1220; color: #e5e7eb; outline: none; transition: border-color .15s, box-shadow .15s }
  .form-group input:focus, .form-group select:focus { border-color: var(--accent); box-shadow: 0 0 0 4px rgba(16,185,129,0.15) }
  .btn { padding: 12px 16px; border-radius: 12px; background: var(--accent); color: #0b1220; border: none; cursor: pointer; width: 100%; font-weight: 800; letter-spacing: .02em; box-shadow: 0 8px 20px rgba(16,185,129,0.35) }
  .btn:hover { background: var(--accent-600); color: #e5e7eb }
  .btn:active { transform: translateY(1px) }
  .link { color: #93c5fd; text-decoration: none; font-size: 14px }
  .link:hover { text-decoration: underline }
  .error { background: rgba(239,68,68,0.1); border: 1px solid #ef4444; color: #fecaca; padding: 10px 12px; border-radius: 10px; margin-bottom: 12px }
  @media (max-width: 900px) { .form-row { grid-template-columns: 1fr } }
</style>

<main class="container auth-page">
  <div class="auth-card">
    <h1>Create account</h1>
    <p class="subtitle">Create an account to track orders and save your shipping details for faster checkout.</p>

    <?php if ($errors): ?>
      <div class="error">
        <?php foreach ($errors as $e): ?>
          <div>- <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="form-row">
        <div class="form-group">
          <label for="first_name">First Name</label>
          <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label for="last_name">Last Name</label>
          <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="phone">Phone</label>
          <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label for="city">City</label>
          <?php $selectedCity = $_POST['city'] ?? ''; ?>
          <select id="city" name="city" required>
            <option value="">Select City</option>
            <option value="Addis Ababa" <?= ($selectedCity==='Addis Ababa')?'selected':'' ?>>Addis Ababa</option>
            <option value="Adama" <?= ($selectedCity==='Adama')?'selected':'' ?>>Adama</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label for="delivery_location">Delivery Location</label>
        <?php $selectedLoc = $_POST['delivery_location'] ?? ''; ?>
        <select id="delivery_location" name="delivery_location" required>
          <option value="">Select Delivery Location</option>
          <?php if ($selectedCity==='Addis Ababa'): $opts=['Megenagna','Ayat','Mexico','Haile Garment','Betel'];
            foreach ($opts as $o): $sel = ($selectedLoc===$o)?'selected':''; ?>
              <option <?= $sel ?>><?= htmlspecialchars($o) ?></option>
          <?php endforeach; elseif ($selectedCity==='Adama'): $opts=['Derartu Tulu Square','Adama University'];
            foreach ($opts as $o): $sel = ($selectedLoc===$o)?'selected':''; ?>
              <option <?= $sel ?>><?= htmlspecialchars($o) ?></option>
          <?php endforeach; endif; ?>
        </select>
      </div>
      <button class="btn" type="submit">Create Account</button>
      <p style="margin-top:10px; color:#9ca3af">Have an account? <a href="./loginc.php" style="color: var(--accent); text-decoration: none;">Go to login</a></p>
    </form>
  </div>
</main>