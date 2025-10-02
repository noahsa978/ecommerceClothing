<?php
$page_title = 'Admin • Users';
include '../includes/header.php'; // regular header
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

.badge { padding: 4px 8px; border-radius: 999px; font-size: 12px; border: 1px solid var(--border); }
.b-admin { background: rgba(124,58,237,0.12); color: #c4b5fd; border-color: rgba(124,58,237,0.25); }
.b-staff { background: rgba(59,130,246,0.12); color: #93c5fd; border-color: rgba(59,130,246,0.25); }
.b-customer { background: rgba(16,185,129,0.12); color: #6ee7b7; border-color: rgba(16,185,129,0.25); }
.b-suspended { background: rgba(239,68,68,0.12); color: #fca5a5; border-color: rgba(239,68,68,0.25); }

.row-actions { display: flex; gap: 8px; }
.row-actions .btn { padding: 8px 10px; border-radius: 10px; border: 1px solid var(--border); background: #0b1220; color: var(--text); }
.row-actions .btn:hover { background: rgba(124,58,237,0.12); }

.modal { position: fixed; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.45); z-index: 60; }
.modal.open { display: flex; }
.modal .content { width: min(680px, 92vw); background: var(--card); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
.modal .content header { display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; border-bottom: 1px solid var(--border); }
.modal .content .body { padding: 14px; }

select, input { padding: 10px 12px; border-radius: 12px; border: 1px solid var(--border); background: #0b1220; color: var(--text); width:100%; margin-bottom:10px; }
button { cursor: pointer; }
</style>

<main class="container">
  <!-- Admin navigation -->
  <section class="admin-header">
    <h1>Users</h1>
    <nav class="admin-nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="employees.php">Employees</a>
      <a href="users.php" class="active">Users</a>
      <a href="products.php">Products</a>
      <a href="orders.php">Orders</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <!-- Quick Actions -->
  <section class="quick-actions">
    <button class="btn primary" data-tab="add">Add User</button>
    <button class="btn primary" data-tab="edit">Edit Roles</button>
    <button class="btn primary" data-tab="bulk">Bulk Actions</button>
  </section>

  <!-- Tabs -->
  <section class="tab-content" id="tab-add">
    <div class="card">
      <div class="card-head"><strong>Add User</strong></div>
      <div class="card-body">
        <form>
          <input type="text" placeholder="Name" required />
          <input type="email" placeholder="Email" required />
          <select>
            <option value="">Select Role</option>
            <option>Admin</option>
            <option>Staff</option>
            <option>Customer</option>
          </select>
          <select>
            <option>Active</option>
            <option>Suspended</option>
          </select>
          <button class="btn primary">Save User</button>
        </form>
      </div>
    </div>
  </section>

  <section class="tab-content" id="tab-edit" style="display:none;">
    <div class="card">
      <div class="card-head"><strong>Edit Roles</strong></div>
      <div class="card-body">
        <table>
          <thead>
            <tr>
              <th>User</th><th>Current Role</th><th>New Role</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Jane Cooper</td>
              <td><span class="badge b-admin">Admin</span></td>
              <td>
                <select>
                  <option>Admin</option>
                  <option>Staff</option>
                  <option>Customer</option>
                </select>
              </td>
              <td><button class="btn primary">Save</button></td>
            </tr>
            <tr>
              <td>Robert Fox</td>
              <td><span class="badge b-staff">Staff</span></td>
              <td>
                <select>
                  <option>Admin</option>
                  <option>Staff</option>
                  <option>Customer</option>
                </select>
              </td>
              <td><button class="btn primary">Save</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="tab-content" id="tab-bulk" style="display:none;">
    <div class="card">
      <div class="card-head"><strong>Bulk Actions</strong></div>
      <div class="card-body">
        <button class="btn primary">Suspend Selected</button>
        <button class="btn primary">Activate Selected</button>
        <button class="btn primary">Delete Selected</button>
      </div>
    </div>
  </section>

  <!-- Users Table -->
  <section class="card">
    <div class="card-head">
      <strong>All Users</strong>
      <span style="color: var(--muted);">Showing 6 of 1,024</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>U-1006</td>
            <td>Jane Cooper</td>
            <td>jane.cooper@example.com</td>
            <td><span class="badge b-admin">Admin</span></td>
            <td><span class="badge b-customer">Active</span></td>
            <td>
              <div class="row-actions">
                <button class="btn btn-view">View</button>
                <button class="btn">Edit</button>
              </div>
            </td>
          </tr>
          <tr>
            <td>U-1005</td>
            <td>Robert Fox</td>
            <td>r.fox@example.com</td>
            <td><span class="badge b-staff">Staff</span></td>
            <td><span class="badge b-customer">Active</span></td>
            <td>
              <div class="row-actions">
                <button class="btn btn-view">View</button>
                <button class="btn">Edit</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
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
