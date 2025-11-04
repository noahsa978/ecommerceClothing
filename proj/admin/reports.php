<?php
  $page_title = 'Admin • Reports';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
  </head>
  <body style="margin-bottom: 16px;">
<style>
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
      <a href="inventory.php">Inventory</a>
      <a href="reports.php" class="active">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <!-- Quick Actions -->
  <section class="quick-actions">
    <button class="btn primary" data-tab="sales">Generate Sales Report</button>
    <button class="btn primary" data-tab="inventory">Inventory Report</button>
  </section>

  <!-- Tabs -->
  <section class="tab-content active" id="tab-sales">
    <div class="card">
      <div class="card-head"><strong>Sales Report</strong></div>
      <div class="card-body">
        <?php
          require_once __DIR__ . '/../includes/db_connect.php';
          $byDate = [];
          $top = [];
          $totalOrders = 0;
          $totalRevenue = 0.0;

          if (isset($conn) && $conn instanceof mysqli) {
            // Pull last 60 days of orders for the report (adjust as needed)
            $sql = "SELECT id, total_amount, created_at, order_items FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) ORDER BY created_at DESC";
            if ($rs = $conn->query($sql)) {
              while ($row = $rs->fetch_assoc()) {
                $date = substr((string)($row['created_at'] ?? ''), 0, 10);
                if ($date === '') { $date = date('Y-m-d'); }
                $amt = (float)($row['total_amount'] ?? 0);
                if (!isset($byDate[$date])) { $byDate[$date] = ['orders'=>0,'revenue'=>0.0]; }
                $byDate[$date]['orders'] += 1;
                $byDate[$date]['revenue'] += $amt;
                $totalOrders += 1;
                $totalRevenue += $amt;

                // Decode items and aggregate top products/variants
                $items = [];
                if (!empty($row['order_items'])) {
                  $dec = json_decode($row['order_items'], true);
                  if (is_array($dec)) { $items = $dec; }
                }
                foreach ($items as $it) {
                  $variantId = isset($it['variant_id']) ? (int)$it['variant_id'] : 0;
                  $name = (string)($it['name'] ?? 'Unknown');
                  $size = (string)($it['size'] ?? '');
                  $color = (string)($it['color'] ?? '');
                  $qty = (int)($it['quantity'] ?? 0);
                  $line = (float)($it['line_total'] ?? 0);
                  $key = $variantId.'|'.$name.'|'.$size.'|'.$color;
                  if (!isset($top[$key])) {
                    $top[$key] = [
                      'variant_id'=>$variantId,
                      'name'=>$name,
                      'size'=>$size,
                      'color'=>$color,
                      'qty'=>0,
                      'revenue'=>0.0
                    ];
                  }
                  $top[$key]['qty'] += $qty;
                  $top[$key]['revenue'] += $line;
                }
              }
              $rs->close();
            }
          }

          // Sort dates descending and top products by qty desc, then revenue desc
          krsort($byDate);
          uasort($top, function($a,$b){
            if ($a['qty'] === $b['qty']) { return $b['revenue'] <=> $a['revenue']; }
            return $b['qty'] <=> $a['qty'];
          });
        ?>

        <div style="margin-bottom:10px;">
          <strong>Summary:</strong> <?= (int)$totalOrders ?> orders · ETB <?= number_format($totalRevenue, 2) ?> revenue (last 60 days)
        </div>

        <!-- Download Report Section -->
        <div style="background: rgba(124,58,237,0.1); border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 16px;">
          <h3 style="margin:0 0 12px; color:#fff; font-size: 16px;">📥 Download Sales Report</h3>
          <form id="download-report-form" style="display:flex; gap:10px; flex-wrap:wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 150px;">
              <label style="display: block; font-size: 13px; color: var(--muted); margin-bottom: 4px;" for="report_from">From Date</label>
              <input id="report_from" name="from" type="date" value="<?= date('Y-m-01') ?>" style="margin-bottom: 0;" />
            </div>
            <div style="flex: 1; min-width: 150px;">
              <label style="display: block; font-size: 13px; color: var(--muted); margin-bottom: 4px;" for="report_to">To Date</label>
              <input id="report_to" name="to" type="date" value="<?= date('Y-m-d') ?>" style="margin-bottom: 0;" />
            </div>
            <button type="button" class="btn primary" onclick="downloadReport()" style="padding: 10px 16px; white-space: nowrap;">
              Download PDF
            </button>
            <button type="button" class="btn" onclick="downloadTodayReport()" style="padding: 10px 16px; white-space: nowrap; background: var(--accent-2); border-color: transparent;">
              Today's Report
            </button>
          </form>
        </div>

        <h3 style="margin:10px 0 6px; color:#fff;">Sales by Date</h3>
        <form id="sales-filter-form" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
          <div>
            <label class="muted" for="sales_from">From</label>
            <input id="sales_from" type="date" />
          </div>
          <div>
            <label class="muted" for="sales_to">To</label>
            <input id="sales_to" type="date" />
          </div>
          <div>
            <label class="muted" for="sales_sort">Sort by</label>
            <select id="sales_sort">
              <option value="date_desc">Date (newest)</option>
              <option value="date_asc">Date (oldest)</option>
              <option value="orders_desc">Orders (high→low)</option>
              <option value="orders_asc">Orders (low→high)</option>
              <option value="rev_desc">Revenue (high→low)</option>
              <option value="rev_asc">Revenue (low→high)</option>
            </select>
          </div>
        </form>
        <?php if (empty($byDate)): ?>
          <div>No sales data for the selected period.</div>
        <?php else: ?>
          <table id="sales_table">
            <thead>
              <tr><th>Date</th><th>Orders</th><th>Total Revenue</th></tr>
            </thead>
            <tbody>
              <?php foreach ($byDate as $d => $stats): ?>
              <tr data-date="<?= htmlspecialchars($d) ?>" data-orders="<?= (int)$stats['orders'] ?>" data-revenue="<?= number_format((float)$stats['revenue'], 2, '.', '') ?>">
                <td><?= htmlspecialchars($d) ?></td>
                <td><?= (int)$stats['orders'] ?></td>
                <td>ETB <?= number_format((float)$stats['revenue'], 2) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>

      </div>
    </div>

    <div class="card" style="margin-top:12px;">
      <div class="card-head"><strong>Top 5 Products / Variants</strong></div>
      <div class="card-body">
        <?php if (empty($top)): ?>
          <div>No product sales yet.</div>
        <?php else: ?>
          <?php $top = array_slice($top, 0, 5, true); ?>
          <table>
            <thead>
              <tr><th>#</th><th>Variant ID</th><th>Name</th><th>Size</th><th>Color</th><th>Qty Sold</th><th>Revenue</th></tr>
            </thead>
            <tbody>
              <?php $rank=1; foreach ($top as $row): ?>
              <tr>
                <td><?= $rank++ ?></td>
                <td><?= (int)$row['variant_id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['size']) ?></td>
                <td><?= htmlspecialchars($row['color']) ?></td>
                <td><?= (int)$row['qty'] ?></td>
                <td>ETB <?= number_format((float)$row['revenue'], 2) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="tab-content" id="tab-inventory">
    <div class="card">
      <div class="card-head"><strong>Inventory Report</strong></div>
      <div class="card-body">
        <?php
          require_once __DIR__ . '/../includes/db_connect.php';
          $threshold = 5; // global low stock threshold
          $variants = [];
          // Read filters
          $inv_q = isset($_GET['inv_q']) ? trim($_GET['inv_q']) : '';
          $inv_status = isset($_GET['inv_status']) ? $_GET['inv_status'] : 'all'; // all|ok|not_ok
          if (isset($conn) && $conn instanceof mysqli) {
            $sql = "SELECT v.id AS variant_id, p.name AS product_name, v.color, v.size, v.stock
                    FROM product_variants v
                    JOIN products p ON p.id = v.product_id
                    ORDER BY p.name, v.size, v.color";
            if ($rs = $conn->query($sql)) {
              while ($row = $rs->fetch_assoc()) { $variants[] = $row; }
              $rs->close();
            }
          }
          // Compute status and apply filters
          $filtered = [];
          foreach ($variants as $v) {
            $stock = (int)($v['stock'] ?? 0);
            $status = 'OK';
            if ($stock <= 0) { $status = 'Out of stock'; }
            elseif ($stock < $threshold) { $status = 'Low stock'; }

            // Status filter
            if ($inv_status === 'ok' && $status !== 'OK') { continue; }
            if ($inv_status === 'not_ok' && $status === 'OK') { continue; }

            // Query filter across product, variant (color/size/id)
            if ($inv_q !== '') {
              $hay = strtolower(
                (string)$v['product_name'].' '.(string)$v['color'].' '.(string)$v['size'].' '.(string)$v['variant_id']
              );
              if (strpos($hay, strtolower($inv_q)) === false) { continue; }
            }

            $v['computed_status'] = $status;
            $filtered[] = $v;
          }
        ?>
        <form id="inv-filter-form" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
          <div style="flex:1 1 260px;">
            <input id="inv_q" type="text" name="inv_q" value="<?= htmlspecialchars($inv_q) ?>" placeholder="Search product, color, size, or variant ID" />
          </div>
          <div>
            <select id="inv_status" name="inv_status">
              <option value="all" <?= $inv_status==='all'?'selected':'' ?>>All statuses</option>
              <option value="ok" <?= $inv_status==='ok'?'selected':'' ?>>OK</option>
              <option value="not_ok" <?= $inv_status==='not_ok'?'selected':'' ?>>Not OK (low/out)</option>
            </select>
          </div>
        </form>
        <?php if (empty($filtered)): ?>
          <div>No inventory records found.</div>
        <?php else: ?>
          <table id="inv_table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Variant</th>
                <th>Stock</th>
                <th>Threshold</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($filtered as $v):
                $stock = (int)($v['stock'] ?? 0);
                $status = $v['computed_status'] ?? 'OK';
                $rowStatus = $status === 'OK' ? 'ok' : 'not_ok';
                $searchBlob = strtolower((string)$v['product_name'].' '.(string)$v['color'].' '.(string)$v['size'].' '.(string)$v['variant_id']);
              ?>
              <tr data-name="<?= htmlspecialchars($v['product_name']) ?>" data-color="<?= htmlspecialchars($v['color']) ?>" data-size="<?= htmlspecialchars($v['size']) ?>" data-id="<?= (int)$v['variant_id'] ?>" data-status="<?= $rowStatus ?>" data-blob="<?= htmlspecialchars($searchBlob) ?>">
                <td><?= htmlspecialchars($v['product_name']) ?></td>
                <td>#<?= (int)$v['variant_id'] ?> · <?= htmlspecialchars($v['color']) ?> <?= htmlspecialchars($v['size']) ?></td>
                <td><?= $stock ?></td>
                <td><?= (int)$threshold ?></td>
                <td><?= htmlspecialchars($status) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
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

  // Inventory instant filter
  (function(){
    const form = document.getElementById('inv-filter-form');
    const qInput = document.getElementById('inv_q');
    const statusSel = document.getElementById('inv_status');
    const table = document.getElementById('inv_table');
    if (!form || !qInput || !statusSel || !table) return;

    function applyFilter(){
      const q = qInput.value.trim().toLowerCase();
      const stat = statusSel.value; // all|ok|not_ok
      const rows = table.querySelectorAll('tbody tr');
      let shown = 0;
      rows.forEach(tr => {
        const blob = (tr.getAttribute('data-blob') || '').toLowerCase();
        const rowStat = tr.getAttribute('data-status') || 'ok';
        let match = true;
        if (q && blob.indexOf(q) === -1) match = false;
        if (stat !== 'all') {
          if (stat === 'ok' && rowStat !== 'ok') match = false;
          if (stat === 'not_ok' && rowStat !== 'not_ok') match = false;
        }
        tr.style.display = match ? '' : 'none';
        if (match) shown++;
      });
    }

    form.addEventListener('submit', (e) => { e.preventDefault(); applyFilter(); });
    qInput.addEventListener('input', applyFilter);
    statusSel.addEventListener('change', applyFilter);
  })();

  // Sales instant filter/sort
  (function(){
    const tbl = document.getElementById('sales_table');
    const fromEl = document.getElementById('sales_from');
    const toEl = document.getElementById('sales_to');
    const sortEl = document.getElementById('sales_sort');
    if (!tbl || !fromEl || !toEl || !sortEl) return;

    function toDate(s){
      // s in YYYY-MM-DD
      const parts = (s||'').split('-');
      if (parts.length !== 3) return null;
      return new Date(parseInt(parts[0]), parseInt(parts[1])-1, parseInt(parts[2]));
    }

    function apply(){
      const from = toDate(fromEl.value);
      const to = toDate(toEl.value);
      const rows = Array.from(tbl.querySelectorAll('tbody tr'));
      // Filter by date range
      rows.forEach(tr => {
        const d = toDate(tr.getAttribute('data-date'));
        let show = true;
        if (from && d && d < from) show = false;
        if (to && d && d > to) show = false;
        tr.style.display = show ? '' : 'none';
      });

      // Sort visible rows
      const mode = sortEl.value;
      const visible = rows.filter(tr => tr.style.display !== 'none');
      visible.sort((a,b)=>{
        const ad = a.getAttribute('data-date');
        const bd = b.getAttribute('data-date');
        const ao = parseInt(a.getAttribute('data-orders')||'0');
        const bo = parseInt(b.getAttribute('data-orders')||'0');
        const ar = parseFloat(a.getAttribute('data-revenue')||'0');
        const br = parseFloat(b.getAttribute('data-revenue')||'0');
        switch(mode){
          case 'date_asc': return ad.localeCompare(bd);
          case 'date_desc': return bd.localeCompare(ad);
          case 'orders_asc': return ao - bo;
          case 'orders_desc': return bo - ao;
          case 'rev_asc': return ar - br;
          case 'rev_desc': return br - ar;
        }
        return 0;
      });
      // Reattach in order
      const tbody = tbl.querySelector('tbody');
      visible.forEach(tr => tbody.appendChild(tr));
    }

    fromEl.addEventListener('change', apply);
    toEl.addEventListener('change', apply);
    sortEl.addEventListener('change', apply);
  })();

  // Download report functions
  function downloadReport() {
    const fromInput = document.getElementById('report_from');
    const toInput = document.getElementById('report_to');
    const from = fromInput.value;
    const to = toInput.value;
    
    if (!from || !to) {
      alert('Please select both From and To dates');
      return;
    }
    
    if (new Date(from) > new Date(to)) {
      alert('From date cannot be after To date');
      return;
    }
    
    // Open download in new window
    window.open('download_sales_report.php?from=' + encodeURIComponent(from) + '&to=' + encodeURIComponent(to), '_blank');
  }
  
  function downloadTodayReport() {
    const today = new Date().toISOString().split('T')[0];
    window.open('download_sales_report.php?from=' + encodeURIComponent(today) + '&to=' + encodeURIComponent(today), '_blank');
  }
  </script>

  </body>
</html>
