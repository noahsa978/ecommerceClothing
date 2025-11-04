<?php
require_once __DIR__ . '/../includes/db_connect.php';

// Load company settings (name)
$company = [ 'company_name' => 'Ecom clothing' ];
if (isset($conn) && $conn instanceof mysqli) {
  if ($res = $conn->query('SELECT company_name FROM company_settings WHERE id=1 LIMIT 1')) {
    if ($row = $res->fetch_assoc()) { $company = array_merge($company, $row); }
  }
}

// Load about content
$about = [
  'our_company' => 'We’re a customer-first apparel brand crafting modern essentials with quality, comfort, and sustainability in mind.',
  'our_history' => 'Founded in 2020 by a small team of designers and engineers, we set out to remove the friction between people and great clothing.',
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
if (isset($conn) && $conn instanceof mysqli) {
  if ($res = $conn->query('SELECT our_company, our_history, our_mission, our_vision, values_json FROM about_settings WHERE id=1 LIMIT 1')) {
    if ($row = $res->fetch_assoc()) { $about = array_merge($about, $row); }
  }
}

$page_title = 'About Us — ' . htmlspecialchars($company['company_name']);
include '../includes/header.php';
?>

<style>
  .about-page { padding: 28px 0 }
  .about-hero { background: rgba(17,24,39,0.85); border: 1px solid var(--border); border-radius: 16px; padding: 24px; margin-bottom: 20px }
  .about-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px }
  .about-card { background: rgba(17,24,39,0.85); border: 1px solid var(--border); border-radius: 16px; padding: 20px }
  .about-card h2 { margin: 0 0 10px; font-size: 18px; color: var(--text) }
  .about-card p { color: #cbd5e1; line-height: 1.6; margin: 0 }
  .values-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 10px }
  .values-list li { background: #0b1220; border: 1px solid var(--border); border-radius: 12px; padding: 12px; color: #cbd5e1 }
  .values-list li b { color: var(--text) }
  .about-cta { margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px }
  .cta-card { background: rgba(17,24,39,0.85); border: 1px solid var(--border); border-radius: 16px; padding: 20px }
  .cta-card a { display: inline-block; margin-top: 10px; padding: 10px 14px; border-radius: 10px; background: var(--accent); color: #fff; text-decoration: none }

  @media (max-width: 900px) {
    .about-grid, .about-cta { grid-template-columns: 1fr }
  }
</style>

<main class="container about-page">
  <div class="about-hero">
    <h1 style="margin: 0 0 10px; font-size: 28px">About <?= htmlspecialchars($company['company_name']) ?></h1>
    <p style="color:#cbd5e1; margin:0"><?= nl2br(htmlspecialchars($about['our_company'] ?? '')) ?></p>
  </div>

  <div class="about-grid">
    <section class="about-card">
      <h2>Our Company</h2>
      <p><?= nl2br(htmlspecialchars($about['our_company'] ?? '')) ?></p>
    </section>
    <section class="about-card">
      <h2>Our History</h2>
      <p><?= nl2br(htmlspecialchars($about['our_history'] ?? '')) ?></p>
    </section>
    <section class="about-card">
      <h2>Our Mission</h2>
      <p><?= nl2br(htmlspecialchars($about['our_mission'] ?? '')) ?></p>
    </section>
    <section class="about-card">
      <h2>Our Vision</h2>
      <p><?= nl2br(htmlspecialchars($about['our_vision'] ?? '')) ?></p>
    </section>
    <section class="about-card" style="grid-column: 1 / -1">
      <h2>Our Values</h2>
      <ul class="values-list">
        <?php $vals = json_decode($about['values_json'] ?? '[]', true) ?: []; ?>
        <?php foreach ($vals as $v): $t = trim($v['title'] ?? ''); $tx = trim($v['text'] ?? ''); if ($t==='' && $tx==='') continue; ?>
          <li><b><?= htmlspecialchars($t) ?>:</b> <?= htmlspecialchars($tx) ?></li>
        <?php endforeach; ?>
      </ul>
    </section>
  </div>

  <div class="about-cta">
    <section class="cta-card">
      <h2>Contact Us</h2>
      <p>Have questions about sizing, orders, or returns? Our team is here to help.</p>
      <a href="./contact.php">Get in touch →</a>
    </section>
  </div>
</main>

<?php include '../includes/footer.php'; ?>