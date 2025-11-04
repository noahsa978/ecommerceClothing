<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$page_title = 'Shopping Cart — Ecom Clothing';
require_once __DIR__ . '/../includes/db_connect.php';

// Helpers
function get_active_cart_id(mysqli $conn, ?int $userId, string $sessionId): ?int {
  $cartId = null;
  if ($userId) {
    if ($st = $conn->prepare('SELECT id FROM carts WHERE user_id=? AND status="active" ORDER BY id DESC LIMIT 1')) {
      $st->bind_param('i', $userId);
      if ($st->execute()) { $r = $st->get_result(); if ($row = $r->fetch_assoc()) { $cartId = (int)$row['id']; } }
      $st->close();
    }
    if ($cartId === null && $sessionId) {
      if ($st = $conn->prepare('SELECT id FROM carts WHERE session_id=? AND status="active" ORDER BY id DESC LIMIT 1')) {
        $st->bind_param('s', $sessionId);
        if ($st->execute()) { $r = $st->get_result(); if ($row = $r->fetch_assoc()) { $cartId = (int)$row['id']; } }
        $st->close();
      }
    }
  } else {
    if ($sessionId && ($st = $conn->prepare('SELECT id FROM carts WHERE session_id=? AND status="active" ORDER BY id DESC LIMIT 1'))) {
      $st->bind_param('s', $sessionId);
      if ($st->execute()) { $r = $st->get_result(); if ($row = $r->fetch_assoc()) { $cartId = (int)$row['id']; } }
      $st->close();
    }
  }
  return $cartId;
}

function compute_totals(mysqli $conn, int $cartId): array {
  $subtotal = 0.0;
  if ($st = $conn->prepare('SELECT quantity, price FROM cart_items WHERE cart_id=?')) {
    $st->bind_param('i', $cartId);
    if ($st->execute()) {
      $res = $st->get_result();
      while ($row = $res->fetch_assoc()) {
        $subtotal += ((float)$row['price']) * ((int)$row['quantity']);
      }
    }
    $st->close();
  }
  // Free shipping for orders over ETB 2000
  $shipping = $subtotal >= 2000 ? 0.0 : ($subtotal > 0 ? 150.0 : 0.0);
  $taxRate = 0.084;
  $tax = $subtotal * $taxRate;
  $total = $subtotal + $shipping + $tax;
  return [
    'subtotal' => $subtotal,
    'shipping' => $shipping,
    'tax' => $tax,
    'total' => $total,
  ];
}

