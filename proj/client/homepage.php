<?php
$page_title = 'Ecom Clothing — Home';
require_once __DIR__ . '/../includes/db_connect.php';
// Load banner path from site_assets
$bannerRel = '';
if ($conn instanceof mysqli) {
  if ($res = $conn->query('SELECT banner_path FROM site_assets WHERE id=1 LIMIT 1')) {
    if ($row = $res->fetch_assoc()) { $bannerRel = (string)($row['banner_path'] ?? ''); }
  }
}
include '../includes/header.php';
  // Load 4 newest products with price range and an image
  $newArrivals = [];
  if ($conn instanceof mysqli) {
    $sql = "
      SELECT p.id, p.name, p.base_price, p.image, p.created_at,
             COALESCE(SUM(v.stock), 0) AS total_stock,
             MIN(COALESCE(v.price, p.base_price)) AS min_price,
             MAX(COALESCE(v.price, p.base_price)) AS max_price,
             GROUP_CONCAT(DISTINCT v.color ORDER BY v.color SEPARATOR ',') AS colors,
             GROUP_CONCAT(DISTINCT v.size ORDER BY FIELD(v.size,'XS','S','M','L','XL','XXL')) AS sizes,
             MAX(COALESCE(NULLIF(TRIM(v.image), ''), NULL)) AS variant_image
        FROM products p
        LEFT JOIN product_variants v ON v.product_id = p.id
       GROUP BY p.id
       ORDER BY p.created_at DESC
       LIMIT 4
    ";
    if ($res = $conn->query($sql)) {
      while ($row = $res->fetch_assoc()) { $newArrivals[] = $row; }
      $res->free();
    }
  }
  // Load 4 best rated products (by average rating)
  $bestRated = [];
  if ($conn instanceof mysqli) {
    $sqlBest = "
      SELECT p.id, p.name, p.base_price, p.image,
             COALESCE(SUM(v.stock), 0) AS total_stock,
             MIN(COALESCE(v.price, p.base_price)) AS min_price,
             MAX(COALESCE(v.price, p.base_price)) AS max_price,
             GROUP_CONCAT(DISTINCT v.color ORDER BY v.color SEPARATOR ',') AS colors,
             GROUP_CONCAT(DISTINCT v.size ORDER BY FIELD(v.size,'XS','S','M','L','XL','XXL')) AS sizes,
             MAX(COALESCE(NULLIF(TRIM(v.image), ''), NULL)) AS variant_image,
             AVG(r.rating) AS avg_rating,
             COUNT(r.id) AS review_count
        FROM products p
        LEFT JOIN product_variants v ON v.product_id = p.id
        LEFT JOIN reviews r ON r.product_id = p.id
       GROUP BY p.id
      HAVING review_count > 0
       ORDER BY avg_rating DESC, review_count DESC, p.created_at DESC
       LIMIT 4
    ";
    if ($resB = $conn->query($sqlBest)) {
      while ($row = $resB->fetch_assoc()) { $bestRated[] = $row; }
      $resB->free();
    }
  }
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
    display: grid;
    grid-template-rows: auto 1fr; /* tag then image area */
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
    height: 100%;
    border-radius: 12px;
    background: repeating-linear-gradient(
      135deg,
      rgba(255, 255, 255, 0.08) 0 10px,
      rgba(255, 255, 255, 0.02) 10px 20px
    );
  }
  .hero-card .banner-img {
    margin-top: 14px;
    height: 100%;
    width: 100%;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid var(--border);
    display: block;
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
    padding: 14px;
  }
  .title {
    margin: 0 0 8px;
    font-weight: 700;
    font-size: 16px;
  }
  .price {
    color: #10b981;
    font-weight: 700;
    font-size: 18px;
    margin: 0 0 10px;
  }
  .meta-row {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 12px;
  }
  .stock {
    font-size: 13px;
    color: #94a3b8;
  }
  .badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }
  .badge-mini {
    display: inline-block;
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 6px;
    background: rgba(124, 58, 237, 0.15);
    border: 1px solid rgba(124, 58, 237, 0.3);
    color: #c4b5fd;
  }
  .btn-view {
    display: block;
    width: 100%;
    padding: 10px;
    background: linear-gradient(135deg, #7c3aed, #10b981);
    color: #fff;
    text-align: center;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .btn-view:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.4);
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
            <a class="btn primary" href="#new-arrivals">Shop New Arrivals</a>
            <a class="btn" href="#best-rated">Best Rated Products</a>
          </div>
        </div>
        <div class="hero-card">
          <span class="tag">Limited Release</span>
          <?php $bannerUrl = $bannerRel ? ('../' . ltrim($bannerRel, '/')) : ''; ?>
          <?php if ($bannerUrl !== '') { ?>
            <img class="banner-img" src="<?= htmlspecialchars($bannerUrl) ?>" alt="Promotional banner" />
          <?php } else { ?>
            <div class="illus"></div>
          <?php } ?>
        </div>
      </div>
    </section>

    <section class="section" id="new-arrivals">
      <div class="container">
        <h2>Featured — New Arrivals</h2>
        <div class="grid">
          <?php if (empty($newArrivals)) { ?>
            <article class="card" style="grid-column: 1 / -1;">
              <div class="info" style="padding:16px; color:#9ca3af;">No new products yet.</div>
            </article>
          <?php } else { foreach ($newArrivals as $p) {
            $img = $p['image'] ?: ($p['variant_image'] ?: null);
            if (!$img && ($conn instanceof mysqli)) {
              $pid = (int)$p['id'];
              if ($resImg = $conn->query("SELECT image_url FROM product_images WHERE product_id={$pid} ORDER BY sort_order ASC, id ASC LIMIT 1")) {
                if ($rimg = $resImg->fetch_assoc()) { $img = $rimg['image_url']; }
              }
            }
            $imgUrl = $img ? ((stripos($img,'http')===0) ? $img : ('../' . ltrim($img,'/'))) : '';
            $min = (float)($p['min_price'] ?? $p['base_price']);
            $max = (float)($p['max_price'] ?? $p['base_price']);
            $priceLabel = ($min > 0 && $max > 0)
              ? (($min == $max) ? ('ETB ' . number_format($min,2)) : ('ETB ' . number_format($min,2) . '–ETB ' . number_format($max,2)))
              : '';
            $stock = (int)($p['total_stock'] ?? 0);
            $colors = array_filter(array_unique(array_map('trim', explode(',', (string)($p['colors'] ?? '')))));
            $sizes = array_filter(array_unique(array_map('trim', explode(',', (string)($p['sizes'] ?? '')))));
          ?>
            <article class="card">
              <a href="./details.php?id=<?= (int)$p['id'] ?>" class="thumb" style="display:block; text-decoration:none; color:inherit;">
                <?php if ($imgUrl) { ?>
                  <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width:100%; height:100%; object-fit:cover; display:block;" />
                <?php } ?>
              </a>
              <div class="info">
                <p class="title"><a href="./details.php?id=<?= (int)$p['id'] ?>" style="color:inherit; text-decoration:none;">
                  <?= htmlspecialchars($p['name']) ?></a></p>
                <?php if ($priceLabel !== '') { ?><p class="price"><?= $priceLabel ?></p><?php } ?>
                <div class="meta-row">
                  <span class="stock">📦 In stock: <?= $stock ?></span>
                  <div class="badges">
                    <?php if (!empty($colors)) { ?><span class="badge-mini">🎨 <?= htmlspecialchars(implode(', ', array_slice($colors,0,3))) ?><?= count($colors)>3?' +more':'' ?></span><?php } ?>
                    <?php if (!empty($sizes)) { ?><span class="badge-mini">📏 <?= htmlspecialchars(implode(', ', $sizes)) ?></span><?php } ?>
                  </div>
                </div>
                <a href="./details.php?id=<?= (int)$p['id'] ?>" class="btn-view">View Details</a>
              </div>
            </article>
          <?php } } ?>
        </div>
      </div>
    </section>

    <section class="section" id="best-rated">
      <div class="container">
        <h2>Best Rated Products</h2>
        <div class="grid">
          <?php if (empty($bestRated)) { ?>
            <article class="card" style="grid-column: 1 / -1;">
              <div class="info" style="padding:16px; color:#9ca3af;">No rated products yet.</div>
            </article>
          <?php } else { foreach ($bestRated as $p) {
            $img = $p['image'] ?: ($p['variant_image'] ?: null);
            if (!$img && ($conn instanceof mysqli)) {
              $pid = (int)$p['id'];
              if ($resImg = $conn->query("SELECT image_url FROM product_images WHERE product_id={$pid} ORDER BY sort_order ASC, id ASC LIMIT 1")) {
                if ($rimg = $resImg->fetch_assoc()) { $img = $rimg['image_url']; }
              }
            }
            $imgUrl = $img ? ((stripos($img,'http')===0) ? $img : ('../' . ltrim($img,'/'))) : '';
            $min = (float)($p['min_price'] ?? $p['base_price']);
            $max = (float)($p['max_price'] ?? $p['base_price']);
            $priceLabel = ($min > 0 && $max > 0)
              ? (($min == $max) ? ('ETB ' . number_format($min,2)) : ('ETB ' . number_format($min,2) . '–ETB ' . number_format($max,2)))
              : '';
            $stock = (int)($p['total_stock'] ?? 0);
            $colors = array_filter(array_unique(array_map('trim', explode(',', (string)($p['colors'] ?? '')))));
            $sizes = array_filter(array_unique(array_map('trim', explode(',', (string)($p['sizes'] ?? '')))));
          ?>
            <article class="card">
              <a href="./details.php?id=<?= (int)$p['id'] ?>" class="thumb" style="display:block; text-decoration:none; color:inherit;">
                <?php if ($imgUrl) { ?>
                  <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width:100%; height:100%; object-fit:cover; display:block;" />
                <?php } ?>
              </a>
              <div class="info">
                <p class="title"><a href="./details.php?id=<?= (int)$p['id'] ?>" style="color:inherit; text-decoration:none;">
                  <?= htmlspecialchars($p['name']) ?></a></p>
                <?php if ($priceLabel !== '') { ?><p class="price"><?= $priceLabel ?></p><?php } ?>
                <div class="meta-row">
                  <span class="stock">📦 In stock: <?= $stock ?></span>
                  <div class="badges">
                    <?php if (!empty($colors)) { ?><span class="badge-mini">🎨 <?= htmlspecialchars(implode(', ', array_slice($colors,0,3))) ?><?= count($colors)>3?' +more':'' ?></span><?php } ?>
                    <?php if (!empty($sizes)) { ?><span class="badge-mini">📏 <?= htmlspecialchars(implode(', ', $sizes)) ?></span><?php } ?>
                  </div>
                </div>
                <a href="./details.php?id=<?= (int)$p['id'] ?>" class="btn-view">View Details</a>
              </div>
            </article>
          <?php } } ?>
        </div>
      </div>
    </section>

<?php include '../includes/footer.php'; ?>
