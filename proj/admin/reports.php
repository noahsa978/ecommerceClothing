<?php
$page_title = 'Admin • Reports';
include '../includes/header.php';
?>

<style>
/* Admin page styling consistent with Employees.php */
.admin-header {
  padding: 16px 0;
  border-bottom: 1px solid var(--border);
  margin-bottom: 10px;
}
.admin-nav {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 8px;
}
.admin-nav a {
  padding: 8px 10px;
  border-radius: 10px;
  color: #cbd5e1;
  border: 1px solid var(--border);
  background: #0b1220;
}
.admin-nav a.active, .admin-nav a:hover {
  background: rgba(124,58,237,0.12);
  color: #fff;
}

.quick-actions { display: flex; gap: 10px; flex-wrap: wrap; margin: 12px 0; }
.quick-actions .btn { padding: 10px 12px; border: 1px solid var(--border); border-radius: 12px; background: #0b1220; color: var(--text); }
.quick-actions .btn.primary { background: var(--accent); border-color: transparent; }
.quick-actions .btn.primary:hover { background: var(--accent-600); }

.card { border: 1px solid var(--border); background: var(--card); border-radius: 12px; overflow: hidden; margin-bottom: 16px; }
.card-head { display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; border-bottom: 1px solid var(--border); }
.card-body { padding: 14px; color: var(--muted); }

.stats { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 10px; }
.stat { flex: 1; background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 12px; text-align: center; }
.stat .k { font-size: 20px; font-weight: bold; }
.stat .l { font-size: 12px; color: var(--muted); }

.tab-content { display: none; }
.tab-content.active { display: block; }

table { width: 100%; border-collapse: collapse; font-size: 14px; margin-top:10px; }
th, td { padding: 12px 14px; border-bottom: 1px solid var(--border); text-align: left; white-space: nowrap; }
thead th { color: var(--muted); font-weight: 600; }

input, select { padding: 10px 12px; border-radius: 12px; border: 1px solid var(--border); background: #0b1220; color: var(--text); width:100%; margin-bottom:10px; }
button { cursor: pointer; }
</style>

<main class="container">
  <!-- Admin navigation -->
  <section class="admin-header">
    <h1>Reports</h1>
    <nav class="admin-nav" aria-label="Admin navigation">
      <a href="dashboard.php">Dashboard</a>
      <a href="employees.php">Employees</a>
      <a href="users.php">Users</a>
      <a href="products.php">Products</a>
      <a href="orders.php">Orders</a>
      <a href="reports.php" class="active">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <!-- Quick Actions -->
  <section class="quick-actions">
    <button class="btn primary" data-tab="sales">Generate Sales Report</button>
    <button class="btn primary" data-tab="inventory">Inventory Report</button>
    <button class="btn primary" data-tab="customers">Customer Behavior</button>
  </section>

  <!-- Tabs -->
  <section class="tab-content active" id="tab-sales">
    <div class="card">
      <div class="card-head"><strong>Sales Report</strong></div>
      <div class="card-body">
        <p>No sales data yet.</p>
        <table>
          <thead>
            <tr><th>Date</th><th>Orders</th><th>Total Revenue</th></tr>
          </thead>
          <tbody>
            <tr><td>2025-09-28</td><td>0</td><td>$0.00</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="tab-content" id="tab-inventory">
    <div class="card">
      <div class="card-head"><strong>Inventory Report</strong></div>
      <div class="card-body">
        <p>No inventory data yet.</p>
        <table>
          <thead>
            <tr><th>Product</th><th>Stock</th><th>Low Stock Threshold</th></tr>
          </thead>
          <tbody>
            <tr><td>T-Shirt</td><td>0</td><td>5</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="tab-content" id="tab-customers">
    <div class="card">
      <div class="card-head"><strong>Customer Behavior</strong></div>
      <div class="card-body">
        <p>No customer behavior data yet.</p>
        <table>
          <thead>
            <tr><th>Customer</th><th>Total Orders</th><th>Last Purchase</th></tr>
          </thead>
          <tbody>
            <tr><td>Jane Cooper</td><td>0</td><td>N/A</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- Quick Stats -->
  <section style="margin-top: 20px;">
    <h2>Quick Stats</h2>
    <div class="stats">
      <div class="stat"><div class="k">0</div><div class="l">Total Reports</div></div>
      <div class="stat"><div class="k">0</div><div class="l">Sales Today</div></div>
      <div class="stat"><div class="k">$0.00</div><div class="l">Revenue</div></div>
      <div class="stat"><div class="k">0</div><div class="l">Inventory Alerts</div></div>
    </div>
  </section>
</main>

<script>
// Tab switching
const tabButtons = document.querySelectorAll('.quick-actions .btn');
const tabContents = document.querySelectorAll('.tab-content');
tabButtons.forEach(btn => {
  btn.addEventListener('click', () => {
    const target = btn.getAttribute('data-tab');
    tabContents.forEach(tc => tc.classList.remove('active'));
    document.getElementById('tab-' + target).classList.add('active');
  });
});
</script>

<?php include '../includes/footer.php'; ?>
