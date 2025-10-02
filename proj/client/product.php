<?php
$page_title = 'Product — Ecom Clothing';
include '../includes/header.php';
?>

<style>
  .product-page { padding: 28px 0 }
  .product-grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 24px }

  /* Gallery */
  .gallery { display: grid; grid-template-columns: 100px 1fr; gap: 12px }
  .thumbs { display: grid; gap: 8px }
  .thumbs button { padding: 0; border: 1px solid var(--border); border-radius: 10px; background: transparent; cursor: pointer; overflow: hidden }
  .thumbs img { display: block; width: 100%; height: 90px; object-fit: cover; transition: transform .2s ease }
  .thumbs img:hover { transform: scale(1.03) }
  .main-image { position: relative; border: 1px solid var(--border); border-radius: 14px; overflow: hidden; background: #0b1220 }
  .main-image img { width: 100%; height: 520px; object-fit: cover; transition: transform .2s ease }
  .main-image:hover img { transform: scale(1.15) }
  .zoom-hint { position: absolute; bottom: 8px; right: 12px; font-size: 12px; color: #9ca3af; background: rgba(0,0,0,.35); border: 1px solid var(--border); padding: 4px 8px; border-radius: 999px }

  /* Details */
  .details { background: rgba(17,24,39,0.85); border: 1px solid var(--border); border-radius: 16px; padding: 20px }
  .product-title { margin: 0 0 8px; font-size: 28px; letter-spacing: -0.01em }
  .price-row { display: flex; align-items: baseline; gap: 10px; margin-bottom: 8px }
  .price { font-size: 24px; font-weight: 800 }
  .old-price { color: #9ca3af; text-decoration: line-through }
  .stock { font-size: 14px; color: #10b981; margin-bottom: 12px }
  .description { color: #cbd5e1; line-height: 1.55; margin-bottom: 16px }

  .variants { display: grid; gap: 12px; margin: 16px 0 }
  .variants label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px }
  select, .qty { width: 100%; max-width: 240px; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: #0b1220; color: var(--text) }
  .actions { display: flex; gap: 10px; margin-top: 14px }
  .btn.secondary { background: transparent; border-color: var(--border) }

  /* Reviews */
  .reviews { margin-top: 28px; background: rgba(17,24,39,0.85); border: 1px solid var(--border); border-radius: 16px; padding: 20px }
  .reviews-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px }
  .stars { color: #fbbf24; letter-spacing: 1px; font-size: 16px }
  .review { border-top: 1px solid var(--border); padding: 12px 0 }
  .review:first-of-type { border-top: 0; padding-top: 0 }
  .review .meta { display: flex; gap: 8px; color: #9ca3af; font-size: 13px; margin-bottom: 6px }

  @media (max-width: 900px) {
    .product-grid { grid-template-columns: 1fr }
    .gallery { grid-template-columns: 1fr; }
    .thumbs { grid-template-columns: repeat(4, 1fr) }
    .thumbs img { height: 70px }
    .main-image img { height: 360px }
  }
</style>

<main class="container product-page">
  <div class="product-grid">
    <section class="gallery">
      <div class="thumbs">
        <button type="button"><img src="https://images.unsplash.com/photo-1520975954732-35dd222996f2?q=80&w=800&auto=format&fit=crop" alt="Thumb 1" data-full="https://images.unsplash.com/photo-1520975954732-35dd222996f2?q=80&w=1600&auto=format&fit=crop" /></button>
        <button type="button"><img src="https://images.unsplash.com/photo-1520975926867-b9d9f7cb7da3?q=80&w=800&auto=format&fit=crop" alt="Thumb 2" data-full="https://images.unsplash.com/photo-1520975926867-b9d9f7cb7da3?q=80&w=1600&auto=format&fit=crop" /></button>
        <button type="button"><img src="https://images.unsplash.com/photo-1520975918311-77d9d6dd0a5f?q=80&w=800&auto=format&fit=crop" alt="Thumb 3" data-full="https://images.unsplash.com/photo-1520975918311-77d9d6dd0a5f?q=80&w=1600&auto=format&fit=crop" /></button>
        <button type="button"><img src="https://images.unsplash.com/photo-1520975862215-5946d8a3a8bf?q=80&w=800&auto=format&fit=crop" alt="Thumb 4" data-full="https://images.unsplash.com/photo-1520975862215-5946d8a3a8bf?q=80&w=1600&auto=format&fit=crop" /></button>
      </div>
      <div class="main-image">
        <img id="mainImage" src="https://images.unsplash.com/photo-1520975954732-35dd222996f2?q=80&w=1600&auto=format&fit=crop" alt="Product image" />
        <span class="zoom-hint">Hover to zoom</span>
      </div>
    </section>

    <section class="details">
      <h1 class="product-title">Premium Cotton Tee</h1>
      <div class="price-row">
        <span class="price">$24.00</span>
        <span class="old-price">$29.00</span>
        <span class="badge">NEW</span>
      </div>
      <div class="stock">In Stock • Ships in 24h</div>
      <div class="description">
        Soft, breathable premium cotton tee designed for everyday comfort. Tailored fit with reinforced seams and pre-shrunk fabric to maintain shape after wash.
      </div>

      <div class="variants">
        <div>
          <label for="size">Size</label>
          <select id="size">
            <option value="">Select size</option>
            <option value="s">S</option>
            <option value="m">M</option>
            <option value="l">L</option>
            <option value="xl">XL</option>
            <option value="xxl">XXL</option>
          </select>
        </div>
        <div>
          <label for="color">Color</label>
          <select id="color">
            <option value="">Select color</option>
            <option value="black">Black</option>
            <option value="white">White</option>
            <option value="navy">Navy</option>
            <option value="olive">Olive</option>
          </select>
        </div>
        <div>
          <label for="qty">Quantity</label>
          <input class="qty" id="qty" type="number" min="1" value="1" />
        </div>
      </div>

      <div class="actions">
        <button class="btn primary" type="button">Add to Cart</button>
        <button class="btn secondary" type="button">Add to Wishlist</button>
      </div>
    </section>
  </div>

  <section class="reviews">
    <div class="reviews-header">
      <h2 style="margin:0;font-size:20px">Customer Reviews</h2>
      <div class="stars" aria-label="Average rating 4.5 out of 5">★★★★☆ (4.5)</div>
    </div>

    <article class="review">
      <div class="meta"><strong>Jane D.</strong> • ★★★★★ • 2 days ago</div>
      <p style="margin:0;color:#cbd5e1">Great fit and very comfortable. The fabric feels premium and holds up after washing.</p>
    </article>
    <article class="review">
      <div class="meta"><strong>Alex R.</strong> • ★★★★☆ • 1 week ago</div>
      <p style="margin:0;color:#cbd5e1">Color matches the photos. I sized up for a looser look and its perfect.</p>
    </article>
  </section>
</main>

<script>
  // Simple gallery switcher
  document.querySelectorAll('.thumbs img').forEach(function(img){
    img.addEventListener('click', function(){
      var full = img.getAttribute('data-full') || img.src;
      var main = document.getElementById('mainImage');
      main.src = full;
    });
  });
</script>

<?php include '../includes/footer.php'; ?>