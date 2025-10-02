<?php
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
      </ul>
      <div style="margin-top:12px">
        <a href="./logout.php" class="btn" style="display:block; text-align:center">Logout</a>
      </div>
    </div>

    <!-- Content -->
    <div class="account-card">
      <div class="account-content">
        <!-- Personal Info -->
        <section id="tab-personal" class="account-section active">
          <div class="section-header">
            <h2>Personal Info</h2>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="pi-first">First Name</label>
              <input type="text" id="pi-first" placeholder="Abebe" value="Abebe">
            </div>
            <div class="form-group">
              <label for="pi-last">Last Name</label>
              <input type="text" id="pi-last" placeholder="Kebede" value="Kebede">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="pi-email">Email</label>
              <input type="email" id="pi-email" placeholder="you@example.com" value="you@example.com">
            </div>
            <div class="form-group">
              <label for="pi-phone">Phone</label>
              <input type="tel" id="pi-phone" placeholder="(251) 933 55 22 66" value="(251) 933 55 22 66">
            </div>
          </div>
          <div class="form-actions">
            <button class="btn primary" onclick="savePersonalInfo()">Save Changes</button>
          </div>
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
                <tr>
                  <td>#10045</td>
                  <td>2025-08-19</td>
                  <td>3</td>
                  <td>$129.00</td>
                  <td><span class="status completed">Completed</span></td>
                  <td><button class="btn" onclick="viewOrder('10045')">View</button></td>
                </tr>
                <tr>
                  <td>#10044</td>
                  <td>2025-08-02</td>
                  <td>1</td>
                  <td>$59.00</td>
                  <td><span class="status processing">Processing</span></td>
                  <td><button class="btn" onclick="viewOrder('10044')">View</button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Saved Addresses -->
        <section id="tab-addresses" class="account-section">
          <div class="section-header">
            <h2>Shipping Address</h2>
            <button class="btn primary" onclick="editSingleAddress()">Edit Address</button>
          </div>

          <div class="address-list" id="address-list">
            <div class="address-card">
              <div style="font-weight:600; color: var(--text);">Default</div>
              <div class="address-line" style="margin-top:6px; color:#cbd5e1">Addis Ababa, Megenagna</div>
              <div class="phone-line" style="margin-top:6px; color:#9ca3af">Phone: (251) 933 55 22 66</div>
              <div class="form-actions">
                <button class="btn" onclick="editSingleAddress()">Edit</button>
              </div>
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
