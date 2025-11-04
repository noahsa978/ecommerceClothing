<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$page_title = 'Product Details — Ecom Clothing';
require_once __DIR__ . '/../includes/db_connect.php';

$flash = null;

// Read and validate product id
$pid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pid <= 0) {
  header('Location: ./shop.php');
  exit;
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'add_to_cart' && ($conn instanceof mysqli)) {
  $selSize = trim($_POST['size'] ?? '');
  $selColor = trim($_POST['color'] ?? '');
  $qty = max(1, (int)($_POST['qty'] ?? 1));

  // Find variant by selection
  $variant = null;
  if ($stmt = $conn->prepare('SELECT id, size, color, stock, price, image FROM product_variants WHERE product_id=? AND (size = ? OR ? = "") AND (color = ? OR ? = "") ORDER BY (size = ?) DESC, (color = ?) DESC LIMIT 1')) {
    $stmt->bind_param('issssss', $pid, $selSize, $selSize, $selColor, $selColor, $selSize, $selColor);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      $variant = $res->fetch_assoc();
    }
    $stmt->close();
  }

  if (!$variant) {
    $flash = ['type' => 'error', 'msg' => 'Please select a valid variant.'];
  } else {
    // Load product basic info
    $prod = null;
    if ($st2 = $conn->prepare('SELECT name, base_price, image FROM products WHERE id=? LIMIT 1')) {
      $st2->bind_param('i', $pid);
      if ($st2->execute()) { $prod = $st2->get_result()->fetch_assoc(); }
      $st2->close();
    }
    $name = $prod['name'] ?? 'Product';
    $basePrice = (float)($prod['base_price'] ?? 0);
    $price = (float)($variant['price'] ?? $basePrice);
    $vImage = $variant['image'] ?: ($prod['image'] ?? '');
    $imgUrl = $vImage ? ((stripos($vImage, 'http') === 0) ? $vImage : ('../' . ltrim($vImage, '/'))) : '';

    // Initialize cart (session for immediate UI use)
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) { $_SESSION['cart'] = []; }
    $key = 'v' . (int)$variant['id'];
    if (!isset($_SESSION['cart'][$key])) {
      $_SESSION['cart'][$key] = [
        'variant_id' => (int)$variant['id'],
        'product_id' => $pid,
        'name' => $name,
        'size' => (string)$variant['size'],
        'color' => (string)$variant['color'],
        'qty' => $qty,
        'price' => $price,
        'image' => $imgUrl,
      ];
    } else {
      $_SESSION['cart'][$key]['qty'] += $qty;
    }
    
    // Persist to DB carts/cart_items (transactional)
    try {
      if ($conn instanceof mysqli) {
        $conn->begin_transaction();
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $sessionId = session_id();
        $cartId = null;

        // Lookup by user if logged in
        $userCartId = null; $sessionCartId = null;
        if ($userId) {
          if ($st = $conn->prepare('SELECT id FROM carts WHERE user_id = ? AND status = "active" ORDER BY id DESC LIMIT 1')) {
            $st->bind_param('i', $userId);
            if ($st->execute()) { $rs = $st->get_result(); if ($row = $rs->fetch_assoc()) { $userCartId = (int)$row['id']; } }
            $st->close();
          }
          if ($sessionId) {
            if ($st = $conn->prepare('SELECT id FROM carts WHERE session_id = ? AND status = "active" ORDER BY id DESC LIMIT 1')) {
              $st->bind_param('s', $sessionId);
              if ($st->execute()) { $rs = $st->get_result(); if ($row = $rs->fetch_assoc()) { $sessionCartId = (int)$row['id']; } }
              $st->close();
            }
          }
          // If both exist and are different, merge session cart into user cart
          if ($userCartId !== null && $sessionCartId !== null && $userCartId !== $sessionCartId) {
            // Load items from guest/session cart
            if ($st = $conn->prepare('SELECT variant_id, quantity FROM cart_items WHERE cart_id = ?')) {
              $st->bind_param('i', $sessionCartId);
              if ($st->execute()) {
                $rs = $st->get_result();
                while ($it = $rs->fetch_assoc()) {
                  $vid = (int)$it['variant_id'];
                  $qGuest = (int)$it['quantity'];
                  // Get stock for variant
                  $stock = null;
                  if ($stv = $conn->prepare('SELECT stock FROM product_variants WHERE id = ?')) {
                    $stv->bind_param('i', $vid);
                    if ($stv->execute()) { $rv = $stv->get_result(); if ($rw = $rv->fetch_assoc()) { $stock = (int)$rw['stock']; } }
                    $stv->close();
                  }
                  // Existing qty in user cart
                  $existId = null; $existQty = 0;
                  if ($stx = $conn->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = ? AND variant_id = ? LIMIT 1')) {
                    $stx->bind_param('ii', $userCartId, $vid);
                    if ($stx->execute()) { $rx = $stx->get_result(); if ($rw = $rx->fetch_assoc()) { $existId = (int)$rw['id']; $existQty = (int)$rw['quantity']; } }
                    $stx->close();
                  }
                  $canAdd = ($stock === null) ? $qGuest : max(0, $stock - $existQty);
                  $toAdd = min($qGuest, $canAdd);
                  if ($toAdd > 0) {
                    if ($existId) {
                      if ($stu = $conn->prepare('UPDATE cart_items SET quantity = quantity + ? WHERE id = ?')) { $stu->bind_param('ii', $toAdd, $existId); $stu->execute(); $stu->close(); }
                    } else {
                      // Use current price snapshot from variant or product base
                      $pSnap = $price; // fallback to the price being added now
                      if ($stp = $conn->prepare('SELECT price FROM product_variants WHERE id = ?')) { $stp->bind_param('i', $vid); if ($stp->execute()) { $rp=$stp->get_result(); if ($rw=$rp->fetch_assoc()) { $pSnap = (float)($rw['price'] ?? $pSnap); } } $stp->close(); }
                      if ($sti = $conn->prepare('INSERT INTO cart_items (cart_id, variant_id, quantity, price) VALUES (?, ?, ?, ?)')) { $sti->bind_param('iiid', $userCartId, $vid, $toAdd, $pSnap); $sti->execute(); $sti->close(); }
                    }
                  }
                }
              }
              $st->close();
            }
            // Delete guest cart (items cascade)
            if ($std = $conn->prepare('DELETE FROM carts WHERE id = ?')) { $std->bind_param('i', $sessionCartId); $std->execute(); $std->close(); }
            $cartId = $userCartId;
          } else if ($userCartId !== null) {
            $cartId = $userCartId;
          } else if ($sessionCartId !== null) {
            // adopt session cart to user
            if ($st = $conn->prepare('UPDATE carts SET user_id = ? WHERE id = ?')) { $st->bind_param('ii', $userId, $sessionCartId); $st->execute(); $st->close(); }
            $cartId = $sessionCartId;
          }
        } else {
          // Guest: use session cart
          if ($sessionId) {
            if ($st = $conn->prepare('SELECT id FROM carts WHERE session_id = ? AND status = "active" ORDER BY id DESC LIMIT 1')) {
              $st->bind_param('s', $sessionId);
              if ($st->execute()) { $rs = $st->get_result(); if ($row = $rs->fetch_assoc()) { $cartId = (int)$row['id']; } }
              $st->close();
            }
          }
        }

        // Create cart if needed
        if ($cartId === null) {
          if ($st = $conn->prepare('INSERT INTO carts (user_id, session_id, status) VALUES (?, ?, "active")')) {
            if ($userId) { $st->bind_param('is', $userId, $sessionId); }
            else { $null = null; $st->bind_param('is', $null, $sessionId); }
            if ($st->execute()) { $cartId = (int)$conn->insert_id; }
            $st->close();
          }
        }

        // Upsert cart item (by variant)
        if ($cartId !== null) {
          $existingId = null; $existingQty = 0;
          if ($st = $conn->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = ? AND variant_id = ? LIMIT 1')) {
            $vid = (int)$variant['id'];
            $st->bind_param('ii', $cartId, $vid);
            if ($st->execute()) { $rs = $st->get_result(); if ($row = $rs->fetch_assoc()) { $existingId = (int)$row['id']; $existingQty = (int)$row['quantity']; } }
            $st->close();
          }
          // Stock safeguard: cap add based on variant stock
          $stockAvailable = isset($variant['stock']) ? (int)$variant['stock'] : null;
          $maxAllowed = ($stockAvailable === null) ? $qty : max(0, $stockAvailable - $existingQty);
          $qtyToAdd = min($qty, $maxAllowed);
          if ($qtyToAdd <= 0) {
            // No stock to add; rollback changes and show flash
            $conn->rollback();
            $flash = ['type' => 'error', 'msg' => 'Requested quantity exceeds available stock.'];
            throw new Exception('Out of stock');
          }
          if ($existingId) {
            if ($st = $conn->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?')) {
              $newQty = $existingQty + $qtyToAdd;
              $st->bind_param('ii', $newQty, $existingId);
              $st->execute();
              $st->close();
            }
          } else {
            if ($st = $conn->prepare('INSERT INTO cart_items (cart_id, variant_id, quantity, price) VALUES (?, ?, ?, ?)')) {
              $vid = (int)$variant['id'];
              $q = $qtyToAdd; $pSnapshot = $price;
              $st->bind_param('iiid', $cartId, $vid, $q, $pSnapshot);
              $st->execute();
              $st->close();
            }
          }
          // Touch the cart to update updated_at
          if ($st = $conn->prepare('UPDATE carts SET updated_at = NOW() WHERE id = ?')) { $st->bind_param('i', $cartId); $st->execute(); $st->close(); }
        }

        $conn->commit();
      }
    } catch (Throwable $e) {
      if ($conn instanceof mysqli) { $conn->rollback(); }
      // Keep session cart even if DB op fails
    }
    $flash = ['type' => 'success', 'msg' => 'Added to cart.'];
  }
}