// AJAX actions: update quantity / remove item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($conn instanceof mysqli)) {
  // Force a clean JSON response (avoid BOM/whitespace/previous output)
  if (function_exists('ob_get_level')) { while (ob_get_level()) { ob_end_clean(); } }
  header('Content-Type: application/json');
  $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
  $sessionId = session_id();
  $cartId = get_active_cart_id($conn, $userId, $sessionId);
  if ($cartId === null) { echo json_encode(['ok'=>false,'error'=>'No active cart']); exit; }

  $action = $_POST['action'];
  try {
    if ($action === 'update_qty') {
      $ciId = (int)($_POST['ci_id'] ?? 0);
      $qty = max(0, (int)($_POST['qty'] ?? 0));
      if ($ciId <= 0) { echo json_encode(['ok'=>false,'error'=>'Invalid item']); exit; }
      // Ensure item belongs to this cart
      $variantId = null; $currentPrice = 0.0;
      if ($st = $conn->prepare('SELECT variant_id, price FROM cart_items WHERE id=? AND cart_id=?')) {
        $st->bind_param('ii', $ciId, $cartId);
        if ($st->execute()) { $r = $st->get_result(); if ($row = $r->fetch_assoc()) { $variantId = (int)$row['variant_id']; $currentPrice = (float)$row['price']; } }
        $st->close();
      }
      if ($variantId === null) { echo json_encode(['ok'=>false,'error'=>'Item not found']); exit; }
      // Stock cap
      $stock = null;
      if ($sv = $conn->prepare('SELECT stock FROM product_variants WHERE id=?')) {
        $sv->bind_param('i', $variantId);
        if ($sv->execute()) { $r = $sv->get_result(); if ($row = $r->fetch_assoc()) { $stock = (int)$row['stock']; } }
        $sv->close();
      }
      $newQty = ($stock === null) ? $qty : min($qty, max(0, $stock));
      if ($newQty <= 0) {
        if ($sd = $conn->prepare('DELETE FROM cart_items WHERE id=? AND cart_id=?')) { $sd->bind_param('ii', $ciId, $cartId); $sd->execute(); $sd->close(); }
        if ($su = $conn->prepare('UPDATE carts SET updated_at = NOW() WHERE id=?')) { $su->bind_param('i', $cartId); $su->execute(); $su->close(); }
        // Remove from session cart as well
        $sessKey = 'v' . $variantId;
        if (isset($_SESSION['cart'][$sessKey])) { unset($_SESSION['cart'][$sessKey]); }
        $tot = compute_totals($conn, $cartId);
        echo json_encode(['ok'=>true,'removed'=>true,'totals'=>$tot]); exit;
      } else {
        if ($su = $conn->prepare('UPDATE cart_items SET quantity=? WHERE id=? AND cart_id=?')) { $su->bind_param('iii', $newQty, $ciId, $cartId); $su->execute(); $su->close(); }
        if ($su2 = $conn->prepare('UPDATE carts SET updated_at = NOW() WHERE id=?')) { $su2->bind_param('i', $cartId); $su2->execute(); $su2->close(); }
        // Sync session cart qty
        $sessKey = 'v' . $variantId;
        if (isset($_SESSION['cart'][$sessKey])) { $_SESSION['cart'][$sessKey]['qty'] = $newQty; }
        $lineTotal = $currentPrice * $newQty;
        $tot = compute_totals($conn, $cartId);
        echo json_encode(['ok'=>true,'removed'=>false,'qty'=>$newQty,'line_total'=>$lineTotal,'totals'=>$tot]); exit;
      }
    }
    if ($action === 'remove_item') {
      $ciId = (int)($_POST['ci_id'] ?? 0);
      if ($ciId <= 0) { echo json_encode(['ok'=>false,'error'=>'Invalid item']); exit; }
      // Find variant to clean up session cart too
      $variantId = null;
      if ($st = $conn->prepare('SELECT variant_id FROM cart_items WHERE id=? AND cart_id=?')) { $st->bind_param('ii', $ciId, $cartId); if ($st->execute()) { $r=$st->get_result(); if ($row=$r->fetch_assoc()) { $variantId=(int)$row['variant_id']; } } $st->close(); }
      if ($sd = $conn->prepare('DELETE FROM cart_items WHERE id=? AND cart_id=?')) { $sd->bind_param('ii', $ciId, $cartId); $sd->execute(); $sd->close(); }
      if ($su = $conn->prepare('UPDATE carts SET updated_at = NOW() WHERE id=?')) { $su->bind_param('i', $cartId); $su->execute(); $su->close(); }
      if ($variantId !== null) { $sessKey = 'v' . $variantId; if (isset($_SESSION['cart'][$sessKey])) { unset($_SESSION['cart'][$sessKey]); } }
      $tot = compute_totals($conn, $cartId);
      echo json_encode(['ok'=>true,'removed'=>true,'totals'=>$tot]); exit;
    }
    echo json_encode(['ok'=>false,'error'=>'Unknown action']);
  } catch (Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>'Server error']);
  }
  exit;
}

// Load active cart by user or session
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$sessionId = session_id();
$cartId = null;
$items = [];
$subtotal = 0.0;

if ($conn instanceof mysqli) {
  // Prefer user cart if logged in, else session cart
  if ($userId) {
    if ($st = $conn->prepare('SELECT id FROM carts WHERE user_id=? AND status="active" ORDER BY id DESC LIMIT 1')) {
      $st->bind_param('i', $userId);
      if ($st->execute()) { $r = $st->get_result(); if ($row = $r->fetch_assoc()) { $cartId = (int)$row['id']; } }
      $st->close();
    }
    if ($cartId === null && $sessionId) {
      if ($st = $conn->prepare('SELECT id FROM carts WHERE session_id=? AND status="active" ORDER BY id DESC LIMIT 1')) {
        $st->bind_param('s', $sessionId);
        if ($st->execute()) { $r = $st->get_result(); if ($row = $r->fetch_assoc()) { $cartId = (int)$row['id']; } }
        $st->close();
      }
    }
  } else {
    if ($sessionId && ($st = $conn->prepare('SELECT id FROM carts WHERE session_id=? AND status="active" ORDER BY id DESC LIMIT 1'))) {
      $st->bind_param('s', $sessionId);
      if ($st->execute()) { $r = $st->get_result(); if ($row = $r->fetch_assoc()) { $cartId = (int)$row['id']; } }
      $st->close();
    }
  }

  if ($cartId !== null) {
    $sql = 'SELECT ci.id AS cart_item_id, ci.variant_id, ci.quantity, ci.price,
                   v.size, v.color, v.image AS vimg, v.product_id,
                   p.name, p.description, p.image AS pimg
              FROM cart_items ci
              JOIN product_variants v ON v.id = ci.variant_id
              JOIN products p ON p.id = v.product_id
             WHERE ci.cart_id = ?
             ORDER BY ci.id DESC';
    if ($st = $conn->prepare($sql)) {
      $st->bind_param('i', $cartId);
      if ($st->execute()) {
        $res = $st->get_result();
        while ($row = $res->fetch_assoc()) {
          $row['line_total'] = (float)$row['price'] * (int)$row['quantity'];
          $subtotal += $row['line_total'];
          $items[] = $row;
        }
      }
      $st->close();
    }
  }
}

