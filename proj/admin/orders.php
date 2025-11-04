<?php
  $page_title = 'Admin • Orders';
  if (session_status() === PHP_SESSION_NONE) { session_start(); }
  require_once __DIR__ . '/../includes/db_connect.php';
  $flash = [ 'type' => null, 'message' => '' ];

  // Handle inline status update
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
      if (!($conn instanceof mysqli)) { throw new Exception('DB not available'); }
      $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
      $newStatus = trim($_POST['status'] ?? '');
      $allowed = ['pending','paid','processing','shipped','delivered','cancelled'];
      if ($orderId <= 0 || !in_array($newStatus, $allowed, true)) { throw new Exception('Invalid input'); }
      if (!($st = $conn->prepare('UPDATE orders SET status=? WHERE id=?'))) { throw new Exception('Failed to prepare update'); }
      $st->bind_param('si', $newStatus, $orderId);
      if (!$st->execute()) { throw new Exception('Failed to update: ' . $st->error); }
      $st->close();
      $flash = [ 'type' => 'success', 'message' => 'Order #'.$orderId.' status updated to "'.$newStatus.'"' ];
    } catch (Throwable $e) {
      $flash = [ 'type' => 'error', 'message' => $e->getMessage() ];
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
    <h1>Orders</h1>
    <nav class="admin-nav" aria-label="Admin navigation">
      <a href="dashboard.php">Dashboard</a>
      <a href="employees.php">Employees</a>
      <a href="users.php">Users</a>
      <a href="products.php">Products</a>
      <a href="orders.php" class="active">Orders</a>
      <a href="inventory.php">Inventory</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <!-- Quick Actions -->
  <section class="quick-actions">
    <button class="btn primary" data-tab="manage">Manage Orders</button>
    <button class="btn primary" data-tab="customers">View Customers</button>
  </section>

  <!-- Tabs -->
  <section class="tab-content" id="tab-manage">
    <div class="card">
      <div class="card-head"><strong>Manage Orders</strong></div>
      <div class="card-body">
        <?php if ($flash['type']): ?>
          <div style="margin-bottom:10px; padding:10px; border-radius:8px; border:1px solid var(--border); background: <?= $flash['type']==='success' ? 'rgba(16,185,129,0.12)' : 'rgba(239,68,68,0.12)' ?>; color:#fff;">
            <?= htmlspecialchars($flash['message']) ?>
          </div>
        <?php endif; ?>
        <?php
          $orders = [];
          if (isset($conn) && $conn instanceof mysqli) {
            $sql = 'SELECT id, user_id, is_guest, email, first_name, last_name, status, total_amount, created_at FROM orders ORDER BY id DESC LIMIT 200';
            if ($rs = $conn->query($sql)) {
              while ($row = $rs->fetch_assoc()) { $orders[] = $row; }
              $rs->close();
            }
          }
        ?>
        <div style="margin:0 0 12px;">
          <!-- Search by Order ID -->
          <div style="display:flex; gap:8px; margin-bottom:8px;">
            <input type="number" id="search-order-by-id" placeholder="Search by Order ID" style="width:200px;" />
            <button class="btn primary" type="button" onclick="searchOrderById()">Search by ID</button>
            <button class="btn" type="button" onclick="clearOrderSearch()">Clear</button>
          </div>
          <!-- Real-time filter -->
          <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <input type="text" id="filter-order-customer" placeholder="Filter by Customer Name" style="flex:1; min-width:150px;" />
            <select id="filter-order-status" style="flex:1; min-width:150px;">
              <option value="">Filter by Status</option>
              <option value="pending">Pending</option>
              <option value="paid">Paid</option>
              <option value="processing">Processing</option>
              <option value="shipped">Shipped</option>
              <option value="delivered">Delivered</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <input type="date" id="filter-order-date" placeholder="Filter by Date" style="flex:1; min-width:150px;" />
          </div>
        </div>
        <?php if (empty($orders)): ?>
          <div>No orders found.</div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Total</th>
                <th>Created</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="orders-tbody">
              <?php foreach ($orders as $o):
                $customer = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''));
                if ($customer === '') { $customer = $o['email'] ?? 'Guest'; }
                $dateOnly = substr($o['created_at'] ?? '', 0, 10);
              ?>
              <tr data-id="<?= (int)$o['id'] ?>" data-customer="<?= htmlspecialchars(strtolower($customer)) ?>" data-status="<?= htmlspecialchars($o['status']) ?>" data-date="<?= htmlspecialchars($dateOnly) ?>">
                <td>#<?= (int)$o['id'] ?></td>
                <td><?= htmlspecialchars($customer) ?></td>
                <td>
                  <form method="post" style="display:flex; gap:8px; align-items:center;">
                    <input type="hidden" name="update_status" value="1" />
                    <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>" />
                    <select name="status">
                      <?php $opts=['pending','paid','processing','shipped','delivered','cancelled']; foreach ($opts as $st): ?>
                        <option value="<?= $st ?>" <?= ($o['status']===$st ? 'selected' : '') ?>><?= $st ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn" type="submit">Save</button>
                  </form>
                </td>
                <td>$<?= number_format((float)($o['total_amount'] ?? 0), 2) ?></td>
                <td><?= htmlspecialchars($o['created_at'] ?? '') ?></td>
                <td><a class="btn" href="detailsa.php?id=<?= (int)$o['id'] ?>">View</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </section>


  <section class="tab-content" id="tab-customers" style="display:none;">
    <div class="card">
      <div class="card-head"><strong>View Customers</strong></div>
      <div class="card-body">
        <?php
          $customers = [];
          if (isset($conn) && $conn instanceof mysqli) {
            // Aggregate by effective email (orders.email fallback to users.email) and show latest name
            $sql = "SELECT 
                      COALESCE(NULLIF(o.email,''), u.email) AS email,
                      MAX(TRIM(CONCAT(COALESCE(o.first_name,''),' ',COALESCE(o.last_name,'')))) AS customer_name,
                      COUNT(*) AS total_orders,
                      MAX(o.created_at) AS last_order
                    FROM orders o
                    LEFT JOIN users u ON u.id = o.user_id
                    GROUP BY email
                    ORDER BY last_order DESC";
            if ($rs = $conn->query($sql)) {
              while ($row = $rs->fetch_assoc()) { $customers[] = $row; }
              $rs->close();
            }
          }
        ?>
        <div style="margin:0 0 12px;">
          <!-- Real-time filter -->
          <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <input type="text" id="filter-customer-name" placeholder="Filter by Customer Name" style="flex:1; min-width:200px;" />
            <input type="text" id="filter-customer-email" placeholder="Filter by Email" style="flex:1; min-width:200px;" />
          </div>
        </div>
        <?php if (empty($customers)): ?>
          <div>No customers found.</div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Customer Name</th><th>Email</th><th>Total Orders</th><th>Last Order</th>
              </tr>
            </thead>
            <tbody id="customers-tbody">
              <?php foreach ($customers as $c): ?>
              <tr data-name="<?= htmlspecialchars(strtolower($c['customer_name'] ?? '')) ?>" data-email="<?= htmlspecialchars(strtolower($c['email'] ?? '')) ?>">
                <td><?= htmlspecialchars(($c['customer_name'] ?? '') !== '' ? $c['customer_name'] : ($c['email'] ?? '—')) ?></td>
                <td><?= htmlspecialchars($c['email'] ?: '—') ?></td>
                <td><?= (int)($c['total_orders'] ?? 0) ?></td>
                <td><?= htmlspecialchars($c['last_order'] ?? '') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
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

