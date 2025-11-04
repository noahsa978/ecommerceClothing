<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['user_id'])) {
  header('Location: ./loginc.php');
  exit;
}
require_once __DIR__ . '/../includes/db_connect.php';

// Fetch current user info
$uid = (int)$_SESSION['user_id'];
$u = [
  'fullname' => $_SESSION['user']['fullname'] ?? '',
  'email'    => $_SESSION['user']['email'] ?? '',
  'phone'    => $_SESSION['user']['phone'] ?? '',
  'address'  => $_SESSION['user']['address'] ?? '',
];
// Flash message
$flash = null;

// Handle Personal Info update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'update_profile' && ($conn instanceof mysqli)) {
  $first = trim($_POST['first_name'] ?? '');
  $last = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $fullname = trim($first . ' ' . $last);
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $flash = ['type' => 'error', 'msg' => 'Please provide a valid email.'];
  } else {
    $stmt = $conn->prepare('UPDATE users SET fullname=?, email=?, phone=? WHERE id=?');
    $stmt->bind_param('sssi', $fullname, $email, $phone, $uid);
    if ($stmt->execute()) {
      $flash = ['type' => 'success', 'msg' => 'Profile updated.'];
      $u['fullname'] = $fullname; $u['email'] = $email; $u['phone'] = $phone;
      // Update session snapshot
      $_SESSION['user']['fullname'] = $fullname;
      $_SESSION['user']['email'] = $email;
      $_SESSION['user']['phone'] = $phone;
    } else {
      $flash = ['type' => 'error', 'msg' => 'Failed to update profile.'];
    }
    $stmt->close();
  }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'change_password' && ($conn instanceof mysqli)) {
  $current = $_POST['current_password'] ?? '';
  $new = $_POST['new_password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';
  if ($new === '' || strlen($new) < 6) {
    $flash = ['type' => 'error', 'msg' => 'New password must be at least 6 characters.'];
  } elseif ($new !== $confirm) {
    $flash = ['type' => 'error', 'msg' => 'New passwords do not match.'];
  } else {
    // Fetch hash
    $stmt = $conn->prepare('SELECT upassword FROM users WHERE id=?');
    $stmt->bind_param('i', $uid);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      if ($row = $res->fetch_assoc()) {
        if (!password_verify($current, $row['upassword'])) {
          $flash = ['type' => 'error', 'msg' => 'Current password is incorrect.'];
        } else {
          $stmt->close();
          $hash = password_hash($new, PASSWORD_BCRYPT);
          $upd = $conn->prepare('UPDATE users SET upassword=? WHERE id=?');
          $upd->bind_param('si', $hash, $uid);
          if ($upd->execute()) {
            $flash = ['type' => 'success', 'msg' => 'Password changed successfully.'];
          } else {
            $flash = ['type' => 'error', 'msg' => 'Failed to change password.'];
          }
          $upd->close();
        }
      }
    }
  }
}

// Handle shipping address update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'update_address' && ($conn instanceof mysqli)) {
  $city = trim($_POST['city'] ?? '');
  $delivery = trim($_POST['delivery_location'] ?? '');
  $allowed = [
    'Addis Ababa' => ['Megenagna','Ayat','Mexico','Garment','Betel'],
    'Adama' => ['Derartu Tulu Square','Adama University'],
  ];
  if ($city !== '' && isset($allowed[$city])) {
    if ($delivery !== '' && !in_array($delivery, $allowed[$city], true)) {
      $flash = ['type' => 'error', 'msg' => 'Invalid delivery location for selected city.'];
    }
  }
  if (!$flash) {
    $address = '';
    if ($city !== '' && $delivery !== '') {
      $address = $city . ' - ' . $delivery;
    } elseif ($city !== '') {
      $address = $city;
    } elseif ($delivery !== '') {
      $address = $delivery;
    }
    $stmt = $conn->prepare('UPDATE users SET address=? WHERE id=?');
    $stmt->bind_param('si', $address, $uid);
    if ($stmt->execute()) {
      $flash = ['type' => 'success', 'msg' => 'Shipping address updated.'];
      $u['address'] = $address;
      $_SESSION['user']['address'] = $address;
    } else {
      $flash = ['type' => 'error', 'msg' => 'Failed to update shipping address.'];
    }
    $stmt->close();
  }
}

