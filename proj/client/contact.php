<?php
$page_title = 'Contact — Ecom clothing';
require_once __DIR__ . '/../includes/db_connect.php';

// Load company settings from database
$company = [
  'company_name' => 'EcomClothing',
  'address' => 'Bole Road, Addis Ababa, Ethiopia',
  'contact_email' => 'support@ecomclothing.com',
  'phone' => '+251 11 123 4567',
];
if (isset($conn) && $conn instanceof mysqli) {
  if ($res = $conn->query('SELECT company_name, address, contact_email, phone FROM company_settings WHERE id=1 LIMIT 1')) {
    if ($row = $res->fetch_assoc()) {
      $company = array_merge($company, $row);
    }
    $res->free();
  }
}

// Load support settings (FAQs, Shipping, Returns) from database
$support = [
  'faqs_json' => json_encode([
    ['q' => 'What sizes do you offer?', 'a' => 'We offer sizes XS–XXL for most items. Size guides are available on each product page.'],
    ['q' => 'How long will my order take?', 'a' => 'Orders are processed in 1–2 business days. Addis Ababa deliveries typically arrive in 1–3 days, other cities 2–5 days.'],
    ['q' => 'Can I change or cancel my order?', 'a' => 'If your order hasn\'t shipped, contact us ASAP and we\'ll do our best to update or cancel it.'],
  ]),
  'shipping_points_json' => json_encode([
    'Addis Ababa: 1–3 business days.',
    'Adama and other cities: 2–5 business days.',
    'Standard shipping: ETB 150.',
    'Free shipping on orders over ETB 2000!'
  ]),
  'returns_points_json' => json_encode([
    '30-day return window for unworn items with tags.',
    'Easy exchanges for size/color within 30 days.',
    'Contact support to initiate a return.'
  ]),
];
if (isset($conn) && $conn instanceof mysqli) {
  if ($res = $conn->query('SELECT faqs_json, shipping_points_json, returns_points_json FROM support_settings WHERE id=1 LIMIT 1')) {
    if ($row = $res->fetch_assoc()) {
      $support = array_merge($support, $row);
    }
    $res->free();
  }
}

// Decode JSON data
$faqsArr = json_decode($support['faqs_json'] ?? '[]', true) ?: [];
$shippingArr = json_decode($support['shipping_points_json'] ?? '[]', true) ?: [];
$returnsArr = json_decode($support['returns_points_json'] ?? '[]', true) ?: [];

include '../includes/header.php';
?>

<style>
  .contact-page { padding: 28px 0 }
  .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px }
  .contact-card { background: rgba(17,24,39,0.85); border: 1px solid var(--border); border-radius: 16px; padding: 20px }
  .contact-card h2 { margin: 0 0 12px; font-size: 20px; color: var(--text) }
  .contact-card p, .contact-card li { color: #cbd5e1; line-height: 1.6 }
  .list { margin: 0; padding-left: 18px }

  .faq { display: grid; gap: 10px }
  .faq-item { border: 1px solid var(--border); border-radius: 12px; background: #0b1220; overflow: hidden }
  .faq-q { display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; cursor: pointer; color: #e5e7eb }
  .faq-a { display: none; padding: 0 14px 12px; color: #cbd5e1 }
  .faq-item.open .faq-a { display: block }

  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px }
  .form-group { margin-bottom: 12px }
  .form-group label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--text) }
  .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border); background: #0b1220; color: var(--text); outline: none; font-size: 14px }
  .form-group textarea { min-height: 120px; resize: vertical }
  .btn { padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); background: var(--accent); color: #fff; cursor: pointer }

  @media (max-width: 900px) {
    .contact-grid { grid-template-columns: 1fr }
    .form-row { grid-template-columns: 1fr }
  }
</style>

