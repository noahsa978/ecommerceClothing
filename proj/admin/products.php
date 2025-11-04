<?php
  $page_title = 'Admin • Products';
  // DB connection and form handlers
  require_once __DIR__ . '/../includes/db_connect.php';
  $flash = null;

  // Ensure products table has a description column
  if ($conn instanceof mysqli) {
    if ($res = $conn->query("SHOW COLUMNS FROM products LIKE 'description'")) {
      if ($res->num_rows === 0) {
        $conn->query("ALTER TABLE products ADD COLUMN description TEXT NULL AFTER base_price");
      }
      $res->free();
    }
  }

  // Helper: save upload and return relative path or null
  function save_upload($field, $subdir) {
    if (!isset($_FILES[$field]) || !is_uploaded_file($_FILES[$field]['tmp_name'])) return null;
    $baseDir = dirname(__DIR__) . '/uploads/' . trim($subdir, '/');
    if (!is_dir($baseDir)) { @mkdir($baseDir, 0775, true); }
    $name = basename($_FILES[$field]['name']);
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($name, PATHINFO_FILENAME));
    $fname = $safeBase . '_' . time() . ($ext ? ('.' . strtolower($ext)) : '');
    $target = $baseDir . '/' . $fname;
    if (move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
      // return relative path from proj directory
      return 'uploads/' . trim($subdir, '/') . '/' . $fname;
    }
    return null;
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && ($conn instanceof mysqli)) {
    if ($_POST['form_type'] === 'add_product') {
      $name = trim($_POST['name'] ?? '');
      $category = trim($_POST['category'] ?? '');
      $gender = trim($_POST['gender'] ?? '');
      $base_price = $_POST['base_price'] ?? '';
      $description = trim($_POST['description'] ?? '');
      $imgPath = save_upload('image', 'products');
      if ($name === '' || $category === '' || $gender === '' || $base_price === '') {
        $flash = ['type' => 'error', 'msg' => 'All fields except image are required.'];
      } else {
        $stmt = $conn->prepare('INSERT INTO products (name, category, gender, base_price, description, image) VALUES (?,?,?,?,?,?)');
        $zero = 0.00; $priceVal = is_numeric($base_price) ? (float)$base_price : $zero;
        $stmt->bind_param('sssdss', $name, $category, $gender, $priceVal, $description, $imgPath);
        if ($stmt->execute()) {
          $flash = ['type' => 'success', 'msg' => 'Product added successfully.'];
        } else {
          $flash = ['type' => 'error', 'msg' => 'Failed to add product: ' . $stmt->error];
        }
        $stmt->close();
      }
    } elseif ($_POST['form_type'] === 'add_variant') {
      $product_id = intval($_POST['product_id'] ?? 0);
      $color = trim($_POST['color'] ?? '');
      $size = trim($_POST['size'] ?? '');
      $stock = intval($_POST['stock'] ?? 0);
      $price = $_POST['price'] !== '' ? (float)$_POST['price'] : null;
      $vImg = save_upload('variant_image', 'variants');
      if ($product_id <= 0 || $color === '' || $size === '' || $stock < 0) {
        $flash = ['type' => 'error', 'msg' => 'Please fill in product, color, size and stock.'];
      } else {
        // Build statement with nullable price and image
        $stmt = $conn->prepare('INSERT INTO product_variants (product_id, color, size, stock, price, image) VALUES (?,?,?,?,?,?)');
        // Bind price as double or null
        // For mysqli, null must be bound as null; using s for image path which can be null
        $stmt->bind_param('issids', $product_id, $color, $size, $stock, $price, $vImg);
        if ($stmt->execute()) {
          $flash = ['type' => 'success', 'msg' => 'Variant added successfully.'];
        } else {
          $flash = ['type' => 'error', 'msg' => 'Failed to add variant: ' . $stmt->error];
        }
        $stmt->close();
      }
    } elseif ($_POST['form_type'] === 'update_product') {
      $pid = intval($_POST['id'] ?? 0);
      $name = trim($_POST['name'] ?? '');
      $category = trim($_POST['category'] ?? '');
      $gender = trim($_POST['gender'] ?? '');
      $base_price = $_POST['base_price'] ?? '';
      if ($pid <= 0 || $name === '' || $category === '' || $gender === '' || $base_price === '') {
        $flash = ['type' => 'error', 'msg' => 'Please fill all product fields.'];
      } else {
        $stmt = $conn->prepare('UPDATE products SET name=?, category=?, gender=?, base_price=? WHERE id=?');
        $priceVal = is_numeric($base_price) ? (float)$base_price : 0.00;
        $stmt->bind_param('sssdi', $name, $category, $gender, $priceVal, $pid);
        if ($stmt->execute()) {
          $flash = ['type' => 'success', 'msg' => 'Product updated.'];
        } else {
          $flash = ['type' => 'error', 'msg' => 'Failed to update product: ' . $stmt->error];
        }
        $stmt->close();
      }
    } elseif ($_POST['form_type'] === 'update_variant') {
      $vid = intval($_POST['id'] ?? 0);
      $product_id = intval($_POST['product_id'] ?? 0);
      $color = trim($_POST['color'] ?? '');
      $size = trim($_POST['size'] ?? '');
      $stock = intval($_POST['stock'] ?? 0);
      $price = $_POST['price'] !== '' ? (float)$_POST['price'] : null;
      if ($vid <= 0 || $product_id <= 0 || $color === '' || $size === '' || $stock < 0) {
        $flash = ['type' => 'error', 'msg' => 'Please fill all variant fields.'];
      } else {
        $stmt = $conn->prepare('UPDATE product_variants SET product_id=?, color=?, size=?, stock=?, price=? WHERE id=?');
        $stmt->bind_param('issidi', $product_id, $color, $size, $stock, $price, $vid);
        if ($stmt->execute()) {
          $flash = ['type' => 'success', 'msg' => 'Variant updated.'];
        } else {
          $flash = ['type' => 'error', 'msg' => 'Failed to update variant: ' . $stmt->error];
        }
        $stmt->close();
      }
    } elseif ($_POST['form_type'] === 'delete_product') {
      $pid = intval($_POST['id'] ?? 0);
      if ($pid > 0) {
        // Delete related records first to avoid foreign key constraint errors
        // 1. Delete reviews for this product
        $stmt = $conn->prepare('DELETE FROM reviews WHERE product_id=?');
        $stmt->bind_param('i', $pid);
        $stmt->execute();
        $stmt->close();
        
        // 2. Get all variant IDs for this product
        $variant_ids = [];
        $stmt = $conn->prepare('SELECT id FROM product_variants WHERE product_id=?');
        $stmt->bind_param('i', $pid);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
          $variant_ids[] = $row['id'];
        }
        $stmt->close();
        
        // 3. Delete stock movements and cart items for each variant
        if (!empty($variant_ids)) {
          foreach ($variant_ids as $vid) {
            // Delete stock movements first
            $stmt = $conn->prepare('DELETE FROM stock_movements WHERE variant_id=?');
            $stmt->bind_param('i', $vid);
            $stmt->execute();
            $stmt->close();
            
            // Delete cart items
            $stmt = $conn->prepare('DELETE FROM cart_items WHERE variant_id=?');
            $stmt->bind_param('i', $vid);
            $stmt->execute();
            $stmt->close();
          }
        }
        
        // 4. Delete all product variants
        $stmt = $conn->prepare('DELETE FROM product_variants WHERE product_id=?');
        $stmt->bind_param('i', $pid);
        $stmt->execute();
        $stmt->close();
        
        // 5. Finally delete the product
        $stmt = $conn->prepare('DELETE FROM products WHERE id=?');
        $stmt->bind_param('i', $pid);
        if ($stmt->execute()) {
          $flash = ['type' => 'success', 'msg' => 'Product and all related data deleted.'];
        } else {
          $flash = ['type' => 'error', 'msg' => 'Failed to delete product: ' . $stmt->error];
        }
        $stmt->close();
      } else {
        $flash = ['type' => 'error', 'msg' => 'Invalid product ID.'];
      }
    } elseif ($_POST['form_type'] === 'delete_variant') {
      $vid = intval($_POST['id'] ?? 0);
      if ($vid > 0) {
        // Delete related records first to avoid foreign key constraint errors
        // 1. Delete stock movements for this variant
        $stmt = $conn->prepare('DELETE FROM stock_movements WHERE variant_id=?');
        $stmt->bind_param('i', $vid);
        $stmt->execute();
        $stmt->close();
        
        // 2. Delete cart items for this variant
        $stmt = $conn->prepare('DELETE FROM cart_items WHERE variant_id=?');
        $stmt->bind_param('i', $vid);
        $stmt->execute();
        $stmt->close();
        
        // 3. Finally delete the variant
        $stmt = $conn->prepare('DELETE FROM product_variants WHERE id=?');
        $stmt->bind_param('i', $vid);
        if ($stmt->execute()) {
          $flash = ['type' => 'success', 'msg' => 'Variant and related data deleted.'];
        } else {
          $flash = ['type' => 'error', 'msg' => 'Failed to delete variant: ' . $stmt->error];
        }
        $stmt->close();
      } else {
        $flash = ['type' => 'error', 'msg' => 'Invalid variant ID.'];
      }
    }
  }

  // Load products for variants dropdown
  $products_for_select = [];
  // Load categories for selects
  $categories_for_select = [];
  if ($conn instanceof mysqli) {
    if ($res = $conn->query('SELECT id, name FROM products ORDER BY created_at DESC LIMIT 200')) {
      while ($row = $res->fetch_assoc()) { $products_for_select[] = $row; }
      $res->free();
    }
    if ($resC = $conn->query('SELECT id, name FROM categories ORDER BY name ASC')) {
      while ($row = $resC->fetch_assoc()) { $categories_for_select[] = $row; }
      $resC->free();
    }
  }

  // Load data for Edit tab tables
  $products_list = [];
  $variants_list = [];
  // Handle search queries (GET)
  $p_search = isset($_GET['p_search']) ? trim($_GET['p_search']) : '';
  $v_search = isset($_GET['v_search']) ? trim($_GET['v_search']) : '';

  if ($conn instanceof mysqli) {
    // Products query ascending by ID with optional LIKE filter
    if ($p_search !== '') {
      $like = '%' . $conn->real_escape_string($p_search) . '%';
      $sqlP = "SELECT id, name, category, gender, base_price, image, created_at
               FROM products
               WHERE name LIKE '$like' OR category LIKE '$like' OR gender LIKE '$like'
               ORDER BY id ASC LIMIT 500";
    } else {
      $sqlP = 'SELECT id, name, category, gender, base_price, image, created_at FROM products ORDER BY id ASC LIMIT 500';
    }
    if ($res = $conn->query($sqlP)) {
      while ($row = $res->fetch_assoc()) { $products_list[] = $row; }
      $res->free();
    }

    // Variants query ascending by ID with optional LIKE filter on product name/color/size
    if ($v_search !== '') {
      $likeV = '%' . $conn->real_escape_string($v_search) . '%';
      $sqlV = "SELECT v.id, v.product_id, p.name AS product_name, v.color, v.size, v.stock, v.price, v.image
               FROM product_variants v INNER JOIN products p ON p.id = v.product_id
               WHERE p.name LIKE '$likeV' OR v.color LIKE '$likeV' OR v.size LIKE '$likeV'
               ORDER BY v.id ASC LIMIT 1000";
    } else {
      $sqlV = 'SELECT v.id, v.product_id, p.name AS product_name, v.color, v.size, v.stock, v.price, v.image
               FROM product_variants v INNER JOIN products p ON p.id = v.product_id
               ORDER BY v.id ASC LIMIT 1000';
    }
    if ($resV = $conn->query($sqlV)) {
      while ($row = $resV->fetch_assoc()) { $variants_list[] = $row; }
      $resV->free();
    }
  }

  // Determine default active tab (show Edit when searching or after updates)
  $active_tab = 'add';
  if ($p_search !== '' || $v_search !== '' || (isset($flash['type']) && in_array($flash['type'], ['success','error']))) {
    $active_tab = 'edit';
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
    <h1>Products</h1>
    <nav class="admin-nav" aria-label="Admin navigation">
      <a href="dashboard.php">Dashboard</a>
      <a href="employees.php">Employees</a>
      <a href="users.php">Users</a>
      <a href="products.php" class="active">Products</a>
      <a href="orders.php">Orders</a>
      <a href="inventory.php">Inventory</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <!-- Quick Actions -->
  <section class="quick-actions">
    <button class="btn primary" data-tab="add">Add Product</button>
    <button class="btn primary" data-tab="edit">Edit Products</button>
  </section>

  <!-- Add Product Tab -->
  <section class="tab-content" id="tab-add" style="<?php echo $active_tab==='add' ? '' : 'display:none;'; ?>">
    <div class="card">
      <div class="card-head"><strong>Add Product</strong></div>
      <div class="card-body">
        <?php if (!empty($flash)) { $isErr = $flash['type'] === 'error'; ?>
          <div style="margin-bottom:10px; padding:10px 12px; border:1px solid <?= $isErr ? '#7f1d1d' : '#065f46' ?>; background: <?= $isErr ? '#3f1d1d' : '#064e3b' ?>; color:#fff; border-radius:10px; font-size:14px;">
            <?= htmlspecialchars($flash['msg']); ?>
          </div>
        <?php } ?>
        <form method="post" action="" enctype="multipart/form-data">
          <input type="hidden" name="form_type" value="add_product" />
          <input type="text" name="name" placeholder="Product Name" required />
          <select name="category" required>
            <option value="">Category</option>
            <?php foreach ($categories_for_select as $cat) { ?>
              <option value="<?= htmlspecialchars($cat['name']); ?>"><?= htmlspecialchars($cat['name']); ?></option>
            <?php } ?>
          </select>
          <select name="gender" required>
            <option value="">Gender</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
          </select>
          <input type="number" name="base_price" placeholder="Price" step="0.01" min="0" required />
          <textarea name="description" rows="3" placeholder="Description (optional)"></textarea>
          <input type="file" name="image" accept="image/*" />
          <button type="submit" class="btn primary">Save Product</button>
        </form>
      </div>
    </div>
    <div class="card">
      <div class="card-head"><strong>Add Product Variant</strong></div>
      <div class="card-body">
        <form method="post" action="" enctype="multipart/form-data">
          <input type="hidden" name="form_type" value="add_variant" />
          <select name="product_id" required>
            <option value="">Select Product</option>
            <?php foreach ($products_for_select as $p) { ?>
              <option value="<?= (int)$p['id']; ?>"><?= htmlspecialchars($p['name']); ?></option>
            <?php } ?>
          </select>
          <input type="text" name="color" placeholder="Color" required />
          <select name="size" required>
            <option value="">Size</option>
            <option value="XS">XS</option>
            <option value="S">S</option>
            <option value="M">M</option>
            <option value="L">L</option>
            <option value="XL">XL</option>
            <option value="XXL">XXL</option>
          </select>
          <input type="number" name="stock" placeholder="Stock" min="0" required />
          <input type="number" name="price" placeholder="Variant Price (optional)" step="0.01" min="0" />
          <input type="file" name="variant_image" accept="image/*" />
          <button type="submit" class="btn primary">Add Variant</button>
        </form>
      </div>
    </div>
  </section>

  <!-- Edit Products Tab -->
  <section class="tab-content" id="tab-edit" style="<?php echo $active_tab==='edit' ? '' : 'display:none;'; ?>">
    <div class="card">
      <div class="card-head"><strong>Edit Products</strong></div>
      <div class="card-body">
        <h3 style="margin:0 0 10px;">Products</h3>
        <div style="margin:0 0 12px; display:flex; gap:8px; flex-wrap:wrap;">
          <input type="text" id="filter-product-name" placeholder="Filter by Name" style="flex:1; min-width:180px;" />
          <select id="filter-product-category" style="min-width:160px;">
            <option value="">All Categories</option>
            <?php foreach ($categories_for_select as $cat) { $cname = $cat['name']; ?>
              <option value="<?= htmlspecialchars(strtolower($cname)); ?>"><?= htmlspecialchars($cname); ?></option>
            <?php } ?>
          </select>
          <select id="filter-product-gender" style="min-width:140px;">
            <option value="">All Genders</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
          </select>
          <button class="btn" type="button" id="filter-product-clear">Clear</button>
        </div>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Category</th>
              <th>Gender</th>
              <th>Base Price</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="products-tbody">
            <?php if (empty($products_list)) { ?>
              <tr><td colspan="7">No products found.</td></tr>
            <?php } else { foreach ($products_list as $pr) { ?>
              <tr data-name="<?= htmlspecialchars(strtolower($pr['name'])); ?>" data-category="<?= htmlspecialchars(strtolower($pr['category'])); ?>" data-gender="<?= htmlspecialchars(strtolower($pr['gender'])); ?>">
                <form method="post" action="">
                  <input type="hidden" name="form_type" value="update_product" />
                  <input type="hidden" name="id" value="<?= (int)$pr['id']; ?>" />
                  <td><?= (int)$pr['id']; ?></td>
                  <td><input type="text" name="name" value="<?= htmlspecialchars($pr['name']); ?>" /></td>
                  <td>
                    <select name="category">
                      <?php foreach ($categories_for_select as $cat) { $c = $cat['name']; $sel = ($pr['category']===$c)?'selected':''; ?>
                        <option value="<?= htmlspecialchars($c); ?>" <?= $sel; ?>><?= htmlspecialchars($c); ?></option>
                      <?php } ?>
                    </select>
                  </td>
                  <td>
                    <select name="gender">
                      <option value="male" <?= $pr['gender']==='male'?'selected':''; ?>>Male</option>
                      <option value="female" <?= $pr['gender']==='female'?'selected':''; ?>>Female</option>
                    </select>
                  </td>
                  <td><input type="number" step="0.01" min="0" name="base_price" value="<?= htmlspecialchars($pr['base_price']); ?>" /></td>
                  <td><?= htmlspecialchars($pr['created_at']); ?></td>
                  <td>
                    <div style="display:flex; gap:6px;">
                      <button class="btn primary" type="submit">Save</button>
                      </form>
                      <form method="post" action="" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                        <input type="hidden" name="form_type" value="delete_product" />
                        <input type="hidden" name="id" value="<?= (int)$pr['id']; ?>" />
                        <button class="btn" type="submit" style="border-color:#7f1d1d; background:#3f1d1d; color:#fecaca;">Delete</button>
                      </form>
                    </div>
                  </td>
              </tr>
            <?php } } ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card">
      <div class="card-head"><strong>Product Variants</strong></div>
      <div class="card-body">
        <div style="margin:0 0 12px; display:flex; gap:8px; flex-wrap:wrap;">
          <input type="text" id="filter-variant-product" placeholder="Filter by Product" style="flex:1; min-width:180px;" />
          <input type="text" id="filter-variant-color" placeholder="Filter by Color" style="flex:1; min-width:160px;" />
          <select id="filter-variant-size" style="min-width:140px;">
            <option value="">All Sizes</option>
            <?php $sizes=['XS','S','M','L','XL','XXL']; foreach ($sizes as $s) { ?>
              <option value="<?= strtolower($s); ?>"><?= $s; ?></option>
            <?php } ?>
          </select>
          <button class="btn" type="button" id="filter-variant-clear">Clear</button>
        </div>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Product</th>
              <th>Color</th>
              <th>Size</th>
              <th>Stock</th>
              <th>Price</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="variants-tbody">
            <?php if (empty($variants_list)) { ?>
              <tr><td colspan="7">No variants found.</td></tr>
            <?php } else { foreach ($variants_list as $vr) { ?>
              <tr data-product="<?= htmlspecialchars(strtolower($vr['product_name'])); ?>" data-color="<?= htmlspecialchars(strtolower($vr['color'])); ?>" data-size="<?= htmlspecialchars(strtolower($vr['size'])); ?>">
                <form method="post" action="">
                  <input type="hidden" name="form_type" value="update_variant" />
                  <input type="hidden" name="id" value="<?= (int)$vr['id']; ?>" />
                  <td><?= (int)$vr['id']; ?></td>
                  <td>
                    <select name="product_id">
                      <?php foreach ($products_for_select as $p) { $sel = ($p['id']==$vr['product_id'])?'selected':''; ?>
                        <option value="<?= (int)$p['id']; ?>" <?= $sel; ?>><?= htmlspecialchars($p['name']); ?></option>
                      <?php } ?>
                    </select>
                  </td>
                  <td><input type="text" name="color" value="<?= htmlspecialchars($vr['color']); ?>" /></td>
                  <td>
                    <select name="size">
                      <?php $sizes=['XS','S','M','L','XL','XXL']; foreach ($sizes as $s) { $sel = ($vr['size']===$s)?'selected':''; ?>
                        <option value="<?= $s; ?>" <?= $sel; ?>><?= $s; ?></option>
                      <?php } ?>
                    </select>
                  </td>
                  <td><input type="number" min="0" name="stock" value="<?= (int)$vr['stock']; ?>" /></td>
                  <td><input type="number" step="0.01" min="0" name="price" value="<?= htmlspecialchars((string)$vr['price']); ?>" /></td>
                  <td>
                    <div style="display:flex; gap:6px;">
                      <button class="btn primary" type="submit">Save</button>
                      </form>
                      <form method="post" action="" onsubmit="return confirm('Delete this variant? This cannot be undone.');">
                        <input type="hidden" name="form_type" value="delete_variant" />
                        <input type="hidden" name="id" value="<?= (int)$vr['id']; ?>" />
                        <button class="btn" type="submit" style="border-color:#7f1d1d; background:#3f1d1d; color:#fecaca;">Delete</button>
                      </form>
                    </div>
                  </td>
              </form>
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
  </script>
  <script>
  // Client-side filtering for products
  const fpName = document.getElementById('filter-product-name');
  const fpCategory = document.getElementById('filter-product-category');
  const fpGender = document.getElementById('filter-product-gender');
  const fpClear = document.getElementById('filter-product-clear');
  function filterProducts() {
    const n = (fpName?.value || '').toLowerCase().trim();
    const c = (fpCategory?.value || '').toLowerCase();
    const g = (fpGender?.value || '').toLowerCase();
    const rows = document.querySelectorAll('#products-tbody tr');
    let visible = 0;
    rows.forEach(row => {
      if (!row.hasAttribute('data-name')) return;
      const rn = row.getAttribute('data-name') || '';
      const rc = row.getAttribute('data-category') || '';
      const rg = row.getAttribute('data-gender') || '';
      const match = (n === '' || rn.includes(n)) && (c === '' || rc === c) && (g === '' || rg === g);
      row.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    const tbody = document.getElementById('products-tbody');
    let noRow = tbody.querySelector('.no-results-row');
    if (visible === 0 && rows.length > 0) {
      if (!noRow) {
        noRow = document.createElement('tr');
        noRow.className = 'no-results-row';
        noRow.innerHTML = '<td colspan="7" style="text-align:center; padding:20px;">No products match your filters.</td>';
        tbody.appendChild(noRow);
      }
      noRow.style.display = '';
    } else if (noRow) {
      noRow.style.display = 'none';
    }
  }
  if (fpName && fpCategory && fpGender) {
    fpName.addEventListener('input', filterProducts);
    fpCategory.addEventListener('change', filterProducts);
    fpGender.addEventListener('change', filterProducts);
  }
  if (fpClear) {
    fpClear.addEventListener('click', () => {
      if (fpName) fpName.value = '';
      if (fpCategory) fpCategory.value = '';
      if (fpGender) fpGender.value = '';
      filterProducts();
    });
  }

  // Client-side filtering for variants
  const fvProduct = document.getElementById('filter-variant-product');
  const fvColor = document.getElementById('filter-variant-color');
  const fvSize = document.getElementById('filter-variant-size');
  const fvClear = document.getElementById('filter-variant-clear');
  function filterVariants() {
    const p = (fvProduct?.value || '').toLowerCase().trim();
    const c = (fvColor?.value || '').toLowerCase().trim();
    const s = (fvSize?.value || '').toLowerCase();
    const rows = document.querySelectorAll('#variants-tbody tr');
    let visible = 0;
    rows.forEach(row => {
      if (!row.hasAttribute('data-product')) return;
      const rp = row.getAttribute('data-product') || '';
      const rc = row.getAttribute('data-color') || '';
      const rs = row.getAttribute('data-size') || '';
      const match = (p === '' || rp.includes(p)) && (c === '' || rc.includes(c)) && (s === '' || rs === s);
      row.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    const tbody = document.getElementById('variants-tbody');
    let noRow = tbody.querySelector('.no-results-row');
    if (visible === 0 && rows.length > 0) {
      if (!noRow) {
        noRow = document.createElement('tr');
        noRow.className = 'no-results-row';
        noRow.innerHTML = '<td colspan="7" style="text-align:center; padding:20px;">No variants match your filters.</td>';
        tbody.appendChild(noRow);
      }
      noRow.style.display = '';
    } else if (noRow) {
      noRow.style.display = 'none';
    }
  }
  if (fvProduct && fvColor && fvSize) {
    fvProduct.addEventListener('input', filterVariants);
    fvColor.addEventListener('input', filterVariants);
    fvSize.addEventListener('change', filterVariants);
  }
  if (fvClear) {
    fvClear.addEventListener('click', () => {
      if (fvProduct) fvProduct.value = '';
      if (fvColor) fvColor.value = '';
      if (fvSize) fvSize.value = '';
      filterVariants();
    });
  }
  </script>

  </body>
</html>
