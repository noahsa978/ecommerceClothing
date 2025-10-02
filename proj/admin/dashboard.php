<?php
$page_title = 'Admin • Dashboard';
include '../includes/header.php';
?>

<style>
  /* Page-specific styles reused for all admin pages */
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
    text-decoration: none;
    font-weight: 500;
  }
  .admin-nav a:hover {
    background: rgba(124,58,237,0.12);
    color: #fff;
  }
  .admin-nav a.active {
    background: var(--accent);
    color: #fff;
    pointer-events: none;
  }

  .stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin: 20px 0;
  }
  .stat {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 20px;
    text-align: center;
    transition: transform 0.15s ease;
  }
  .stat:hover { transform: translateY(-2px); }
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
  .card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border-bottom: 1px solid var(--border);
  }
  .card-body { padding: 14px; color: var(--muted); }

  .quick-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
  .quick-actions .btn { padding: 10px 12px; border: 1px solid var(--border); border-radius: 12px; background: #0b1220; color: var(--text); text-decoration: none; }
  .quick-actions .btn.primary { background: var(--accent); border-color: transparent; }
  .quick-actions .btn.primary:hover { background: var(--accent-600); }

  @media (max-width: 900px) { .stats { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 700px) { .stats { grid-template-columns: 1fr; } .cards { grid-template-columns: 1fr; } }
</style>

<main class="container">
  <section class="admin-header">
    <h1 style="margin:0 0 8px;">Admin Dashboard</h1>
    <nav class="admin-nav" aria-label="Admin navigation">
      <a href="dashboard.php" class="active">Dashboard</a>
      <a href="employees.php">Employees</a>
      <a href="users.php">Users</a>
      <a href="products.php">Products</a>
      <a href="orders.php">Orders</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <section>
    <h2 style="margin:0 0 6px;">Welcome, Admin!</h2>
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
        <div class="k">0</div>
        <div class="l">Users</div>
      </div>
      <div class="stat">
        <div class="k">0</div>
        <div class="l">Products</div>
      </div>
      <div class="stat">
        <div class="k">0</div>
        <div class="l">Orders</div>
      </div>
      <div class="stat">
        <div class="k">$0.00</div>
        <div class="l">Total Sales</div>
      </div>
    </div>
  </section>

  <section class="cards" aria-label="Overview">
    <div class="card">
      <div class="card-head"><strong>Recent Orders</strong><span style="color:var(--muted);">Last 5</span></div>
      <div class="card-body">No data yet.</div>
    </div>
    <div class="card">
      <div class="card-head"><strong>Low Stock</strong><span style="color:var(--muted);">Top 5</span></div>
      <div class="card-body">No data yet.</div>
    </div>
  </section>
</main>

<?php include '../includes/footer.php'; ?>
