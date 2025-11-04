<?php
require_once __DIR__ . '/../includes/db_connect.php';

// Define base URL for localhost
define('BASE_URL', 'http://localhost/ecomClothing2/proj/client/');

$msg = '';
$show_form = false;

// Get token from URL
$token = $_GET['token'] ?? '';

if (!$token) {
    $msg = '❌ Invalid reset link.';
} else {
    $stmt = $conn->prepare("SELECT id, email, reset_expires FROM users WHERE reset_token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($user = $res->fetch_assoc()) {
        $expires = strtotime($user['reset_expires']);
        if ($expires < time()) {
            $msg = '❌ This reset link has expired.';
        } else {
            $show_form = true;
        }
    } else {
        $msg = '❌ Invalid reset link.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password === '' || $confirm_password === '') {
        $msg = 'Please fill in all fields.';
        $show_form = true;
    } elseif ($password !== $confirm_password) {
        $msg = 'Passwords do not match.';
        $show_form = true;
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE users SET upassword = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update->bind_param("si", $hashed_password, $user['id']);
        $update->execute();

        $msg = '✅ Your password has been reset successfully. You can now <a href="'.BASE_URL.'login.php">login</a>.';
        $show_form = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>
<style>
body { background:#0b1220; color:#fff; font-family:sans-serif; display:grid; place-items:center; height:100vh }
form { background:#111827; padding:24px; border-radius:12px; width:320px }
input,button { width:100%; padding:10px; margin-top:10px; border-radius:8px; border:1px solid #1f2937; }
button { background:#10b981; color:#0b1220; font-weight:bold; cursor:pointer }
p { margin-top:10px; }
a { color:#10b981; text-decoration:none; }
</style>
</head>
<body>

<form method="POST">
<h2>Reset Password</h2>
<?php if ($msg): ?>
<p><?= $msg ?></p>
<?php endif; ?>

<?php if ($show_form): ?>
<input type="password" name="password" placeholder="New Password" required>
<input type="password" name="confirm_password" placeholder="Confirm Password" required>
<button type="submit">Reset Password</button>
<?php endif; ?>

<p style="margin-top:10px;"><a href="<?= BASE_URL ?>loginc.php">Back to Login</a></p>
</form>

</body>
</html>
