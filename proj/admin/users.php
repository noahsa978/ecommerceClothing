<?php
  $page_title = 'Admin • Users';
  require_once __DIR__ . '/../includes/db_connect.php';
  $flash = null;

  // Handle Add User
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'add_user' && ($conn instanceof mysqli)) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? ''); // 'admin' or 'customer'
    $password = $_POST['password'] ?? '';
    if ($name === '' || $email === '' || $password === '' || !in_array($role, ['admin','customer'], true)) {
      $flash = ['type' => 'error', 'msg' => 'Please fill name, email, password, and select Admin or Customer.'];
    } else {
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $username = preg_replace('/\s+/', '.', strtolower(trim($name)));
      $stmt = $conn->prepare('INSERT INTO users (username, email, upassword, role, fullname) VALUES (?,?,?,?,?)');
      $stmt->bind_param('sssss', $username, $email, $hash, $role, $name);
      if ($stmt->execute()) {
        $flash = ['type' => 'success', 'msg' => 'User added successfully.'];
      } else {
        $flash = ['type' => 'error', 'msg' => 'Failed to add user: ' . $stmt->error];
      }
      $stmt->close();
    }
  }

  // Handle Update User (role, fullname, phone)
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'update_user' && ($conn instanceof mysqli)) {
    $uid = intval($_POST['id'] ?? 0);
    $role = trim($_POST['role'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($uid <= 0 || !in_array($role, ['admin','customer'], true) || $fullname === '') {
      $flash = ['type' => 'error', 'msg' => 'Please provide a valid role and full name.'];
    } else {
      $stmt = $conn->prepare('UPDATE users SET role=?, fullname=?, phone=? WHERE id=?');
      $stmt->bind_param('sssi', $role, $fullname, $phone, $uid);
      if ($stmt->execute()) {
        $flash = ['type' => 'success', 'msg' => 'User updated successfully.'];
      } else {
        $flash = ['type' => 'error', 'msg' => 'Failed to update user: ' . $stmt->error];
      }
      $stmt->close();
    }
  }

  // Handle Delete User
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'delete_user' && ($conn instanceof mysqli)) {
    $uid = intval($_POST['id'] ?? 0);
    if ($uid > 0) {
      $stmt = $conn->prepare('DELETE FROM users WHERE id=?');
      $stmt->bind_param('i', $uid);
      if ($stmt->execute()) {
        $flash = ['type' => 'success', 'msg' => 'User deleted.'];
      } else {
        $flash = ['type' => 'error', 'msg' => 'Failed to delete user: ' . $stmt->error];
      }
      $stmt->close();
    } else {
      $flash = ['type' => 'error', 'msg' => 'Invalid user ID.'];
    }
  }

  // Search term for Edit Users
  $u_search = isset($_GET['u_search']) ? trim($_GET['u_search']) : '';

  // Load users list
  $users_list = [];
  if ($conn instanceof mysqli) {
    $sql_all = 'SELECT id, username, email, role, fullname, phone, created_at FROM users ORDER BY id ASC LIMIT 1000';
    if ($res = $conn->query($sql_all)) {
      while ($row = $res->fetch_assoc()) { $users_list[] = $row; }
      $res->free();
    }
    // Users for Edit tab (apply search if provided)
    $users_edit = [];
    if ($u_search !== '') {
      $like = '%' . $conn->real_escape_string($u_search) . '%';
      $sql_edit = "SELECT id, username, email, role, fullname, phone, created_at FROM users
                   WHERE username LIKE '$like' OR fullname LIKE '$like' OR email LIKE '$like' OR phone LIKE '$like' OR role LIKE '$like'
                   ORDER BY id ASC LIMIT 1000";
    } else {
      $sql_edit = 'SELECT id, username, email, role, fullname, phone, created_at FROM users ORDER BY id ASC LIMIT 1000';
    }
    if ($res2 = $conn->query($sql_edit)) {
      while ($row = $res2->fetch_assoc()) { $users_edit[] = $row; }
      $res2->free();
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
      <a href="inventory.php">Inventory</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <!-- Quick Actions -->
  <section class="quick-actions">
    <button class="btn primary" data-tab="add">Add User</button>
    <button class="btn primary" data-tab="edit">Edit Roles</button>
  </section>

  <!-- Tabs -->
  <section class="tab-content" id="tab-add">
    <div class="card">
      <div class="card-head"><strong>Add User</strong></div>
      <div class="card-body">
        <?php if (!empty($flash)) { $isErr = $flash['type']==='error'; ?>
          <div style="margin-bottom:10px; padding:10px 12px; border:1px solid <?= $isErr ? '#7f1d1d' : '#065f46' ?>; background: <?= $isErr ? '#3f1d1d' : '#064e3b' ?>; color:#fff; border-radius:10px; font-size:14px;">
            <?= htmlspecialchars($flash['msg']); ?>
          </div>
        <?php } ?>
        <form method="post" action="">
          <input type="hidden" name="form_type" value="add_user" />
          <input type="text" name="name" placeholder="Full Name" required />
          <input type="email" name="email" placeholder="Email" required />
          <input type="password" name="password" placeholder="Password" minlength="6" required />
          <select name="role" required>
            <option value="">Select Role</option>
            <option value="admin">Admin</option>
            <option value="customer">Customer</option>
          </select>
          <button class="btn primary" type="submit">Save User</button>
        </form>
      </div>
    </div>
  </section>

  <section class="tab-content" id="tab-edit" style="display:none;">
    <div class="card">
      <div class="card-head"><strong>Edit Users</strong></div>
      <div class="card-body">
        <div style="margin:0 0 12px;">
          <!-- Search by ID -->
          <form id="user-search-form" method="get" action="" style="display:flex; gap:8px; margin-bottom:8px;">
            <input type="number" name="u_search" id="search-user-by-id" value="<?= htmlspecialchars($u_search); ?>" placeholder="Search by ID" style="width:200px;" />
            <button class="btn primary" type="submit">Search by ID</button>
            <a class="btn" id="user-search-clear" href="users.php">Clear</a>
          </form>
          <!-- Real-time filter -->
          <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <input type="text" id="filter-user-name" placeholder="Filter by Name" style="flex:1; min-width:150px;" />
            <input type="text" id="filter-user-email" placeholder="Filter by Email" style="flex:1; min-width:150px;" />
            <input type="text" id="filter-user-phone" placeholder="Filter by Phone" style="flex:1; min-width:150px;" />
            <select id="filter-user-role" style="flex:1; min-width:150px;">
              <option value="">Filter by Role</option>
              <option value="admin">Admin</option>
              <option value="customer">Customer</option>
            </select>
          </div>
        </div>
        <table>
          <thead>
            <tr>
              <th>ID</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Actions</th>
            </tr>
          </thead>
          <tbody id="users-tbody">
            <?php if (empty($users_edit)) { ?>
              <tr><td colspan="6">No users found.</td></tr>
            <?php } else { foreach ($users_edit as $usr) { ?>
              <tr data-id="<?= (int)$usr['id']; ?>" data-name="<?= htmlspecialchars(strtolower($usr['fullname'] ?: $usr['username'])); ?>" data-email="<?= htmlspecialchars(strtolower($usr['email'])); ?>" data-phone="<?= htmlspecialchars($usr['phone'] ?? ''); ?>" data-role="<?= htmlspecialchars($usr['role']); ?>">
                <form method="post" action="">
                  <input type="hidden" name="form_type" value="update_user" />
                  <input type="hidden" name="id" value="<?= (int)$usr['id']; ?>" />
                  <td><?= (int)$usr['id']; ?></td>
                  <td><input type="text" name="fullname" value="<?= htmlspecialchars($usr['fullname'] ?: $usr['username']); ?>" /></td>
                  <td><?= htmlspecialchars($usr['email']); ?></td>
                  <td><input type="text" name="phone" value="<?= htmlspecialchars($usr['phone'] ?? ''); ?>" /></td>
                  <td>
                    <select name="role">
                      <option value="admin" <?= $usr['role']==='admin'?'selected':''; ?>>Admin</option>
                      <option value="customer" <?= $usr['role']==='customer'?'selected':''; ?>>Customer</option>
                    </select>
                  </td>
                  <td>
                    <div class="row-actions">
                      <button class="btn primary" type="submit">Save</button>
                      </form>
                      <form method="post" action="" onsubmit="return confirm('Remove this user? This cannot be undone.');">
                        <input type="hidden" name="form_type" value="delete_user" />
                        <input type="hidden" name="id" value="<?= (int)$usr['id']; ?>" />
                        <button class="btn" type="submit" style="border-color:#7f1d1d; background:#3f1d1d; color:#fecaca;">Remove</button>
                      </form>
                    </div>
                  </td>
              </tr>
            <?php } } ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- Users Table -->
  <section class="card">
    <div class="card-head">
      <strong>All Users</strong>
      <span style="color: var(--muted);">Showing <?= count($users_list); ?></span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users_list)) { ?>
            <tr><td colspan="5">No users found.</td></tr>
          <?php } else { foreach ($users_list as $u) { ?>
            <tr>
              <td><?= (int)$u['id']; ?></td>
              <td><?= htmlspecialchars($u['fullname'] ?: $u['username']); ?></td>
              <td><?= htmlspecialchars($u['email']); ?></td>
              <td>
                <?php if ($u['role'] === 'admin') { ?>
                  <span class="badge b-admin">Admin</span>
                <?php } else { ?>
                  <span class="badge b-customer">Customer</span>
                <?php } ?>
              </td>
              <td><?= htmlspecialchars($u['created_at']); ?></td>
            </tr>
          <?php } } ?>
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

// Real-time user filtering
const filterUserName = document.getElementById('filter-user-name');
const filterUserEmail = document.getElementById('filter-user-email');
const filterUserPhone = document.getElementById('filter-user-phone');
const filterUserRole = document.getElementById('filter-user-role');

function filterUsers() {
  const nameValue = filterUserName.value.toLowerCase().trim();
  const emailValue = filterUserEmail.value.toLowerCase().trim();
  const phoneValue = filterUserPhone.value.trim();
  const roleValue = filterUserRole.value;
  
  let visibleCount = 0;
  const usersRowsNow = document.querySelectorAll('#users-tbody tr');
  usersRowsNow.forEach(row => {
    // Skip if it's the "no users" message row
    if (!row.hasAttribute('data-id')) {
      return;
    }
    
    const rowName = row.getAttribute('data-name') || '';
    const rowEmail = row.getAttribute('data-email') || '';
    const rowPhone = row.getAttribute('data-phone') || '';
    const rowRole = (row.getAttribute('data-role') || '').toLowerCase();
    
    const nameMatch = nameValue === '' || rowName.includes(nameValue);
    const emailMatch = emailValue === '' || rowEmail.includes(emailValue);
    const phoneMatch = phoneValue === '' || rowPhone.includes(phoneValue);
    const roleMatch = roleValue === '' || rowRole === roleValue.toLowerCase();
    
    if (nameMatch && emailMatch && phoneMatch && roleMatch) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });
  
  // Show/hide "no results" message
  const tbody = document.getElementById('users-tbody');
  let noResultsRow = tbody.querySelector('.no-results-row');
  
  if (visibleCount === 0 && usersRowsNow.length > 0) {
    if (!noResultsRow) {
      noResultsRow = document.createElement('tr');
      noResultsRow.className = 'no-results-row';
      noResultsRow.innerHTML = '<td colspan="6" style="text-align:center; padding:20px;">No users match your filters.</td>';
      tbody.appendChild(noResultsRow);
    }
    noResultsRow.style.display = '';
  } else if (noResultsRow) {
    noResultsRow.style.display = 'none';
  }
}

// Client-side search by ID (prevent reload) and clear
const userSearchForm = document.getElementById('user-search-form');
const userSearchInput = document.getElementById('search-user-by-id');
const userSearchClear = document.getElementById('user-search-clear');
if (userSearchForm && userSearchInput) {
  userSearchForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const searchId = (userSearchInput.value || '').trim();
    const rows = document.querySelectorAll('#users-tbody tr');
    let found = false;
    if (searchId === '') {
      rows.forEach(row => { if (row.hasAttribute('data-id')) row.style.display = ''; });
    } else {
      rows.forEach(row => {
        if (!row.hasAttribute('data-id')) return;
        const id = row.getAttribute('data-id');
        if (id === searchId) {
          row.style.display = '';
          found = true;
        } else {
          row.style.display = 'none';
        }
      });
    }
    const tbody = document.getElementById('users-tbody');
    let noResultsRow = tbody.querySelector('.no-results-row');
    if (searchId !== '' && !found) {
      if (!noResultsRow) {
        noResultsRow = document.createElement('tr');
        noResultsRow.className = 'no-results-row';
        noResultsRow.innerHTML = '<td colspan="6" style="text-align:center; padding:20px;">No user found with ID ' + searchId + '</td>';
        tbody.appendChild(noResultsRow);
      } else {
        noResultsRow.innerHTML = '<td colspan="6" style="text-align:center; padding:20px;">No user found with ID ' + searchId + '</td>';
      }
      noResultsRow.style.display = '';
    } else if (noResultsRow) {
      noResultsRow.style.display = 'none';
    }
  });
}

if (userSearchClear) {
  userSearchClear.addEventListener('click', (e) => {
    e.preventDefault();
    if (userSearchInput) userSearchInput.value = '';
    if (filterUserName) filterUserName.value = '';
    if (filterUserEmail) filterUserEmail.value = '';
    if (filterUserPhone) filterUserPhone.value = '';
    if (filterUserRole) filterUserRole.value = '';
    const rows = document.querySelectorAll('#users-tbody tr');
    rows.forEach(row => { if (row.hasAttribute('data-id')) row.style.display = ''; });
    const tbody = document.getElementById('users-tbody');
    const noResultsRow = tbody.querySelector('.no-results-row');
    if (noResultsRow) noResultsRow.style.display = 'none';
  });
}

// Add event listeners for real-time filtering
if (filterUserName && filterUserEmail && filterUserPhone && filterUserRole) {
  filterUserName.addEventListener('input', filterUsers);
  filterUserEmail.addEventListener('input', filterUsers);
  filterUserPhone.addEventListener('input', filterUsers);
  filterUserRole.addEventListener('change', filterUsers);
}
</script>

  </body>
</html>
