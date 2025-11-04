<!-- manage admin settings, update company information, manage categories --><?php
  if (session_status() === PHP_SESSION_NONE) { session_start(); }
  require_once __DIR__ . '/../includes/db_connect.php';
  $page_title = 'Admin • Settings';

  // Flash message holder
  $flash = null;

  // Ensure company_settings table exists
  if ($conn instanceof mysqli) {
    $conn->query("CREATE TABLE IF NOT EXISTS company_settings (
      id INT PRIMARY KEY,
      company_name VARCHAR(150) NOT NULL,
      address TEXT,
      contact_email VARCHAR(150),
      phone VARCHAR(50)
    )");
  }

  // Handle Categories: update existing category
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'update_category' && ($conn instanceof mysqli)) {
    $cid = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($cid <= 0 || $name === '') {
      $flash = ['type' => 'error', 'msg' => 'Valid ID and name are required.'];
    } else {
      if ($slug === '') { $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)); }
      if ($stmt = $conn->prepare('UPDATE categories SET name=?, slug=? WHERE id=?')) {
        $stmt->bind_param('ssi', $name, $slug, $cid);
        if ($stmt->execute()) { $flash = ['type' => 'success', 'msg' => 'Category updated.']; }
        else { $flash = ['type' => 'error', 'msg' => 'Failed to update: ' . $stmt->error]; }
        $stmt->close();
      } else {
        $flash = ['type' => 'error', 'msg' => 'Failed to prepare update.'];
      }
    }
  }

  // Handle Categories: delete category
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'delete_category' && ($conn instanceof mysqli)) {
    $cid = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($cid <= 0) {
      $flash = ['type' => 'error', 'msg' => 'Invalid category ID.'];
    } else {
      if ($stmt = $conn->prepare('DELETE FROM categories WHERE id=?')) {
        $stmt->bind_param('i', $cid);
        if ($stmt->execute()) { $flash = ['type' => 'success', 'msg' => 'Category deleted.']; }
        else { $flash = ['type' => 'error', 'msg' => 'Failed to delete: ' . $stmt->error]; }
        $stmt->close();
      } else {
        $flash = ['type' => 'error', 'msg' => 'Failed to prepare delete.'];
      }
    }
  }
  
  // Handle Categories: add new category
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'add_category' && ($conn instanceof mysqli)) {
    $newCat = trim($_POST['new_category'] ?? '');
    if ($newCat === '') {
      $flash = ['type' => 'error', 'msg' => 'Category name is required.'];
    } else {
      // Ensure categories table exists (as per schema)
      // Create with the exact schema specified (no AUTO_INCREMENT)
      $conn->query("CREATE TABLE IF NOT EXISTS categories (
        id INT(11) NOT NULL,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(120) DEFAULT NULL,
        parent_id INT(11) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
      )");
      // Simple slug
      $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($newCat)));
      if ($slug === '-') { $slug = null; }
      // First try insert without specifying id (works when id is AUTO_INCREMENT)
      $ok = false; $err = '';
      if ($stmt = $conn->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)')) {
        $stmt->bind_param('ss', $newCat, $slug);
        if ($stmt->execute()) { $ok = true; }
        else { $err = $stmt->error; }
        $stmt->close();
      } else { $err = 'prepare failed'; }
      // If failed due to NO DEFAULT for id, fallback to compute next id
      if (!$ok) {
        $nextId = null;
        if ($res = $conn->query('SELECT COALESCE(MAX(id),0)+1 AS next_id FROM categories')) {
          if ($row = $res->fetch_assoc()) { $nextId = (int)$row['next_id']; }
          $res->free();
        }
        if ($nextId !== null && ($stmt2 = $conn->prepare('INSERT INTO categories (id, name, slug) VALUES (?,?,?)'))) {
          $stmt2->bind_param('iss', $nextId, $newCat, $slug);
          if ($stmt2->execute()) { $ok = true; }
          else { $err = $stmt2->error; }
          $stmt2->close();
        }
      }
      $flash = $ok ? ['type' => 'success', 'msg' => 'Category added.'] : ['type' => 'error', 'msg' => 'Failed to add category: ' . $err];
    }
  }

  // Load categories for Manage Categories UI
  $categories_list = [];
  if ($conn instanceof mysqli) {
    if ($res = $conn->query('SELECT id, name, slug, created_at FROM categories ORDER BY name ASC')) {
      while ($row = $res->fetch_assoc()) { $categories_list[] = $row; }
      $res->free();
    }
  }

  // Ensure site_assets table exists and load current banner
  $assets = [ 'banner_path' => '' ];
  if ($conn instanceof mysqli) {
    $conn->query("CREATE TABLE IF NOT EXISTS site_assets (
      id INT PRIMARY KEY,
      banner_path VARCHAR(255)
    )");
    if ($res = $conn->query('SELECT banner_path FROM site_assets WHERE id=1 LIMIT 1')) {
      if ($row = $res->fetch_assoc()) { $assets = array_merge($assets, $row); }
    }
  }

  // Handle Banner Upload
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'banner_upload' && ($conn instanceof mysqli)) {
    if (!isset($_FILES['banner']) || ($_FILES['banner']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      $flash = ['type' => 'error', 'msg' => 'Please select a banner image to upload.'];
    } else {
      $file = $_FILES['banner'];
      $tmp = $file['tmp_name'];
      $size = (int)$file['size'];
      // Basic validations
      if ($size <= 0 || $size > 5 * 1024 * 1024) {
        $flash = ['type' => 'error', 'msg' => 'Image must be between 1 byte and 5MB.'];
      } else {
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        $mime = $finfo ? finfo_file($finfo, $tmp) : mime_content_type($tmp);
        if ($finfo) { finfo_close($finfo); }
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
          $flash = ['type' => 'error', 'msg' => 'Only JPG, PNG, GIF, or WEBP images are allowed.'];
        } else {
          // Prepare destination
          $ext = $allowed[$mime];
          $baseDir = realpath(__DIR__ . '/..'); // proj
          $uploadDir = $baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'banners';
          if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }
          $newName = 'banner_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
          $destPath = $uploadDir . DIRECTORY_SEPARATOR . $newName;
          if (!@move_uploaded_file($tmp, $destPath)) {
            $flash = ['type' => 'error', 'msg' => 'Failed to save uploaded image.'];
          } else {
            // Store path relative to proj root: uploads/banners/xyz.ext
            $relPath = 'uploads/banners/' . $newName;
            $stmt = $conn->prepare('INSERT INTO site_assets (id, banner_path) VALUES (1, ?) ON DUPLICATE KEY UPDATE banner_path=VALUES(banner_path)');
            $stmt->bind_param('s', $relPath);
            if ($stmt->execute()) {
              $flash = ['type' => 'success', 'msg' => 'Banner uploaded successfully.'];
              $assets['banner_path'] = $relPath;
            } else {
              $flash = ['type' => 'error', 'msg' => 'Failed to save banner path.'];
            }
            $stmt->close();
          }
        }
      }
    }
  }

  // Load current admin info (for Admin Settings tab)
  $admin = [ 'fullname' => 'Admin User', 'email' => 'admin@ecom.com', 'address' => '' ];
  $adminId = $_SESSION['admin_id'] ?? null;
  // Flexible session email fallback across possible keys
  $adminEmailSess = '';
  if (!empty($_SESSION['admin_email'])) {
    $adminEmailSess = $_SESSION['admin_email'];
  } elseif (!empty($_SESSION['email'])) {
    $adminEmailSess = $_SESSION['email'];
  } elseif (!empty($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['email'])) {
    $adminEmailSess = $_SESSION['user']['email'];
  } elseif (!empty($_SESSION['admin']) && is_array($_SESSION['admin']) && !empty($_SESSION['admin']['email'])) {
    $adminEmailSess = $_SESSION['admin']['email'];
  }
  $loaded = false;

  // Ensure the logged-in user's role is admin in the users table
  if ($conn instanceof mysqli) {
    $roleOk = false;
    if ($adminId) {
      if ($stmt = $conn->prepare('SELECT role FROM users WHERE id=? LIMIT 1')) {
        $stmt->bind_param('i', $adminId);
        if ($stmt->execute()) {
          $res = $stmt->get_result();
          if ($r = $res->fetch_assoc()) { $roleOk = (strcasecmp($r['role'] ?? '', 'admin') === 0); }
        }
        $stmt->close();
      }
    } elseif ($adminEmailSess !== '') {
      if ($stmt = $conn->prepare('SELECT role, id FROM users WHERE email=? LIMIT 1')) {
        $stmt->bind_param('s', $adminEmailSess);
        if ($stmt->execute()) {
          $res = $stmt->get_result();
          if ($r = $res->fetch_assoc()) {
            $roleOk = (strcasecmp($r['role'] ?? '', 'admin') === 0);
            if ($roleOk && !$adminId && !empty($r['id'])) { $adminId = (int)$r['id']; }
          }
        }
        $stmt->close();
      }
    }
    if (!$roleOk) {
      // Not authorized for admin settings
      header('Location: logina.php');
      exit;
    }
  }
  if ($conn instanceof mysqli) {
    // 1) Try by id (no role restriction to avoid mismatched string values)
    if ($adminId) {
      $stmt = $conn->prepare('SELECT fullname, username, email, address FROM users WHERE id=? LIMIT 1');
      if ($stmt) {
        $stmt->bind_param('i', $adminId);
        if ($stmt->execute()) {
          $res = $stmt->get_result();
          if ($row = $res->fetch_assoc()) {
            $admin['fullname'] = ($row['fullname'] ?: $row['username'] ?: ($_SESSION['admin_name'] ?? $admin['fullname']));
            $admin['email'] = ($row['email'] ?: $adminEmailSess ?: $admin['email']);
            $admin['address'] = $row['address'] ?? '';
            $loaded = true;
          }
        }
        $stmt->close();
      }
    }
    // 2) Try by session email if not loaded yet
    if (!$loaded && $adminEmailSess !== '') {
      $stmt = $conn->prepare('SELECT fullname, username, email, address FROM users WHERE email=? LIMIT 1');
      if ($stmt) {
        $stmt->bind_param('s', $adminEmailSess);
        if ($stmt->execute()) {
          $res = $stmt->get_result();
          if ($row = $res->fetch_assoc()) {
            $admin['fullname'] = ($row['fullname'] ?: $row['username'] ?: ($_SESSION['admin_name'] ?? $admin['fullname']));
            $admin['email'] = ($row['email'] ?: $adminEmailSess);
            $admin['address'] = $row['address'] ?? '';
            $loaded = true;
          }
        }
        $stmt->close();
      }
    }
  }
  // 3) Fallback to session snapshot
  if (!$loaded) {
    if (!empty($_SESSION['admin_name'])) $admin['fullname'] = $_SESSION['admin_name'];
    if (!empty($_SESSION['admin_email'])) $admin['email'] = $_SESSION['admin_email'];
  }

  // Handle Admin Settings update
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'admin_settings' && ($conn instanceof mysqli)) {
    if (!$adminId) {
      $flash = ['type' => 'error', 'msg' => 'Not authorized.'];
    } else {
      $newName = trim($_POST['admin_name'] ?? '');
      $newEmail = trim($_POST['admin_email'] ?? '');
      $newPass = $_POST['admin_pass'] ?? '';
      $currentPass = $_POST['admin_current'] ?? '';
      if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $flash = ['type' => 'error', 'msg' => 'Please provide a valid email.'];
      } else {
        if ($newPass !== '' && strlen($newPass) < 6) {
          $flash = ['type' => 'error', 'msg' => 'Password must be at least 6 characters.'];
        } else {
          if ($newPass !== '') {
            // Require current password verification before allowing change
            $check = $conn->prepare('SELECT upassword FROM users WHERE id=? AND role="admin" LIMIT 1');
            $check->bind_param('i', $adminId);
            $validCurrent = false;
            if ($check && $check->execute()) {
              $res = $check->get_result();
              if ($row = $res->fetch_assoc()) {
                $stored = (string)$row['upassword'];
                if (strlen($stored) >= 20 && password_verify($currentPass, $stored)) {
                  $validCurrent = true;
                } elseif (hash('sha256', $currentPass) === $stored) {
                  $validCurrent = true;
                } elseif ($currentPass === $stored) {
                  $validCurrent = true;
                }
              }
            }
            if ($check) { $check->close(); }
            if (!$validCurrent) {
              $flash = ['type' => 'error', 'msg' => 'Current password is incorrect.'];
              goto after_admin_update;
            }
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare('UPDATE users SET fullname=?, email=?, upassword=? WHERE id=? AND role="admin"');
            $stmt->bind_param('sssi', $newName, $newEmail, $hash, $adminId);
          } else {
            $stmt = $conn->prepare('UPDATE users SET fullname=?, email=? WHERE id=? AND role="admin"');
            $stmt->bind_param('ssi', $newName, $newEmail, $adminId);
          }
          if ($stmt && $stmt->execute()) {
            $flash = ['type' => 'success', 'msg' => 'Admin settings updated.'];
            $admin['fullname'] = $newName;
            $admin['email'] = $newEmail;
            $_SESSION['admin_name'] = $newName;
            $_SESSION['admin_email'] = $newEmail;
          } else {
            $flash = ['type' => 'error', 'msg' => 'Failed to update admin settings.'];
          }
          if ($stmt) $stmt->close();
        }
      }
    }
    after_admin_update:
  }

  // Ensure support_settings table exists and load defaults
  $support = [
    'faqs_json' => json_encode([
      ['q' => 'What sizes do you offer?', 'a' => 'We offer sizes XS–XXL for most items. Size guides are available on each product page.'],
      ['q' => 'How long will my order take?', 'a' => 'Orders are processed in 1–2 business days. Addis Ababa deliveries typically arrive in 1–3 days, other cities 2–5 days.'],
      ['q' => 'Can I change or cancel my order?', 'a' => 'If your order hasn’t shipped, contact us ASAP and we’ll do our best to update or cancel it.'],
    ]),
    'shipping_points_json' => json_encode([
      'Addis Ababa: 1–3 business days.',
      'Adama and other cities: 2–5 business days.',
      'Free shipping on orders over $100.'
    ]),
    'returns_points_json' => json_encode([
      '30-day return window for unworn items with tags.',
      'Easy exchanges for size/color within 30 days.',
      'Contact support to initiate a return.'
    ]),
  ];
  if ($conn instanceof mysqli) {
    $conn->query("CREATE TABLE IF NOT EXISTS support_settings (
      id INT PRIMARY KEY,
      faqs_json JSON,
      shipping_points_json JSON,
      returns_points_json JSON
    )");
    if ($res = $conn->query('SELECT faqs_json, shipping_points_json, returns_points_json FROM support_settings WHERE id=1 LIMIT 1')) {
      if ($row = $res->fetch_assoc()) {
        $support = array_merge($support, $row);
      }
    }
  }

  // Handle Support (FAQs & Shipping/Returns) update
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'support_settings' && ($conn instanceof mysqli)) {
    $faqs_json = $_POST['faqs_json'] ?? '[]';
    $shipping_points_json = $_POST['shipping_points_json'] ?? '[]';
    $returns_points_json = $_POST['returns_points_json'] ?? '[]';
    // validate JSON shapes
    $f = json_decode($faqs_json, true); if (!is_array($f)) $faqs_json = '[]';
    $s = json_decode($shipping_points_json, true); if (!is_array($s)) $shipping_points_json = '[]';
    $r = json_decode($returns_points_json, true); if (!is_array($r)) $returns_points_json = '[]';
    $stmt = $conn->prepare('INSERT INTO support_settings (id, faqs_json, shipping_points_json, returns_points_json) VALUES (1,?,?,?)
      ON DUPLICATE KEY UPDATE faqs_json=VALUES(faqs_json), shipping_points_json=VALUES(shipping_points_json), returns_points_json=VALUES(returns_points_json)');
    $stmt->bind_param('sss', $faqs_json, $shipping_points_json, $returns_points_json);
    if ($stmt->execute()) {
      $flash = $flash ?? ['type' => 'success', 'msg' => 'Saved'];
      $support['faqs_json'] = $faqs_json;
      $support['shipping_points_json'] = $shipping_points_json;
      $support['returns_points_json'] = $returns_points_json;
    } else {
      $flash = ['type' => 'error', 'msg' => 'Failed to save FAQs & Shipping/Returns.'];
    }
    $stmt->close();
  }

  // Load existing company settings (single-row table with id=1)
  $company = [
    'company_name' => 'Ecom Clothing',
    'address' => '123 Main Street, City',
    'contact_email' => 'info@ecom.com',
    'phone' => '+1234567890',
  ];
  if ($conn instanceof mysqli) {
    if ($res = $conn->query('SELECT company_name, address, contact_email, phone FROM company_settings WHERE id=1 LIMIT 1')) {
      if ($row = $res->fetch_assoc()) {
        $company = array_merge($company, $row);
      }
    }
  }

  // Ensure about_settings table exists
  $about = [
    'our_company' => 'We are a customer-first apparel brand crafting modern essentials with quality, comfort, and sustainability in mind.',
    'our_history' => 'Founded in 2020 by a small team of designers and engineers, we set out to remove friction between people and great clothing.',
    'our_mission' => 'Empower customers to look and feel their best with thoughtfully designed essentials—delivered with exceptional service and honest pricing.',
    'our_vision'  => 'To be the most trusted everyday apparel brand in Africa and beyond, known for quality, sustainability, and a remarkable shopping experience.',
    'values_json' => json_encode([
      ['title' => 'Customer Obsession', 'text' => 'We design, build, and improve with your feedback at the center.'],
      ['title' => 'Quality', 'text' => 'Materials and craftsmanship that stand up to real life—wash after wash.'],
      ['title' => 'Transparency', 'text' => 'Clear pricing, clear communication, clear policies.'],
      ['title' => 'Sustainability', 'text' => 'Responsible sourcing and packaging with an eye on long-term impact.'],
      ['title' => 'Inclusivity', 'text' => 'Styles and sizes for everyone, because great clothing is for all.'],
    ]),
  ];
  if ($conn instanceof mysqli) {
    $conn->query("CREATE TABLE IF NOT EXISTS about_settings (
      id INT PRIMARY KEY,
      our_company TEXT,
      our_history TEXT,
      our_mission TEXT,
      our_vision TEXT,
      values_json JSON
    )");
    if ($res = $conn->query('SELECT our_company, our_history, our_mission, our_vision, values_json FROM about_settings WHERE id=1 LIMIT 1')) {
      if ($row = $res->fetch_assoc()) {
        $about = array_merge($about, $row);
      }
    }
  }

  // Handle About Description update
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'company_desc' && ($conn instanceof mysqli)) {
    $our_company = trim($_POST['our_company'] ?? '');
    $our_history = trim($_POST['our_history'] ?? '');
    $our_mission = trim($_POST['our_mission'] ?? '');
    $our_vision  = trim($_POST['our_vision'] ?? '');
    $values_json = $_POST['values_json'] ?? '[]';
    // Sanity check JSON
    $decoded = json_decode($values_json, true);
    if (!is_array($decoded)) { $values_json = '[]'; }
    $stmt = $conn->prepare('INSERT INTO about_settings (id, our_company, our_history, our_mission, our_vision, values_json) VALUES (1,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE our_company=VALUES(our_company), our_history=VALUES(our_history), our_mission=VALUES(our_mission), our_vision=VALUES(our_vision), values_json=VALUES(values_json)');
    $stmt->bind_param('sssss', $our_company, $our_history, $our_mission, $our_vision, $values_json);
    if ($stmt->execute()) {
      $flash = $flash ?? ['type' => 'success', 'msg' => 'Saved'];
      $about['our_company'] = $our_company;
      $about['our_history'] = $our_history;
      $about['our_mission'] = $our_mission;
      $about['our_vision']  = $our_vision;
      $about['values_json'] = $values_json;
    } else {
      $flash = ['type' => 'error', 'msg' => 'Failed to save company description.'];
    }
    $stmt->close();
  }

  // Handle Company Info update
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'company_info' && ($conn instanceof mysqli)) {
    $name = trim($_POST['company_name'] ?? '');
    $addr = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($name === '' || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
      $flash = ['type' => 'error', 'msg' => 'Please provide a valid company name and email.'];
    } else {
      // Upsert row id=1
      $stmt = $conn->prepare('INSERT INTO company_settings (id, company_name, address, contact_email, phone) VALUES (1,?,?,?,?)
        ON DUPLICATE KEY UPDATE company_name=VALUES(company_name), address=VALUES(address), contact_email=VALUES(contact_email), phone=VALUES(phone)');
      $stmt->bind_param('ssss', $name, $addr, $email, $phone);
      if ($stmt->execute()) {
        $flash = ['type' => 'success', 'msg' => 'Company info saved.'];
        $company['company_name'] = $name;
        $company['address'] = $addr;
        $company['contact_email'] = $email;
        $company['phone'] = $phone;
      } else {
        $flash = ['type' => 'error', 'msg' => 'Failed to save company info.'];
      }
      $stmt->close();
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
    <h1>Settings</h1>
    <nav class="admin-nav" aria-label="Admin navigation">
      <a href="dashboard.php">Dashboard</a>
      <a href="employees.php">Employees</a>
      <a href="users.php">Users</a>
      <a href="products.php">Products</a>
      <a href="orders.php">Orders</a>
      <a href="inventory.php">Inventory</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php" class="active">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <!-- Quick Actions: stays fixed under the header -->
  <section class="quick-actions main-quick-actions">
    <button class="btn primary" data-tab="company">Company Info</button>
    <button class="btn primary" data-tab="categories">Manage Categories</button>
    <button class="btn primary" data-tab="company-description">Company Description</button>
    <button class="btn primary" data-tab="support">FAQs & Shipping/Returns</button>
    <button class="btn primary" data-tab="banner">Upload Banner</button>
    <button class="btn primary" data-tab="admin">Admin Settings</button>
  </section>

  <!-- Upload Banner -->
  <section class="tab-content" id="tab-banner">
    <div class="card">
      <div class="card-head"><strong>Upload Banner</strong></div>
      <div class="card-body">
        <?php if (!empty($flash)) { $isErr = $flash['type'] === 'error'; ?>
          <div class="flash-msg" style="margin-bottom:10px; padding:10px 12px; border:1px solid <?= $isErr ? '#7f1d1d' : '#065f46' ?>; background: <?= $isErr ? '#3f1d1d' : '#064e3b' ?>; color:#fff; border-radius:10px; font-size:14px;">
            <?= htmlspecialchars($flash['msg']); ?>
          </div>
        <?php } ?>
        <?php $bannerRel = $assets['banner_path'] ?? ''; $bannerUrl = $bannerRel ? ('../' . ltrim($bannerRel, '/')) : ''; ?>
        <?php if ($bannerRel) { ?>
          <div style="margin-bottom:12px;">
            <div style="font-size:12px; color:#9ca3af; margin-bottom:6px;">Current Banner</div>
            <img src="<?= htmlspecialchars($bannerUrl) ?>" alt="Current banner" style="max-width:100%; border-radius:10px; border:1px solid var(--border);" />
          </div>
        <?php } ?>
        <form id="bannerForm" method="post" action="" enctype="multipart/form-data">
          <input type="hidden" name="form_type" value="banner_upload" />
          <label for="banner">Choose image (JPG, PNG, GIF, WEBP, max 5MB)</label>
          <input type="file" id="banner" name="banner" accept="image/*" required />
          <button type="submit" class="btn primary">Upload</button>
        </form>
      </div>
    </div>
  </section>

  <!-- FAQs & Shipping/Returns -->
  <section class="tab-content" id="tab-support">
    <div class="card">
      <div class="card-head"><strong>FAQs & Shipping/Returns</strong></div>
      <div class="card-body">
        <?php if (!empty($flash)) { $isErr = $flash['type'] === 'error'; ?>
          <div class="flash-msg" style="margin-bottom:10px; padding:10px 12px; border:1px solid <?= $isErr ? '#7f1d1d' : '#065f46' ?>; background: <?= $isErr ? '#3f1d1d' : '#064e3b' ?>; color:#fff; border-radius:10px; font-size:14px;">
            <?= htmlspecialchars($flash['msg']); ?>
          </div>
        <?php } ?>
        <?php $faqsArr = json_decode($support['faqs_json'] ?? '[]', true) ?: []; ?>
        <?php $shipArr = json_decode($support['shipping_points_json'] ?? '[]', true) ?: []; ?>
        <?php $retArr = json_decode($support['returns_points_json'] ?? '[]', true) ?: []; ?>
        <form id="supportForm" method="post" action="">
          <input type="hidden" name="form_type" value="support_settings" />

          <h3 style="color:#e5e7eb; margin:8px 0">FAQs</h3>
          <div id="faqsList">
            <?php foreach ($faqsArr as $f): ?>
              <div class="faq-item-edit" style="border:1px solid var(--border); border-radius:10px; padding:10px; margin-bottom:8px;">
                <input type="text" class="faq-q-input" placeholder="Question" value="<?= htmlspecialchars($f['q'] ?? '') ?>">
                <textarea class="faq-a-input" rows="2" placeholder="Answer" style="margin-top:6px;"><?= htmlspecialchars($f['a'] ?? '') ?></textarea>
                <button type="button" class="btn" data-action="remove-faq" style="margin-top:6px">Remove</button>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="quick-actions"><button type="button" class="btn" id="addFaqBtn">Add FAQ</button></div>

          <h3 style="color:#e5e7eb; margin:8px 0">Shipping Points</h3>
          <div id="shipList">
            <?php foreach ($shipArr as $p): ?>
              <div class="ship-item" style="display:grid; grid-template-columns:1fr auto; gap:8px; margin-bottom:8px;">
                <input type="text" class="ship-input" value="<?= htmlspecialchars($p) ?>">
                <button type="button" class="btn" data-action="remove-ship">Remove</button>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="quick-actions"><button type="button" class="btn" id="addShipBtn">Add Shipping Point</button></div>

          <h3 style="color:#e5e7eb; margin:8px 0">Return Points</h3>
          <div id="retList">
            <?php foreach ($retArr as $p): ?>
              <div class="ret-item" style="display:grid; grid-template-columns:1fr auto; gap:8px; margin-bottom:8px;">
                <input type="text" class="ret-input" value="<?= htmlspecialchars($p) ?>">
                <button type="button" class="btn" data-action="remove-ret">Remove</button>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="quick-actions"><button type="button" class="btn" id="addRetBtn">Add Return Point</button></div>

          <input type="hidden" name="faqs_json" id="faqsJson" />
          <input type="hidden" name="shipping_points_json" id="shipJson" />
          <input type="hidden" name="returns_points_json" id="retJson" />
          <button type="submit" class="btn primary">Save</button>
        </form>
      </div>
    </div>
  </section>

  <!-- Company Description -->
  <section class="tab-content" id="tab-company-description">
    <div class="card">
      <div class="card-head"><strong>Company Description</strong></div>
      <div class="card-body">
        <?php if (!empty($flash)) { $isErr = $flash['type'] === 'error'; ?>
          <div class="flash-msg" style="margin-bottom:10px; padding:10px 12px; border:1px solid <?= $isErr ? '#7f1d1d' : '#065f46' ?>; background: <?= $isErr ? '#3f1d1d' : '#064e3b' ?>; color:#fff; border-radius:10px; font-size:14px;">
            <?= htmlspecialchars($flash['msg']); ?>
          </div>
        <?php } ?>
        <?php $valuesArr = json_decode($about['values_json'] ?? '[]', true) ?: []; ?>
        <form id="companyDescForm" method="post" action="">
          <input type="hidden" name="form_type" value="company_desc" />
          <label>Company name (from Company Info)</label>
          <input type="text" value="<?= htmlspecialchars($company['company_name']) ?>" disabled>

          <label>Our Company</label>
          <textarea name="our_company" rows="4" placeholder="About the company"><?= htmlspecialchars($about['our_company'] ?? '') ?></textarea>

          <label>Our History</label>
          <textarea name="our_history" rows="4" placeholder="Company history"><?= htmlspecialchars($about['our_history'] ?? '') ?></textarea>

          <label>Our Mission</label>
          <textarea name="our_mission" rows="3" placeholder="Company mission"><?= htmlspecialchars($about['our_mission'] ?? '') ?></textarea>

          <label>Our Vision</label>
          <textarea name="our_vision" rows="3" placeholder="Company vision"><?= htmlspecialchars($about['our_vision'] ?? '') ?></textarea>

          <label>Our Values</label>
          <div id="valuesList">
            <?php foreach ($valuesArr as $idx => $val): ?>
              <div class="value-item" data-idx="<?= (int)$idx ?>" style="border:1px solid var(--border); border-radius:10px; padding:10px; margin-bottom:8px;">
                <div style="display:grid; grid-template-columns: 1fr auto; gap:8px; align-items:center;">
                  <input type="text" class="val-title" placeholder="Title" value="<?= htmlspecialchars($val['title'] ?? '') ?>">
                  <button type="button" class="btn" data-action="remove">Remove</button>
                </div>
                <textarea class="val-text" rows="2" placeholder="Value description" style="margin-top:6px;"><?= htmlspecialchars($val['text'] ?? '') ?></textarea>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="quick-actions">
            <button type="button" class="btn" id="addValueBtn">Add Value</button>
          </div>

          <input type="hidden" name="values_json" id="valuesJson" />
          <button type="submit" class="btn primary">Save</button>
        </form>
      </div>
    </div>
  </section>

  <!-- Tabs -->

  <!-- Company Info -->
  <section class="tab-content active" id="tab-company">
    <div class="card">
      <div class="card-head"><strong>Company Information</strong></div>
      <div class="card-body">
        <?php if (!empty($flash)) { $isErr = $flash['type'] === 'error'; ?>
          <div class="flash-msg" style="margin-bottom:10px; padding:10px 12px; border:1px solid <?= $isErr ? '#7f1d1d' : '#065f46' ?>; background: <?= $isErr ? '#3f1d1d' : '#064e3b' ?>; color:#fff; border-radius:10px; font-size:14px;">
            <?= htmlspecialchars($flash['msg']); ?>
          </div>
        <?php } ?>
        <form id="companyForm" method="post" action="">
          <input type="hidden" name="form_type" value="company_info" />
          <label>Company Name</label>
          <input type="text" name="company_name" value="<?= htmlspecialchars($company['company_name']) ?>" required>
          <label>Address</label>
          <textarea name="address"><?= htmlspecialchars($company['address']) ?></textarea>
          <label>Contact Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($company['contact_email']) ?>">
          <label>Phone</label>
          <input type="text" name="phone" value="<?= htmlspecialchars($company['phone']) ?>">
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
        <?php if (!empty($flash)) { $isErr = $flash['type'] === 'error'; ?>
          <div class="flash-msg" style="margin-bottom:10px; padding:10px 12px; border:1px solid <?= $isErr ? '#7f1d1d' : '#065f46' ?>; background: <?= $isErr ? '#3f1d1d' : '#064e3b' ?>; color:#fff; border-radius:10px; font-size:14px;">
            <?= htmlspecialchars($flash['msg']); ?>
          </div>
        <?php } ?>
        <form id="categoryForm" method="post" action="">
          <input type="hidden" name="form_type" value="add_category" />
          <label>Add New Category</label>
          <input type="text" name="new_category" placeholder="Category Name" required>
          <button type="submit" class="btn primary">Add Category</button>
        </form>
        <div style="margin-top: 12px;">
          <strong>Existing Categories:</strong>
          <?php if (empty($categories_list)) { ?>
            <div style="margin-top:8px;">No categories yet.</div>
          <?php } else { ?>
            <table style="width:100%; border-collapse: collapse; margin-top:8px;">
              <thead>
                <tr>
                  <th style="text-align:left; padding:8px; border-bottom:1px solid var(--border);">ID</th>
                  <th style="text-align:left; padding:8px; border-bottom:1px solid var(--border);">Name</th>
                  <th style="text-align:left; padding:8px; border-bottom:1px solid var(--border);">Slug</th>
                  <th style="text-align:left; padding:8px; border-bottom:1px solid var(--border);">Created</th>
                  <th style="text-align:left; padding:8px; border-bottom:1px solid var(--border);">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($categories_list as $c): ?>
                  <tr>
                    <td style="padding:8px; border-bottom:1px solid var(--border);">#<?= (int)$c['id'] ?></td>
                    <td style="padding:8px; border-bottom:1px solid var(--border);">
                      <form method="post" action="" style="display:flex; gap:8px; align-items:center;">
                        <input type="hidden" name="form_type" value="update_category" />
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>" />
                        <input type="text" name="name" value="<?= htmlspecialchars($c['name']) ?>" style="min-width:180px;" />
                    </td>
                    <td style="padding:8px; border-bottom:1px solid var(--border);">
                        <input type="text" name="slug" value="<?= htmlspecialchars($c['slug'] ?? '') ?>" style="min-width:160px;" />
                    </td>
                    <td style="padding:8px; border-bottom:1px solid var(--border);"><?= htmlspecialchars($c['created_at'] ?? '') ?></td>
                    <td style="padding:8px; border-bottom:1px solid var(--border);">
                        <button class="btn primary" type="submit">Save</button>
                      </form>
                      <form method="post" action="" onsubmit="return confirm('Delete this category?');" style="display:inline-block; margin-left:6px;">
                        <input type="hidden" name="form_type" value="delete_category" />
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>" />
                        <button class="btn" type="submit" style="border-color:#7f1d1d; background:#3f1d1d; color:#fecaca;">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php } ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Admin Settings -->
  <section class="tab-content" id="tab-admin">
    <div class="card">
      <div class="card-head"><strong>Admin Account Settings</strong></div>
      <div class="card-body">
        <?php if (!empty($flash)) { $isErr = $flash['type'] === 'error'; ?>
          <div class="flash-msg" style="margin-bottom:10px; padding:10px 12px; border:1px solid <?= $isErr ? '#7f1d1d' : '#065f46' ?>; background: <?= $isErr ? '#3f1d1d' : '#064e3b' ?>; color:#fff; border-radius:10px; font-size:14px;">
            <?= htmlspecialchars($flash['msg']); ?>
          </div>
        <?php } ?>
        <div style="border:1px solid var(--border); border-radius:12px; padding:12px; background:#0b1220; color:#cbd5e1; margin-bottom:12px;">
          <div style="font-weight:600; color:#e5e7eb; margin-bottom:8px;">Current Admin Info</div>
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
            <div>
              <div style="font-size:12px; color:#9ca3af;">Name</div>
              <div><?= htmlspecialchars($admin['fullname']) ?></div>
            </div>
            <div>
              <div style="font-size:12px; color:#9ca3af;">Email</div>
              <div><?= htmlspecialchars($admin['email']) ?></div>
            </div>
            <div style="grid-column:1 / -1;">
              <div style="font-size:12px; color:#9ca3af;">Address</div>
              <div><?= htmlspecialchars($admin['address'] ?: 'No address set') ?></div>
            </div>
          </div>
        </div>
        <form id="adminForm" method="post" action="">
          <input type="hidden" name="form_type" value="admin_settings" />
          <label>Admin Name</label>
          <input type="text" name="admin_name" value="<?= htmlspecialchars($admin['fullname']) ?>" required>
          <label>Email</label>
          <input type="email" name="admin_email" value="<?= htmlspecialchars($admin['email']) ?>" required>
          <label>Current Password (required to set a new password)</label>
          <input type="password" name="admin_current" placeholder="Current Password">
          <label>New Password</label>
          <input type="password" name="admin_pass" placeholder="New Password (min 6 chars)">
          <button type="submit" class="btn primary">Update Admin Settings</button>
        </form>
      </div>
    </div>
  </section>

</main>

<script>
// Tab switching (only from the main quick actions bar)
const tabButtons = document.querySelectorAll('.main-quick-actions .btn');
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
  // Allow real submit for these forms
  const allow = ['companyForm','companyDescForm','adminForm','bannerForm','categoryForm','supportForm'];
  if (allow.includes(form.id)) return;
  form.addEventListener('submit', e => {
    e.preventDefault();
    alert('Form submitted! (Frontend demo only)');
  });
});

// Values editor for Company Description
(function(){
  const list = document.getElementById('valuesList');
  const addBtn = document.getElementById('addValueBtn');
  const out = document.getElementById('valuesJson');
  const form = document.getElementById('companyDescForm');
  if (!list || !addBtn || !out || !form) return;

  function serialize() {
    const items = Array.from(list.querySelectorAll('.value-item')).map(item => {
      return {
        title: item.querySelector('.val-title')?.value?.trim() || '',
        text: item.querySelector('.val-text')?.value?.trim() || ''
      };
    }).filter(v => v.title !== '' || v.text !== '');
    out.value = JSON.stringify(items);
  }

  function createItem(title = '', text = '') {
    const wrap = document.createElement('div');
    wrap.className = 'value-item';
    wrap.style.cssText = 'border:1px solid var(--border); border-radius:10px; padding:10px; margin-bottom:8px;';
    wrap.innerHTML = `
      <div style="display:grid; grid-template-columns: 1fr auto; gap:8px; align-items:center;">
        <input type="text" class="val-title" placeholder="Title" value="${title.replaceAll('"','&quot;')}">
        <button type="button" class="btn" data-action="remove">Remove</button>
      </div>
      <textarea class="val-text" rows="2" placeholder="Value description" style="margin-top:6px;">${text.replaceAll('<','&lt;')}</textarea>
    `;
    list.appendChild(wrap);
  }

  addBtn.addEventListener('click', () => {
    createItem();
  });

  list.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-action="remove"]');
    if (!btn) return;
    const item = btn.closest('.value-item');
    if (item) item.remove();
  });

  form.addEventListener('submit', () => {
    serialize();
  });
})();