// Search Order by ID
function searchOrderById() {
  const searchId = document.getElementById('search-order-by-id').value.trim();
  
  if (searchId === '') {
    alert('Please enter an Order ID');
    return;
  }
  
  const ordersRows = document.querySelectorAll('#orders-tbody tr');
  let found = false;
  
  ordersRows.forEach(row => {
    if (!row.hasAttribute('data-id')) {
      return;
    }
    
    const rowId = row.getAttribute('data-id');
    
    if (rowId === searchId) {
      row.style.display = '';
      found = true;
    } else {
      row.style.display = 'none';
    }
  });
  
  // Show/hide "no results" message
  const tbody = document.getElementById('orders-tbody');
  let noResultsRow = tbody.querySelector('.no-results-row');
  
  if (!found) {
    if (!noResultsRow) {
      noResultsRow = document.createElement('tr');
      noResultsRow.className = 'no-results-row';
      noResultsRow.innerHTML = '<td colspan="6" style="text-align:center; padding:20px;">No order found with ID #' + searchId + '</td>';
      tbody.appendChild(noResultsRow);
    } else {
      noResultsRow.innerHTML = '<td colspan="6" style="text-align:center; padding:20px;">No order found with ID #' + searchId + '</td>';
    }
    noResultsRow.style.display = '';
  } else if (noResultsRow) {
    noResultsRow.style.display = 'none';
  }
  
  // Clear real-time filters when searching by ID
  document.getElementById('filter-order-customer').value = '';
  document.getElementById('filter-order-status').value = '';
  document.getElementById('filter-order-date').value = '';
}

