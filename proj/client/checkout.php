<?php
$page_title = 'Checkout — Ecom Clothing';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db_connect.php';

$is_logged_in = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
$userId = $is_logged_in ? (int)$_SESSION['user_id'] : null;
$sessionId = session_id();

// Prefill shipping: default address -> users fallback -> session fallback
$shipping = [
  'first_name' => '', 'last_name' => '', 'city' => '', 'delivery_location' => '', 'phone' => ''
];
if ($conn instanceof mysqli && $is_logged_in) {
  // addresses default
  if ($st = $conn->prepare('SELECT full_name, line1, city, phone FROM addresses WHERE user_id=? AND is_default=1 ORDER BY id DESC LIMIT 1')) {
    $st->bind_param('i', $userId);
    if ($st->execute()) {
      $res = $st->get_result();
      if ($row = $res->fetch_assoc()) {
        $full = trim((string)$row['full_name']);
        if ($full !== '') {
          $parts = preg_split('/\s+/', $full, 2);
          $shipping['first_name'] = $parts[0] ?? '';
          $shipping['last_name'] = $parts[1] ?? '';
        }
        $shipping['city'] = (string)($row['city'] ?? '');
        $shipping['delivery_location'] = (string)($row['line1'] ?? '');
        $shipping['phone'] = (string)($row['phone'] ?? '');
      }
    }
    $st->close();
  }
  // fallback to users table if still empty
  if ($shipping['first_name'] === '' && $shipping['last_name'] === '' && $st = $conn->prepare('SELECT fullname, address, phone FROM users WHERE id=? LIMIT 1')) {
    $st->bind_param('i', $userId);
    if ($st->execute()) {
      $res = $st->get_result();
      if ($row = $res->fetch_assoc()) {
        $full = trim((string)$row['fullname']);
        if ($full !== '') { $parts = preg_split('/\s+/', $full, 2); $shipping['first_name'] = $parts[0] ?? ''; $shipping['last_name'] = $parts[1] ?? ''; }
        if ($shipping['delivery_location'] === '') { $shipping['delivery_location'] = (string)($row['address'] ?? ''); }
        if ($shipping['phone'] === '') { $shipping['phone'] = (string)($row['phone'] ?? ''); }
      }
    }
    $st->close();
  }
}
// last resort from session
$sessionShip = $_SESSION['shipping'] ?? null;
if ($sessionShip && is_array($sessionShip)) { $shipping = array_merge($shipping, $sessionShip); }

// Load active cart and compute totals
$items = [];
$subtotal = 0.0; $shippingFee = 0.0; $tax = 0.0; $total = 0.0;
if ($conn instanceof mysqli) {
  // find cart id
  $cartId = null;
  if ($userId) {
    if ($st = $conn->prepare('SELECT id FROM carts WHERE user_id=? AND status="active" ORDER BY id DESC LIMIT 1')) { $st->bind_param('i', $userId); if ($st->execute()) { $r=$st->get_result(); if ($row=$r->fetch_assoc()) { $cartId=(int)$row['id']; } } $st->close(); }
    if ($cartId === null && $sessionId) { if ($st = $conn->prepare('SELECT id FROM carts WHERE session_id=? AND status="active" ORDER BY id DESC LIMIT 1')) { $st->bind_param('s', $sessionId); if ($st->execute()) { $r=$st->get_result(); if ($row=$r->fetch_assoc()) { $cartId=(int)$row['id']; } } $st->close(); } }
  } else {
    if ($sessionId && ($st = $conn->prepare('SELECT id FROM carts WHERE session_id=? AND status="active" ORDER BY id DESC LIMIT 1'))) { $st->bind_param('s', $sessionId); if ($st->execute()) { $r=$st->get_result(); if ($row=$r->fetch_assoc()) { $cartId=(int)$row['id']; } } $st->close(); }
  }
  if ($cartId) {
    $sql = 'SELECT ci.quantity, ci.price, v.size, v.color, p.name FROM cart_items ci JOIN product_variants v ON v.id=ci.variant_id JOIN products p ON p.id=v.product_id WHERE ci.cart_id=? ORDER BY ci.id DESC';
    if ($st = $conn->prepare($sql)) {
      $st->bind_param('i', $cartId);
      if ($st->execute()) {
        $res = $st->get_result();
        while ($row = $res->fetch_assoc()) {
          $row['line_total'] = (float)$row['price'] * (int)$row['quantity'];
          $subtotal += $row['line_total'];
          $items[] = $row;
        }
      }
      $st->close();
    }
  }
}
// Free shipping for orders over ETB 2000
$shippingFee = $subtotal >= 2000 ? 0.0 : ($subtotal > 0 ? 150.0 : 0.0);
$tax = $subtotal * 0.084;
$total = $subtotal + $shippingFee + $tax;

