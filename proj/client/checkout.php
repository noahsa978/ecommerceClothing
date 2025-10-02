<?php
$page_title = 'Checkout — Ecom Clothing';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$shipping = $_SESSION['shipping'] ?? null;
$is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['user']);
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
  .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border); background: #0b1220; color: var(--text); outline: none; font-size: 14px }
  .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(124,58,237,0.15) }
  .form-group textarea { resize: vertical; min-height: 80px }
  .checkbox-group { display: flex; align-items: center; gap: 8px; margin-top: 8px }
  .checkbox-group input[type="checkbox"] { width: auto; margin: 0 }

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
  .payment-option input[type="radio"] { margin: 0 }
  .payment-icon { width: 24px; height: 24px; background: #374151; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 12px }

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
      <!-- Login/Guest Toggle (shown only for guests) -->
      <?php if (!$is_logged_in): ?>
      <div class="checkout-section">
        <div class="auth-toggle">
          <button class="auth-btn active" onclick="toggleAuth('guest')">Guest Checkout</button>
          <button class="auth-btn" onclick="toggleAuth('login')">Customer Login</button>
        </div>
        
        <div id="guest-form">
          <h2>Contact Information</h2>
          <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" required placeholder="your@email.com">
          </div>
          <div class="checkbox-group">
            <input type="checkbox" id="newsletter">
            <label for="newsletter">Subscribe to our newsletter for updates and special offers</label>
          </div>
        </div>

        <div id="login-form" style="display: none;">
          <h2>Customer Login</h2>
          <div class="form-group">
            <label for="login-email">Email Address *</label>
            <input type="email" id="login-email" required placeholder="your@email.com">
          </div>
          <div class="form-group">
            <label for="login-password">Password *</label>
            <input type="password" id="login-password" required placeholder="Enter your password">
          </div>
          <div class="checkbox-group">
            <input type="checkbox" id="remember">
            <label for="remember">Remember me</label>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Shipping Information -->
      <div class="checkout-section">
        <h2>Shipping Information</h2>
        <div class="form-row">
          <div class="form-group">
            <label for="first-name">First Name *</label>
            <input type="text" id="first-name" required placeholder="Abebe" value="<?= isset($shipping['first_name']) ? htmlspecialchars($shipping['first_name']) : '' ?>">
          </div>
          <div class="form-group">
            <label for="last-name">Last Name *</label>
            <input type="text" id="last-name" required placeholder="Kebede" value="<?= isset($shipping['last_name']) ? htmlspecialchars($shipping['last_name']) : '' ?>">
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
          <input type="tel" id="phone" required placeholder="(251) 933 55 22 66" value="<?= isset($shipping['phone']) ? htmlspecialchars($shipping['phone']) : '' ?>">
        </div>
        <div class="checkbox-group">
          <input type="checkbox" id="same-billing">
          <label for="same-billing">Use same address for billing</label>
        </div>
      </div>

      <!-- Billing Information -->
      <div class="checkout-section" id="billing-section" style="display: none;">
        <h2>Billing Information</h2>
        <div class="form-row">
          <div class="form-group">
            <label for="bill-first-name">First Name *</label>
            <input type="text" id="bill-first-name" placeholder="John">
          </div>
          <div class="form-group">
            <label for="bill-last-name">Last Name *</label>
            <input type="text" id="bill-last-name" placeholder="Doe">
          </div>
        </div>
        <div class="form-group">
          <label for="bill-address">Street Address *</label>
          <input type="text" id="bill-address" placeholder="123 Main Street">
        </div>
        <div class="form-group">
          <label for="bill-apartment">Apartment, suite, etc. (optional)</label>
          <input type="text" id="bill-apartment" placeholder="Apt 4B">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="bill-city">City *</label>
            <input type="text" id="bill-city" placeholder="New York">
          </div>
          <div class="form-group">
            <label for="bill-state">State *</label>
            <select id="bill-state">
              <option value="">Select State</option>
              <option value="NY">New York</option>
              <option value="CA">California</option>
              <option value="TX">Texas</option>
              <option value="FL">Florida</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label for="bill-zip">ZIP Code *</label>
          <input type="text" id="bill-zip" placeholder="10001">
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

    <!-- Order Summary -->
    <div class="order-summary">
      <h2 style="margin: 0 0 16px; font-size: 20px">Order Summary</h2>
      
      <div class="summary-item">
        <span>Premium Cotton Tee (M, Black) × 2</span>
        <span>$48.00</span>
      </div>
      <div class="summary-item">
        <span>Classic Hoodie (L, Navy) × 1</span>
        <span>$59.00</span>
      </div>
      <div class="summary-item">
        <span>Denim Jacket (M, Blue) × 1</span>
        <span>$89.00</span>
      </div>
      
      <div class="summary-item">
        <span>Subtotal</span>
        <span>$196.00</span>
      </div>
      <div class="summary-item">
        <span>Shipping</span>
        <span>$9.99</span>
      </div>
      <div class="summary-item">
        <span>Tax</span>
        <span>$16.48</span>
      </div>
      <div class="summary-item total">
        <span>Total</span>
        <span>$222.47</span>
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
  function toggleAuth(type) {
    const guestBtn = document.querySelector('.auth-btn:first-child');
    const loginBtn = document.querySelector('.auth-btn:last-child');
    const guestForm = document.getElementById('guest-form');
    const loginForm = document.getElementById('login-form');

    if (type === 'guest') {
      guestBtn.classList.add('active');
      loginBtn.classList.remove('active');
      guestForm.style.display = 'block';
      loginForm.style.display = 'none';
    } else {
      loginBtn.classList.add('active');
      guestBtn.classList.remove('active');
      guestForm.style.display = 'none';
      loginForm.style.display = 'block';
    }
  }

  function selectPayment(method) {
    // Remove selected class from all options
    document.querySelectorAll('.payment-option').forEach(option => {
      option.classList.remove('selected');
    });
    
    // Add selected class to clicked option
    event.currentTarget.classList.add('selected');
    
    // Update radio button
    document.querySelector(`input[value="${method}"]`).checked = true;
    
    // Show/hide card details
    const cardDetails = document.getElementById('card-details');
    if (method === 'card') {
      cardDetails.style.display = 'block';
    } else {
      cardDetails.style.display = 'none';
    }
  }

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
    let isValid = true;

    requiredFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (!field.value.trim()) {
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

    // In a real app, this would process the order
    alert('Order placed successfully! Redirecting to confirmation page...');
    // window.location.href = './order-confirmation.php';
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

  // Toggle billing section
  document.getElementById('same-billing').addEventListener('change', function(e) {
    const billingSection = document.getElementById('billing-section');
    if (e.target.checked) {
      billingSection.style.display = 'none';
    } else {
      billingSection.style.display = 'block';
    }
  });
</script>

<?php include '../includes/footer.php'; ?>