if ($conn instanceof mysqli) {
  $stmt = $conn->prepare('SELECT username, email, fullname, address, phone FROM users WHERE id=? LIMIT 1');
  $stmt->bind_param('i', $uid);
  if ($stmt->execute()) {
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
      $u['fullname'] = $row['fullname'] ?? $u['fullname'];
      $u['email'] = $row['email'] ?? $u['email'];
      $u['phone'] = $row['phone'] ?? $u['phone'];
      $u['address'] = $row['address'] ?? $u['address'];
    }
  }
  $stmt->close();
}

// Split fullname to first/last best-effort
$first = ''; $last = '';
if (!empty($u['fullname'])) {
  $parts = preg_split('/\s+/', trim($u['fullname']));
  $first = $parts[0] ?? '';
  $last = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';
}

// Derive city and delivery from stored address
$addrCity = '';
$addrDelivery = '';
if (!empty($u['address'])) {
  if (strpos($u['address'], ' - ') !== false) {
    [$addrCity, $addrDelivery] = explode(' - ', $u['address'], 2);
    $addrCity = trim($addrCity);
    $addrDelivery = trim($addrDelivery);
  } else {
    $addrCity = trim($u['address']);
  }
}
$page_title = 'My Account — Ecom Clothing';
include '../includes/header.php';
?>

