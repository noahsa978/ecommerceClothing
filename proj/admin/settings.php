<!-- manage admin settings, update company information, manage categories --><?php
$page_title = 'Admin • Settings';
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

.tab-content { display: none; }
.tab-content.active { display: block; }

input, select, textarea { padding: 10px 12px; border-radius: 12px; border: 1px solid var(--border); background: #0b1220; color: var(--text); width:100%; margin-bottom:10px; }
button { cursor: pointer; }
</style>

<main class="container">
  <!-- Admin navigation -->
  <section class="admin-header">
    <h1>Settings</h1>
    <nav class="admin-nav" aria-label="Admin navigation">
      <a href="dashboard.php">Dashboard</a>
      <a href="employees.php">Employees</a>
      <a href="users.php">Users</a>
      <a href="products.php">Products</a>
      <a href="orders.php">Orders</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php" class="active">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <!-- Quick Actions -->
  <section class="quick-actions">
    <button class="btn primary" data-tab="company">Company Info</button>
    <button class="btn primary" data-tab="categories">Manage Categories</button>
    <button class="btn primary" data-tab="admin">Admin Settings</button>
  </section>

  <!-- Tabs -->

  <!-- Company Info -->
  <section class="tab-content active" id="tab-company">
    <div class="card">
      <div class="card-head"><strong>Company Information</strong></div>
      <div class="card-body">
        <form id="companyForm">
          <label>Company Name</label>
          <input type="text" name="company_name" value="Ecom Clothing" required>
          <label>Address</label>
          <textarea name="address">123 Main Street, City</textarea>
          <label>Contact Email</label>
          <input type="email" name="email" value="info@ecom.com">
          <label>Phone</label>
          <input type="text" name="phone" value="+1234567890">
          <button type="submit" class="btn primary">Update Company Info</button>
        </form>
      </div>
    </div>
  </section>

  <!-- Categories -->
  <section class="tab-content" id="tab-categories">
    <div class="card">
      <div class="card-head"><strong>Manage Categories</strong></div>
      <div class="card-body">
        <form id="categoryForm">
          <label>Add New Category</label>
          <input type="text" name="new_category" placeholder="Category Name">
          <button type="submit" class="btn primary">Add Category</button>
        </form>
        <div style="margin-top: 12px;">
          <strong>Existing Categories:</strong>
          <ul id="categoryList">
            <li>Men's Clothing <button class="btn" data-action="edit">Edit</button> <button class="btn" data-action="delete">Delete</button></li>
            <li>Women's Clothing <button class="btn" data-action="edit">Edit</button> <button class="btn" data-action="delete">Delete</button></li>
            <li>Accessories <button class="btn" data-action="edit">Edit</button> <button class="btn" data-action="delete">Delete</button></li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- Admin Settings -->
  <section class="tab-content" id="tab-admin">
    <div class="card">
      <div class="card-head"><strong>Admin Account Settings</strong></div>
      <div class="card-body">
        <form id="adminForm">
          <label>Admin Name</label>
          <input type="text" name="admin_name" value="Admin User" required>
          <label>Email</label>
          <input type="email" name="admin_email" value="admin@ecom.com" required>
          <label>Change Password</label>
          <input type="password" name="admin_pass" placeholder="New Password">
          <button type="submit" class="btn primary">Update Admin Settings</button>
        </form>
      </div>
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

// Example frontend-only form handling
document.querySelectorAll('form').forEach(form => {
  form.addEventListener('submit', e => {
    e.preventDefault();
    alert('Form submitted! (Frontend demo only)');
  });
});
</script>

<?php include '../includes/footer.php'; ?>
