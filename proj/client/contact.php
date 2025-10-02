<?php
$page_title = 'Contact — Ecom clothing';
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
        <div class="faq-item">
          <div class="faq-q">What sizes do you offer? <span>+</span></div>
          <div class="faq-a">We offer sizes XS–XXL for most items. Size guides are available on each product page.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q">How long will my order take? <span>+</span></div>
          <div class="faq-a">Orders are processed in 1–2 business days. Addis Ababa deliveries typically arrive in 1–3 days, other cities 2–5 days.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q">Can I change or cancel my order? <span>+</span></div>
          <div class="faq-a">If your order hasn’t shipped, contact us ASAP and we’ll do our best to update or cancel it.</div>
        </div>
      </div>
    </section>

    <!-- Shipping & Returns -->
    <section class="contact-card">
      <h2>Shipping & Returns</h2>
      <p><b style="color:var(--text)">Shipping</b></p>
      <ul class="list">
        <li>Addis Ababa: 1–3 business days.</li>
        <li>Adama and other cities: 2–5 business days.</li>
        <li>Free shipping on orders over $100.</li>
      </ul>
      <p><b style="color:var(--text)">Returns</b></p>
      <ul class="list">
        <li>30-day return window for unworn items with tags.</li>
        <li>Easy exchanges for size/color within 30 days.</li>
        <li>Contact support to initiate a return.</li>
      </ul>
    </section>

    <!-- Contact Form -->
    <section class="contact-card" style="grid-column: 1 / -1">
      <h2>Contact Form</h2>
      <form id="contact-form" onsubmit="return submitContact(event)">
        <div class="form-row">
          <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" required placeholder="Your name">
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" required placeholder="you@example.com">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="topic">Topic</label>
            <select id="topic" required>
              <option value="">Select a topic</option>
              <option>Order Support</option>
              <option>Shipping & Returns</option>
              <option>Product Question</option>
              <option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label for="order-number">Order # (optional)</label>
            <input type="text" id="order-number" placeholder="#12345">
          </div>
        </div>
        <div class="form-group">
          <label for="message">Message</label>
          <textarea id="message" required placeholder="How can we help?"></textarea>
        </div>
        <button class="btn" type="submit">Send Message</button>
      </form>
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

  // Contact form submit (placeholder)
  function submitContact(e) {
    e.preventDefault();
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const topic = document.getElementById('topic').value;
    const message = document.getElementById('message').value.trim();

    if (!name || !email || !topic || !message) {
      alert('Please fill in all required fields.');
      return false;
    }

    alert('Thanks! Your message has been sent.');
    (document.getElementById('contact-form')).reset();
    return false;
  }
</script>

<?php include '../includes/footer.php'; ?>