function clearOrderSearch() {
  document.getElementById('search-order-by-id').value = '';
  document.getElementById('filter-order-customer').value = '';
  document.getElementById('filter-order-status').value = '';
  document.getElementById('filter-order-date').value = '';
  
  const ordersRows = document.querySelectorAll('#orders-tbody tr');
  ordersRows.forEach(row => {
    if (row.hasAttribute('data-id')) {
      row.style.display = '';
    }
  });
  
  const tbody = document.getElementById('orders-tbody');
  const noResultsRow = tbody.querySelector('.no-results-row');
  if (noResultsRow) {
    noResultsRow.style.display = 'none';
  }
}

// Real-time order filtering
const filterOrderCustomer = document.getElementById('filter-order-customer');
const filterOrderStatus = document.getElementById('filter-order-status');
const filterOrderDate = document.getElementById('filter-order-date');

function filterOrders() {
  const customerValue = filterOrderCustomer.value.toLowerCase().trim();
  const statusValue = filterOrderStatus.value;
  const dateValue = filterOrderDate.value;
  
  // Clear ID search when using filters
  document.getElementById('search-order-by-id').value = '';
  
  let visibleCount = 0;
  const ordersRows = document.querySelectorAll('#orders-tbody tr');
  
  ordersRows.forEach(row => {
    if (!row.hasAttribute('data-id')) {
      return;
    }
    
    const rowCustomer = row.getAttribute('data-customer') || '';
    const rowStatus = row.getAttribute('data-status') || '';
    const rowDate = row.getAttribute('data-date') || '';
    
    const customerMatch = customerValue === '' || rowCustomer.includes(customerValue);
    const statusMatch = statusValue === '' || rowStatus === statusValue;
    const dateMatch = dateValue === '' || rowDate === dateValue;
    
    if (customerMatch && statusMatch && dateMatch) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });
  
  // Show/hide "no results" message
  const tbody = document.getElementById('orders-tbody');
  let noResultsRow = tbody.querySelector('.no-results-row');
  
  if (visibleCount === 0 && ordersRows.length > 0) {
    if (!noResultsRow) {
      noResultsRow = document.createElement('tr');
      noResultsRow.className = 'no-results-row';
      noResultsRow.innerHTML = '<td colspan="6" style="text-align:center; padding:20px;">No orders match your filters.</td>';
      tbody.appendChild(noResultsRow);
    }
    noResultsRow.style.display = '';
  } else if (noResultsRow) {
    noResultsRow.style.display = 'none';
  }
}

// Add event listeners for real-time filtering
if (filterOrderCustomer && filterOrderStatus && filterOrderDate) {
  filterOrderCustomer.addEventListener('input', filterOrders);
  filterOrderStatus.addEventListener('change', filterOrders);
  filterOrderDate.addEventListener('change', filterOrders);
}

// Real-time customer filtering
const filterCustomerName = document.getElementById('filter-customer-name');
const filterCustomerEmail = document.getElementById('filter-customer-email');
const customersRows = document.querySelectorAll('#customers-tbody tr');

function filterCustomers() {
  const nameValue = filterCustomerName.value.toLowerCase().trim();
  const emailValue = filterCustomerEmail.value.toLowerCase().trim();
  
  let visibleCount = 0;
  
  customersRows.forEach(row => {
    if (!row.hasAttribute('data-name')) {
      return;
    }
    
    const rowName = row.getAttribute('data-name') || '';
    const rowEmail = row.getAttribute('data-email') || '';
    
    const nameMatch = nameValue === '' || rowName.includes(nameValue);
    const emailMatch = emailValue === '' || rowEmail.includes(emailValue);
    
    if (nameMatch && emailMatch) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });
  
  // Show/hide "no results" message
  const tbody = document.getElementById('customers-tbody');
  let noResultsRow = tbody.querySelector('.no-results-row');
  
  if (visibleCount === 0 && customersRows.length > 0) {
    if (!noResultsRow) {
      noResultsRow = document.createElement('tr');
      noResultsRow.className = 'no-results-row';
      noResultsRow.innerHTML = '<td colspan="4" style="text-align:center; padding:20px;">No customers match your filters.</td>';
      tbody.appendChild(noResultsRow);
    }
    noResultsRow.style.display = '';
  } else if (noResultsRow) {
    noResultsRow.style.display = 'none';
  }
}

// Add event listeners for real-time filtering
if (filterCustomerName && filterCustomerEmail) {
  filterCustomerName.addEventListener('input', filterCustomers);
  filterCustomerEmail.addEventListener('input', filterCustomers);
}
</script>

  </body>
</html>
