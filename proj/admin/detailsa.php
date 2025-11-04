<?php
$page_title = 'Admin • Order Details';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db_connect.php';

$order = null;
$error = null;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { $error = 'Invalid order id.'; }

if (!$error && ($conn instanceof mysqli)) {
  $sql = 'SELECT * FROM orders WHERE id=? LIMIT 1';
  if ($st = $conn->prepare($sql)) {
    $st->bind_param('i', $id);
    $st->execute();
    $res = $st->get_result();
    if ($row = $res->fetch_assoc()) { $order = $row; }
    $st->close();
  } else {
    $error = 'Failed to prepare query';
  }
  if (!$order) { $error = 'Order not found.'; }
}

// Parse items JSON safely
$items = [];
if ($order && !empty($order['order_items'])) {
  $decoded = json_decode($order['order_items'], true);
  if (is_array($decoded)) { $items = $decoded; }
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= h($page_title) ?></title>
  <link rel="stylesheet" href="../assets/css/global.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <style>
    .row { display:grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .muted { color: var(--muted); font-size: 13px; }
    img.screenshot { max-width: 360px; width: 100%; height: auto; border: 1px solid var(--border); border-radius: 8px; }
  </style>
</head>
<body>
  <main class="container">
    <div style="margin-bottom: 12px;">
      <a class="btn" href="orders.php">← Back to Orders</a>
    </div>

    <div class="card">
      <div class="card-head">Order Details</div>
      <div class="card-body">
        <?php if ($error): ?>
          <div style="padding:10px; border:1px solid var(--border); border-radius:8px; background: rgba(239,68,68,0.12);"><?= h($error) ?></div>
        <?php else: ?>
          <div class="row">
            <div>
              <div><span class="muted">Order ID:</span> #<?= (int)$order['id'] ?></div>
              <div><span class="muted">Status:</span> <?= h($order['status']) ?></div>
              <div><span class="muted">Created:</span> <?= h($order['created_at']) ?></div>
              <div><span class="muted">Updated:</span> <?= h($order['updated_at']) ?></div>
            </div>
            <div>
              <div><span class="muted">Customer:</span> <?= h(trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?: ($order['email'] ?? '')) ?></div>
              <div><span class="muted">Email:</span> <?= h($order['email']) ?></div>
              <div><span class="muted">Phone:</span> <?= h($order['phone_number']) ?></div>
            </div>
          </div>
          <div class="row" style="margin-top:12px;">
            <div>
              <div><span class="muted">City:</span> <?= h($order['city']) ?></div>
              <div><span class="muted">Delivery Location:</span> <?= h($order['delivery_location']) ?></div>
            </div>
            <div>
              <div><span class="muted">Payment Method:</span> <?= h($order['payment_method']) ?></div>
              <div><span class="muted">Transaction Ref:</span> <?= h($order['transaction_ref']) ?></div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!$error): ?>
    <div class="card">
      <div class="card-head">Items</div>
      <div class="card-body">
        <?php if (empty($items)): ?>
          <div class="muted">No items recorded.</div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Variant ID</th>
                <th>Name</th>
                <th>Size</th>
                <th>Color</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Line Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
              <tr>
                <td><?= h($it['variant_id'] ?? '') ?></td>
                <td><?= h($it['name'] ?? '') ?></td>
                <td><?= h($it['size'] ?? '') ?></td>
                <td><?= h($it['color'] ?? '') ?></td>
                <td><?= h($it['quantity'] ?? '') ?></td>
                <td>$<?= number_format((float)($it['price'] ?? 0), 2) ?></td>
                <td>$<?= number_format((float)($it['line_total'] ?? 0), 2) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-head">Payment</div>
      <div class="card-body">
        <div><span class="muted">Total Amount:</span> $<?= number_format((float)($order['total_amount'] ?? 0), 2) ?></div>
        <?php if (!empty($order['payment_screenshot'])): ?>
          <?php
            $ps = (string)$order['payment_screenshot'];
            // Normalize to a web path under XAMPP http://localhost/ecomClothing2
            if (strpos($ps, 'http://') === 0 || strpos($ps, 'https://') === 0) {
              $webPath = $ps;
            } elseif (strpos($ps, '/uploads/') === 0) {
              // Stored without project prefix; add '/ecomClothing2/proj'
              $webPath = '/ecomClothing2/proj' . $ps;
            } elseif (strpos($ps, '/proj/') === 0) {
              $webPath = '/ecomClothing2' . $ps;
            } elseif ($ps[0] === '/') {
              // Some other absolute path from web root
              $webPath = '/ecomClothing2' . $ps;
            } else {
              // Relative path fallback
              $webPath = '/ecomClothing2/' . ltrim($ps, '/');
            }
          ?>
          <div style="margin-top:10px;" class="muted">Payment Screenshot:</div>
          <div style="margin-top:6px;"><img src="<?= h($webPath) ?>" alt="Payment Screenshot" class="screenshot" /></div>
        <?php else: ?>
          <div class="muted" style="margin-top:10px;">No screenshot uploaded.</div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </main>
</body>
</html>