<style>
  .account-page { padding: 28px 0 }
  .account-grid { display: grid; grid-template-columns: 260px 1fr; gap: 24px }

  .account-card { background: rgba(17,24,39,0.85); border: 1px solid var(--border); border-radius: 16px; overflow: hidden }
  .account-sidebar { padding: 16px }
  .account-nav { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px }
  .account-nav button { width: 100%; text-align: left; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: #0b1220; color: #cbd5e1; cursor: pointer; transition: all .15s ease }
  .account-nav button:hover { background: rgba(124,58,237,0.12); color: #fff }
  .account-nav button.active { background: var(--accent); border-color: transparent; color: #fff }

  .account-content { padding: 20px }
  .account-section { display: none }
  .account-section.active { display: block }

  .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px }
  .section-header h2 { margin: 0; font-size: 20px; color: var(--text) }

  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px }
  .form-group { margin-bottom: 12px }
  .form-group label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--text) }
  .form-group input, .form-group select { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border); background: #0b1220; color: var(--text); outline: none; font-size: 14px }
  .form-actions { margin-top: 12px; display: flex; gap: 8px }
  .btn { padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); background: #0b1220; color: #fff; cursor: pointer }
  .btn.primary { background: var(--accent); border-color: transparent }
  .btn.danger { background: #ef4444; border-color: transparent }

  table { width: 100%; border-collapse: collapse; font-size: 14px; color: #cbd5e1 }
  th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--border) }
  th { color: var(--text) }
  .status { padding: 3px 8px; border-radius: 999px; font-size: 12px; display: inline-block }
  .status.completed { background: rgba(16,185,129,0.15); color: #10b981 }
  .status.processing { background: rgba(59,130,246,0.15); color: #3b82f6 }
  .status.cancelled { background: rgba(239,68,68,0.15); color: #ef4444 }

  .address-card { border: 1px solid var(--border); border-radius: 12px; padding: 12px; background: #0b1220 }
  .address-list { display: grid; grid-template-columns: 1fr 1fr; gap: 12px }

  @media (max-width: 900px) {
    .account-grid { grid-template-columns: 1fr }
    .form-row { grid-template-columns: 1fr }
    .address-list { grid-template-columns: 1fr }
  }
</style>

<main class="container account-page">
  <h1 style="margin: 0 0 24px; font-size: 28px">My Account</h1>

  <div class="account-grid">
    <!-- Sidebar -->
    <div class="account-card account-sidebar">
      <ul class="account-nav">
        <li><button class="active" data-tab="personal">Personal Info</button></li>
        <li><button data-tab="orders">Order History</button></li>
        <li><button data-tab="addresses">Shipping Address</button></li>
        <li><button data-tab="password">Change Password</button></li>
      </ul>
      <div style="margin-top:12px">
        <a href="./logout.php" class="btn" style="display:block; text-align:center">Logout</a>
      </div>
    </div>

    <!-- Content -->
    <div class="account-card">
      <div class="account-content">
        <?php if (!empty($flash)) { $isErr = $flash['type'] === 'error'; ?>
          <div style="margin-bottom:10px; padding:10px 12px; border:1px solid <?= $isErr ? '#7f1d1d' : '#065f46' ?>; background: <?= $isErr ? '#3f1d1d' : '#064e3b' ?>; color:#fff; border-radius:10px; font-size:14px;">
            <?= htmlspecialchars($flash['msg']); ?>
          </div>
        <?php } ?>
        <!-- Personal Info -->
        <section id="tab-personal" class="account-section active">
          <div class="section-header">
            <h2>Personal Info</h2>
          </div>

          <form method="post" action="">
            <input type="hidden" name="form_type" value="update_profile" />
            <div class="form-row">
              <div class="form-group">
                <label for="pi-first">First Name</label>
                <input type="text" id="pi-first" name="first_name" placeholder="First name" value="<?= htmlspecialchars($first) ?>">
              </div>
              <div class="form-group">
                <label for="pi-last">Last Name</label>
                <input type="text" id="pi-last" name="last_name" placeholder="Last name" value="<?= htmlspecialchars($last) ?>">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="pi-email">Email</label>
                <input type="email" id="pi-email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($u['email']) ?>" required>
              </div>
              <div class="form-group">
                <label for="pi-phone">Phone</label>
                <input type="tel" id="pi-phone" name="phone" placeholder="(251) 900 00 00 00" value="<?= htmlspecialchars($u['phone']) ?>">
              </div>
            </div>
            <div class="form-actions">
              <button class="btn primary" type="submit">Save Changes</button>
            </div>
          </form>
        </section>

        <!-- Order History -->
        <section id="tab-orders" class="account-section">
          <div class="section-header">
            <h2>Order History</h2>
          </div>

          <div style="overflow-x:auto">
            <table>
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Date</th>
                  <th>Items</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="orders-body">
                <?php
                  $orders = [];
                  if ($conn instanceof mysqli) {
                    if ($st = $conn->prepare('SELECT id, order_items, total_amount, status, created_at FROM orders WHERE user_id=? ORDER BY created_at DESC')) {
                      $st->bind_param('i', $uid);
                      if ($st->execute()) {
                        $res = $st->get_result();
                        while ($row = $res->fetch_assoc()) { $orders[] = $row; }
                      }
                      $st->close();
                    }
                  }
                  if (empty($orders)) {
                    echo '<tr><td colspan="6">No orders yet.</td></tr>';
                  } else {
                    foreach ($orders as $o) {
                      $date = htmlspecialchars(substr((string)($o['created_at'] ?? ''), 0, 19));
                      $id = (int)$o['id'];
                      $total = number_format((float)($o['total_amount'] ?? 0), 2);
                      $status = (string)($o['status'] ?? 'pending');
                      $itemsJson = $o['order_items'] ?? '[]';
                      $arr = json_decode($itemsJson, true);
                      $qty = 0;
                      if (is_array($arr)) {
                        foreach ($arr as $it) {
                          $q = isset($it['quantity']) ? (int)$it['quantity'] : 1;
                          $qty += max(0, $q);
                        }
                      } else {
                        $qty = 0;
                      }
                      $statusClass = 'processing';
                      if ($status === 'delivered' || $status === 'paid' || $status === 'completed') $statusClass = 'completed';
                      if ($status === 'cancelled') $statusClass = 'cancelled';
                ?>
                  <tr>
                    <td>#<?= $id ?></td>
                    <td><?= $date ?></td>
                    <td><?= (int)$qty ?></td>
                    <td>ETB <?= $total ?></td>
                    <td><span class="status <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span></td>
                    <td>
                      <a class="btn" href="download_invoice.php?order_id=<?= $id ?>">Download Invoice</a>
                    </td>
                  </tr>
                <?php } } ?>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Change Password -->
        <section id="tab-password" class="account-section">
          <div class="section-header">
            <h2>Change Password</h2>
          </div>
          <form method="post" action="">
            <input type="hidden" name="form_type" value="change_password" />
            <div class="form-row">
              <div class="form-group" style="grid-column: 1 / -1;">
                <label for="cp-current">Current Password</label>
                <input type="password" id="cp-current" name="current_password" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="cp-new">New Password</label>
                <input type="password" id="cp-new" name="new_password" minlength="6" required>
              </div>
              <div class="form-group">
                <label for="cp-confirm">Confirm New Password</label>
                <input type="password" id="cp-confirm" name="confirm_password" minlength="6" required>
              </div>
            </div>
            <div class="form-actions">
              <button class="btn primary" type="submit">Update Password</button>
            </div>
          </form>
        </section>

        <!-- Saved Addresses -->
        <section id="tab-addresses" class="account-section">
          <div class="section-header">
            <h2>Shipping Address</h2>
          </div>

          <div class="address-list" id="address-list">
            <div class="address-card" style="grid-column: 1 / -1;">
              <form method="post" action="">
                <input type="hidden" name="form_type" value="update_address" />
                <div class="form-row">
                  <div class="form-group">
                    <label for="addr-city">City</label>
                    <?php $allowedCities = ['Addis Ababa','Adama']; ?>
                    <select id="addr-city" name="city">
                      <option value="">Select City</option>
                      <?php foreach ($allowedCities as $c): $sel = ($addrCity===$c)?'selected':''; ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $sel ?>><?= htmlspecialchars($c) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="addr-delivery">Delivery Location</label>
                    <select id="addr-delivery" name="delivery_location">
                      <option value="">Select Delivery Location</option>
                      <?php if ($addrCity==='Addis Ababa'): $opts=['Megenagna','Ayat','Mexico','Garment','Betel'];
                        foreach ($opts as $o): $sel = ($addrDelivery===$o)?'selected':''; ?>
                          <option value="<?= htmlspecialchars($o) ?>" <?= $sel ?>><?= htmlspecialchars($o) ?></option>
                      <?php endforeach; elseif ($addrCity==='Adama'): $opts=['Derartu Tulu Square','Adama University'];
                        foreach ($opts as $o): $sel = ($addrDelivery===$o)?'selected':''; ?>
                          <option value="<?= htmlspecialchars($o) ?>" <?= $sel ?>><?= htmlspecialchars($o) ?></option>
                      <?php endforeach; endif; ?>
                    </select>
                  </div>
                </div>
                <div class="form-actions">
                  <button class="btn primary" type="submit">Save Address</button>
                </div>
                <div style="margin-top:8px; color:#9ca3af; font-size:14px">
                  Current: <?= htmlspecialchars($u['address'] ?: 'No address set') ?>
                </div>
              </form>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</main>

<script>
  // Tab switching
  document.querySelectorAll('.account-nav button').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.account-nav button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const tab = btn.getAttribute('data-tab');
      document.querySelectorAll('.account-section').forEach(sec => sec.classList.remove('active'));
      document.getElementById(`tab-${tab}`).classList.add('active');
    });
  });

  // Actions (placeholder behaviors)
  function savePersonalInfo() {
    alert('Personal info saved.');
  }
  function viewOrder(id) {
    alert('Viewing order #' + id);
  }
  function editSingleAddress() {
    const addressEl = document.querySelector('#address-list .address-line');
    const phoneEl = document.querySelector('#address-list .phone-line');

    const currentAddress = addressEl ? addressEl.textContent : '';
    const currentPhone = phoneEl ? phoneEl.textContent.replace('Phone: ', '') : '';

    const currentParts = currentAddress.split(',');
    const currentCity = (currentParts[0] || '').trim();
    const currentLocation = (currentParts[1] || '').trim();

    const newCity = prompt('City (e.g., Addis Ababa, Adama):', currentCity);
    if (newCity === null) return;

    const newLocation = prompt('Location (e.g., Megenagna, Adama University):', currentLocation);
    if (newLocation === null) return;

    const newPhone = prompt('Phone:', currentPhone);
    if (newPhone === null) return;

    if (addressEl) addressEl.textContent = `${newCity}, ${newLocation}`;
    if (phoneEl) phoneEl.textContent = `Phone: ${newPhone}`;
  }
</script>

<?php include '../includes/footer.php'; ?>
