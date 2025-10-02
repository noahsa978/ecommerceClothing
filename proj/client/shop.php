<?php
$page_title = 'Shop — Ecom Clothing';
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
    background: linear-gradient(
      120deg,
      rgba(124, 58, 237, 0.25),
      rgba(16, 185, 129, 0.25)
    );
  }
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
                <li><a href="#">All Products</a></li>
                <li><a href="#">Tops</a></li>
                <li><a href="#">Bottoms</a></li>
                <li><a href="#">Dresses</a></li>
                <li><a href="#">Accessories</a></li>
              </ul>
            </div>

            <div id="male-categories" style="display: none">
              <ul class="category-list">
                <li><a href="#">Tops</a></li>
                <li><a href="#">Bottoms</a></li>
              </ul>
            </div>

            <div id="female-categories" style="display: none">
              <ul class="category-list">
                <li><a href="#">Tops</a></li>
                <li><a href="#">Bottoms</a></li>
                <li><a href="#">Dresses</a></li>
              </ul>
            </div>
          </div>

          <div class="sidebar-section">
            <h3>Filter By</h3>

            <div class="filter-group">
              <label for="price-range">Price Range</label>
              <div class="price-range">
                <input type="number" placeholder="Min" min="0" />
                <span>to</span>
                <input type="number" placeholder="Max" min="0" />
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

            <button class="btn primary" style="width: 100%; margin-top: 8px">
              Browse
            </button>
          </div>
        </aside>

        <section class="products-section">
          <div class="products-header">
            <h2>All Products</h2>
            <select class="sort-select">
              <option value="newest">Newest First</option>
              <option value="price-low">Price: Low to High</option>
              <option value="price-high">Price: High to Low</option>
              <option value="popular">Most Popular</option>
            </select>
          </div>

          <div class="products-grid">
            <article class="product-card">
              <div class="product-thumb"></div>
              <div class="product-info">
                <p class="product-title">Essential Tee</p>
                <p class="product-price">$24.00</p>
              </div>
            </article>

            <article class="product-card">
              <div class="product-thumb"></div>
              <div class="product-info">
                <p class="product-title">Classic Hoodie</p>
                <p class="product-price">$59.00</p>
              </div>
            </article>

            <article class="product-card">
              <div class="product-thumb"></div>
              <div class="product-info">
                <p class="product-title">Denim Jacket</p>
                <p class="product-price">$89.00</p>
              </div>
            </article>

            <article class="product-card">
              <div class="product-thumb"></div>
              <div class="product-info">
                <p class="product-title">Athletic Joggers</p>
                <p class="product-price">$49.00</p>
              </div>
            </article>

            <article class="product-card">
              <div class="product-thumb"></div>
              <div class="product-info">
                <p class="product-title">Oversized Sweater</p>
                <p class="product-price">$64.00</p>
              </div>
            </article>

            <article class="product-card">
              <div class="product-thumb"></div>
              <div class="product-info">
                <p class="product-title">Canvas Sneakers</p>
                <p class="product-price">$69.00</p>
              </div>
            </article>

            <article class="product-card">
              <div class="product-thumb"></div>
              <div class="product-info">
                <p class="product-title">Puffer Jacket</p>
                <p class="product-price">$129.00</p>
              </div>
            </article>

            <article class="product-card">
              <div class="product-thumb"></div>
              <div class="product-info">
                <p class="product-title">Classic Chinos</p>
                <p class="product-price">$54.00</p>
              </div>
            </article>
          </div>
        </section>
      </div>
    </main>

    <script>
      // Gender navigation functionality
      const genderButtons = document.querySelectorAll(".gender-btn");
      const allCategories = document.getElementById("all-categories");
      const maleCategories = document.getElementById("male-categories");
      const femaleCategories = document.getElementById("female-categories");

      genderButtons.forEach((button) => {
        button.addEventListener("click", () => {
          // Remove active class from all buttons
          genderButtons.forEach((btn) => btn.classList.remove("active"));
          // Add active class to clicked button
          button.classList.add("active");

          // Show/hide categories based on gender
          const gender = button.dataset.gender;
          if (gender === "all") {
            allCategories.style.display = "block";
            maleCategories.style.display = "none";
            femaleCategories.style.display = "none";
          } else if (gender === "male") {
            allCategories.style.display = "none";
            maleCategories.style.display = "block";
            femaleCategories.style.display = "none";
          } else {
            allCategories.style.display = "none";
            maleCategories.style.display = "none";
            femaleCategories.style.display = "block";
          }
        });
      });
    </script>

<?php include '../includes/footer.php'; ?>
