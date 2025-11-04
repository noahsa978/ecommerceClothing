<?php
  if (session_status() === PHP_SESSION_NONE) { session_start(); }
  $page_title = 'Admin • Dashboard';
  require_once __DIR__ . '/../includes/db_connect.php';
  
  // Determine the admin display name from session
  $adminName = $_SESSION['admin_name']
    ?? ($_SESSION['user']['fullname'] ?? ($_SESSION['user']['username'] ?? 'Admin'));
  $adminName = is_string($adminName) ? trim($adminName) : 'Admin';
  $adminFirst = $adminName !== '' ? explode(' ', $adminName)[0] : 'Admin';

  // Initialize stats
  $totalUsers = 0;
  $totalProducts = 0;
  $totalOrders = 0;
  $totalSales = 0.0;
  $recentOrders = [];

  if ($conn instanceof mysqli) {
    // Get total users count
    if ($res = $conn->query("SELECT COUNT(*) as count FROM users")) {
      if ($row = $res->fetch_assoc()) {
        $totalUsers = (int)$row['count'];
      }
      $res->free();
    }

    // Get total products count
    if ($res = $conn->query("SELECT COUNT(*) as count FROM products")) {
      if ($row = $res->fetch_assoc()) {
        $totalProducts = (int)$row['count'];
      }
      $res->free();
    }

    // Get total orders count
    if ($res = $conn->query("SELECT COUNT(*) as count FROM orders")) {
      if ($row = $res->fetch_assoc()) {
        $totalOrders = (int)$row['count'];
      }
      $res->free();
    }

    // Get total sales (sum of all order totals)
    if ($res = $conn->query("SELECT SUM(total_amount) as total_sales FROM orders WHERE status != 'cancelled'")) {
      if ($row = $res->fetch_assoc()) {
        $totalSales = (float)($row['total_sales'] ?? 0);
      }
      $res->free();
    }

    // Get top 5 recent orders
    $sql = "SELECT o.id, o.created_at, o.status, o.total_amount, 
                   COALESCE(u.fullname, o.first_name) as customer_name,
                   COALESCE(u.email, o.email) as customer_email
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
            LIMIT 5";
    if ($res = $conn->query($sql)) {
      while ($row = $res->fetch_assoc()) {
        $recentOrders[] = $row;
      }
      $res->free();
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
  </head>
  <body>

<style>
  /* Dashboard-specific styles */
  .stat { 
    transition: transform 0.2s ease;
    cursor: default;
  }
  .stat:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(124, 58, 237, 0.15); }
  .stat .k { color: var(--accent); }
  
  .card {
    border-radius: 12px;
    overflow: hidden;
  }
  .card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border-bottom: 1px solid var(--border);
  }
  .card-body { padding: 14px; color: var(--muted); }

  .quick-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
  .quick-actions .btn { 
    padding: 10px 12px; 
    border: 1px solid var(--border); 
    border-radius: 12px; 
    background: #0b1220; 
    color: var(--text); 
    text-decoration: none;
    transition: all 0.15s ease;
  }
  .quick-actions .btn:hover { background: rgba(124,58,237,0.12); }
  .quick-actions .btn.primary { background: var(--accent); border-color: transparent; }
  .quick-actions .btn.primary:hover { background: var(--accent-600); }

  table tbody tr { transition: background 0.15s ease; }
  table tbody tr:hover { background: rgba(124, 58, 237, 0.05); }

  @media (max-width: 900px) { .stats { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 700px) { 
    .stats { grid-template-columns: 1fr; } 
    table { font-size: 13px; }
    table th, table td { padding: 8px 10px; }
  }
</style>

<main class="container">
  <section class="admin-header">
    <h1 style="margin:0 0 8px;">Dashboard</h1>
    <nav class="admin-nav" aria-label="Admin navigation">
      <a href="dashboard.php" class="active">Dashboard</a>
      <a href="employees.php">Employees</a>
      <a href="users.php">Users</a>
      <a href="products.php">Products</a>
      <a href="orders.php">Orders</a>
      <a href="inventory.php">Inventory</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <section>
    <h2 style="margin:0 0 6px;">Welcome, <?= htmlspecialchars($adminFirst) ?>!</h2>
    <p style="margin:0 0 16px; color: var(--muted);">Use the menu above to manage your store.</p>
  </section>

  <section>
    <h2 style="margin:0 0 10px;">Quick Actions</h2>
    <div class="quick-actions">
      <a class="btn primary" href="products.php">Add Product</a>
      <a class="btn" href="orders.php">View Orders</a>
      <a class="btn" href="users.php">Manage Users</a>
      <a class="btn" href="reports.php">View Reports</a>
    </div>
  </section>

  <section style="margin-top: 20px;">
    <h2 style="margin:0 0 10px;">Quick Stats</h2>
    <div class="stats">
      <div class="stat">
        <div class="k"><?= number_format($totalUsers) ?></div>
        <div class="l">Users</div>
      </div>
      <div class="stat">
        <div class="k"><?= number_format($totalProducts) ?></div>
        <div class="l">Products</div>
      </div>
      <div class="stat">
        <div class="k"><?= number_format($totalOrders) ?></div>
        <div class="l">Orders</div>
      </div>
      <div class="stat">
        <div class="k">ETB <?= number_format($totalSales, 2) ?></div>
        <div class="l">Total Sales</div>
      </div>
    </div>
  </section>

  <section class="card" style="margin-top: 20px;">
    <div class="card-head"><strong>Recent Orders</strong><span style="color:var(--muted);">Last 5</span></div>
    <div class="card-body">
      <?php if (empty($recentOrders)): ?>
        <p style="color: var(--muted); text-align: center; padding: 20px 0;">No orders yet.</p>
      <?php else: ?>
        <table style="margin: 0;">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Date</th>
              <th>Status</th>
              <th>Total</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $order): 
              $statusColors = [
                'pending' => 'background: rgba(251, 191, 36, 0.12); color: #fbbf24; border-color: rgba(251, 191, 36, 0.25);',
                'processing' => 'background: rgba(59, 130, 246, 0.12); color: #93c5fd; border-color: rgba(59, 130, 246, 0.25);',
                'shipped' => 'background: rgba(124, 58, 237, 0.12); color: #c4b5fd; border-color: rgba(124, 58, 237, 0.25);',
                'delivered' => 'background: rgba(16, 185, 129, 0.12); color: #6ee7b7; border-color: rgba(16, 185, 129, 0.25);',
                'cancelled' => 'background: rgba(239, 68, 68, 0.12); color: #fca5a5; border-color: rgba(239, 68, 68, 0.25);',
              ];
              $statusStyle = $statusColors[$order['status']] ?? $statusColors['pending'];
            ?>
              <tr>
                <td>#<?= (int)$order['id'] ?></td>
                <td>
                  <div style="font-weight: 600;"><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></div>
                  <div style="font-size: 12px; color: var(--muted);"><?= htmlspecialchars($order['customer_email'] ?? '') ?></div>
                </td>
                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                <td>
                  <span class="badge" style="<?= $statusStyle ?>">
                    <?= ucfirst(htmlspecialchars($order['status'])) ?>
                  </span>
                </td>
                <td style="font-weight: 600;">ETB <?= number_format((float)$order['total_amount'], 2) ?></td>
                <td>
                  <a href="detailsa.php?id=<?= (int)$order['id'] ?>" class="btn" style="padding: 6px 10px; font-size: 13px;">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </section>
</main>

</body>
</html>