// Handle Add Review (logged-in users)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'add_review' && ($conn instanceof mysqli)) {
  $uid = (int)($_SESSION['user_id'] ?? 0);
  $rating = (int)($_POST['rating'] ?? 0);
  $comment = trim($_POST['comment'] ?? '');
  if ($uid <= 0) {
    $flash = ['type' => 'error', 'msg' => 'Please log in to add a review.'];
  } elseif ($rating < 1 || $rating > 5) {
    $flash = ['type' => 'error', 'msg' => 'Rating must be between 1 and 5.'];
  } else {
    if ($stmt = $conn->prepare('INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)')) {
      $stmt->bind_param('iiis', $pid, $uid, $rating, $comment);
      if ($stmt->execute()) {
        $flash = ['type' => 'success', 'msg' => 'Review submitted.'];
      } else {
        $flash = ['type' => 'error', 'msg' => 'Failed to submit review.'];
      }
      $stmt->close();
    }
  }
}

// Load product details
$product = null; $variants = []; $images = [];
if ($conn instanceof mysqli) {
  if ($st = $conn->prepare('SELECT id, name, description, base_price, image FROM products WHERE id=? LIMIT 1')) {
    $st->bind_param('i', $pid);
    if ($st->execute()) { $product = $st->get_result()->fetch_assoc(); }
    $st->close();
  }
  // Variants
  if ($sv = $conn->prepare('SELECT id, color, size, stock, price, image FROM product_variants WHERE product_id=? ORDER BY color, FIELD(size,\'XS\',\'S\',\'M\',\'L\',\'XL\',\'XXL\')')) {
    $sv->bind_param('i', $pid);
    if ($sv->execute()) {
      $r = $sv->get_result();
      while ($row = $r->fetch_assoc()) { $variants[] = $row; }
    }
    $sv->close();
  }
  // Additional product images
  if ($si = $conn->prepare('SELECT image_url FROM product_images WHERE product_id=? ORDER BY sort_order ASC, id ASC')) {
    $si->bind_param('i', $pid);
    if ($si->execute()) {
      $r = $si->get_result();
      while ($row = $r->fetch_assoc()) { $images[] = $row['image_url']; }
    }
    $si->close();
  }
}

