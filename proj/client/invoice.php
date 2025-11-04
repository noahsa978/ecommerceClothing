<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db_connect.php';

// Auth: require login
if (empty($_SESSION['user_id'])) {
  http_response_code(302);
  header('Location: ./loginc.php');
  exit;
}
$uid = (int)$_SESSION['user_id'];

// Get order id
$oid = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($oid <= 0) {
  http_response_code(400);
  echo 'Invalid order id';
  exit;
}

// Load order (only if it belongs to user)
$order = null;
if ($conn instanceof mysqli) {
  if ($st = $conn->prepare('SELECT id, user_id, email, first_name, last_name, city, delivery_location, phone_number, payment_method, transaction_ref, order_items, total_amount, status, created_at FROM orders WHERE id=? AND user_id=? LIMIT 1')) {
    $st->bind_param('ii', $oid, $uid);
    if ($st->execute()) {
      $res = $st->get_result();
      $order = $res->fetch_assoc() ?: null;
    }
    $st->close();
  }
}

if (!$order) {
  http_response_code(404);
  echo 'Order not found';
  exit;
}

// Decode items
$items = [];
$raw = $order['order_items'] ?? '[]';
$decoded = json_decode($raw, true);
if (is_array($decoded)) { $items = $decoded; }

// Optionally enrich each item with product/variant details if missing name
function enrich_item($conn, $item) {
  if (!empty($item['name'])) return $item;
  $vid = isset($item['variant_id']) ? (int)$item['variant_id'] : 0;
  if ($vid > 0 && ($conn instanceof mysqli)) {
    $sql = 'SELECT p.name AS product_name, v.size, v.color FROM product_variants v INNER JOIN products p ON p.id=v.product_id WHERE v.id=? LIMIT 1';
    if ($st = $conn->prepare($sql)) {
      $st->bind_param('i', $vid);
      if ($st->execute()) {
        $res = $st->get_result();
        if ($r = $res->fetch_assoc()) {
          $item['name'] = trim(($r['product_name'] ?? 'Variant #'.$vid) . ' ' . ($r['size'] ? ('['.$r['size'].']') : '') . ' ' . ($r['color'] ? ('- '.$r['color']) : ''));
        }
      }
      $st->close();
    }
  }
  if (empty($item['name'])) $item['name'] = 'Variant #'.$vid;
  return $item;
}

$line_total_sum = 0.0;
$qty_sum = 0;
$enriched = [];
foreach ($items as $it) {
  $it = enrich_item($conn, $it);
  $qty = isset($it['quantity']) ? (int)$it['quantity'] : 1;
  $price = isset($it['price']) ? (float)$it['price'] : 0.0;
  $line_total = $qty * $price;
  $qty_sum += max(0, $qty);
  $line_total_sum += $line_total;
  $it['_qty'] = $qty;
  $it['_price'] = $price;
  $it['_line_total'] = $line_total;
  $enriched[] = $it;
}
$total_amount = (float)($order['total_amount'] ?? $line_total_sum);

$full_name = trim(($order['first_name'] ?? '').' '.($order['last_name'] ?? ''));
$created = htmlspecialchars(substr((string)($order['created_at'] ?? ''), 0, 19));
$status = htmlspecialchars((string)($order['status'] ?? 'pending'));
$addr = htmlspecialchars(trim(($order['city'] ?? '').($order['delivery_location'] ? (' - '.$order['delivery_location']) : '')));
$phone = htmlspecialchars((string)($order['phone_number'] ?? ''));
$payment = htmlspecialchars((string)($order['payment_method'] ?? ''));
$tx = htmlspecialchars((string)($order['transaction_ref'] ?? ''));

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Invoice #<?php echo (int)$order['id']; ?></title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color:#e5e7eb; background:#0b1220; margin:0; }
    .wrap { max-width: 900px; margin: 20px auto; background:#111827; border:1px solid rgba(255,255,255,0.08); border-radius:12px; overflow:hidden; }
    header, section { padding:16px; }
    header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid rgba(255,255,255,0.08); }
    h1 { margin:0; font-size:20px; color:#fff; }
    .muted { color:#9ca3af; font-size:13px; }
    table { width:100%; border-collapse:collapse; }
    th, td { text-align:left; padding:10px 12px; border-bottom:1px solid rgba(255,255,255,0.08); }
    th { color:#cbd5e1; }
    .totals { text-align:right; }
    .btn { display:inline-block; padding:8px 12px; border-radius:10px; background:#7c3aed; color:#fff; text-decoration:none; border:1px solid transparent; }
    .grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    @media (max-width: 700px) { .grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <div>
        <h1>Invoice #<?php echo (int)$order['id']; ?></h1>
        <div class="muted">Date: <?php echo $created; ?></div>
        <div class="muted">Status: <?php echo ucfirst($status); ?></div>
      </div>
      <div>
        <a class="btn" href="#" onclick="window.print(); return false;">Download / Print</a>
      </div>
    </header>

    <section class="grid">
      <div>
        <h3 style="margin:0 0 6px; color:#fff;">Billed To</h3>
        <div><?php echo htmlspecialchars($full_name ?: ($_SESSION['user']['fullname'] ?? '')); ?></div>
        <div class="muted"><?php echo $addr; ?></div>
        <div class="muted"><?php echo $phone; ?></div>
        <div class="muted"><?php echo htmlspecialchars($order['email'] ?? ''); ?></div>
      </div>
      <div>
        <h3 style="margin:0 0 6px; color:#fff;">Payment</h3>
        <div class="muted">Method: <?php echo $payment ?: 'N/A'; ?></div>
        <div class="muted">Reference: <?php echo $tx ?: '—'; ?></div>
        <div class="muted">Items: <?php echo (int)$qty_sum; ?></div>
      </div>
    </section>

    <section>
      <table>
        <thead>
          <tr>
            <th style="width: 50%">Item</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Line Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($enriched)) { ?>
            <tr><td colspan="4">No items.</td></tr>
          <?php } else { foreach ($enriched as $it) { ?>
            <tr>
              <td><?php echo htmlspecialchars((string)($it['name'] ?? 'Item')); ?></td>
              <td><?php echo (int)$it['_qty']; ?></td>
              <td>ETB <?php echo number_format((float)$it['_price'], 2); ?></td>
              <td>ETB <?php echo number_format((float)$it['_line_total'], 2); ?></td>
            </tr>
          <?php } } ?>
        </tbody>
      </table>
      <div class="totals" style="padding:12px 0;">
        <div><strong style="color:#fff;">Total:</strong> ETB <?php echo number_format($total_amount, 2); ?></div>
      </div>
    </section>
  </div>
</body>
</html>
