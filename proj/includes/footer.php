<?php
// Load company settings for footer display
require_once __DIR__ . '/../includes/db_connect.php';
$company = [
  'company_name' => 'EcomClothing',
  'address' => '123 Fashion Ave, Suite 100, NY 10001',
  'contact_email' => 'support@ecomclothing.test',
  'phone' => '(212) 555‑0199',
];
if (isset($conn) && $conn instanceof mysqli) {
  if ($res = $conn->query('SELECT company_name, address, contact_email, phone FROM company_settings WHERE id=1 LIMIT 1')) {
    if ($row = $res->fetch_assoc()) {
      $company = array_merge($company, $row);
    }
  }
}
?>
<footer>
  <div class="container footer-row">
    <div>
      <h4>Explore</h4>
      <a href="./homepage.php">Home</a><br />
      <a href="./shop.php">Shop</a><br />
      <a href="./about.php">About</a>
    </div>
    <div>
      <h4>Contact</h4>
      <p style="margin: 0; color: #9ca3af">
        <?= htmlspecialchars($company['address']) ?>
      </p>
      <p style="margin: 6px 0; color: #9ca3af">Tel: <?= htmlspecialchars($company['phone']) ?></p>
      <p style="margin: 0; color: #9ca3af">
        Email: <?= htmlspecialchars($company['contact_email']) ?>
      </p>
    </div>

    <div>
      <h4>Help & Social</h4>
      <a href="./contact.php">Shipping & Returns</a><br />
      <a href="./contact.php#faq-list">FAQ</a>
      <div class="social-icons">
        <a href="#" aria-label="Facebook" title="Facebook">
          <img src="../images/facebook.svg" width="22" height="22" alt="Facebook" style="filter: brightness(0.7)" />
        </a>
        <a href="#" aria-label="Instagram" title="Instagram">
          <img src="../images/instagram.svg" width="22" height="22" alt="Instagram" style="filter: brightness(0.7)" />
        </a>
        <a href="#" aria-label="Tiktok" title="Tiktok">
          <img src="../images/tiktok.svg" width="22" height="22" alt="Tiktok" style="filter: brightness(0.7)" />
        </a>
      </div>
    </div>
  </div>
  <div class="container copyright">
    <small>&copy; <span id="y"></span> <?= htmlspecialchars($company['company_name'] ?: 'EcomClothing') ?>. All rights reserved.</small>
  </div>
</footer>

<script>
  document.getElementById("y").textContent = new Date().getFullYear();
</script>
</body>
</html>