// Compute stock and price range
$totalStock = 0; $minPrice = null; $maxPrice = null;
foreach ($variants as $v) {
  $totalStock += (int)$v['stock'];
  $vp = isset($v['price']) && $v['price'] !== null ? (float)$v['price'] : (float)($product['base_price'] ?? 0);
  $minPrice = ($minPrice === null) ? $vp : min($minPrice, $vp);
  $maxPrice = ($maxPrice === null) ? $vp : max($maxPrice, $vp);
}
if ($minPrice === null || $maxPrice === null) {
  $minPrice = $maxPrice = (float)($product['base_price'] ?? 0);
}
$priceLabel = ($minPrice == $maxPrice) ? ('ETB ' . number_format($minPrice, 2)) : ('ETB ' . number_format($minPrice,2) . '–ETB ' . number_format($maxPrice,2));

// Determine main image
$mainImage = '';
if (!empty($product['image'])) { $mainImage = $product['image']; }
if (!$mainImage && !empty($images)) { $mainImage = $images[0]; }
if (!$mainImage && !empty($variants)) {
  foreach ($variants as $v) { if (!empty($v['image'])) { $mainImage = $v['image']; break; } }
}
$mainUrl = $mainImage ? ((stripos($mainImage, 'http') === 0) ? $mainImage : ('../' . ltrim($mainImage, '/'))) : '';

