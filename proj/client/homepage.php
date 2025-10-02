<?php
$page_title = 'Ecom Clothing — Home';
include '../includes/header.php';
?>

<style>
  /* Hero */
  .hero {
    position: relative;
    padding: 56px 0;
    border-bottom: 1px solid var(--border);
  }
  .hero-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 24px;
    align-items: center;
  }
  .hero h1 {
    margin: 0 0 12px;
    font-size: 42px;
    letter-spacing: -0.02em;
  }
  .hero p {
    margin: 0 0 18px;
    color: var(--muted);
  }
  .cta {
    display: inline-flex;
    gap: 10px;
  }
  .cta .btn {
    font-weight: 700;
  }
  .hero-card {
    background: linear-gradient(
      160deg,
      rgba(124, 58, 237, 0.18),
      rgba(16, 185, 129, 0.18)
    );
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 22px;
    min-height: 220px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
  }
  .hero-card .tag {
    display: inline-block;
    font-size: 12px;
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(0, 0, 0, 0.35);
    border: 1px solid var(--border);
  }
  .hero-card .illus {
    margin-top: 14px;
    height: 160px;
    border-radius: 12px;
    background: repeating-linear-gradient(
      135deg,
      rgba(255, 255, 255, 0.08) 0 10px,
      rgba(255, 255, 255, 0.02) 10px 20px
    );
  }

  /* Sections */
  .section {
    padding: 36px 0;
  }
  .section h2 {
    margin: 0 0 16px;
    font-size: 28px;
  }
  .grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
  }
  .card {
    background: rgba(17, 24, 39, 0.85);
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
    transition: transform 0.15s ease;
  }
  .card:hover {
    transform: translateY(-2px);
  }
  .thumb {
    height: 180px;
    background: linear-gradient(
      120deg,
      rgba(124, 58, 237, 0.25),
      rgba(16, 185, 129, 0.25)
    );
  }
  .info {
    padding: 12px;
  }
  .title {
    margin: 0 0 4px;
    font-weight: 700;
  }
  .price {
    color: #cbd5e1;
    font-weight: 600;
  }
  .badge {
    display: inline-block;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.06em;
    color: #0b1220;
    background: linear-gradient(90deg, #34d399, #60a5fa);
    padding: 4px 8px;
    border-radius: 999px;
  }

  @media (max-width: 900px) {
    .hero-grid {
      grid-template-columns: 1fr;
    }
    .grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  @media (max-width: 600px) {
    .grid {
      grid-template-columns: 1fr;
    }
  }
</style>

    <section class="hero">
      <div class="container hero-grid">
        <div>
          <h1>Fall Collection 2025</h1>
          <p>
            Discover premium essentials crafted for comfort and style. Men,
            Women, and Kids — curated looks for everyone.
          </p>
          <div class="cta">
            <a class="btn primary" href="#">Shop New Arrivals</a>
            <a class="btn" href="#">Explore Best Sellers</a>
          </div>
        </div>
        <div class="hero-card">
          <span class="tag">Limited Release</span>
          <div class="illus"></div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <h2>Featured — New Arrivals</h2>
        <div class="grid">
          <article class="card">
            <div class="thumb"></div>
            <div class="info">
              <p class="title">Essential Tee</p>
              <p class="price">$24.00</p>
            </div>
          </article>
          <article class="card">
            <div class="thumb"></div>
            <div class="info">
              <p class="title">Relaxed Hoodie</p>
              <p class="price">$59.00</p>
            </div>
          </article>
          <article class="card">
            <div class="thumb"></div>
            <div class="info">
              <p class="title">Denim Jacket</p>
              <p class="price">$89.00</p>
            </div>
          </article>
          <article class="card">
            <div class="thumb"></div>
            <div class="info">
              <p class="title">Athletic Joggers</p>
              <p class="price">$49.00</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <h2>Recommended For You</h2>
        <div class="grid">
          <article class="card">
            <div class="thumb"></div>
            <div class="info">
              <p class="title">Classic Chinos</p>
              <p class="price">$54.00</p>
            </div>
          </article>
          <article class="card">
            <div class="thumb"></div>
            <div class="info">
              <p class="title">Oversized Sweater</p>
              <p class="price">$64.00</p>
            </div>
          </article>
          <article class="card">
            <div class="thumb"></div>
            <div class="info">
              <p class="title">Puffer Jacket</p>
              <p class="price">$129.00</p>
            </div>
          </article>
          <article class="card">
            <div class="thumb"></div>
            <div class="info">
              <p class="title">Canvas Sneakers</p>
              <p class="price">$69.00</p>
            </div>
          </article>
        </div>
      </div>
    </section>

<?php include '../includes/footer.php'; ?>
