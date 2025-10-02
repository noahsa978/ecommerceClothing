<?php
$page_title = 'Admin • Products';
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

.stats { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 10px; }
.stat { flex: 1; background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 12px; text-align: center; }
.stat .k { font-size: 20px; font-weight: bold; }
.stat .l { font-size: 12px; color: var(--muted); }
</style>

<main class="container">
  <!-- Admin navigation -->
  <section class="admin-header">
    <h1>Products</h1>
    <nav class="admin-nav" aria-label="Admin navigation">
      <a href="dashboard.php">Dashboard</a>
      <a href="employees.php">Employees</a>
      <a href="users.php">Users</a>
      <a href="products.php" class="active">Products</a>
      <a href="orders.php">Orders</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <!-- Quick Actions -->
  <section class="quick-actions">
    <button class="btn primary" data-tab="add">Add Product</button>
    <button class="btn primary" data-tab="edit">Edit Products</button>
    <button class="btn primary" data-tab="remove">Remove Products</button>
  </section>

  <!-- Add Product Tab -->
  <section class="tab-content" id="tab-add">
    <div class="card">
      <div class="card-head"><strong>Add Product</strong></div>
      <div class="card-body">
        <form>
          <input type="text" placeholder="Product Name" required />
          <input type="number" placeholder="Price" required />
          <input type="number" placeholder="Stock Quantity" required />
          <select required>
            <option value="">Category</option>
            <option>Men's Clothing</option>
            <option>Women's Clothing</option>
            <option>Kids</option>
            <option>Footwear</option>
            <option>Accessories</option>
          </select>
          <button class="btn primary">Save Product</button>
        </form>
      </div>
    </div>
  </section>

  <!-- Edit Products Tab -->
  <section class="tab-content" id="tab-edit" style="display:none;">
    <div class="card">
      <div class="card-head"><strong>Edit Products</strong></div>
      <div class="card-body">
        <table>
          <thead>
            <tr>
              <th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Men's T-Shirt</td><td>Men's Clothing</td><td>$20</td><td>50</td>
              <td><button class="btn primary">Save</button></td>
            </tr>
            <tr>
              <td>Women's Dress</td><td>Women's Clothing</td><td>$45</td><td>25</td>
              <td><button class="btn primary">Save</button></td>
            </tr>
            <tr>
              <td>Leather Belt</td><td>Accessories</td><td>$15</td><td>40</td>
              <td><button class="btn primary">Save</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- Remove Products Tab -->
  <section class="tab-content" id="tab-remove" style="display:none;">
    <div class="card">
      <div class="card-head"><strong>Remove Products</strong></div>
      <div class="card-body">
        <table>
          <thead>
            <tr>
              <th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Men's T-Shirt</td><td>Men's Clothing</td><td>$20</td><td>50</td>
              <td><button class="btn primary">Delete</button></td>
            </tr>
            <tr>
              <td>Women's Dress</td><td>Women's Clothing</td><td>$45</td><td>25</td>
              <td><button class="btn primary">Delete</button></td>
            </tr>
            <tr>
              <td>Leather Belt</td><td>Accessories</td><td>$15</td><td>40</td>
              <td><button class="btn primary">Delete</button></td>
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
      <div class="stat"><div class="k">0</div><div class="l">Total Products</div></div>
      <div class="stat"><div class="k">0</div><div class="l">In Stock</div></div>
      <div class="stat"><div class="k">0</div><div class="l">Low Stock</div></div>
      <div class="stat"><div class="k">0</div><div class="l">Out of Stock</div></div>
    </div>
  </section>

  <section class="cards">
    <div class="card">
      <div class="card-head"><strong>Top Selling</strong></div>
      <div class="card-body">No data yet.</div>
    </div>
    <div class="card">
      <div class="card-head"><strong>Low Stock Items</strong></div>
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