<main class="container contact-page">
  <h1 style="margin: 0 0 24px; font-size: 28px">Help & Support</h1>
  <div class="contact-grid">
    <!-- Help & Support / FAQs -->
    <section class="contact-card">
      <h2>FAQs</h2>
      <div class="faq" id="faq-list">
        <?php if (empty($faqsArr)) { ?>
          <p style="color: #9ca3af;">No FAQs available at the moment.</p>
        <?php } else { foreach ($faqsArr as $faq) { ?>
          <div class="faq-item">
            <div class="faq-q"><?= htmlspecialchars($faq['q'] ?? '') ?> <span>+</span></div>
            <div class="faq-a"><?= htmlspecialchars($faq['a'] ?? '') ?></div>
          </div>
        <?php } } ?>
      </div>
    </section>

    <!-- Shipping & Returns -->
    <section class="contact-card">
      <h2>Shipping & Returns</h2>
      <p><b style="color:var(--text)">Shipping</b></p>
      <?php if (empty($shippingArr)) { ?>
        <p style="color: #9ca3af;">No shipping information available.</p>
      <?php } else { ?>
        <ul class="list">
          <?php foreach ($shippingArr as $point) { ?>
            <li><?= htmlspecialchars($point) ?></li>
          <?php } ?>
        </ul>
      <?php } ?>
      <p><b style="color:var(--text)">Returns</b></p>
      <?php if (empty($returnsArr)) { ?>
        <p style="color: #9ca3af;">No returns information available.</p>
      <?php } else { ?>
        <ul class="list">
          <?php foreach ($returnsArr as $point) { ?>
            <li><?= htmlspecialchars($point) ?></li>
          <?php } ?>
        </ul>
      <?php } ?>
    </section>

    <!-- Contact Information -->
    <section class="contact-card" style="grid-column: 1 / -1">
      <h2>Contact Us</h2>
      <p style="margin-bottom: 20px; line-height: 1.8;">For any inquiries, support, or assistance, please feel free to reach out to us through the following channels:</p>
      
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 16px;">
        <div style="background: #0b1220; border: 1px solid var(--border); border-radius: 12px; padding: 20px;">
          <h3 style="margin: 0 0 12px; font-size: 18px; color: var(--accent);">📞 Phone</h3>
          <p style="margin: 0; color: #cbd5e1; font-size: 16px;"><?= htmlspecialchars($company['phone']) ?></p>
          <p style="margin: 4px 0 0; color: #9ca3af; font-size: 14px;">Mon-Fri: 9:00 AM - 6:00 PM</p>
        </div>
        
        <div style="background: #0b1220; border: 1px solid var(--border); border-radius: 12px; padding: 20px;">
          <h3 style="margin: 0 0 12px; font-size: 18px; color: var(--accent);">✉️ Email</h3>
          <p style="margin: 0; color: #cbd5e1; font-size: 16px;"><?= htmlspecialchars($company['contact_email']) ?></p>
          <p style="margin: 4px 0 0; color: #9ca3af; font-size: 14px;">We'll respond within 24 hours</p>
        </div>
        
        <div style="background: #0b1220; border: 1px solid var(--border); border-radius: 12px; padding: 20px;">
          <h3 style="margin: 0 0 12px; font-size: 18px; color: var(--accent);">📍 Address</h3>
          <p style="margin: 0; color: #cbd5e1; font-size: 16px;"><?= htmlspecialchars($company['address']) ?></p>
        </div>
      </div>
      
      <p style="margin-top: 24px; padding: 16px; background: rgba(124, 58, 237, 0.1); border: 1px solid rgba(124, 58, 237, 0.3); border-radius: 10px; color: #cbd5e1;">
        <strong style="color: var(--text);">💡 Tip:</strong> For faster assistance with order-related inquiries, please have your order number ready when contacting us.
      </p>
    </section>
  </div>
</main>

<script>
  // FAQ toggle
  document.querySelectorAll('.faq-q').forEach(q => {
    q.addEventListener('click', () => {
      const item = q.closest('.faq-item');
      item.classList.toggle('open');
      q.querySelector('span').textContent = item.classList.contains('open') ? '−' : '+';
    });
  });
</script>

<?php include '../includes/footer.php'; ?>