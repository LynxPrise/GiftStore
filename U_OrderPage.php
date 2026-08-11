<?php
session_start();
require_once 'U_db.php'; // Includes $pdo from U_db.php

$order_status_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        // Append recipient info to card message if mode is Delivery
        $recipient_details = "";
        if (isset($_POST['mode_of_transpo']) && $_POST['mode_of_transpo'] == '1') {
            $rec_name = trim($_POST['recipient_name'] ?? '');
            $rec_phone = trim($_POST['recipient_contact'] ?? '');
            if ($rec_name || $rec_phone) {
                $recipient_details = "[Recipient: {$rec_name} | Contact: {$rec_phone}]\n";
            }
        }

        // Combine recipient info with user's card message / special instructions
        $final_card_message = $recipient_details . trim($_POST['notes'] ?? '');

        // Prepare database insertion matching updated schema
        $sql = "INSERT INTO orders (
                    user_id, products_id, full_name, phone_number, address, 
                    product_name, price, mode_of_transpo, date_of_pickup, 
                    product_image, status, card_message
                ) VALUES (
                    :user_id, :products_id, :full_name, :phone_number, :address, 
                    :product_name, :price, :mode_of_transpo, :date_of_pickup, 
                    :product_image, 'pending', :card_message
                )";

        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':user_id'         => !empty($_POST['user_id']) ? intval($_POST['user_id']) : NULL,
            ':products_id'     => !empty($_POST['products_id']) ? intval($_POST['products_id']) : NULL,
            ':full_name'       => trim($_POST['full_name'] ?? ''),
            ':phone_number'    => trim($_POST['phone_number'] ?? ''),
            ':address'         => trim($_POST['address'] ?? ''),
            ':product_name'    => trim($_POST['product_name'] ?? ''),
            ':price'           => floatval($_POST['price'] ?? 0),
            ':mode_of_transpo' => intval($_POST['mode_of_transpo'] ?? 1),
            ':date_of_pickup'  => !empty($_POST['date_of_pickup']) ? $_POST['date_of_pickup'] : NULL,
            ':product_image'   => trim($_POST['product_image'] ?? ''),
            ':card_message'    => $final_card_message
        ]);

        echo "<script>alert('Order placed successfully!'); window.location.href='U_OrderPage.php';</script>";
        exit;

    } catch (PDOException $e) {
        $order_status_message = "<div style='background:#ffebee; color:#d32f2f; padding:12px; border-radius:12px; text-align:center; margin-bottom:20px; font-weight:600;'>Error saving order: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Complete Your Order | LynxPrise</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..700;1,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sacramento&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-cream: #fff9f6;
      --bg-soft-pink: #fdeee8;
      --card-bg: #fffbf9;
      --accent-pink: #d9658b;
      --accent-pink-hover: #c45075;
      --text-dark: #3b2219;
      --text-muted: #785a50;
      --gold-border: #e8c3b0;
      --gold-accent: #c28851;
      --radius-lg: 24px;
      --radius-md: 16px;
      --radius-btn: 30px;
      --success-green: #2e7d32;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-soft-pink); color: var(--text-dark); line-height: 1.6; }

    /* Navigation Bar */
    .lp-nav { position: sticky; top: 0; z-index: 1000; background: var(--bg-cream); padding: 18px 5%; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(59, 34, 25, 0.05); }
    .lp-logo { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; color: var(--text-dark); text-decoration: none; }
    .lp-logo span { font-family: 'Sacramento', cursive; color: var(--accent-pink); font-size: 32px; margin-left: 2px; }

    .lp-nav-links { display: flex; gap: 30px; list-style: none; }
    .lp-nav-links a { text-decoration: none; color: var(--text-dark); font-weight: 500; font-size: 15px; transition: color 0.2s; }
    .lp-nav-links a:hover { color: var(--accent-pink); }

    .btn-nav { background-color: var(--accent-pink); color: #fff; padding: 10px 24px; border-radius: var(--radius-btn); text-decoration: none; font-weight: 600; font-size: 14px; transition: background-color 0.2s; }
    .btn-nav:hover { background-color: var(--accent-pink-hover); }

    /* Container Wrapper */
    .lp-wrapper { max-width: 900px; margin: 0 auto; padding: 40px 20px 60px; }
    .lp-order-header { text-align: center; margin-bottom: 30px; }
    .lp-order-title { font-family: 'Playfair Display', serif; font-size: 42px; color: var(--text-dark); }
    .lp-order-subtitle { font-size: 16px; color: var(--text-muted); margin-top: 8px; }

    /* Category Cards Grid */
    .lp-category-section { margin-bottom: 40px; }
    .lp-category-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .lp-cat-card { background: var(--card-bg); border: 1px solid var(--gold-border); border-radius: var(--radius-md); padding: 16px; text-align: center; cursor: pointer; transition: transform 0.2s; }
    .lp-cat-card:hover { transform: translateY(-4px); }
    .lp-cat-img { width: 100%; height: 140px; object-fit: cover; border-radius: 12px; margin-bottom: 12px; }

    /* Modal Popups */
    .lp-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(59, 34, 25, 0.6); backdrop-filter: blur(3px); z-index: 2000; justify-content: center; align-items: center; }
    .lp-modal-content { background: var(--bg-cream); border-radius: var(--radius-lg); padding: 32px; max-width: 750px; width: 90%; max-height: 85vh; overflow-y: auto; position: relative; border: 1px solid var(--gold-border); }
    .lp-modal-close { position: absolute; top: 16px; right: 20px; font-size: 28px; border: none; background: none; cursor: pointer; }
    .lp-modal-title { font-family: 'Playfair Display', serif; font-size: 26px; margin-bottom: 20px; border-bottom: 1px solid var(--gold-border); padding-bottom: 10px; }

    .lp-product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; }
    .lp-product-card { background: #fff; border: 1px solid var(--gold-border); border-radius: var(--radius-md); padding: 16px; text-align: center; }
    .lp-product-card img { width: 100%; height: 130px; object-fit: cover; border-radius: 10px; margin-bottom: 10px; }
    .btn-add { background-color: var(--accent-pink); color: #fff; border: none; padding: 10px 16px; border-radius: var(--radius-btn); font-weight: 600; font-size: 13px; cursor: pointer; margin-top: 8px; width: 100%; }

    /* Form Layout */
    .lp-order-form { background-color: var(--card-bg); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 40px; display: flex; flex-direction: column; gap: 28px; box-shadow: 0 8px 30px rgba(59, 34, 25, 0.04); }
    .lp-fieldset { border: none; display: flex; flex-direction: column; gap: 18px; }
    .lp-legend { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 600; color: var(--text-dark); border-bottom: 1px solid var(--bg-soft-pink); padding-bottom: 8px; width: 100%; }

    .lp-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .lp-field { display: flex; flex-direction: column; gap: 6px; }
    .lp-field label { font-size: 14px; font-weight: 600; color: var(--text-dark); }
    .lp-input, .lp-textarea, .lp-select { width: 100%; padding: 12px 16px; background-color: #fff; border: 1px solid var(--gold-border); border-radius: var(--radius-md); font-family: inherit; font-size: 14px; outline: none; }

    /* Cart Summary List */
    .lp-summary-box { background-color: var(--bg-cream); border: 1px dashed var(--gold-border); padding: 16px; border-radius: var(--radius-md); }
    .cart-item-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid var(--bg-soft-pink); }
    .cart-item-row:last-child { border-bottom: none; }
    .btn-remove { background: none; border: none; color: #d32f2f; cursor: pointer; font-size: 12px; margin-left: 8px; }

    .btn-submit { background-color: var(--accent-pink); color: #fff; padding: 16px; border: none; border-radius: var(--radius-btn); font-weight: 700; font-size: 16px; cursor: pointer; width: 100%; }

    @media (max-width: 640px) {
      .lp-grid-2, .lp-category-grid { grid-template-columns: 1fr; }
      .lp-order-form { padding: 24px 18px; }
    }
  </style>
</head>
<body>
  <!-- Navigation Bar -->
  <nav class="lp-nav">
    <a href="#" class="lp-logo">Lynx<span>Prise</span></a>
    <ul class="lp-nav-links">
      <li><a href="index.php#categories">Categories</a></li>
      <li><a href="index.php#how-it-works">How it works</a></li>
      <li><a href="U_OrderPage.php">Order</a></li>
    </ul>
    <a href="U_OrderPage.php" class="btn-nav">Order now</a>
  </nav>

  <main class="lp-wrapper">
    <div class="lp-order-header">
      <h1 class="lp-order-title">Complete Your LynxPrise Order</h1>
      <p class="lp-order-subtitle">Select items across categories or specify custom gift items below.</p>
    </div>

    <?= $order_status_message ?>

    <!-- Category Popups Trigger Grid -->
    <section class="lp-category-section" id="categories">
      <div class="lp-category-grid">
        <div class="lp-cat-card" onclick="openModal('cat-bouquets')">
          <img src="Assets/Images/category-flowers.jpg" alt="Bouquets" class="lp-cat-img" />
          <h3>Flower Bouquets</h3>
        </div>
        <div class="lp-cat-card" onclick="openModal('cat-cakes')">
          <img src="Assets/Images/category-cakes.jpg" alt="Bento Cakes" class="lp-cat-img" />
          <h3>Celebration Cakes</h3>
        </div>
        <div class="lp-cat-card" onclick="openModal('cat-balloons')">
          <img src="Assets/Images/category-balloons.jpg" alt="Balloons" class="lp-cat-img" />
          <h3>Balloon Decor</h3>
        </div>
      </div>
    </section>

    <!-- Category Modal: Bouquets -->
    <div id="cat-bouquets" class="lp-modal-overlay">
      <div class="lp-modal-content">
        <button class="lp-modal-close" onclick="closeModal('cat-bouquets')">&times;</button>
        <h2 class="lp-modal-title">Flower Bouquets</h2>
        <div class="lp-product-grid">
          <div class="lp-product-card">
            <img src="Assets/Images/med-fuz.jpg" alt="Med Fuzzy Flower Bouquet" />
            <h4>Med Fuzzy Flower Bouquet</h4>
            <p style="color:var(--accent-pink); font-weight:700;">₱250</p>
            <button class="btn-add" onclick="addItemToOrder(101, 'Med Fuzzy Flower Bouquet', 250, 'Assets/Images/med-fuz.jpg', 'cat-bouquets')">+ Add Item</button>
          </div>
          <div class="lp-product-card">
            <img src="Assets/Images/round.jpg" alt="Round Flower Bouquet" />
            <h4>.5 Round Flower Bouquet</h4>
            <p style="color:var(--accent-pink); font-weight:700;">₱400</p>
            <button class="btn-add" onclick="addItemToOrder(102, '.5 Round Flower Bouquet', 400, 'Assets/Images/round.jpg', 'cat-bouquets')">+ Add Item</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Modal: Cakes -->
    <div id="cat-cakes" class="lp-modal-overlay">
      <div class="lp-modal-content">
        <button class="lp-modal-close" onclick="closeModal('cat-cakes')">&times;</button>
        <h2 class="lp-modal-title">Celebration Cakes</h2>
        <div class="lp-product-grid">
          <div class="lp-product-card">
            <img src="Assets/Images/bento.jpg" alt="Bento Cake" />
            <h4>Custom Bento Cake</h4>
            <p style="color:var(--accent-pink); font-weight:700;">₱350</p>
            <button class="btn-add" onclick="addItemToOrder(201, 'Bento Cake', 350, 'Assets/Images/bento.jpg', 'cat-cakes')">+ Add Item</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Modal: Balloons -->
    <div id="cat-balloons" class="lp-modal-overlay">
      <div class="lp-modal-content">
        <button class="lp-modal-close" onclick="closeModal('cat-balloons')">&times;</button>
        <h2 class="lp-modal-title">Balloon Decor</h2>
        <div class="lp-product-grid">
          <div class="lp-product-card">
            <img src="Assets/Images/balloon.jpg" alt="Hot Air Balloon Box" />
            <h4>Hot Air Balloon Box</h4>
            <p style="color:var(--accent-pink); font-weight:700;">₱500</p>
            <button class="btn-add" onclick="addItemToOrder(301, 'Hot Air Balloon Box', 500, 'Assets/Images/balloon.jpg', 'cat-balloons')">+ Add Item</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Order Form -->
    <form class="lp-order-form" id="checkout-section" method="POST" action="U_OrderPage.php">
      
      <!-- Hidden Table Mapped Fields -->
      <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?? '' ?>" />
      <input type="hidden" name="products_id" id="form_products_id" value="" />
      <input type="hidden" name="product_image" id="form_product_image" value="" />
      <input type="hidden" name="product_name" id="form_product_name" required />
      <input type="hidden" name="price" id="form_price" value="0.00" required />

      <!-- Step 1: Order Items & Calculated Total -->
      <fieldset class="lp-fieldset">
        <legend class="lp-legend">1. Order Items Summary</legend>
        <div class="lp-summary-box">
          <div id="cart-list-container">
            <p style="color:var(--text-muted); font-size:14px;">No catalog items added yet. Click categories above to add items.</p>
          </div>
          <hr style="border:none; border-top: 1px dashed var(--gold-border); margin:12px 0;" />
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <strong>Calculated Total Amount:</strong>
            <span id="summary-total-price" style="color: var(--accent-pink); font-weight:700; font-size:20px; margin-left:auto;">₱0.00</span>
          </div>
        </div>
      </fieldset>

      <!-- Step 2: Customer Details -->
      <fieldset class="lp-fieldset">
        <legend class="lp-legend">2. Customer Details</legend>
        
        <div class="lp-grid-2">
          <div class="lp-field">
            <label for="full_name">Your Full Name</label>
            <input class="lp-input" id="full_name" name="full_name" required placeholder="Juan Dela Cruz" />
          </div>

          <div class="lp-field">
            <label for="phone_number">Customer Contact Number</label>
            <input class="lp-input" id="phone_number" name="phone_number" type="tel" required placeholder="09XX XXX XXXX" />
          </div>
        </div>
      </fieldset>

      <!-- Step 3: Logistics & Options -->
      <fieldset class="lp-fieldset">
        <legend class="lp-legend">3. Fulfillment & Options</legend>
        
        <div class="lp-grid-2">
          <div class="lp-field">
            <label for="mode_of_transpo">Fulfillment Option</label>
            <select class="lp-select" id="mode_of_transpo" name="mode_of_transpo" onchange="toggleFulfillmentMode()" required>
              <option value="1">Delivery (with minimal delivery fee)</option>
              <option value="2">Store Pickup</option>
            </select>
          </div>

          <div class="lp-field">
            <label for="date_of_pickup" id="date-label">Preferred Delivery Date & Time</label>
            <input class="lp-input" id="date_of_pickup" name="date_of_pickup" type="datetime-local" required />
          </div>
        </div>

        <!-- DYNAMIC CONTAINER 1: Delivery Fields -->
        <div id="delivery-fields" style="display: flex; flex-direction: column; gap: 16px;">
          <div class="lp-grid-2">
            <div class="lp-field">
              <label for="recipient_name">Recipient's Name</label>
              <input class="lp-input" id="recipient_name" name="recipient_name" placeholder="Recipient's Name" />
            </div>
            <div class="lp-field">
              <label for="recipient_contact">Recipient's Contact Number (Optional)</label>
              <input class="lp-input" id="recipient_contact" name="recipient_contact" placeholder="09XX XXX XXXX" />
            </div>
          </div>

          <div class="lp-field">
            <label for="address">Delivery Address</label>
            <input class="lp-input" id="address" name="address" required placeholder="Street / Barangay / Municipality" />
          </div>
        </div>

        <!-- DYNAMIC CONTAINER 2: Store Pickup Location -->
        <div id="pickup-info" style="display: none; background: var(--bg-cream); border: 1px dashed var(--gold-border); padding: 18px; border-radius: var(--radius-md);">
          <h4 style="color: var(--text-dark); margin-bottom: 6px; font-family: 'Playfair Display', serif;">Store Pickup Location</h4>
          <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 12px;">
            📍 <strong>LynxPrise Shop:</strong> Brgy. Malitbogay, Javier, Leyte
          </p>
          <div style="width: 100%; height: 220px; border-radius: 12px; overflow: hidden; border: 1px solid var(--gold-border);">
            <iframe 
              width="100%" 
              height="100%" 
              frameborder="0" 
              scrolling="no" 
              marginheight="0" 
              marginwidth="0" 
              src="https://maps.google.com/maps?q=Malitbogay%20Javier%20Leyte&t=&z=14&ie=UTF8&iwloc=&output=embed">
            </iframe>
          </div>
        </div>
      </fieldset>

      <!-- Step 4: Notes (Custom Card Message / Special Instructions) -->
      <fieldset class="lp-fieldset">
        <legend class="lp-legend">4. Additional Information</legend>

        <div class="lp-field">
          <label for="notes">Custom card message / special instructions</label>
          <textarea class="lp-textarea" id="notes" name="notes" rows="3" placeholder="Write your card message, dedication, color preferences, ribbon requests, or extra custom instructions..."></textarea>
        </div>
      </fieldset>

      <button type="submit" name="place_order" id="place-order-btn" class="btn-submit">Place My Order</button>
    </form>
  </main>

  <script>
    let orderCart = [];

    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function addItemToOrder(id, name, price, img, modalId) {
      const existing = orderCart.find(item => item.id === id);
      if (existing) {
        existing.qty += 1;
      } else {
        orderCart.push({ id: id, name: name, price: price, img: img, qty: 1 });
      }

      renderCart();
      closeModal(modalId);
      document.getElementById('checkout-section').scrollIntoView({ behavior: 'smooth' });
    }

    function removeItemFromCart(id) {
      orderCart = orderCart.filter(item => item.id !== id);
      renderCart();
    }

    function renderCart() {
      const container = document.getElementById('cart-list-container');
      const formProductName = document.getElementById('form_product_name');
      const formPrice = document.getElementById('form_price');
      const formProductId = document.getElementById('form_products_id');
      const formProductImg = document.getElementById('form_product_image');
      const totalPriceSpan = document.getElementById('summary-total-price');

      if (orderCart.length === 0) {
        container.innerHTML = '<p style="color:var(--text-muted); font-size:14px;">No catalog items added yet. Click categories above to add items.</p>';
        formProductName.value = '';
        formPrice.value = '0.00';
        formProductId.value = '';
        formProductImg.value = '';
        totalPriceSpan.textContent = '₱0.00';
        return;
      }

      let html = '';
      let formattedNames = [];
      let grandTotal = 0;
      let primaryIds = [];
      let primaryImgs = [];

      orderCart.forEach(item => {
        const itemTotal = item.price * item.qty;
        grandTotal += itemTotal;
        formattedNames.push(`${item.qty}x ${item.name}`);
        primaryIds.push(item.id);
        primaryImgs.push(item.img);

        html += `
          <div class="cart-item-row">
            <div>
              <strong>${item.qty}x</strong> ${item.name}
              <button type="button" class="btn-remove" onclick="removeItemFromCart(${item.id})">Remove</button>
            </div>
            <div><strong>₱${itemTotal.toLocaleString('en-US', { minimumFractionDigits: 2 })}</strong></div>
          </div>
        `;
      });

      container.innerHTML = html;
      
      formProductName.value = formattedNames.join(', ');
      formPrice.value = grandTotal.toFixed(2);
      formProductId.value = primaryIds.join(',');
      formProductImg.value = primaryImgs[0] || '';
      totalPriceSpan.textContent = '₱' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2 });
    }

    function toggleFulfillmentMode() {
      const transpoMode = document.getElementById('mode_of_transpo').value;
      const deliveryFields = document.getElementById('delivery-fields');
      const pickupInfo = document.getElementById('pickup-info');
      const addressInput = document.getElementById('address');
      const dateLabel = document.getElementById('date-label');

      if (transpoMode === '2') { // Store Pickup
        deliveryFields.style.display = 'none';
        pickupInfo.style.display = 'block';
        addressInput.removeAttribute('required');
        addressInput.value = 'Store Pickup - Brgy. Malitbogay, Javier, Leyte';
        dateLabel.textContent = 'Preferred Pickup Date & Time';
      } else { // Delivery
        deliveryFields.style.display = 'flex';
        pickupInfo.style.display = 'none';
        addressInput.setAttribute('required', 'required');
        if (addressInput.value.startsWith('Store Pickup')) {
          addressInput.value = '';
        }
        dateLabel.textContent = 'Preferred Delivery Date & Time';
      }
    }

    document.addEventListener('DOMContentLoaded', toggleFulfillmentMode);
  </script>
</body>
</html>