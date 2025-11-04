<?php
$page_title = 'Shop — Ecom Clothing';
require_once __DIR__ . '/../includes/db_connect.php';

// Fetch products with aggregate variant data
$products = [];
if ($conn instanceof mysqli) {
  $sql = "
    SELECT p.id, p.name, p.description, p.base_price, p.image, p.gender, p.category,
           COALESCE(NULLIF(TRIM(p.image), ''), NULL) AS prod_image,
           COALESCE(SUM(v.stock), 0) AS total_stock,
           MIN(COALESCE(v.price, p.base_price)) AS min_price,
           MAX(COALESCE(v.price, p.base_price)) AS max_price,
           GROUP_CONCAT(DISTINCT v.color ORDER BY v.color SEPARATOR ',') AS colors,
           GROUP_CONCAT(DISTINCT v.size ORDER BY FIELD(v.size,'XS','S','M','L','XL','XXL')) AS sizes,
           MAX(COALESCE(NULLIF(TRIM(v.image), ''), NULL)) AS variant_image,
           COUNT(DISTINCT r.id) AS review_count
      FROM products p
      LEFT JOIN product_variants v ON v.product_id = p.id
      LEFT JOIN reviews r ON r.product_id = p.id
     GROUP BY p.id
     ORDER BY p.created_at DESC
  ";
  if ($res = $conn->query($sql)) {
    while ($row = $res->fetch_assoc()) {
      $products[] = $row;
    }
  }
}

include '../includes/header.php';
?>

