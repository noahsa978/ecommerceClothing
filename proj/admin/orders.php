<?php
$page_title = 'Admin • Orders';
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

table { width: 100%; border-collapse: collapse; font-size: 14px; }
th, td { padding: 12px 14px; border-bottom: 1px solid var(--border); text-align: left; white-space: nowrap; }
thead th { color: var(--muted); font-weight: 600; }

input, select { padding: 10px 12px; border-radius: 12px; border: 1px solid var(--border); background: #0b1220; color: var(--text); width:100%; margin-bottom:10px; }
button { cursor: pointer; }

.modal { position: fixed; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.45); z-index: 60; }
.modal.open { display: flex; }
.modal .content { width: min(680px, 92vw); background: var(--card); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
.modal .content header { display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; border-bottom: 1px solid var(--border); }
.modal .content .body { padding: 14px; }
</style>

<main class="container">
  <!-- Admin navigation -->
  <section class="admin-header">
    <h1>Orders</h1>
    <nav class="admin-nav" aria-label="Admin navigation">
      <a href="dashboard.php">Dashboard</a>
      <a href="employees.php">Employees</a>
      <a href="users.php">Users</a>
      <a href="products.php">Products</a>
      <a href="orders.php" class="active">Orders</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <!-- Quick Actions -->
  <section class="quick-actions">
    <button class="btn primary" data-tab="add">Add Order</button>
    <button class="btn primary" data-tab="status">Update Status</button>
    <button class="btn primary" data-tab="customers">View Customers</button>
  </section>

  <!-- Tabs -->
  <section class="tab-content" id="tab-add">
    <div class="card">
      <div class="card-head"><strong>Add Order</strong></div>
      <div class="card-body">
        <form>
          <input type="text" placeholder="Customer Name" required />
          <input type="text" placeholder="Product Name" required />
          <input type="number" placeholder="Quantity" required />
          <input type="number" placeholder="Price" required />
          <button class="btn primary">Save Order</button>
        </form>
      </div>
    </div>
  </section>

  <section class="tab-content" id="tab-status" style="display:none;">
    <div class="card">
      <div class="card-head"><strong>Update Order Status</strong></div>
      <div class="card-body">
        <table>
          <thead>
            <tr>
              <th>Order ID</th><th>Customer</th><th>Product</th><th>Status</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>O-1001</td>
              <td>Jane Cooper</td>
              <td>T-Shirt</td>
              <td><select><option>Pending</option><option>Completed</option><option>Cancelled</option></select></td>
              <td><button class="btn primary">Update</button></td>
            </tr>
            <tr>
              <td>O-1002</td>
              <td>Robert Fox</td>
              <td>Laptop</td>
              <td><select><option>Pending</option><option>Completed</option><option>Cancelled</option></select></td>
              <td><button class="btn primary">Update</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="tab-content" id="tab-customers" style="display:none;">
    <div class="card">
      <div class="card-head"><strong>View Customers</strong></div>
      <div class="card-body">
        <table>
          <thead>
            <tr>
              <th>Customer Name</th><th>Email</th><th>Total Orders</th><th>Last Order</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Jane Cooper</td>
              <td>jane.cooper@example.com</td>
              <td>5</td>
              <td>2025-09-20</td>
            </tr>
            <tr>
              <td>Robert Fox</td>
              <td>r.fox@example.com</td>
              <td>3</td>
              <td>2025-09-18</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- Quick Stats -->
  <section style="margin-top: 20px;">
    <h2>Quick Stats</h2>
    <div class="stats">
      <div class="stat"><div class="k">0</div><div class="l">Total Orders</div></div>
      <div class="stat"><div class="k">0</div><div class="l">Pending</div></div>
      <div class="stat"><div class="k">0</div><div class="l">Completed</div></div>
      <div class="stat"><div class="k">$0.00</div><div class="l">Total Revenue</div></div>
    </div>
  </section>

  <section class="cards">
    <div class="card">
      <div class="card-head"><strong>Recent Orders</strong></div>
      <div class="card-body">No data yet.</div>
    </div>
    <div class="card">
      <div class="card-head"><strong>Pending Orders</strong></div>
      <div class="card-body">No data yet.</div>
    </div>
  </section>
</main>

<script>
// Tab switching
const buttons = document.querySelectorAll('.quick-actions .btn');
const tabs = document.querySelectorAll('.tab-content');
buttons.forEach(btn => {
  btn.addEventListener('click', () => {
    const target = btn.getAttribute('data-tab');
    tabs.forEach(tab => tab.style.display = (tab.id === 'tab-' + target) ? 'block' : 'none');
  });
});
</script>

<?php include '../includes/footer.php'; ?>
