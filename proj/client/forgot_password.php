<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPMailer/SMTP.php';
require_once __DIR__ . '/../includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Define base URL for localhost
define('BASE_URL', 'http://localhost/ecomClothing2/proj/client/');

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if ($email === '') {
        $msg = 'Please enter your email.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // Store token and expiration
            $update = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE email=?");
            $update->bind_param("sss", $token, $expires, $email);
            $update->execute();

            // Localhost reset link
            $reset_link = BASE_URL . "reset_password.php?token=$token";

            $mail = new PHPMailer(true);

            try {
                $mail->SMTPDebug = 0;
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'stefstefanian@gmail.com'; 
                $mail->Password = 'lmbp qssh plox grxr'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('stefstefanian@gmail.com', 'Ecom Clothing');
                $mail->addReplyTo('stefstefanian@gmail.com', 'Ecom Clothing');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "
                    <h2>Password Reset Request</h2>
                    <p>Click the button below to reset your password:</p>
                    <p><a href='$reset_link' style='background:#10b981;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;'>Reset Password</a></p>
                    <p>This link will expire in 1 hour.</p>
                ";

                $mail->send();
                $msg = '✅ A reset link has been sent to your email.';
            } catch (Exception $e) {
                $msg = "❌ Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $msg = '❌ Email not found.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password</title>
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
<h2>Forgot Password</h2>
<input type="email" name="email" placeholder="you@example.com" required>
<button type="submit">Send Reset Link</button>
<?php if($msg): ?>
<p><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>
<p style="margin-top:10px;"><a href="<?= BASE_URL ?>loginc.php">Back to Login</a></p>
</form>
</body>
</html>