<style>
  /* Shop Layout */
  .shop-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 24px;
    padding: 24px 0;
    min-height: calc(100vh - 200px);
  }

  /* Sidebar */
  .sidebar {
    background: rgba(17, 24, 39, 0.85);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 20px;
    height: fit-content;
    position: sticky;
    top: 100px;
  }
  .sidebar h3 {
    margin: 0 0 16px;
    font-size: 18px;
    color: var(--text);
  }
  .sidebar-section {
    margin-bottom: 24px;
  }
  .sidebar-section:last-child {
    margin-bottom: 0;
  }

  /* Gender Navigation */
  .gender-nav {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
  }
  .gender-btn {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: #0b1220;
    color: #cbd5e1;
    text-align: center;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  .gender-btn.active {
    background: var(--accent);
    border-color: transparent;
    color: #fff;
  }
  .gender-btn:hover:not(.active) {
    background: rgba(124, 58, 237, 0.12);
    color: #fff;
  }

  /* Category Lists */
  .category-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  .category-list li {
    margin-bottom: 8px;
  }
  .category-list a {
    display: block;
    padding: 8px 12px;
    border-radius: 8px;
    color: #cbd5e1;
    transition: all 0.15s ease;
  }
  .category-list a:hover {
    background: rgba(124, 58, 237, 0.12);
    color: #fff;
  }

  /* Filter Groups */
  .filter-group {
    margin-bottom: 16px;
  }
  .filter-group:last-child {
    margin-bottom: 0;
  }
  .filter-group label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 8px;
    color: var(--text);
  }
  .filter-group select,
  .filter-group input {
    width: 100%;
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: #0b1220;
    color: var(--text);
    outline: none;
  }
  .price-range {
    display: flex;
    gap: 8px;
    align-items: center;
  }
  .price-range input {
    flex: 1;
  }
  .price-range span {
    color: var(--muted);
    font-size: 14px;
  }

  /* Products Grid */
  .products-section {
    background: rgba(17, 24, 39, 0.85);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 20px;
  }
  .products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }
  .products-header h2 {
    margin: 0;
    font-size: 24px;
  }
  .sort-select {
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: #0b1220;
    color: var(--text);
    outline: none;
  }
  .products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
  }
  .product-card {
    background: rgba(11, 18, 32, 0.6);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.15s ease;
  }
  .product-card:hover {
    transform: translateY(-2px);
  }
  .product-thumb {
    height: 200px;
    background: linear-gradient(120deg, rgba(124,58,237,0.15), rgba(16,185,129,0.15));
    display: grid;
    place-items: center;
    overflow: hidden;
  }
  .product-img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .product-info {
    padding: 12px;
  }
  .product-title {
    margin: 0 0 4px;
    font-weight: 700;
    font-size: 14px;
  }
  .product-price {
    color: #cbd5e1;
    font-weight: 600;
    font-size: 14px;
  }
  /* .product-desc removed: description appears only on details.php */
  .meta-row { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
  .stock { font-size: 12px; color: #9ca3af; }
  .badges { display: flex; gap: 6px; flex-wrap: wrap; }
  .badge-mini { display: inline-block; font-size: 11px; padding: 4px 8px; border-radius: 999px; border: 1px solid var(--border); color: #cbd5e1; background: rgba(255,255,255,0.04); }
  .product-info .actions { display: flex; justify-content: center; margin-top: 8px; }
  .btn.small { padding: 8px 10px; font-size: 12px; border-radius: 10px; }

  /* Search Box */
  .search-box {
    position: relative;
    margin-bottom: 16px;
  }
  .search-box input {
    width: 100%;
    padding: 12px 14px;
    padding-left: 40px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: rgba(11, 18, 32, 0.6);
    color: var(--text);
    outline: none;
    font-size: 14px;
  }
  .search-box input::placeholder {
    color: #748094;
  }
  .search-box .icon {
    position: absolute;
    top: 50%;
    left: 12px;
    transform: translateY(-50%);
    opacity: 0.7;
    font-size: 16px;
  }

  @media (max-width: 900px) {
    .shop-layout {
      grid-template-columns: 1fr;
    }
    .sidebar {
      position: static;
      order: 2;
    }
    .products-section {
      order: 1;
    }
  }
  @media (max-width: 600px) {
    .products-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
</style>

    <main class="container">
      <div class="shop-layout">
        <aside class="sidebar">
          <div class="sidebar-section">
            <h3>Browse By</h3>
            <div class="gender-nav">
              <button class="gender-btn active" data-gender="all">All</button>
              <button class="gender-btn" data-gender="male">Male</button>
              <button class="gender-btn" data-gender="female">Female</button>
            </div>

            <div id="all-categories">
              <ul class="category-list">
                <li><a href="#" data-category="all">All Products</a></li>
                <li><a href="#" data-category="Tops">Tops</a></li>
                <li><a href="#" data-category="Bottoms">Bottoms</a></li>
                <li><a href="#" data-category="Dresses">Dresses</a></li>
              </ul>
            </div>

            <div id="male-categories" style="display: none">
              <ul class="category-list">
                <li><a href="#" data-category="Tops">Tops</a></li>
                <li><a href="#" data-category="Bottoms">Bottoms</a></li>
              </ul>
            </div>

            <div id="female-categories" style="display: none">
              <ul class="category-list">
                <li><a href="#" data-category="Tops">Tops</a></li>
                <li><a href="#" data-category="Bottoms">Bottoms</a></li>
                <li><a href="#" data-category="Dresses">Dresses</a></li>
              </ul>
            </div>
          </div>

          <div class="sidebar-section">
            <h3>Filter By</h3>

            <div class="filter-group">
              <label for="price-range">Price Range</label>
              <div class="price-range">
                <input id="minPrice" type="number" placeholder="Min" min="0" />
                <span>to</span>
                <input id="maxPrice" type="number" placeholder="Max" min="0" />
              </div>
            </div>

            <div class="filter-group">
              <label for="size">Size</label>
              <select id="size">
                <option value="">All Sizes</option>
                <option value="xs">XS</option>
                <option value="s">S</option>
                <option value="m">M</option>
                <option value="l">L</option>
                <option value="xl">XL</option>
                <option value="xxl">XXL</option>
              </select>
            </div>

            <div class="filter-group">
              <label for="color">Color</label>
              <select id="color">
                <option value="">All Colors</option>
                <option value="black">Black</option>
                <option value="white">White</option>
                <option value="blue">Blue</option>
                <option value="red">Red</option>
                <option value="green">Green</option>
              </select>
            </div>

            <button id="applyFilters" class="btn primary" style="width: 100%; margin-top: 8px">
              Browse
            </button>
            <button id="resetFilters" class="btn" style="width: 100%; margin-top: 8px;">Reset</button>
          </div>
        </aside>

        <section class="products-section">
          <div class="search-box">
            <span class="icon">🔍</span>
            <input type="search" id="searchInput" placeholder="Search products by name..." />
          </div>
          <div class="products-header">
            <h2>All Products</h2>
            <select id="sortSelect" class="sort-select">
              <option value="newest">Newest First</option>
              <option value="price-low">Price: High to Low</option>
              <option value="price-high">Price: Low to High</option>
              <option value="popular">Most Popular</option>
            </select>
          </div>

          <div class="products-grid">
            <?php if (empty($products)) { ?>
              <div style="grid-column: 1 / -1; color: #9ca3af;">No products available.</div>
            <?php } else { foreach ($products as $p) {
              $img = $p['prod_image'] ?: ($p['variant_image'] ?: null);
              // Try product_images fallback if none
              if (!$img && ($conn instanceof mysqli)) {
                $pid = (int)$p['id'];
                if ($resImg = $conn->query("SELECT image_url FROM product_images WHERE product_id={$pid} ORDER BY sort_order ASC, id ASC LIMIT 1")) {
                  if ($rimg = $resImg->fetch_assoc()) { $img = $rimg['image_url']; }
                }
              }
              $imgUrl = '';
              if ($img) { $imgUrl = (stripos($img, 'http') === 0) ? $img : ('../' . ltrim($img, '/')); }
              $desc = trim((string)($p['description'] ?? ''));
              $descShort = $desc !== '' ? (mb_strlen($desc) > 110 ? (mb_substr($desc,0,110).'…') : $desc) : '';
              $stock = (int)$p['total_stock'];
              $min = (float)$p['min_price'];
              $max = (float)$p['max_price'];
              $priceLabel = ($min > 0 && $max > 0) ? (($min == $max) ? ("ETB " . number_format($min,2)) : ("ETB " . number_format($min,2) . "–ETB " . number_format($max,2))) : '';
              $popular = (int)($p['review_count'] ?? 0);
              $colors = array_filter(array_unique(array_map('trim', explode(',', (string)($p['colors'] ?? '')))));
              $sizes = array_filter(array_unique(array_map('trim', explode(',', (string)($p['sizes'] ?? '')))));
            ?>
              <article class="product-card" 
                       data-gender="<?= htmlspecialchars(strtolower($p['gender'] ?? '')) ?>"
                       data-category="<?= htmlspecialchars(strtolower($p['category'] ?? '')) ?>"
                       data-min-price="<?= htmlspecialchars((string)$min) ?>"
                       data-max-price="<?= htmlspecialchars((string)$max) ?>"
                       data-colors="<?= htmlspecialchars(implode(',', $colors)) ?>"
                       data-sizes="<?= htmlspecialchars(implode(',', $sizes)) ?>"
                       data-popular="<?= $popular ?>">
                <a href="./details.php?id=<?= (int)$p['id'] ?>" class="product-thumb" style="text-decoration:none; color:inherit;">
                  <?php if ($imgUrl) { ?>
                    <img class="product-img" src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($p['name']) ?>" />
                  <?php } else { ?>
                    <span style="color:#9ca3af; font-size:12px;">No image</span>
                  <?php } ?>
                </a>
                <div class="product-info">
                  <p class="product-title"><a href="./details.php?id=<?= (int)$p['id'] ?>" style="color:inherit; text-decoration:none;"><?= htmlspecialchars($p['name']) ?></a></p>
                  <?php if ($priceLabel !== '') { ?><p class="product-price"><?= $priceLabel ?></p><?php } ?>
                  <div class="meta-row">
                    <span class="stock">In stock: <?= $stock ?></span>
                    <div class="badges">
                      <?php if (!empty($colors)) { ?><span class="badge-mini">Colors: <?= htmlspecialchars(implode(', ', array_slice($colors,0,3))) ?><?= count($colors)>3?'+':'' ?></span><?php } ?>
                      <?php if (!empty($sizes)) { ?><span class="badge-mini">Sizes: <?= htmlspecialchars(implode(', ', $sizes)) ?></span><?php } ?>
                    </div>
                  </div>
                  <div class="actions">
                    <a class="btn small" href="./details.php?id=<?= (int)$p['id'] ?>">View Details</a>
                  </div>
                </div>
              </article>
            <?php } } ?>
          </div>
        </section>
      </div>
    </main>

    <script>
      // Elements
      const genderButtons = document.querySelectorAll('.gender-btn');
      const allCategories = document.getElementById('all-categories');
      const maleCategories = document.getElementById('male-categories');
      const femaleCategories = document.getElementById('female-categories');
      const categoryLinks = document.querySelectorAll('.category-list a[data-category]');
      const applyFiltersBtn = document.getElementById('applyFilters');
      const resetBtn = document.getElementById('resetFilters');
      const minPriceInput = document.getElementById('minPrice');
      const maxPriceInput = document.getElementById('maxPrice');
      const sizeSelect = document.getElementById('size');
      const colorSelect = document.getElementById('color');
      const productCards = Array.from(document.querySelectorAll('.product-card'));
      const productsGrid = document.querySelector('.products-grid');
      const sortSelect = document.getElementById('sortSelect');
      const searchInput = document.getElementById('searchInput');
      // remember original order (newest first from SQL)
      productCards.forEach((card, idx) => card.setAttribute('data-index', String(idx)));

      // State
      let selectedGender = 'all';
      let selectedCategory = 'all';
      let searchQuery = '';

      // Helpers
      function normalize(v){ return (v || '').toString().trim().toLowerCase(); }
      function parseNum(v){ const n = parseFloat(v); return isNaN(n) ? null : n; }

      function applyFilters(){
        const minP = parseNum(minPriceInput.value);
        const maxP = parseNum(maxPriceInput.value);
        const selSize = normalize(sizeSelect.value);
        const selColor = normalize(colorSelect.value);
        const query = searchQuery.toLowerCase().trim();

        productCards.forEach(card => {
          const g = normalize(card.getAttribute('data-gender'));
          const c = normalize(card.getAttribute('data-category'));
          const cardMin = parseNum(card.getAttribute('data-min-price'));
          const cardMax = parseNum(card.getAttribute('data-max-price'));
          const sizes = normalize(card.getAttribute('data-sizes')).split(',').filter(Boolean);
          const colors = normalize(card.getAttribute('data-colors')).split(',').filter(Boolean);

          // Search filter (by product name)
          if (query) {
            const productName = card.querySelector('.product-title')?.textContent?.toLowerCase() || '';
            if (!productName.includes(query)) { card.style.display = 'none'; return; }
          }

          // Gender filter
          if (selectedGender !== 'all' && g !== selectedGender) { card.style.display = 'none'; return; }
          // Category filter
          if (selectedCategory !== 'all' && c !== normalize(selectedCategory)) { card.style.display = 'none'; return; }
          // Price filter (range overlap)
          if (minP !== null && (cardMax === null || cardMax < minP)) { card.style.display = 'none'; return; }
          if (maxP !== null && (cardMin === null || cardMin > maxP)) { card.style.display = 'none'; return; }
          // Size filter
          if (selSize && sizes.length && !sizes.includes(selSize)) { card.style.display = 'none'; return; }
          // Color filter
          if (selColor && colors.length && !colors.includes(selColor)) { card.style.display = 'none'; return; }

          card.style.display = '';
        });
        sortAndRender();
      }

      function sortAndRender(){
        if (!productsGrid) return;
        const mode = sortSelect ? sortSelect.value : 'newest';
        const cards = productCards.slice();
        cards.sort((a, b) => {
          const aMin = parseNum(a.getAttribute('data-min-price')) ?? 0;
          const aMax = parseNum(a.getAttribute('data-max-price')) ?? 0;
          const bMin = parseNum(b.getAttribute('data-min-price')) ?? 0;
          const bMax = parseNum(b.getAttribute('data-max-price')) ?? 0;
          const aIdx = parseInt(a.getAttribute('data-index') || '0', 10);
          const bIdx = parseInt(b.getAttribute('data-index') || '0', 10);
          const aPop = parseInt(a.getAttribute('data-popular') || '0', 10);
          const bPop = parseInt(b.getAttribute('data-popular') || '0', 10);
          if (mode === 'price-low') {
            // As requested: show highest to lowest when 'Low to High' is selected
            return (bMax - aMax);
          }
          if (mode === 'price-high') {
            // Opposite: show lowest to highest when 'High to Low' is selected
            return (aMin - bMin);
          }
          if (mode === 'popular') {
            return (bPop - aPop);
          }
          // default: keep SQL order (by original index)
          return (aIdx - bIdx);
        });
        cards.forEach(card => productsGrid.appendChild(card));
      }

      // Gender navigation and category sections
      genderButtons.forEach(button => {
        button.addEventListener('click', () => {
          genderButtons.forEach(b => b.classList.remove('active'));
          button.classList.add('active');
          selectedGender = normalize(button.dataset.gender);

          if (selectedGender === 'all') {
            allCategories.style.display = 'block';
            maleCategories.style.display = 'none';
            femaleCategories.style.display = 'none';
          } else if (selectedGender === 'male') {
            allCategories.style.display = 'none';
            maleCategories.style.display = 'block';
            femaleCategories.style.display = 'none';
          } else {
            allCategories.style.display = 'none';
            maleCategories.style.display = 'none';
            femaleCategories.style.display = 'block';
          }

          // Reset category on gender change
          selectedCategory = 'all';
          applyFilters();
        });
      });

      // Category selection
      categoryLinks.forEach(link => {
        link.addEventListener('click', (e) => {
          e.preventDefault();
          selectedCategory = link.getAttribute('data-category') || 'all';
          applyFilters();
        });
      });

      // Apply button for price/size/color
      if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', (e) => {
          e.preventDefault();
          applyFilters();
        });
      }

      if (sortSelect) {
        sortSelect.addEventListener('change', () => {
          sortAndRender();
        });
      }

      // Search input handler
      if (searchInput) {
        searchInput.addEventListener('input', (e) => {
          searchQuery = e.target.value;
          applyFilters();
        });
      }

      // Initial filter (none)
      applyFilters();

      // Reset filters
      if (resetBtn) {
        resetBtn.addEventListener('click', (e) => {
          e.preventDefault();
          // Reset state
          selectedGender = 'all';
          selectedCategory = 'all';
          searchQuery = '';
          // Reset UI
          genderButtons.forEach(b => b.classList.remove('active'));
          const allBtn = Array.from(genderButtons).find(b => b.dataset.gender === 'all');
          if (allBtn) allBtn.classList.add('active');
          allCategories.style.display = 'block';
          maleCategories.style.display = 'none';
          femaleCategories.style.display = 'none';
          // Clear inputs
          if (minPriceInput) minPriceInput.value = '';
          if (maxPriceInput) maxPriceInput.value = '';
          if (sizeSelect) sizeSelect.value = '';
          if (colorSelect) colorSelect.value = '';
          if (searchInput) searchInput.value = '';
          // Apply
          applyFilters();
        });
      }
    </script>

<?php include '../includes/footer.php'; ?>
