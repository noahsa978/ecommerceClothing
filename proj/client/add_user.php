<?php
require_once __DIR__ . '/../includes/db_connect.php';

$username = 'stefuser';
$email = 'stefstefanian@gmail.com';
$password = password_hash('123456', PASSWORD_DEFAULT);
$role = 'customer';
$fullname = 'Stefan Stefanian';
$address = 'Gotera, Addis Ababa';
$phone = '0941923252';

$stmt = $conn->prepare("INSERT INTO users (username, email, upassword, role, fullname, address, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('sssssss', $username, $email, $password, $role, $fullname, $address, $phone);
$stmt->execute();

echo $stmt->affected_rows > 0 ? "✅ User added successfully" : "❌ Failed to add user";
$stmt->close();
$conn->close();
?>