// Support settings (FAQs, Shipping, Returns) editor
(function(){
  const faqsList = document.getElementById('faqsList');
  const shipList = document.getElementById('shipList');
  const retList = document.getElementById('retList');
  const addFaqBtn = document.getElementById('addFaqBtn');
  const addShipBtn = document.getElementById('addShipBtn');
  const addRetBtn = document.getElementById('addRetBtn');
  const supportForm = document.getElementById('supportForm');
  const faqsJson = document.getElementById('faqsJson');
  const shipJson = document.getElementById('shipJson');
  const retJson = document.getElementById('retJson');

  if (!supportForm || !faqsJson || !shipJson || !retJson) return;

  // Serialize FAQs
  function serializeFaqs() {
    const items = Array.from(faqsList.querySelectorAll('.faq-item-edit')).map(item => {
      return {
        q: item.querySelector('.faq-q-input')?.value?.trim() || '',
        a: item.querySelector('.faq-a-input')?.value?.trim() || ''
      };
    }).filter(f => f.q !== '' || f.a !== '');
    faqsJson.value = JSON.stringify(items);
  }

  // Serialize Shipping Points
  function serializeShip() {
    const items = Array.from(shipList.querySelectorAll('.ship-input')).map(inp => inp.value.trim()).filter(v => v !== '');
    shipJson.value = JSON.stringify(items);
  }

  // Serialize Return Points
  function serializeRet() {
    const items = Array.from(retList.querySelectorAll('.ret-input')).map(inp => inp.value.trim()).filter(v => v !== '');
    retJson.value = JSON.stringify(items);
  }

  // Add FAQ
  if (addFaqBtn) {
    addFaqBtn.addEventListener('click', () => {
      const div = document.createElement('div');
      div.className = 'faq-item-edit';
      div.style.cssText = 'border:1px solid var(--border); border-radius:10px; padding:10px; margin-bottom:8px;';
      div.innerHTML = `
        <input type="text" class="faq-q-input" placeholder="Question">
        <textarea class="faq-a-input" rows="2" placeholder="Answer" style="margin-top:6px;"></textarea>
        <button type="button" class="btn" data-action="remove-faq" style="margin-top:6px">Remove</button>
      `;
      faqsList.appendChild(div);
    });
  }

  // Add Shipping Point
  if (addShipBtn) {
    addShipBtn.addEventListener('click', () => {
      const div = document.createElement('div');
      div.className = 'ship-item';
      div.style.cssText = 'display:grid; grid-template-columns:1fr auto; gap:8px; margin-bottom:8px;';
      div.innerHTML = `
        <input type="text" class="ship-input" value="">
        <button type="button" class="btn" data-action="remove-ship">Remove</button>
      `;
      shipList.appendChild(div);
    });
  }

  // Add Return Point
  if (addRetBtn) {
    addRetBtn.addEventListener('click', () => {
      const div = document.createElement('div');
      div.className = 'ret-item';
      div.style.cssText = 'display:grid; grid-template-columns:1fr auto; gap:8px; margin-bottom:8px;';
      div.innerHTML = `
        <input type="text" class="ret-input" value="">
        <button type="button" class="btn" data-action="remove-ret">Remove</button>
      `;
      retList.appendChild(div);
    });
  }

  // Remove handlers
  faqsList.addEventListener('click', (e) => {
    if (e.target.closest('[data-action="remove-faq"]')) {
      e.target.closest('.faq-item-edit')?.remove();
    }
  });
  shipList.addEventListener('click', (e) => {
    if (e.target.closest('[data-action="remove-ship"]')) {
      e.target.closest('.ship-item')?.remove();
    }
  });
  retList.addEventListener('click', (e) => {
    if (e.target.closest('[data-action="remove-ret"]')) {
      e.target.closest('.ret-item')?.remove();
    }
  });

  // Serialize before submit
  supportForm.addEventListener('submit', () => {
    serializeFaqs();
    serializeShip();
    serializeRet();
  });
})();

// Auto-hide flash messages after 2 seconds (and dismiss on click)
(function(){
  const flashes = document.querySelectorAll('.flash-msg');
  flashes.forEach(el => {
    const hide = () => {
      el.style.transition = 'opacity .3s ease';
      el.style.opacity = '0';
      setTimeout(() => { try { el.remove(); } catch(_){} }, 300);
    };
    setTimeout(hide, 2000);
    el.addEventListener('click', hide);
  });
})();
</script>

  </body>
</html>
