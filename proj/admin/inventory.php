<?php
$page_title = 'Admin • Inventory';
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once __DIR__ . '/../includes/db_connect.php';

$flash = ['type' => null, 'message' => ''];

// Handle Material Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_material'])) {
    try {
        $name = trim($_POST['name'] ?? '');
        $unit = trim($_POST['unit'] ?? 'meter');
        $quantity = (float)($_POST['quantity'] ?? 0);
        
        if (empty($name)) {
            throw new Exception('Material name is required');
        }
        
        $stmt = $conn->prepare("INSERT INTO materials (name, unit, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param('ssd', $name, $unit, $quantity);
        
        if ($stmt->execute()) {
            $flash = ['type' => 'success', 'message' => 'Material added successfully!'];
        } else {
            throw new Exception('Failed to add material: ' . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $flash = ['type' => 'error', 'message' => $e->getMessage()];
    }
}

// Handle Production Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_production'])) {
    try {
        $material_id = (int)($_POST['material_id'] ?? 0);
        $product_name = trim($_POST['product_name'] ?? '');
        $clothes_produced = (int)($_POST['clothes_produced'] ?? 0);
        $material_used = (float)($_POST['material_used'] ?? 0);
        
        if ($material_id <= 0) {
            throw new Exception('Please select a valid material');
        }
        if (empty($product_name)) {
            throw new Exception('Product name is required');
        }
        if ($clothes_produced <= 0) {
            throw new Exception('Number of clothes produced must be greater than 0');
        }
        if ($material_used <= 0) {
            throw new Exception('Material used must be greater than 0');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Add production record
            $stmt = $conn->prepare("INSERT INTO production (material_id, product_name, clothes_produced, material_used) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('isid', $material_id, $product_name, $clothes_produced, $material_used);
            $stmt->execute();
            $stmt->close();
            
            // Update material quantity
            $update = $conn->prepare("UPDATE materials SET quantity = quantity - ? WHERE id = ?");
            $update->bind_param('di', $material_used, $material_id);
            $update->execute();
            
            if ($update->affected_rows === 0) {
                throw new Exception('Failed to update material quantity');
            }
            
            $update->close();
            $conn->commit();
            $flash = ['type' => 'success', 'message' => 'Production record added successfully!'];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $flash = ['type' => 'error', 'message' => $e->getMessage()];
    }
}

// Fetch all materials
$materials = [];
$materials_result = $conn->query("SELECT * FROM materials ORDER BY name");
if ($materials_result) {
    $materials = $materials_result->fetch_all(MYSQLI_ASSOC);
    $materials_result->free();
}

// Fetch all production records with material names
$productions = [];
$productions_result = $conn->query("
    SELECT p.*, m.name as material_name 
    FROM production p 
    JOIN materials m ON p.material_id = m.id 
    ORDER BY p.created_at DESC
");
if ($productions_result) {
    $productions = $productions_result->fetch_all(MYSQLI_ASSOC);
    $productions_result->free();
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
    <h1>Inventory</h1>
    <nav class="admin-nav" aria-label="Admin navigation">
      <a href="dashboard.php">Dashboard</a>
      <a href="employees.php">Employees</a>
      <a href="users.php">Users</a>
      <a href="products.php">Products</a>
      <a href="orders.php">Orders</a>
      <a href="inventory.php" class="active">Inventory</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <!-- Quick Actions -->
  <section class="quick-actions">
    <button class="btn primary" data-tab="materials">Manage Materials</button>
    <button class="btn primary" data-tab="production">Manage Production</button>
  </section>

  <!-- Flash Message -->
  <?php if (!empty($flash['message'])): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?>" style="margin-bottom: 20px;">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
  <?php endif; ?>

  <!-- Tabs -->
  <section class="tab-content" id="tab-materials">
    <div class="card">
      <div class="card-head"><strong>Add New Material</strong></div>
      <div class="card-body">
        <form method="POST" action="" class="form-grid">
          <div class="form-group">
            <label for="name">Material Name *</label>
            <input type="text" id="name" name="name" required class="form-control" placeholder="e.g., Cotton Fabric">
          </div>
          <div class="form-group">
            <label for="unit">Unit</label>
            <select id="unit" name="unit" class="form-control">
              <option value="meter">Meter</option>
              <option value="yard">Yard</option>
              <option value="piece">Piece</option>
              <option value="kg">Kilogram</option>
              <option value="g">Gram</option>
            </select>
          </div>
          <div class="form-group">
            <label for="quantity">Initial Quantity</label>
            <input type="number" id="quantity" name="quantity" step="0.01" min="0" value="0" class="form-control">
          </div>
          <div class="form-actions">
            <button type="submit" name="add_material" class="btn primary">Add Material</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card" style="margin-top: 20px;">
      <div class="card-head"><strong>Material Inventory</strong></div>
      <div class="card-body">
        <?php if (empty($materials)): ?>
          <p>No materials found. Add your first material above.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Quantity</th>
                  <th>Unit</th>
                  <th>Added On</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($materials as $material): ?>
                  <tr>
                    <td><?= htmlspecialchars($material['id']) ?></td>
                    <td><?= htmlspecialchars($material['name']) ?></td>
                    <td><?= number_format($material['quantity'], 2) ?></td>
                    <td><?= htmlspecialchars($material['unit']) ?></td>
                    <td><?= date('M d, Y', strtotime($material['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="tab-content" id="tab-production" style="display:none;">
    <div class="card">
      <div class="card-head"><strong>Add Production Record</strong></div>
      <div class="card-body">
        <form method="POST" action="" class="form-grid">
          <div class="form-group">
            <label for="material_id">Material *</label>
            <select id="material_id" name="material_id" required class="form-control">
              <option value="">-- Select Material --</option>
              <?php foreach ($materials as $material): ?>
                <option value="<?= $material['id'] ?>">
                  <?= htmlspecialchars($material['name']) ?> (<?= number_format($material['quantity'], 2) ?> <?= htmlspecialchars($material['unit']) ?> available)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="product_name">Product Name *</label>
            <input type="text" id="product_name" name="product_name" required class="form-control" placeholder="e.g., Cotton T-Shirt">
          </div>
          <div class="form-group">
            <label for="clothes_produced">Number of Items Produced *</label>
            <input type="number" id="clothes_produced" name="clothes_produced" required min="1" class="form-control">
          </div>
          <div class="form-group">
            <label for="material_used">Material Used *</label>
            <div class="input-group">
              <input type="number" id="material_used" name="material_used" required step="0.01" min="0.01" class="form-control">
              <span class="input-group-text" id="unit-display">meter(s)</span>
            </div>
            <small class="form-text text-muted">Amount of material used in the selected material's unit</small>
          </div>
          <div class="form-actions">
            <button type="submit" name="add_production" class="btn primary">Add Production Record</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card" style="margin-top: 20px;">
      <div class="card-head"><strong>Production History</strong></div>
      <div class="card-body">
        <?php if (empty($productions)): ?>
          <p>No production records found.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Material</th>
                  <th>Product</th>
                  <th>Items Produced</th>
                  <th>Material Used</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($productions as $prod): ?>
                  <tr>
                    <td><?= date('M d, Y', strtotime($prod['created_at'])) ?></td>
                    <td><?= htmlspecialchars($prod['material_name']) ?></td>
                    <td><?= htmlspecialchars($prod['product_name']) ?></td>
                    <td><?= number_format($prod['clothes_produced']) ?></td>
                    <td><?= number_format($prod['material_used'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</main>

<script>
// Tab switching
const buttons = document.querySelectorAll('.quick-actions .btn');
const tabs = document.querySelectorAll('.tab-content');

buttons.forEach(button => {
  button.addEventListener('click', () => {
    // Remove active class from all buttons and tabs
    buttons.forEach(btn => btn.classList.remove('active'));
    tabs.forEach(tab => tab.style.display = 'none');
    
    // Add active class to clicked button
    button.classList.add('active');
    
    // Show corresponding tab
    const tabId = `tab-${button.dataset.tab}`;
    const tab = document.getElementById(tabId);
    if (tab) {
      tab.style.display = 'block';
    }
  });
});

// Update unit display when material selection changes
document.addEventListener('DOMContentLoaded', () => {
  // Initialize first tab as active
  if (buttons.length > 0) {
    buttons[0].classList.add('active');
  }
  if (tabs.length > 0) {
    tabs[0].style.display = 'block';
  }

  // Handle material unit display update
  const materialSelect = document.getElementById('material_id');
  const unitDisplay = document.getElementById('unit-display');
  
  if (materialSelect && unitDisplay) {
    // Get all material options with their units
    const materialOptions = [];
    materialSelect.querySelectorAll('option').forEach(option => {
      if (option.value) {
        const match = option.textContent.match(/\([\d.,]+\s*([^)]+)\s*available\)/);
        if (match && match[1]) {
          materialOptions[option.value] = match[1].trim();
        }
      }
    });
    
    // Update unit display when material changes
    materialSelect.addEventListener('change', function() {
      const selectedId = this.value;
      if (selectedId && materialOptions[selectedId]) {
        unitDisplay.textContent = materialOptions[selectedId];
      } else {
        unitDisplay.textContent = 'unit(s)';
      }
    });
  }
});
</script>

<style>
/* Form Styling */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  box-sizing: border-box;
}

.form-actions {
  grid-column: 1 / -1;
  margin-top: 10px;
}

/* Table Styling */
.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

.data-table th,
.data-table td {
  padding: 10px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.data-table th {
  background-color: #f5f5f5;
  font-weight: 600;
}

.data-table tr:hover {
  background-color: #f9f9f9;
}

/* Alert Styling */
.alert {
  padding: 12px 15px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.alert-success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-danger {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.alert-dismissible {
  position: relative;
  padding-right: 35px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .data-table {
    display: block;
    overflow-x: auto;
    white-space: nowrap;
  }
}
</style>

</body>
</html>