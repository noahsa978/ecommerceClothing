<?php
$page_title = 'Admin • Employees';
include '../includes/header.php'; // regular header
?>

<style>
/* Reuse dashboard styles */
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

.quick-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
.quick-actions .btn { padding: 10px 12px; border: 1px solid var(--border); border-radius: 12px; background: #0b1220; color: var(--text); }
.quick-actions .btn.primary { background: var(--accent); border-color: transparent; }
.quick-actions .btn.primary:hover { background: var(--accent-600); }

.stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 18px;
  margin-bottom: 20px;
}
.stat {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 20px;
  text-align: center;
}
.stat .k { font-size: 26px; font-weight: 700; margin-bottom: 6px; }
.stat .l { font-size: 14px; color: var(--muted); }

.cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.card {
  border: 1px solid var(--border);
  background: var(--card);
  border-radius: 12px;
  overflow: hidden;
}
.card-head { display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; border-bottom: 1px solid var(--border); }
.card-body { padding: 14px; color: var(--muted); }

table { width: 100%; border-collapse: collapse; margin-top: 10px; }
table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid var(--border); }
select, input { padding: 10px 12px; border-radius: 12px; border: 1px solid var(--border); background: #0b1220; color: var(--text); width:100%; margin-bottom:10px; }
button { cursor: pointer; }
</style>

<main class="container">
  <section class="admin-header">
    <h1>Employees</h1>
    <nav class="admin-nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="employees.php" class="active">Employees</a>
      <a href="users.php">Users</a>
      <a href="products.php">Products</a>
      <a href="orders.php">Orders</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <!-- Quick Actions -->
  <section>
    <h2>Quick Actions</h2>
    <div class="quick-actions">
      <button class="btn primary" data-tab="add">Add Employee</button>
      <button class="btn primary" data-tab="attendance">Manage Attendance</button>
      <button class="btn primary" data-tab="roles">Edit Roles</button>
    </div>
  </section>

  <!-- Tab Contents -->

  <!-- Add Employee -->
  <section class="tab-content" id="tab-add">
    <div class="cards">
      <div class="card">
        <div class="card-head"><strong>Add Employee</strong></div>
        <div class="card-body">
          <form>
            <input type="text" placeholder="Employee Name" required />
            <input type="email" placeholder="Email" required />
            <select>
              <option value="">Select Role</option>
              <option>Staff</option>
              <option>Manager</option>
              <option>Admin</option>
            </select>
            <button type="submit" class="btn primary">Save</button>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- Manage Attendance -->
  <section class="tab-content" id="tab-attendance" style="display:none;">
    <div class="cards">
      <div class="card">
        <div class="card-head"><strong>Manage Attendance</strong></div>
        <div class="card-body">
          <table>
            <thead>
              <tr><th>Employee</th><th>Date</th><th>Status</th></tr>
            </thead>
            <tbody>
              <tr>
                <td>Jane Doe</td>
                <td>2025-09-29</td>
                <td>
                  <select>
                    <option>Present</option>
                    <option>Absent</option>
                    <option>Leave</option>
                  </select>
                </td>
              </tr>
            </tbody>
          </table>
          <button class="btn primary">Save Attendance</button>
        </div>
      </div>
    </div>
  </section>

  <!-- Edit Roles -->
  <section class="tab-content" id="tab-roles" style="display:none;">
    <div class="cards">
      <div class="card">
        <div class="card-head"><strong>Edit Roles</strong></div>
        <div class="card-body">
          <table>
            <thead>
              <tr><th>Employee</th><th>Current Role</th><th>New Role</th></tr>
            </thead>
            <tbody>
              <tr>
                <td>Jane Doe</td>
                <td>Staff</td>
                <td>
                  <select>
                    <option>Staff</option>
                    <option>Manager</option>
                    <option>Admin</option>
                  </select>
                </td>
              </tr>
            </tbody>
          </table>
          <button class="btn primary">Save Changes</button>
        </div>
      </div>
    </div>
  </section>
</main>

<script>
// Tab switching
const buttons = document.querySelectorAll('.quick-actions button');
const tabs = document.querySelectorAll('.tab-content');

buttons.forEach(btn => {
  btn.addEventListener('click', () => {
    const target = btn.getAttribute('data-tab');
    tabs.forEach(tab => tab.style.display = (tab.id === 'tab-' + target) ? 'block' : 'none');
  });
});
</script>

<?php include '../includes/footer.php'; ?>