include '../includes/header.php';
?>

<style>
  .checkout-page { padding: 28px 0 }
  .checkout-grid { display: grid; grid-template-columns: 1fr 400px; gap: 24px }

  /* Checkout Steps */
  .checkout-steps { display: flex; margin-bottom: 24px; padding: 0; list-style: none; border-bottom: 1px solid var(--border) }
  .checkout-steps li { flex: 1; padding: 12px 0; text-align: center; color: #9ca3af; font-size: 14px; position: relative }
  .checkout-steps li.active { color: var(--accent); font-weight: 600 }
  .checkout-steps li.completed { color: #10b981 }
  .checkout-steps li:not(:last-child)::after { content: ''; position: absolute; right: 0; top: 50%; transform: translateY(-50%); width: 1px; height: 20px; background: var(--border) }

  /* Forms */
  .checkout-section { background: rgba(17,24,39,0.85); border: 1px solid var(--border); border-radius: 16px; padding: 20px; margin-bottom: 20px }
  .checkout-section h2 { margin: 0 0 16px; font-size: 18px; color: var(--text) }
  .form-group { margin-bottom: 16px }
  .form-group:last-child { margin-bottom: 0 }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px }
  .form-group label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--text) }
  .form-group input:not([type="radio"]):not([type="checkbox"]):not([type="file"]), .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border); background: #0b1220; color: var(--text); outline: none; font-size: 14px }
  .form-group input:not([type="radio"]):not([type="checkbox"]):not([type="file"]):focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(124,58,237,0.15) }
  .form-group textarea { resize: vertical; min-height: 80px }
  .form-group input[type="file"] { width: 100%; padding: 8px; border-radius: 8px; border: 1px solid var(--border); background: #0b1220; color: var(--text); outline: none; font-size: 14px; cursor: pointer }
  .checkbox-group { display: flex; align-items: center; gap: 8px; margin-top: 8px }
  .checkbox-group input[type="checkbox"] { width: auto; margin: 0; cursor: pointer }

  /* Login/Guest Toggle */
  .auth-toggle { display: flex; gap: 8px; margin-bottom: 16px }
  .auth-btn { flex: 1; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; background: #0b1220; color: #cbd5e1; cursor: pointer; text-align: center; transition: all 0.15s ease }
  .auth-btn.active { background: var(--accent); border-color: transparent; color: #fff }
  .auth-btn:hover:not(.active) { background: rgba(124,58,237,0.12); color: #fff }

  /* Payment Methods */
  .payment-methods { display: grid; gap: 8px }
  .payment-option { display: flex; align-items: center; gap: 12px; padding: 12px; border: 1px solid var(--border); border-radius: 8px; background: #0b1220; cursor: pointer; transition: all 0.15s ease }
  .payment-option:hover { background: rgba(124,58,237,0.12) }
  .payment-option.selected { border-color: var(--accent); background: rgba(124,58,237,0.12) }
  .payment-option input[type="radio"] { margin: 0; width: 18px; height: 18px; cursor: pointer; accent-color: var(--accent); flex-shrink: 0 }
  .payment-icon { width: 24px; height: 24px; background: #374151; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0 }

  /* Order Summary */
  .order-summary { background: rgba(17,24,39,0.85); border: 1px solid var(--border); border-radius: 16px; padding: 20px; height: fit-content; position: sticky; top: 100px }
  .summary-item { display: flex; justify-content: space-between; margin-bottom: 8px; color: #cbd5e1; font-size: 14px }
  .summary-item.total { border-top: 1px solid var(--border); padding-top: 12px; margin-top: 12px; font-size: 18px; font-weight: 700; color: var(--text) }
  .place-order-btn { width: 100%; padding: 14px; margin-top: 16px; background: var(--accent); border: none; border-radius: 12px; color: #fff; font-weight: 700; font-size: 16px; cursor: pointer; transition: all 0.15s ease }
  .place-order-btn:hover { background: var(--accent-600); transform: translateY(-1px) }
  .place-order-btn:disabled { background: #6b7280; cursor: not-allowed; transform: none }

  /* Security Notice */
  .security-notice { display: flex; align-items: center; gap: 8px; margin-top: 12px; color: #9ca3af; font-size: 12px }

  @media (max-width: 900px) {
    .checkout-grid { grid-template-columns: 1fr }
    .order-summary { position: static; order: -1 }
    .form-row { grid-template-columns: 1fr }
    .checkout-steps { font-size: 12px }
  }
</style>

<main class="container checkout-page">
  <h1 style="margin: 0 0 24px; font-size: 28px">Checkout</h1>
  
  <ul class="checkout-steps">
    <li class="completed">Cart</li>
    <li class="active">Checkout</li>
    <li>Confirmation</li>
  </ul>

  <div class="checkout-grid">
    <div class="checkout-form">
      <!-- Guest: simple Contact Information (no login option) -->
      <?php if (!$is_logged_in): ?>
      <div class="checkout-section">
        <h2>Contact Information</h2>
        <div class="form-group">
          <label for="email">Email Address *</label>
          <input type="email" id="email" required placeholder="your@email.com">
        </div>
      </div>
      <?php endif; ?>

      <!-- Shipping Information (prefilled when available) -->
      <div class="checkout-section">
        <h2>Shipping Information</h2>
        <div class="form-row">
          <div class="form-group">
            <label for="first-name">First Name *</label>
            <input type="text" id="first-name" required placeholder="First Name" value="<?= htmlspecialchars($shipping['first_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="last-name">Last Name *</label>
            <input type="text" id="last-name" required placeholder="Last Name" value="<?= htmlspecialchars($shipping['last_name'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="city">City *</label>
            <select id="city" required>
              <option value="">Select City</option>
              <?php $selectedCity = $shipping['city'] ?? ''; ?>
              <option value="Addis Ababa" <?= ($selectedCity === 'Addis Ababa') ? 'selected' : '' ?>>Addis Ababa</option>
              <option value="Adama" <?= ($selectedCity === 'Adama') ? 'selected' : '' ?>>Adama</option>
            </select>
          </div>
          <div class="form-group">
            <label for="delivery-location">Delivery Location *</label>
            <select id="delivery-location" required>
              <option value="">Select Delivery Location</option>
              <?php 
                $selectedLocation = $shipping['delivery_location'] ?? '';
                if ($selectedCity === 'Addis Ababa') {
                  $opts = ['Megenagna','Ayat','Mexico','Haile Garment','Betel'];
                  foreach ($opts as $opt) {
                    $sel = ($selectedLocation === $opt) ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($opt) . "\" $sel>" . htmlspecialchars($opt) . "</option>";
                  }
                } elseif ($selectedCity === 'Adama') {
                  $opts = ['Derartu Tulu Square','Adama University'];
                  foreach ($opts as $opt) {
                    $sel = ($selectedLocation === $opt) ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($opt) . "\" $sel>" . htmlspecialchars($opt) . "</option>";
                  }
                }
              ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label for="phone">Phone Number *</label>
          <input type="tel" id="phone" required placeholder="Phone Number" value="<?= htmlspecialchars($shipping['phone'] ?? '') ?>">
        </div>
      </div>
      

      <!-- Payment Method -->
      <div class="checkout-section">
        <h2>Payment Method</h2>
        <div class="payment-methods">
          <div class="payment-option selected" onclick="selectPayment('telebirr')">
            <input type="radio" name="payment" value="telebirr" checked>
            <div class="payment-icon">
              <img src="../images/telebirr.jpg" alt="Telebirr" style="width: 100%; height: 100%; object-fit: contain; border-radius: 4px;" />
            </div>
            <div>
              <div style="font-weight: 600;">Telebirr</div>
              <div style="font-size: 12px; color: #9ca3af;">Pay securely with Telebirr</div>
            </div>
          </div>
          <div class="payment-option" onclick="selectPayment('cbe')">
            <input type="radio" name="payment" value="cbe">
            <div class="payment-icon">
              <img src="../images/CBE.png" alt="CBE" style="width: 100%; height: 100%; object-fit: contain; border-radius: 4px;" />
            </div>
            <div>
              <div style="font-weight: 600;">CBE</div>
              <div style="font-size: 12px; color: #9ca3af;">Pay securely with CBE Banking</div>
            </div>
          </div>
          <div class="payment-option" onclick="selectPayment('cash')">
            <input type="radio" name="payment" value="cash">
            <div class="payment-icon">💰</div>
            <div>
              <div style="font-weight: 600;">Cash on Delivery</div>
              <div style="font-size: 12px; color: #9ca3af;">Pay when your order arrives</div>
            </div>
          </div>
        </div>

        <!-- Receipt Number (for Telebirr / CBE) -->
        <div id="receipt-field" class="form-group" style="margin-top: 12px; display: none;">
          <label for="receipt-number">Receipt/Transaction Number *</label>
          <input type="text" id="receipt-number" placeholder="Enter your receipt/transaction number">
        </div>

        <!-- Payment Screenshot (for Telebirr / CBE) -->
        <div id="screenshot-field" class="form-group" style="margin-top: 12px; display: none;">
          <label for="payment-screenshot">Payment Screenshot *</label>
          <input type="file" id="payment-screenshot" accept="image/*">
          <div style="font-size: 12px; color: #9ca3af; margin-top: 6px;">Upload a clear screenshot of your Telebirr/CBE payment confirmation.</div>
          <div id="screenshot-preview" style="margin-top: 8px; display: none;">
            <img id="screenshot-img" alt="Payment Screenshot Preview" style="max-width: 100%; height: auto; border: 1px solid var(--border); border-radius: 8px;" />
          </div>
        </div>

        <!-- Card Details -->
        <div id="card-details" style="display: none; margin-top: 16px;">
          <div class="form-group">
            <label for="card-number">Card Number *</label>
            <input type="text" id="card-number" placeholder="1234 5678 9012 3456" maxlength="19">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="expiry">Expiry Date *</label>
              <input type="text" id="expiry" placeholder="MM/YY" maxlength="5">
            </div>
            <div class="form-group">
              <label for="cvv">CVV *</label>
              <input type="text" id="cvv" placeholder="123" maxlength="4">
            </div>
          </div>
          <div class="form-group">
            <label for="card-name">Name on Card *</label>
            <input type="text" id="card-name" placeholder="John Doe">
          </div>
        </div>
      </div>
    </div>

    <!-- Order Summary (from active cart) -->
    <div class="order-summary">
      <h2 style="margin: 0 0 16px; font-size: 20px">Order Summary</h2>
      
      <?php if (empty($items)) { ?>
        <div class="summary-item"><span>Your cart is empty.</span><span></span></div>
      <?php } else { foreach ($items as $it) { ?>
        <div class="summary-item">
          <span><?= htmlspecialchars($it['name']) ?> (<?= htmlspecialchars($it['size'] ?: '-') ?>, <?= htmlspecialchars($it['color'] ?: '-') ?>) × <?= (int)$it['quantity'] ?></span>
          <span>ETB <?= number_format((float)$it['line_total'], 2) ?></span>
        </div>
      <?php } } ?>
      
      <div class="summary-item">
        <span>Subtotal</span>
        <span>ETB <?= number_format($subtotal, 2) ?></span>
      </div>
      <div class="summary-item">
        <span>Shipping</span>
        <span><?= $shippingFee == 0.0 ? 'FREE' : ('ETB '.number_format($shippingFee, 2)) ?></span>
      </div>
      <?php if ($shippingFee > 0 && $subtotal < 2000) { 
        $remaining = 2000 - $subtotal;
      ?>
        <div style="padding: 8px 10px; background: rgba(124, 58, 237, 0.1); border: 1px solid rgba(124, 58, 237, 0.3); border-radius: 8px; margin: 8px 0; font-size: 13px; color: #c4b5fd;">
          🎉 Add ETB <?= number_format($remaining, 2) ?> more to get <strong>FREE SHIPPING</strong>!
        </div>
      <?php } elseif ($shippingFee == 0.0 && $subtotal >= 2000) { ?>
        <div style="padding: 8px 10px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; margin: 8px 0; font-size: 13px; color: #6ee7b7;">
          ✅ You've qualified for <strong>FREE SHIPPING</strong>!
        </div>
      <?php } ?>
      <div class="summary-item">
        <span>Tax</span>
        <span>ETB <?= number_format($tax, 2) ?></span>
      </div>
      <div class="summary-item total">
        <span>Total</span>
        <span>ETB <?= number_format($total, 2) ?></span>
      </div>

      <button class="place-order-btn" onclick="placeOrder()">Place Order</button>
      
      <div class="security-notice">
        <span>🔒</span>
        <span>Your payment information is secure and encrypted</span>
      </div>
    </div>
  </div>
</main>

<script>
  const IS_LOGGED_IN = <?= $is_logged_in ? 'true' : 'false' ?>;

  function selectPayment(method) {
    // Remove selected class from all options
    document.querySelectorAll('.payment-option').forEach(option => {
      option.classList.remove('selected');
    });
    // Add selected class to the option containing the input
    const radio = document.querySelector(`input[name="payment"][value="${method}"]`);
    if (radio) {
      radio.checked = true;
      const optionEl = radio.closest('.payment-option');
      if (optionEl) optionEl.classList.add('selected');
    }
    
    // Show/hide card details
    const cardDetails = document.getElementById('card-details');
    if (method === 'card') {
      cardDetails.style.display = 'block';
    } else {
      cardDetails.style.display = 'none';
    }

    // Show/hide receipt field for Telebirr/CBE
    const receiptField = document.getElementById('receipt-field');
    const screenshotField = document.getElementById('screenshot-field');
    if (method === 'telebirr' || method === 'cbe') {
      receiptField.style.display = 'block';
      screenshotField.style.display = 'block';
    } else {
      receiptField.style.display = 'none';
      screenshotField.style.display = 'none';
    }
  }

  // Initialize payment-dependent UI on load (ensure selected state and receipt/card toggles)
  document.addEventListener('DOMContentLoaded', function () {
    const selected = document.querySelector('input[name="payment"]:checked');
    const method = selected ? selected.value : 'telebirr';
    selectPayment(method);
  });

  // Populate delivery locations based on city selection
  const citySelect = document.getElementById('city');
  const deliverySelect = document.getElementById('delivery-location');

  function populateDeliveryLocations() {
    const city = citySelect.value;
    // Reset options
    deliverySelect.innerHTML = '<option value="">Select Delivery Location</option>';

    let options = [];
    if (city === 'Addis Ababa') {
      options = ['Megenagna', 'Ayat', 'Mexico', 'Haile Garment', 'Betel'];
    } else if (city === 'Adama') {
      options = ['Derartu Tulu Square', 'Adama University'];
    }

    options.forEach(loc => {
      const opt = document.createElement('option');
      opt.value = loc;
      opt.textContent = loc;
      deliverySelect.appendChild(opt);
    });

    // Clear selection after repopulating
    deliverySelect.value = '';
  }

  if (citySelect && deliverySelect) {
    citySelect.addEventListener('change', populateDeliveryLocations);
  }

  function placeOrder() {
    // Basic validation
    const requiredFields = [ 'first-name', 'last-name', 'city', 'delivery-location', 'phone' ];
    if (!IS_LOGGED_IN) {
      requiredFields.unshift('email');
    }
    // Require receipt for Telebirr/CBE
    const selectedPayment = document.querySelector('input[name="payment"]:checked')?.value;
    if (selectedPayment === 'telebirr' || selectedPayment === 'cbe') {
      requiredFields.push('receipt-number');
      requiredFields.push('payment-screenshot');
    }
    let isValid = true;

    requiredFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (!field) return;
      let valid = true;
      if (field.type === 'file') {
        valid = field.files && field.files.length > 0;
      } else {
        valid = !!field.value.trim();
      }
      if (!valid) {
        field.style.borderColor = '#ef4444';
        isValid = false;
      } else {
        field.style.borderColor = '';
      }
    });

    if (!isValid) {
      alert('Please fill in all required fields.');
      return;
    }

    // Collect data and submit to backend
    const data = new FormData();
    const getVal = (id) => document.getElementById(id)?.value?.trim() || '';
    if (!IS_LOGGED_IN) data.append('email', getVal('email'));
    data.append('first_name', getVal('first-name'));
    data.append('last_name', getVal('last-name'));
    data.append('city', getVal('city'));
    data.append('delivery_location', getVal('delivery-location'));
    data.append('phone', getVal('phone'));
    data.append('payment', selectedPayment || 'telebirr');
    data.append('receipt_number', getVal('receipt-number'));
    const shotEl = document.getElementById('payment-screenshot');
    if (shotEl && shotEl.files && shotEl.files[0]) {
      data.append('payment_screenshot', shotEl.files[0]);
    }

    const btn = document.querySelector('.place-order-btn');
    const prevText = btn?.textContent;
    if (btn) { btn.disabled = true; btn.textContent = 'Placing order...'; }

    fetch('./place_order.php', { method: 'POST', body: data })
      .then(async r => {
        const text = await r.text();
        try { return JSON.parse(text); } catch (e) {
          throw new Error('Non-JSON response from server: ' + text.slice(0, 300));
        }
      })
      .then(res => {
        if (res?.success) {
          alert('Order placed successfully!');
          // window.location.href = './order-confirmation.php?order_id=' + res.order_id;
          window.location.href = './homepage.php';
        } else {
          throw new Error(res?.error || 'Failed to place order');
        }
      })
      .catch(err => {
        alert('Error: ' + (err.message || err));
      })
      .finally(() => {
        if (btn) { btn.disabled = false; btn.textContent = prevText || 'Place Order'; }
      });
  }

  // Format card number input
  document.getElementById('card-number').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    e.target.value = formattedValue;
  });

  // Format expiry date input
  document.getElementById('expiry').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
      value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    e.target.value = value;
  });

  // Billing section and same-billing checkbox removed for simplified guest checkout

  // Screenshot preview
  const screenshotInput = document.getElementById('payment-screenshot');
  const previewWrap = document.getElementById('screenshot-preview');
  const previewImg = document.getElementById('screenshot-img');
  if (screenshotInput) {
    screenshotInput.addEventListener('change', function(e) {
      const file = e.target.files && e.target.files[0];
      if (!file) {
        previewWrap.style.display = 'none';
        previewImg.src = '';
        return;
      }
      const url = URL.createObjectURL(file);
      previewImg.src = url;
      previewWrap.style.display = 'block';
    });
  }
</script>

<?php include '../includes/footer.php'; ?>