$shipping = $subtotal >= 100 ? 0.0 : 9.99;
$taxRate = 0.084;
$tax = $subtotal * $taxRate;
$total = $subtotal + $shipping + $tax;
// Only include header after any AJAX exits
include '../includes/header.php';
?>

<style>
  .cart-page { padding: 28px 0 }
  .cart-grid { display: grid; grid-template-columns: 1fr 400px; gap: 24px }

  /* Cart Items */
  .cart-items { background: rgba(17,24,39,0.85); border: 1px solid var(--border); border-radius: 16px; padding: 20px }
  .cart-item { display: grid; grid-template-columns: 80px 1fr auto auto auto; gap: 16px; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--border) }
  .cart-item:last-child { border-bottom: none; padding-bottom: 0 }
  .cart-item img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; background: #0b1220 }
  .item-details h3 { margin: 0 0 4px; font-size: 16px; font-weight: 700 }
  .item-details p { margin: 0; color: #9ca3af; font-size: 14px }
  .item-details .variant { color: #cbd5e1; font-size: 13px; margin-top: 2px }
  .qty-controls { display: flex; align-items: center; gap: 8px }
  .qty-btn { width: 32px; height: 32px; border: 1px solid var(--border); border-radius: 6px; background: #0b1220; color: var(--text); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 600 }
  .qty-btn:hover { background: rgba(124,58,237,0.12) }
  .qty-input { width: 50px; padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px; background: #0b1220; color: var(--text); text-align: center; font-size: 14px }
  .item-price { font-size: 18px; font-weight: 700; color: var(--text) }
  .remove-btn { padding: 8px 12px; border: 1px solid #ef4444; border-radius: 8px; background: transparent; color: #ef4444; cursor: pointer; font-size: 14px; transition: all 0.15s ease }
  .remove-btn:hover { background: #ef4444; color: #fff }

  /* Cart Summary */
  .cart-summary { background: rgba(17,24,39,0.85); border: 1px solid var(--border); border-radius: 16px; padding: 20px; height: fit-content; position: sticky; top: 100px }
  .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; color: #cbd5e1 }
  .summary-row.total { border-top: 1px solid var(--border); padding-top: 12px; margin-top: 12px; font-size: 18px; font-weight: 700; color: var(--text) }
  .checkout-btn { width: 100%; padding: 14px; margin-top: 16px; background: var(--accent); border: none; border-radius: 12px; color: #fff; font-weight: 700; font-size: 16px; cursor: pointer; transition: all 0.15s ease }
  .checkout-btn:hover { background: var(--accent-600); transform: translateY(-1px) }
  .continue-shopping { display: inline-block; margin-top: 12px; color: #9ca3af; text-decoration: none; font-size: 14px }
  .continue-shopping:hover { color: #fff }

  /* Empty Cart */
  .empty-cart { text-align: center; padding: 60px 20px; color: #9ca3af }
  .empty-cart h2 { margin: 0 0 12px; color: var(--text) }
  .empty-cart p { margin: 0 0 20px }

  @media (max-width: 900px) {
    .cart-grid { grid-template-columns: 1fr }
    .cart-summary { position: static; order: -1 }
    .cart-item { grid-template-columns: 60px 1fr auto; gap: 12px }
    .cart-item img { width: 60px; height: 60px }
    .item-details h3 { font-size: 14px }
    .qty-controls { flex-direction: column; gap: 4px }
    .remove-btn { grid-column: 1 / -1; margin-top: 8px }
  }
</style>

<main class="container cart-page">
  <h1 style="margin: 0 0 24px; font-size: 28px">Shopping Cart</h1>
  
  <div class="cart-grid">
    <div class="cart-items">
      <?php if (empty($items)) { ?>
        <div class="empty-cart">
          <h2>Your cart is empty</h2>
          <p>Looks like you haven’t added anything to your cart yet.</p>
          <a href="./shop.php" class="continue-shopping">← Continue Shopping</a>
        </div>
      <?php } else { foreach ($items as $idx => $it) { 
        $img = $it['vimg'] ?: ($it['pimg'] ?? '');
        $imgUrl = $img ? ((stripos($img, 'http') === 0) ? $img : ('../' . ltrim($img, '/'))) : '';
        $itemId = 'ci' . (int)$it['cart_item_id'];
        $line = (float)$it['line_total'];
      ?>
      <div class="cart-item" data-cart-item-id="<?= (int)$it['cart_item_id'] ?>">
        <?php if ($imgUrl) { ?><img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($it['name']) ?>" /><?php } ?>
        <div class="item-details">
          <h3><?= htmlspecialchars($it['name']) ?></h3>
          <?php if (!empty($it['description'])) { ?><p><?= htmlspecialchars(mb_strimwidth($it['description'],0,70,'…')) ?></p><?php } ?>
          <div class="variant">Size: <?= htmlspecialchars($it['size'] ?: '-') ?> • Color: <?= htmlspecialchars($it['color'] ?: '-') ?></div>
        </div>
        <div class="qty-controls">
          <button class="qty-btn" onclick="updateQty('<?= $itemId ?>', -1)">−</button>
          <input class="qty-input" id="qty_<?= $itemId ?>" type="number" value="<?= (int)$it['quantity'] ?>" min="1" onchange="updateQty('<?= $itemId ?>', 0)">
          <button class="qty-btn" onclick="updateQty('<?= $itemId ?>', 1)">+</button>
        </div>
        <div class="item-price" id="price_<?= $itemId ?>">ETB <?= number_format($line,2) ?></div>
        <button class="remove-btn" onclick="removeItem('<?= $itemId ?>')">Remove</button>
      </div>
      <?php } } ?>
    </div>

    <div class="cart-summary">
      <h2 style="margin: 0 0 16px; font-size: 20px">Order Summary</h2>
      
      <div class="summary-row">
        <span>Subtotal (<?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?>)</span>
        <span id="subtotal">ETB <?= number_format($subtotal,2) ?></span>
      </div>
      <div class="summary-row">
        <span>Shipping</span>
        <span id="shipping"><?= $shipping == 0.0 ? 'FREE' : ('ETB '.number_format($shipping,2)) ?></span>
      </div>
      <div class="summary-row">
        <span>Tax</span>
        <span id="tax">ETB <?= number_format($tax,2) ?></span>
      </div>
      <div class="summary-row total">
        <span>Total</span>
        <span id="total">ETB <?= number_format($total,2) ?></span>
      </div>

      <button class="checkout-btn" onclick="proceedToCheckout()">Proceed to Checkout</button>
      <a href="./shop.php" class="continue-shopping">← Continue Shopping</a>
    </div>
  </div>
</main>

<script>
  // AJAX helpers to persist quantity and remove actions

  function findCartItemElement(itemId) {
    // First, try to find any control with an inline onclick matching the id
    let el = document.querySelector(`[onclick*="${itemId}"]`);
    if (el) return el.closest('.cart-item');
    // Fallback: try the quantity input id pattern
    const qtyEl = document.getElementById('qty_' + itemId);
    if (qtyEl) return qtyEl.closest('.cart-item');
    return null;
  }

  async function postForm(formData) {
    const res = await fetch('', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch (e) {
      console.error('Non-JSON response:', text);
      return { ok: false, error: 'Non-JSON response from server' };
    }
  }

  async function updateQty(itemId, change) {
    const itemEl = findCartItemElement(itemId);
    if (!itemEl) return;
    const ciId = itemEl.dataset.cartItemId;
    let qtyInput = document.getElementById('qty_' + itemId);
    if (!qtyInput) { qtyInput = itemEl.querySelector('.qty-input'); }
    if (!qtyInput) { console.error('qtyInput not found for', itemId); return; }
    const currentQty = parseInt(qtyInput.value);
    const desiredQty = Math.max(0, currentQty + change);
    const form = new FormData();
    form.append('action', 'update_qty');
    form.append('ci_id', ciId);
    form.append('qty', String(desiredQty));
    const data = await postForm(form);
    if (!data.ok) { alert(data.error || 'Failed to update quantity'); return; }
    if (data.removed) {
      itemEl.remove();
    } else {
      qtyInput.value = data.qty;
      const priceElement = document.getElementById('price_' + itemId);
      priceElement.textContent = 'ETB ' + (data.line_total || 0).toFixed(2);
    }
    applyTotalsFromServer(data.totals);
    toggleEmptyState();
  }

  function updateItemPrice(itemId, qty) {
    const priceElement = document.getElementById('price_' + itemId);
    // Recalculate price text locally (no server update)
    const currentText = priceElement.textContent.replace('ETB ','');
    let currentValue = parseFloat(currentText);
    if (isNaN(currentValue)) currentValue = 0;
    // This keeps UI responsive; server should recalc on real update
    priceElement.textContent = 'ETB ' + currentValue.toFixed(2);
  }

  async function removeItem(itemId) {
    if (!confirm('Remove this item from cart?')) return;
    const itemEl = findCartItemElement(itemId);
    if (!itemEl) return;
    const ciId = itemEl.dataset.cartItemId;

    // Optimistic UI removal
    const parent = itemEl.parentNode;
    const placeholder = document.createElement('div');
    placeholder.className = 'cart-item placeholder';
    parent.insertBefore(placeholder, itemEl.nextSibling);

    // Cache values to revert if needed
    const oldHTML = itemEl.outerHTML;
    const priceText = itemEl.querySelector('.item-price')?.textContent?.replace('$','') || '0';
    const lineVal = parseFloat(priceText) || 0;
    // Decrement summary count text optimistically
    const countLabel = document.querySelector('.summary-row span:nth-child(1)');
    const m = /Subtotal \((\d+) item/.exec(countLabel.textContent);
    const oldCount = m ? parseInt(m[1]) : document.querySelectorAll('.cart-item').length;
    const newCount = Math.max(0, oldCount - 1);
    countLabel.textContent = `Subtotal (${newCount} item${newCount!==1?'s':''})`;
    // Adjust totals optimistically
    const toNumber = (el) => parseFloat(el.textContent.replace('$','')) || 0;
    const subtotalEl = document.getElementById('subtotal');
    const taxEl = document.getElementById('tax');
    const totalEl = document.getElementById('total');
    const shipEl = document.getElementById('shipping');
    const oldSubtotal = toNumber(subtotalEl);
    const oldTax = toNumber(taxEl);
    const oldTotal = toNumber(totalEl);
    const oldShipping = shipEl.textContent === 'FREE' ? 0 : toNumber(shipEl);
    // Simple optimistic calc: subtract line from subtotal, recompute tax and shipping threshold
    const newSubtotal = Math.max(0, oldSubtotal - lineVal);
    const shipping = newSubtotal >= 100 ? 0.0 : (newSubtotal === 0 ? 0.0 : 9.99);
    const tax = newSubtotal * 0.084;
    const newTotal = newSubtotal + shipping + tax;
    const fmt = (n) => 'ETB ' + n.toFixed(2);
    subtotalEl.textContent = fmt(newSubtotal);
    taxEl.textContent = fmt(tax);
    totalEl.textContent = fmt(newTotal);
    shipEl.textContent = shipping === 0 ? 'FREE' : fmt(shipping);

    // Remove from DOM immediately
    parent.removeChild(itemEl);

    // Server request
    const form = new FormData();
    form.append('action', 'remove_item');
    form.append('ci_id', ciId);
    try {
      const data = await postForm(form);
      console.debug('remove_item response', data);
      if (!data.ok) throw new Error(data.error || 'Failed');
      // Use server authoritative totals
      applyTotalsFromServer(data.totals);
      // Cleanup placeholder and check empty state
      placeholder.remove();
    } catch (e) {
      console.error('remove_item failed', e);
      // If backend says cart/item not found, keep UI removal as-is
      const msg = (e && e.message) ? e.message : 'Error';
      const benign = /No active cart|Item not found/i.test(msg);
      if (benign) {
        // Use optimistic values already applied; just finalize placeholder cleanup
        placeholder.remove();
        toggleEmptyState();
      } else {
        // Restore UI if server failed for other reasons
        placeholder.insertAdjacentHTML('beforebegin', oldHTML);
        placeholder.remove();
        // Revert labels
        const revertCount = oldCount;
        document.querySelector('.summary-row span:nth-child(1)').textContent = `Subtotal (${revertCount} item${revertCount!==1?'s':''})`;
        subtotalEl.textContent = fmt(oldSubtotal);
        taxEl.textContent = fmt(oldTax);
        totalEl.textContent = fmt(oldTotal);
        shipEl.textContent = oldShipping === 0 ? 'FREE' : fmt(oldShipping);
        alert('Failed to remove item. ' + msg);
      }
    }
  }

  function applyTotalsFromServer(t) {
    if (!t) return;
    const s = (n) => 'ETB ' + Number(n || 0).toFixed(2);
    document.getElementById('subtotal').textContent = s(t.subtotal);
    document.getElementById('tax').textContent = s(t.tax);
    document.getElementById('total').textContent = s(t.total);
    const shipEl = document.getElementById('shipping');
    shipEl.textContent = (Number(t.shipping||0) === 0) ? 'FREE' : s(t.shipping);
    // Update the Subtotal label count using total quantity in DOM
    const qtyInputs = Array.from(document.querySelectorAll('.qty-input'));
    const totalQty = qtyInputs.reduce((sum, el) => sum + (parseInt(el.value, 10) || 0), 0);
    const labelEl = document.querySelector('.summary-row span:nth-child(1)');
    if (labelEl) labelEl.textContent = `Subtotal (${totalQty} item${totalQty!==1?'s':''})`;
  }

  function toggleEmptyState() {
    const items = document.querySelectorAll('.cart-item');
    if (items.length === 0) {
      const container = document.querySelector('.cart-items');
      container.innerHTML = '<div class="empty-cart"><h2>Your cart is empty</h2><p>Looks like you haven\'t added anything to your cart yet.</p><a href="./shop.php" class="continue-shopping">← Continue Shopping</a></div>';
      document.querySelector('.summary-row span:nth-child(1)').textContent = 'Subtotal (0 items)';
      applyTotalsFromServer({subtotal:0,shipping:0,tax:0,total:0});
    } else {
      const count = items.length;
      document.querySelector('.summary-row span:nth-child(1)').textContent = `Subtotal (${count} item${count!==1?'s':''})`;
    }
  }

  // Debounced input handling for live quantity updates
  function debounce(fn, wait) {
    let t; return function(...args){ clearTimeout(t); t = setTimeout(() => fn.apply(this,args), wait); };
  }

  document.addEventListener('input', debounce((e) => {
    const el = e.target;
    if (!(el instanceof HTMLInputElement)) return;
    if (!el.classList.contains('qty-input')) return;
    // Derive itemId from input id pattern: qty_ci123 -> ci123
    const inputId = el.id; // e.g., qty_ci15
    const itemId = inputId.replace('qty_', '');
    updateQty(itemId, 0);
  }, 300));

  function updateTotals() {
    let subtotal = 0;
    let itemCount = 0;

    // Calculate subtotal from visible items
    document.querySelectorAll('.cart-item').forEach(item => {
      const priceText = item.querySelector('.item-price').textContent;
      const price = parseFloat(priceText.replace('ETB ', ''));
      subtotal += price;
      itemCount++;
    });

    // Calculate shipping (free over ETB 2000, otherwise ETB 150)
    const shipping = subtotal >= 2000 ? 0 : (subtotal > 0 ? 150 : 0);

    // Calculate tax (8.4%)
    const taxRate = 0.084;
    const tax = subtotal * taxRate;

    // Calculate total
    const total = subtotal + shipping + tax;

    // Update display
    document.getElementById('subtotal').textContent = 'ETB ' + subtotal.toFixed(2);
    document.getElementById('shipping').textContent = shipping === 0 ? 'FREE' : 'ETB ' + shipping.toFixed(2);
    document.getElementById('tax').textContent = 'ETB ' + tax.toFixed(2);
    document.getElementById('total').textContent = 'ETB ' + total.toFixed(2);

    // Update item count label
    const countLabel = document.querySelector('.summary-row span:first-child');
    if (countLabel) {
      countLabel.textContent = `Subtotal (${itemCount} item${itemCount !== 1 ? 's' : ''})`;
    }
  }

  function proceedToCheckout() {
    window.location.href = './checkout.php';
  }

  // Initialize totals on page load
  document.addEventListener('DOMContentLoaded', function () {
    updateTotals();
  });
</script>

<?php include '../includes/footer.php'; ?>