include '../includes/header.php';
?>

<style>
  .details-page { padding: 28px 0; }
  .detail-grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 24px; }
  .gallery { background: rgba(17,24,39,0.85); border: 1px solid var(--border); border-radius: 16px; padding: 16px; }
  .main-photo { width: 100%; height: 420px; border-radius: 12px; object-fit: contain; background: #0b1220; border: 1px solid var(--border) }
  .thumbs { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; margin-top: 10px; }
  .thumbs img { width: 100%; height: 70px; object-fit: contain; background: #0b1220; border-radius: 8px; border: 1px solid var(--border); cursor: pointer; }
  .info { background: rgba(17,24,39,0.85); border: 1px solid var(--border); border-radius: 16px; padding: 20px; }
  .title { margin: 0 0 8px; font-size: 28px }
  .price { font-size: 22px; font-weight: 800; margin-bottom: 10px }
  .desc { color: #9ca3af; line-height: 1.6; }
  .meta { margin: 10px 0; color: #cbd5e1 }
  .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px }
  .actions-row { display: grid; grid-template-columns: 120px 1fr; align-items: end; gap: 12px; }
  select, input[type=number] { padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: #0b1220; color: var(--text) }
  .btn.primary { background: var(--accent); border-color: transparent; }
  .btn { padding: 10px 12px; border: 1px solid var(--border); border-radius: 12px; background: #0b1220; color: var(--text); cursor: pointer; }
  .add-to-cart-btn { margin-left: 8px; }
  .btn-row { display:flex; gap: 10px; align-items:center; margin-top: 12px; }
  .section { margin-top: 20px }
  .flash { margin:10px 0; padding:10px 12px; border-radius:10px; font-size:14px }
  .flash.err { border:1px solid #7f1d1d; background:#3f1d1d; color:#fecaca }
  .flash.ok { border:1px solid #065f46; background:#064e3b; color:#d1fae5 }
  /* Reviews */
  .reviews { background: rgba(17,24,39,0.85); border: 1px solid var(--border); border-radius: 16px; padding: 16px; }
  .review { border-top: 1px solid var(--border); padding: 10px 0 }
  .review:first-child { border-top: none }
  .rating { color: #f59e0b; font-weight: 700 }
</style>

<main class="container details-page">
  <?php if (!empty($flash)) { $isErr = $flash['type'] === 'error'; ?>
    <div class="flash <?= $isErr ? 'err' : 'ok' ?>"><?= htmlspecialchars($flash['msg']) ?></div>
  <?php } ?>

  <div class="detail-grid">
    <section class="gallery">
      <img class="main-photo" src="<?= htmlspecialchars($mainUrl) ?>" alt="<?= htmlspecialchars($product['name'] ?? 'Product') ?>" />
      <div class="thumbs">
        <?php
          $allImages = [];
          if ($mainUrl) $allImages[] = $mainUrl;
          foreach ($images as $im) { $url = (stripos($im,'http')===0)?$im:('../'.ltrim($im,'/')); if (!in_array($url,$allImages)) $allImages[] = $url; }
          foreach ($variants as $v) { if (!empty($v['image'])) { $u = (stripos($v['image'],'http')===0)?$v['image']:('../'.ltrim($v['image'],'/')); if (!in_array($u,$allImages)) $allImages[] = $u; } }
        ?>
        <?php foreach ($allImages as $u) { ?>
          <img src="<?= htmlspecialchars($u) ?>" alt="thumb" onclick="document.querySelector('.main-photo').src='<?= htmlspecialchars($u) ?>'" />
        <?php } ?>
      </div>
    </section>

    <section class="info">
      <h1 class="title"><?= htmlspecialchars($product['name'] ?? 'Product') ?></h1>
      <div class="price" id="priceText"><?= htmlspecialchars($priceLabel) ?></div>
      <?php if (!empty($product['description'])) { ?><p class="desc"><?= nl2br(htmlspecialchars($product['description'])) ?></p><?php } ?>
      <div class="meta">Available stock: <span id="stockText"><?= (int)$totalStock ?></span></div>
      <div class="meta" id="availabilityMsg" style="color:#ef4444; display:none;">No available products for this combination.</div>

      <?php
        // Build selectable variant options
        $colors = []; $sizes = [];
        foreach ($variants as $v) {
          if (!in_array($v['color'], $colors) && $v['color'] !== null && $v['color'] !== '') $colors[] = $v['color'];
          if (!in_array($v['size'], $sizes) && $v['size'] !== null && $v['size'] !== '') $sizes[] = $v['size'];
        }
      ?>

      <form method="post" action="">
        <input type="hidden" name="form_type" value="add_to_cart" />
        <div class="row">
          <div>
            <label for="color">Color</label>
            <select id="color" name="color">
              <option value="">Select color</option>
              <?php foreach ($colors as $c) { ?>
                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
              <?php } ?>
            </select>
          </div>
          <div>
            <label for="size">Size</label>
            <select id="size" name="size">
              <option value="">Select size</option>
              <?php foreach ($sizes as $s) { ?>
                <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
              <?php } ?>
            </select>
          </div>
        </div>
        <div class="row actions-row" style="margin-top:10px;">
          <div>
            <label for="qty">Quantity</label>
            <input type="number" id="qty" name="qty" min="1" value="1" />
          </div>
        </div>

        <div class="btn-row">
          <a class="btn" href="./shop.php">← Back to Shop</a>
          <button type="submit" id="addToCartBtn" class="btn primary add-to-cart-btn">Add to Cart</button>
        </div>
      </form>

    </section>
  </div>

  <!-- Reviews -->
  <section class="reviews section">
    <h2 style="margin:0 0 10px;">Customer Reviews</h2>
    <?php
      $reviews = [];
      if ($conn instanceof mysqli) {
        if ($sr = $conn->prepare('SELECT r.rating, r.comment, r.created_at, u.fullname, u.username FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.product_id=? ORDER BY r.created_at DESC')) {
          $sr->bind_param('i', $pid);
          if ($sr->execute()) {
            $res = $sr->get_result();
            while ($row = $res->fetch_assoc()) { $reviews[] = $row; }
          }
          $sr->close();
        }
      }
    ?>
    <?php if (empty($reviews)) { ?>
      <p style="color:#9ca3af;">No reviews yet.</p>
    <?php } else { foreach ($reviews as $rv) { ?>
      <div class="review">
        <div class="rating"><?= str_repeat('★', (int)$rv['rating']) ?><?= str_repeat('☆', 5-(int)$rv['rating']) ?></div>
        <div style="color:#cbd5e1; font-weight:600; margin-top:2px;">by <?= htmlspecialchars($rv['fullname'] ?: $rv['username'] ?: 'User') ?></div>
        <?php if (!empty($rv['comment'])) { ?><p class="desc" style="margin:6px 0 0;"><?= nl2br(htmlspecialchars($rv['comment'])) ?></p><?php } ?>
      </div>
    <?php } } ?>

    <?php if (!empty($_SESSION['user_id'])) { ?>
      <div class="section">
        <h3 style="margin:0 0 8px;">Add a review</h3>
        <form method="post" action="">
          <input type="hidden" name="form_type" value="add_review" />
          <div style="display:grid; grid-template-columns: 1fr; gap: 12px; align-items:center;">
            <div>
              <label for="ratingRange" style="display:block; margin-bottom:6px;">Rating</label>
              <div style="display:flex; align-items:center; gap:12px;">
                <input type="range" id="ratingRange" name="rating" min="1" max="5" step="1" value="5" style="flex:1;">
                <span id="ratingStars" class="rating" aria-live="polite">★★★★★</span>
              </div>
            </div>
            <div>
              <label for="comment">Comment</label>
              <textarea id="comment" name="comment" rows="3" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid var(--border); background:#0b1220; color:var(--text)"></textarea>
            </div>
          </div>
          <button class="btn primary" type="submit" style="margin-top:10px;">Submit Review</button>
        </form>
      </div>
    <?php } else { ?>
      <p style="margin-top:10px; color:#9ca3af;">Please <a class="btn" href="./loginc.php">log in</a> to add a review.</p>
    <?php } ?>
  </section>
</main>

<script>
  // Variant live update: price, stock, image, and dependent dropdown filtering
  (function(){
    const colorSel = document.getElementById('color');
    const sizeSel = document.getElementById('size');
    const priceEl = document.getElementById('priceText');
    const stockEl = document.getElementById('stockText');
    const qtyEl = document.getElementById('qty');
    const addBtn = document.getElementById('addToCartBtn');
    const imgEl = document.querySelector('.main-photo');
    const naMsg = document.getElementById('availabilityMsg');

    if (!colorSel || !sizeSel || !priceEl || !stockEl) return;

    const variants = <?php echo json_encode(array_map(function($v){
      return [
        'id' => (int)$v['id'],
        'color' => (string)$v['color'],
        'size' => (string)$v['size'],
        'stock' => (int)$v['stock'],
        'price' => isset($v['price']) && $v['price'] !== null ? (float)$v['price'] : null,
        'image' => (string)($v['image'] ?? '')
      ];
    }, $variants), JSON_UNESCAPED_SLASHES); ?>;
    const basePrice = <?php echo json_encode((float)($product['base_price'] ?? 0)); ?>;
    const totalStockDefault = <?php echo json_encode((int)$totalStock); ?>;
    const defaultPriceLabel = <?php echo json_encode($priceLabel); ?>;

    function fmt(n){ return 'ETB ' + (Number(n).toFixed(2)); }
    function priceOf(v){ return (v.price !== null && v.price !== undefined) ? v.price : basePrice; }
    function variantImageUrl(v){
      if (!v.image) return '';
      return v.image.startsWith('http') ? v.image : ('../' + v.image.replace(/^\/+/, ''));
    }

    // Build helper lists
    const allColors = Array.from(new Set(variants.map(v => v.color).filter(Boolean)));
    const allSizes = Array.from(new Set(variants.map(v => v.size).filter(Boolean)));

    function rebuildOptions(select, list, placeholder){
      const prev = select.value;
      // Clear
      while (select.firstChild) select.removeChild(select.firstChild);
      const ph = document.createElement('option');
      ph.value = '';
      ph.textContent = placeholder;
      select.appendChild(ph);
      let foundPrev = false;
      list.forEach(val => {
        const opt = document.createElement('option');
        opt.value = val; opt.textContent = val;
        if (val === prev) { opt.selected = true; foundPrev = true; }
        select.appendChild(opt);
      });
      if (!foundPrev) select.value = '';
    }

    function filterSizesForColor(color){
      if (!color) return allSizes.slice();
      const sizes = new Set();
      variants.forEach(v => { if (v.color === color && (v.stock||0) > 0 && v.size) sizes.add(v.size); });
      return Array.from(sizes);
    }

    function filterColorsForSize(size){
      if (!size) return allColors.slice();
      const colors = new Set();
      variants.forEach(v => { if (v.size === size && (v.stock||0) > 0 && v.color) colors.add(v.color); });
      return Array.from(colors);
    }

    function update(){
      const selColor = (colorSel.value || '').trim();
      const selSize = (sizeSel.value || '').trim();

      // Dependent dropdowns
      const allowedSizes = filterSizesForColor(selColor);
      rebuildOptions(sizeSel, allowedSizes, 'Select size');
      const allowedColors = filterColorsForSize(selSize);
      rebuildOptions(colorSel, allowedColors, 'Select color');

      // Exact match when both chosen
      let exact = null;
      if (selColor !== '' && selSize !== '') {
        exact = variants.find(v => v.color === selColor && v.size === selSize) || null;
      }

      if (exact) {
        const p = priceOf(exact);
        const s = Math.max(0, parseInt(exact.stock || 0, 10));
        priceEl.textContent = fmt(p);
        stockEl.textContent = String(s);
        if (qtyEl) {
          qtyEl.max = String(Math.max(1, s));
          if (parseInt(qtyEl.value||'1',10) > s) qtyEl.value = s > 0 ? String(s) : '1';
        }
        if (addBtn) addBtn.disabled = (s <= 0);
        const u = variantImageUrl(exact);
        if (u && imgEl) { imgEl.src = u; }
        if (naMsg) naMsg.style.display = 'none';
        return;
      }

      // If both selected but no exact match => not available state
      if (selColor !== '' && selSize !== '') {
        priceEl.textContent = defaultPriceLabel;
        stockEl.textContent = '0';
        if (qtyEl) { qtyEl.max = '1'; qtyEl.value = '1'; }
        if (addBtn) addBtn.disabled = true;
        if (naMsg) naMsg.style.display = '';
        return;
      }

      // Fallback: nothing or partial selected => show defaults and hide NA message
      priceEl.textContent = defaultPriceLabel;
      stockEl.textContent = String(totalStockDefault);
      if (qtyEl) { qtyEl.removeAttribute('max'); }
      if (addBtn) addBtn.disabled = (totalStockDefault <= 0);
      if (naMsg) naMsg.style.display = 'none';
    }

    colorSel.addEventListener('change', update);
    sizeSel.addEventListener('change', update);
    update();
  })();
  // Live update star display for rating slider
  (function(){
    const range = document.getElementById('ratingRange');
    const stars = document.getElementById('ratingStars');
    function renderStars(val){
      const v = Math.max(1, Math.min(5, parseInt(val || '5', 10)));
      stars.textContent = '★★★★★'.slice(0, v) + '☆☆☆☆☆'.slice(0, 5 - v);
    }
    if (range && stars) {
      renderStars(range.value);
      range.addEventListener('input', function(){ renderStars(range.value); });
      range.addEventListener('change', function(){ renderStars(range.value); });
    }
  })();
  </script>

<?php include '../includes/footer.php'; ?>

<!-- this page is for product details -->