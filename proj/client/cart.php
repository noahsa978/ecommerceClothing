<?php
$page_title = 'Shopping Cart — Ecom Clothing';
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
      <div class="cart-item">
        <img src="https://images.unsplash.com/photo-1520975954732-35dd222996f2?q=80&w=200&auto=format&fit=crop" alt="Premium Cotton Tee" />
        <div class="item-details">
          <h3>Premium Cotton Tee</h3>
          <p>Soft, breathable premium cotton</p>
          <div class="variant">Size: M • Color: Black</div>
        </div>
        <div class="qty-controls">
          <button class="qty-btn" onclick="updateQty('item1', -1)">−</button>
          <input class="qty-input" id="qty1" type="number" value="2" min="1" onchange="updateQty('item1', 0)">
          <button class="qty-btn" onclick="updateQty('item1', 1)">+</button>
        </div>
        <div class="item-price" id="price1">$48.00</div>
        <button class="remove-btn" onclick="removeItem('item1')">Remove</button>
      </div>

      <div class="cart-item">
        <img src="https://images.unsplash.com/photo-1520975926867-b9d9f7cb7da3?q=80&w=200&auto=format&fit=crop" alt="Classic Hoodie" />
        <div class="item-details">
          <h3>Classic Hoodie</h3>
          <p>Comfortable pullover hoodie</p>
          <div class="variant">Size: L • Color: Navy</div>
        </div>
        <div class="qty-controls">
          <button class="qty-btn" onclick="updateQty('item2', -1)">−</button>
          <input class="qty-input" id="qty2" type="number" value="1" min="1" onchange="updateQty('item2', 0)">
          <button class="qty-btn" onclick="updateQty('item2', 1)">+</button>
        </div>
        <div class="item-price" id="price2">$59.00</div>
        <button class="remove-btn" onclick="removeItem('item2')">Remove</button>
      </div>

      <div class="cart-item">
        <img src="https://images.unsplash.com/photo-1520975918311-77d9d6dd0a5f?q=80&w=200&auto=format&fit=crop" alt="Denim Jacket" />
        <div class="item-details">
          <h3>Denim Jacket</h3>
          <p>Classic denim jacket</p>
          <div class="variant">Size: M • Color: Blue</div>
        </div>
        <div class="qty-controls">
          <button class="qty-btn" onclick="updateQty('item3', -1)">−</button>
          <input class="qty-input" id="qty3" type="number" value="1" min="1" onchange="updateQty('item3', 0)">
          <button class="qty-btn" onclick="updateQty('item3', 1)">+</button>
        </div>
        <div class="item-price" id="price3">$89.00</div>
        <button class="remove-btn" onclick="removeItem('item3')">Remove</button>
      </div>
    </div>

    <div class="cart-summary">
      <h2 style="margin: 0 0 16px; font-size: 20px">Order Summary</h2>
      
      <div class="summary-row">
        <span>Subtotal (3 items)</span>
        <span id="subtotal">$196.00</span>
      </div>
      <div class="summary-row">
        <span>Shipping</span>
        <span id="shipping">$9.99</span>
      </div>
      <div class="summary-row">
        <span>Tax</span>
        <span id="tax">$16.48</span>
      </div>
      <div class="summary-row total">
        <span>Total</span>
        <span id="total">$222.47</span>
      </div>

      <button class="checkout-btn" onclick="proceedToCheckout()">Proceed to Checkout</button>
      <a href="./shop.php" class="continue-shopping">← Continue Shopping</a>
    </div>
  </div>
</main>

<script>
  // Product data
  const products = {
    'item1': { price: 24.00, name: 'Premium Cotton Tee' },
    'item2': { price: 59.00, name: 'Classic Hoodie' },
    'item3': { price: 89.00, name: 'Denim Jacket' }
  };

  function updateQty(itemId, change) {
    const qtyInput = document.getElementById(itemId.replace('item', 'qty'));
    const currentQty = parseInt(qtyInput.value);
    const newQty = Math.max(1, currentQty + change);
    qtyInput.value = newQty;
    updateItemPrice(itemId, newQty);
    updateTotals();
  }

  function updateItemPrice(itemId, qty) {
    const priceElement = document.getElementById(itemId.replace('item', 'price'));
    const unitPrice = products[itemId].price;
    const totalPrice = unitPrice * qty;
    priceElement.textContent = '$' + totalPrice.toFixed(2);
  }

  function removeItem(itemId) {
    if (confirm('Remove this item from cart?')) {
      const itemElement = document.querySelector(`[onclick*="${itemId}"]`).closest('.cart-item');
      itemElement.remove();
      updateTotals();
    }
  }

  function updateTotals() {
    let subtotal = 0;
    let itemCount = 0;

    // Calculate subtotal from visible items
    document.querySelectorAll('.cart-item').forEach(item => {
      const priceText = item.querySelector('.item-price').textContent;
      const price = parseFloat(priceText.replace('$', ''));
      subtotal += price;
      itemCount++;
    });

    // Calculate shipping (free over $100, otherwise $9.99)
    const shipping = subtotal >= 100 ? 0 : 9.99;

    // Calculate tax (8.4%)
    const taxRate = 0.084;
    const tax = subtotal * taxRate;

    // Calculate total
    const total = subtotal + shipping + tax;

    // Update display
    document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('shipping').textContent = shipping === 0 ? 'FREE' : '$' + shipping.toFixed(2);
    document.getElementById('tax').textContent = '$' + tax.toFixed(2);
    document.getElementById('total').textContent = '$' + total.toFixed(2);

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