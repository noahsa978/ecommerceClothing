<?php
// Endpoint: Create order in the new `orders` table schema described by the user
// Saves: email, first/last, city, delivery location, phone, payment_method, transaction_ref, payment_screenshot (path),
//        is_guest, user_id (nullable), order_items (JSON), total_amount, status='pending'

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db_connect.php';

try {
  if (!($conn instanceof mysqli)) { throw new Exception('DB connection not available'); }

  $is_logged_in = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
  $userId = $is_logged_in ? (int)$_SESSION['user_id'] : null;
  $sessionId = session_id();

  // Validate inputs
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  $first = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
  $last = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
  $city = isset($_POST['city']) ? trim($_POST['city']) : '';
  $delivery = isset($_POST['delivery_location']) ? trim($_POST['delivery_location']) : '';
  $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
  $payment = isset($_POST['payment']) ? trim($_POST['payment']) : '';
  $receipt = isset($_POST['receipt_number']) ? trim($_POST['receipt_number']) : '';

  if ($first === '' || $last === '' || $city === '' || $delivery === '' || $phone === '' || $payment === '') {
    throw new Exception('Missing required fields');
  }
  if (!$is_logged_in && $email === '') { throw new Exception('Email required for guests'); }

  // Map payment to DB enum
  // telebirr/cbe -> bank, cash -> cod
  $dbPaymentMethod = 'bank';
  if ($payment === 'cash') { $dbPaymentMethod = 'cod'; }
  elseif ($payment === 'telebirr' || $payment === 'cbe') { $dbPaymentMethod = 'bank'; }
  elseif ($payment === 'card') { $dbPaymentMethod = 'card'; }
  elseif ($payment === 'paypal') { $dbPaymentMethod = 'paypal'; }

  // Payment screenshot handling (required if telebirr/cbe)
  $screenshotPath = null;
  $needsScreenshot = ($dbPaymentMethod === 'bank');
  if ($needsScreenshot) {
    if (!isset($_FILES['payment_screenshot']) || !is_uploaded_file($_FILES['payment_screenshot']['tmp_name'])) {
      throw new Exception('Payment screenshot is required for bank payments');
    }
  }

  // Load active cart and items to compute totals and JSON payload
  $cartId = null;
  if ($userId) {
    if ($st = $conn->prepare('SELECT id FROM carts WHERE user_id=? AND status="active" ORDER BY id DESC LIMIT 1')) {
      $st->bind_param('i', $userId);
      $st->execute();
      $r = $st->get_result();
      if ($row = $r->fetch_assoc()) { $cartId = (int)$row['id']; }
      $st->close();
    }
    if ($cartId === null && $sessionId && ($st = $conn->prepare('SELECT id FROM carts WHERE session_id=? AND status="active" ORDER BY id DESC LIMIT 1'))) {
      $st->bind_param('s', $sessionId);
      $st->execute();
      $r = $st->get_result();
      if ($row = $r->fetch_assoc()) { $cartId = (int)$row['id']; }
      $st->close();
    }
  } else {
    if ($sessionId && ($st = $conn->prepare('SELECT id FROM carts WHERE session_id=? AND status="active" ORDER BY id DESC LIMIT 1'))) {
      $st->bind_param('s', $sessionId);
      $st->execute();
      $r = $st->get_result();
      if ($row = $r->fetch_assoc()) { $cartId = (int)$row['id']; }
      $st->close();
    }
  }
  if (!$cartId) { throw new Exception('No active cart found'); }

  $items = [];
  $subtotal = 0.0; $shippingFee = 0.0; $tax = 0.0; $total = 0.0;
  $sql = 'SELECT ci.quantity, ci.price, ci.variant_id, v.size, v.color, p.name FROM cart_items ci JOIN product_variants v ON v.id=ci.variant_id JOIN products p ON p.id=v.product_id WHERE ci.cart_id=? ORDER BY ci.id DESC';
  if (!($st = $conn->prepare($sql))) { throw new Exception('Failed to prepare cart items'); }
  $st->bind_param('i', $cartId);
  $st->execute();
  $res = $st->get_result();
  while ($row = $res->fetch_assoc()) {
    $row['quantity'] = (int)$row['quantity'];
    $row['price'] = (float)$row['price'];
    $row['line_total'] = $row['price'] * $row['quantity'];
    $subtotal += $row['line_total'];
    $items[] = [
      'name' => (string)$row['name'],
      'variant_id' => (int)$row['variant_id'],
      'size' => (string)($row['size'] ?? ''),
      'color' => (string)($row['color'] ?? ''),
      'quantity' => (int)$row['quantity'],
      'price' => (float)$row['price'],
      'line_total' => (float)$row['line_total']
    ];
  }
  $st->close();
  if (empty($items)) { throw new Exception('Cart is empty'); }

  $shippingFee = $subtotal >= 100 ? 0.0 : ($subtotal > 0 ? 9.99 : 0.0);
  $tax = $subtotal * 0.084;
  $total = $subtotal + $shippingFee + $tax;

  // Upload screenshot if present
  if ($needsScreenshot && isset($_FILES['payment_screenshot']) && is_uploaded_file($_FILES['payment_screenshot']['tmp_name'])) {
    $dir = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'payments';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    if (!is_dir($dir) || !is_writable($dir)) {
      throw new Exception('Upload directory not writable');
    }
    $ext = pathinfo($_FILES['payment_screenshot']['name'], PATHINFO_EXTENSION);
    $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
    $filename = 'proof_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . ($safeExt ? ('.' . $safeExt) : '');
    $target = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($_FILES['payment_screenshot']['tmp_name'], $target)) {
      throw new Exception('Failed to save payment screenshot');
    }
    // Store relative path from client folder for serving
    $relativeBase = str_replace('\\', '/', realpath(__DIR__ . '/../'));
    $relativePath = str_replace('\\', '/', $target);
    $screenshotPath = str_replace($relativeBase, '', $relativePath);
    if (strlen($screenshotPath) > 0 && $screenshotPath[0] !== '/') { $screenshotPath = '/' . $screenshotPath; }
  }

  // Begin transaction: stock updates + order insert should be atomic
  $conn->begin_transaction();

  // Decrement stock for each variant and log movement
  $stStock = $conn->prepare('UPDATE product_variants SET stock = stock - ? WHERE id=? AND stock >= ?');
  $stLog = $conn->prepare('INSERT INTO stock_movements (variant_id, change_amount, reason) VALUES (?,?,?)');
  if (!$stStock || !$stLog) {
    $conn->rollback();
    throw new Exception('Failed to prepare stock statements');
  }
  $reason = 'order';
  foreach ($items as $it) {
    $qty = (int)$it['quantity'];
    $variantId = (int)$it['variant_id'];
    $stStock->bind_param('iii', $qty, $variantId, $qty);
    if (!$stStock->execute() || $stStock->affected_rows === 0) {
      $conn->rollback();
      throw new Exception('Insufficient stock for one or more items');
    }
    $change = -$qty;
    $stLog->bind_param('iis', $variantId, $change, $reason);
    if (!$stLog->execute()) {
      $conn->rollback();
      throw new Exception('Failed to log stock movement');
    }
  }

  // Insert into orders table per provided schema
  $isGuest = $is_logged_in ? 0 : 1;
  $itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($itemsJson === false) { throw new Exception('Failed to encode order items'); }

  $status = 'pending';

  $sql = 'INSERT INTO orders (user_id, is_guest, email, first_name, last_name, city, delivery_location, phone_number, payment_method, transaction_ref, payment_screenshot, order_items, total_amount, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
  $st = $conn->prepare($sql);
  if (!$st) { throw new Exception('Failed to prepare order insert'); }
  $st->bind_param(
    'iissssssssssds',
    $userId,
    $isGuest,
    $email,
    $first,
    $last,
    $city,
    $delivery,
    $phone,
    $dbPaymentMethod,
    $receipt,
    $screenshotPath,
    $itemsJson,
    $total,
    $status
  );
  if (!$st->execute()) { throw new Exception('Failed to create order: ' . $st->error); }
  $orderId = (int)$st->insert_id;
  $st->close();

  // Optional: mark cart as ordered
  if ($cartId && ($st = $conn->prepare('UPDATE carts SET status="ordered" WHERE id=?'))) {
    $st->bind_param('i', $cartId);
    $st->execute();
    $st->close();
  }

  // Commit all changes
  $conn->commit();

  echo json_encode([ 'success' => true, 'order_id' => $orderId ]);
  exit;
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode([ 'success' => false, 'error' => $e->getMessage() ]);
  exit;
}
