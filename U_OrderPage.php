<?php
session_start();

require_once 'U_db.php'; // Includes $pdo from U_db.php

$order_status_message = "";

// Display styled session notification if order was successful
if (isset($_SESSION['order_success'])) {
    $order_status_message = "<div style='background: var(--bg-cream); color: var(--accent-pink); border: 1px solid var(--gold-border); padding:14px; border-radius:16px; text-align:center; margin-bottom:20px; font-weight:600;'>🌸 " . htmlspecialchars($_SESSION['order_success']) . "</div>";
    unset($_SESSION['order_success']);
}

// Fetch dynamic categories, products, blocked dates, and locations from database
$categories = [];
$categories_products = [];
$blocked_dates = [];
$active_locations = [];
$date_location_overrides = [];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Fetch categories
    $stmt_cats = $pdo->query("SELECT * FROM categories");
    $categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

    // Initialize products array for each category
    foreach ($categories as $index => $cat) {
        $catId = $cat['categoryId'] ?? ($cat['id'] ?? ($cat['category_id'] ?? $index));
        $catKey = 'cat-' . $catId;
        $categories_products[$catKey] = [];
    }

    // 2. Fetch products dynamically
    $stmt_products = $pdo->query("SELECT * FROM products");
    $all_products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

    // Group products into their respective categories
    foreach ($all_products as $prod) {
        $matched = false;

        $prodCatId = $prod['categoryId'] ?? ($prod['category_id'] ?? null);
        $prodCatName = $prod['category'] ?? ($prod['categoryName'] ?? '');

        foreach ($categories as $index => $cat) {
            $catId = $cat['categoryId'] ?? ($cat['id'] ?? ($cat['category_id'] ?? $index));
            $catKey = 'cat-' . $catId;
            $catNameDB = $cat['categoryName'] ?? ($cat['categoryname'] ?? '');

            if (
                (!empty($prodCatId) && $prodCatId == $catId) ||
                (!empty($prodCatName) && strtolower(trim($prodCatName)) === strtolower(trim($catNameDB)))
            ) {
                $categories_products[$catKey][] = $prod;
                $matched = true;
                break;
            }
        }

        if (!$matched && !empty($categories)) {
            $firstCatId = $categories[0]['categoryId'] ?? ($categories[0]['id'] ?? ($categories[0]['category_id'] ?? 0));
            $firstCatKey = 'cat-' . $firstCatId;
            $categories_products[$firstCatKey][] = $prod;
        }
    }

    // 3. Fetch blocked dates directly from `blocked_dates` table
    $stmt_blocked = $pdo->query("SELECT blocked_date, service_type, reason FROM blocked_dates");
    if ($stmt_blocked) {
        $blocked_dates = $stmt_blocked->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. Fetch active store locations dynamically
    $stmt_locations = $pdo->query("SELECT id, location_name, address, latitude, longitude FROM store_locations WHERE is_active = 1");
    if ($stmt_locations) {
        $active_locations = $stmt_locations->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. Fetch date location availability overrides
    $stmt_overrides = $pdo->query("SELECT available_date, location_id, is_available FROM date_location_availability");
    if ($stmt_overrides) {
        while ($row = $stmt_overrides->fetch(PDO::FETCH_ASSOC)) {
            $date_location_overrides[$row['available_date']][$row['location_id']] = (int)$row['is_available'];
        }
    }

} catch (PDOException $e) {
    // Handle or log query exceptions if needed
}

// Convert dataset variables for JS evaluation
$js_blocked_dates = json_encode($blocked_dates);
$js_active_locations = json_encode($active_locations);
$js_location_overrides = json_encode($date_location_overrides);

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
  try {
    $recipient_details = "";
    if (isset($_POST['mode_of_transpo']) && $_POST['mode_of_transpo'] == '1') {
      $rec_name = trim($_POST['recipient_name'] ?? '');
      $rec_phone = trim($_POST['recipient_contact'] ?? '');
      if ($rec_name || $rec_phone) {
        $recipient_details = "[Recipient: {$rec_name} | Contact: {$rec_phone}]\n";
      }
    } else {
      $pickup_branch = trim($_POST['pickup_branch'] ?? 'Main Branch - Malitbogay');
      $recipient_details = "[Pickup Branch: {$pickup_branch}]\n";
    }

    $final_card_message = $recipient_details . trim($_POST['notes'] ?? '');
    $lat = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : NULL;
    $lng = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : NULL;

    // Combine Date + Time input into datetime format
    $pickup_date_formatted = NULL;
    if (!empty($_POST['selected_date_val'])) {
      $time_val = !empty($_POST['selected_time_val']) ? $_POST['selected_time_val'] : '10:00';
      $pickup_date_formatted = $_POST['selected_date_val'] . ' ' . $time_val . ':00';
    }

    // Mode of payment handling
    $mode_of_payment = intval($_POST['mode_of_payment'] ?? 0);
    $initial_payment_status = ($mode_of_payment === 1) ? 'PENDING' : 'UNPAID';

    $sql = "INSERT INTO orders (
          user_id, products_id, full_name, phone_number, address, 
          latitude, longitude, product_name, price, mode_of_transpo, 
          mode_of_payment, payment_status, date_of_pickup, product_image, status, card_message
        ) VALUES (
          :user_id, :products_id, :full_name, :phone_number, :address, 
          :latitude, :longitude, :product_name, :price, :mode_of_transpo, 
          :mode_of_payment, :payment_status, :date_of_pickup, :product_image, 'pending', :card_message
        )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
      ':user_id'          => !empty($_POST['user_id']) ? intval($_POST['user_id']) : NULL,
      ':products_id'      => !empty($_POST['products_id']) ? intval($_POST['products_id']) : NULL,
      ':full_name'        => trim($_POST['full_name'] ?? ''),
      ':phone_number'     => trim($_POST['phone_number'] ?? ''),
      ':address'          => trim($_POST['address'] ?? ''),
      ':latitude'         => $lat,
      ':longitude'        => $lng,
      ':product_name'     => trim($_POST['product_name'] ?? ''),
      ':price'            => floatval($_POST['price'] ?? 0),
      ':mode_of_transpo'  => intval($_POST['mode_of_transpo'] ?? 1),
      ':mode_of_payment'  => $mode_of_payment,
      ':payment_status'   => $initial_payment_status,
      ':date_of_pickup'   => $pickup_date_formatted,
      ':product_image'    => trim($_POST['product_image'] ?? ''),
      ':card_message'     => $final_card_message
    ]);

    $last_id = $pdo->lastInsertId();

    // Redirect based on selected payment method
    if ($mode_of_payment === 1) {
      header("Location: create_checkout?order_id=" . $last_id);
    } else {
      header("Location: U_ThankYou?order_id=" . $last_id);
    }
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
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

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
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-soft-pink); color: var(--text-dark); line-height: 1.6; }

    .lp-nav { position: sticky; top: 0; z-index: 1000; background: var(--bg-cream); padding: 18px 5%; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(59, 34, 25, 0.05); }
    .lp-logo { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; color: var(--text-dark); text-decoration: none; }
    .lp-logo span { font-family: 'Sacramento', cursive; color: var(--accent-pink); font-size: 32px; margin-left: 2px; }

    .lp-nav-links { display: flex; gap: 30px; list-style: none; }
    .lp-nav-links a { text-decoration: none; color: var(--text-dark); font-weight: 500; font-size: 15px; transition: color 0.2s; }
    .lp-nav-links a:hover { color: var(--accent-pink); }

    .btn-nav { background-color: var(--accent-pink); color: #fff; padding: 10px 24px; border-radius: var(--radius-btn); text-decoration: none; font-weight: 600; font-size: 14px; transition: background-color 0.2s; }
    .btn-nav:hover { background-color: var(--accent-pink-hover); }

    .lp-wrapper { max-width: 900px; margin: 0 auto; padding: 40px 20px 60px; }
    .lp-order-header { text-align: center; margin-bottom: 30px; }
    .lp-order-title { font-family: 'Playfair Display', serif; font-size: 42px; color: var(--text-dark); }
    .lp-order-subtitle { font-size: 16px; color: var(--text-muted); margin-top: 8px; }

    .lp-category-section { margin-bottom: 40px; }
    .lp-category-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
    .lp-cat-card { background: var(--card-bg); border: 1px solid var(--gold-border); border-radius: var(--radius-md); padding: 16px; text-align: center; cursor: pointer; transition: transform 0.2s; display: flex; flex-direction: column; align-items: center; }
    .lp-cat-card:hover { transform: translateY(-4px); }
    .lp-cat-img { width: 100%; height: 140px; object-fit: cover; border-radius: 12px; margin-bottom: 12px; }
    .lp-cat-title { font-size: 18px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px; }
    .lp-cat-desc { font-size: 13px; color: var(--text-muted); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .lp-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(59, 34, 25, 0.6); backdrop-filter: blur(3px); z-index: 2000; justify-content: center; align-items: center; }
    .lp-modal-content { background: var(--bg-cream); border-radius: var(--radius-lg); padding: 32px; max-width: 800px; width: 92%; max-height: 85vh; overflow-y: auto; position: relative; border: 1px solid var(--gold-border); display: flex; flex-direction: column; }
    .lp-modal-close { position: absolute; top: 16px; right: 20px; font-size: 28px; border: none; background: none; cursor: pointer; color: var(--text-dark); }
    .lp-modal-title { font-family: 'Playfair Display', serif; font-size: 26px; margin-bottom: 20px; border-bottom: 1px solid var(--gold-border); padding-bottom: 10px; }

    .lp-product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 18px; margin-bottom: 20px; }
    .lp-product-card { background: #fff; border: 1px solid var(--gold-border); border-radius: var(--radius-md); padding: 16px; text-align: center; display: flex; flex-direction: column; justify-content: space-between; position: relative; }
    .lp-product-card img { width: 100%; height: 140px; object-fit: cover; border-radius: 10px; margin-bottom: 10px; cursor: pointer; transition: opacity 0.2s; }
    .lp-product-card img:hover { opacity: 0.85; }
    
    .stock-badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; margin-bottom: 6px; }
    .stock-available { background-color: #e8f5e9; color: #2e7d32; }
    .stock-out { background-color: #ffebee; color: #c62828; }

    .prod-desc { font-size: 12px; color: var(--text-muted); margin: 6px 0 10px; line-height: 1.4; word-break: break-word; }
    .see-more-btn { color: var(--accent-pink); font-weight: 600; cursor: pointer; text-decoration: underline; background: none; border: none; font-size: 12px; padding: 0; }

    .btn-add { background-color: var(--accent-pink); color: #fff; border: none; padding: 10px 16px; border-radius: var(--radius-btn); font-weight: 600; font-size: 13px; cursor: pointer; margin-top: 8px; width: 100%; transition: background-color 0.2s; }
    .btn-add:hover { background-color: var(--accent-pink-hover); }
    .btn-add:disabled { background-color: #ccc; cursor: not-allowed; }

    .lp-modal-footer { position: sticky; bottom: -32px; background: var(--bg-cream); border-top: 1px solid var(--gold-border); padding: 16px 0 0; margin-top: auto; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; z-index: 10; }
    .modal-checkout-btn { background-color: var(--text-dark); color: #fff; border: none; padding: 12px 24px; border-radius: var(--radius-btn); font-weight: 700; font-size: 14px; cursor: pointer; transition: background-color 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .modal-checkout-btn:hover { background-color: #24140e; }

    .item-added-toast { position: absolute; top: 12px; left: 50%; transform: translateX(-50%); background: var(--text-dark); color: #fff; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; opacity: 0; transition: opacity 0.3s ease; pointer-events: none; z-index: 5; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .item-added-toast.show { opacity: 1; }

    .lp-order-form { background-color: var(--card-bg); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 40px; display: flex; flex-direction: column; gap: 28px; box-shadow: 0 8px 30px rgba(59, 34, 25, 0.04); }
    .lp-fieldset { border: none; display: flex; flex-direction: column; gap: 18px; }
    .lp-legend { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 600; color: var(--text-dark); border-bottom: 1px solid var(--bg-soft-pink); padding-bottom: 8px; width: 100%; }

    .lp-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .lp-field { display: flex; flex-direction: column; gap: 6px; }
    .lp-field label { font-size: 14px; font-weight: 600; color: var(--text-dark); }
    .lp-input, .lp-textarea, .lp-select { width: 100%; padding: 12px 16px; background-color: #fff; border: 1px solid var(--gold-border); border-radius: var(--radius-md); font-family: inherit; font-size: 14px; outline: none; }

    .lp-summary-box { background-color: var(--bg-cream); border: 1px dashed var(--gold-border); padding: 16px; border-radius: var(--radius-md); }
    .cart-item-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid var(--bg-soft-pink); }
    .cart-item-row:last-child { border-bottom: none; }
    .btn-remove { background: none; border: none; color: #d32f2f; cursor: pointer; font-size: 12px; margin-left: 8px; }

    .btn-submit { background-color: var(--accent-pink); color: #fff; padding: 16px; border: none; border-radius: var(--radius-btn); font-weight: 700; font-size: 16px; cursor: pointer; width: 100%; }

    #image-lightbox { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.85); z-index: 3000; justify-content: center; align-items: center; cursor: zoom-out; }
    #image-lightbox img { max-width: 90%; max-height: 90%; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.5); }

    #delivery-map { width: 100%; height: 280px; border-radius: var(--radius-md); border: 1px solid var(--gold-border); z-index: 1; }
    #pickup-map { width: 100%; height: 220px; border-radius: var(--radius-md); border: 1px solid var(--gold-border); z-index: 1; }

    .date-picker-trigger { display: flex; align-items: center; justify-content: space-between; cursor: pointer; background: #fff; }

    .pink-calendar-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(59, 34, 25, 0.5); backdrop-filter: blur(2px); z-index: 2500; justify-content: center; align-items: center; }
    .pink-calendar-card { background: #fff; border-radius: var(--radius-lg); padding: 24px; width: 90%; max-width: 440px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid var(--gold-border); }
    .cal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .cal-header-title { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 700; color: var(--text-dark); }
    .cal-nav-btn { background: #fdf3f0; border: 1px solid var(--gold-border); border-radius: 12px; padding: 6px 14px; font-size: 13px; font-weight: 700; color: var(--text-dark); cursor: pointer; }
    .cal-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 10px; }
    .cal-days-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
    .cal-day-cell { background: #faf6f5; border-radius: 14px; aspect-ratio: 1 / 1; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; color: var(--text-dark); cursor: pointer; position: relative; transition: all 0.2s; }
    .cal-day-cell:hover:not(.disabled) { background: var(--bg-soft-pink); border: 1px solid var(--accent-pink); }
    .cal-day-cell.selected { background: var(--accent-pink) !important; color: #fff !important; }
    .cal-day-cell.selected .cal-tag { color: #fff !important; }
    .cal-day-cell.disabled { background: #f0f0f0; color: #bbb; cursor: not-allowed; opacity: 0.6; }
    .cal-tag { font-size: 8px; font-weight: 800; padding: 2px 4px; border-radius: 6px; margin-top: 2px; text-transform: uppercase; line-height: 1; }
    .tag-nodel { background: #ffe0b2; color: #e65100; }
    .tag-nopick { background: #e0f7fa; color: #006064; }
    .tag-closed { background: #ffebee; color: #c62828; }

      

    /* Payment Modal Styling */
    .payment-modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
    }

    .payment-modal-content {
      background: #fff;
      padding: 24px;
      border-radius: 16px;
      max-width: 380px;
      width: 90%;
      text-align: center;
      position: relative;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .modal-close-btn {
      position: absolute;
      top: 12px;
      right: 16px;
      border: none;
      background: transparent;
      font-size: 24px;
      cursor: pointer;
      color: #888;
    }

    .payment-qr-img {
      width: 200px;
      height: 200px;
      object-fit: contain;
      margin: 15px 0;
      border: 1px solid #eee;
      border-radius: 8px;
    }

    .modal-note {
      font-size: 13px;
      color: #666;
      line-height: 1.4;
    }

    .btn-confirm-modal {
      margin-top: 15px;
      background-color: #b84357;
      color: #fff;
      border: none;
      padding: 10px 24px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      width: 100%;
    } 




  .payment-selection-group {
  display: grid;
  grid-template-columns: 1fr 1fr; /* Places both options side-by-side */
  gap: 10px;
  margin-top: 10px;
}

.payment-option-card {
  display: flex;
  flex-direction: column; /* Stacks title above description inside each card */
  justify-content: center;
  padding: 10px 14px;
  border: 2px solid #ebd0d7;
  border-radius: 12px;
  cursor: pointer;
  background-color: #fff;
  transition: all 0.2s ease-in-out;
  text-align: left;
}

.payment-option-card:hover {
  border-color: #d17b88;
  background-color: #fffafb;
}

.payment-option-card.active {
  border-color: #b84357;
  background-color: #fff0f3;
}

.payment-title {
  display: block;
  font-weight: 600;
      color: #3b2219;
      font-size: 13px;
      line-height: 1.2;
    }

    .payment-desc {
      display: block;
      font-size: 11px;
      color: #7a6560;
      margin-top: 3px;
      line-height: 1.2;
    }

    /* Mobile responsive fallback so cards stack cleanly on small phone screens */
    @media (max-width: 480px) {
      .payment-selection-group {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 640px) {
      .lp-nav-links { display: none; }
      .lp-nav { padding: 12px 4%; }
      .lp-grid-2, .lp-category-grid { grid-template-columns: 1fr; }
      .lp-order-form { padding: 24px 18px; }
      .lp-modal-footer { flex-direction: column; align-items: stretch; text-align: center; }
      .modal-checkout-btn { justify-content: center; }
    }
  </style>
</head>
<body>

  <nav class="lp-nav">
    <a href="#" class="lp-logo">Lynx<span>Prise</span></a>
    <ul class="lp-nav-links">
      <li><a href="index#categories">Categories</a></li>
      <li><a href="index#testimonials">Feedbacks</a></li>
      <li><a href="U_OrderPage">Order</a></li>
    </ul>
    <a href="index#how-it-works" class="btn-nav">How this works</a>
  </nav>

  <main class="lp-wrapper">
    <div class="lp-order-header">
      <h1 class="lp-order-title">Complete Your LynxPrise Order</h1>
      <p class="lp-order-subtitle">Select items across categories or specify custom gift items below.</p>
    </div>

    <?= $order_status_message ?>

    <!-- Category Cards Section -->
    <section class="lp-category-section" id="categories">
      <div class="lp-category-grid">
        <?php if (!empty($categories)): ?>
          <?php foreach ($categories as $index => $cat): 
            $catId = $cat['categoryId'] ?? ($cat['id'] ?? ($cat['category_id'] ?? $index));
            $catName = htmlspecialchars($cat['categoryName'] ?? ($cat['categoryname'] ?? 'Category'));
            $catDesc = htmlspecialchars($cat['description'] ?? ($cat['categoryDescription'] ?? ''));
            $catImgRaw = $cat['categoryImage'] ?? ($cat['categoryimage'] ?? '');
            $catImg = htmlspecialchars(!empty($catImgRaw) ? $catImgRaw : 'Assets/Images/placeholder.jpg');
          ?>
            <div class="lp-cat-card" onclick="openModal('cat-<?= $catId ?>')">
              <img src="<?= $catImg ?>" alt="<?= $catName ?>" class="lp-cat-img" />
              <h3 class="lp-cat-title"><?= $catName ?></h3>
              <?php if ($catDesc !== ''): ?>
                <p class="lp-cat-desc"><?= $catDesc ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="grid-column: 1 / -1; text-align: center; color: var(--text-muted);">No categories available right now.</p>
        <?php endif; ?>
      </div>
    </section>

    <!-- Custom Pink Theme Alert Modal -->
    <div id="lp-alert-modal" class="lp-modal-overlay">
      <div class="lp-modal-content" style="max-width: 420px; text-align: center; padding: 28px 24px;">
        <div style="font-size: 38px; margin-bottom: 8px;">📍</div>
        <h3 style="font-family: 'Playfair Display', serif; font-size: 22px; color: var(--text-dark); margin-bottom: 8px;">Notice</h3>
        <p id="lp-alert-message" style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px; line-height: 1.5;"></p>
        <button type="button" onclick="closeCustomAlert()" class="btn-submit" style="padding: 10px 24px; font-size: 14px; width: auto; display: inline-block;">Got It</button>
      </div>
    </div>

    <!-- Fullscreen Lightbox -->
    <div id="image-lightbox" onclick="closeLightbox()">
      <img id="lightbox-img" src="" alt="Full Screen View" />
    </div>

    <!-- Custom Calendar Modal -->
    <div id="pink-calendar-modal" class="pink-calendar-modal">
      <div class="pink-calendar-card">
        <div class="cal-header">
          <button type="button" class="cal-nav-btn" onclick="navigateCalendarMonth(-1)">&lt;</button>
          <span class="cal-header-title" id="cal-month-year">Month Year</span>
          <button type="button" class="cal-nav-btn" onclick="navigateCalendarMonth(1)">&gt;</button>
        </div>
        <div class="cal-weekdays">
          <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
        </div>
        <div class="cal-days-grid" id="cal-days-container"></div>
        <div style="margin-top: 18px; text-align: right;">
          <button type="button" onclick="closePinkCalendar()" style="background: none; border: none; color: var(--text-muted); font-weight: 600; cursor: pointer; font-size: 13px;">Cancel</button>
        </div>
      </div>
    </div>

    <?php
    function renderCategoryProducts($modalId, $title, $products) {
    ?>
      <div id="<?= $modalId ?>" class="lp-modal-overlay">
        <div class="lp-modal-content">
          <button class="lp-modal-close" onclick="closeModal('<?= $modalId ?>')">&times;</button>
          <h2 class="lp-modal-title"><?= htmlspecialchars($title) ?></h2>
          <div class="lp-product-grid">
            <?php if (empty($products)): ?>
              <p style="color: var(--text-muted); grid-column: 1 / -1; text-align: center; padding: 20px;">No products available in this category yet.</p>
            <?php else: ?>
              <?php foreach ($products as $p): 
                $id = $p['productId'] ?? ($p['id'] ?? ($p['product_id'] ?? 0));
                $name = htmlspecialchars($p['productName'] ?? ($p['name'] ?? ($p['product_name'] ?? 'Product')));
                $price = floatval($p['productPrice'] ?? ($p['price'] ?? 0));
                $qty = intval($p['productStock'] ?? ($p['quantity'] ?? ($p['stock'] ?? 0)));
                
                $imgRaw = $p['productImage'] ?? ($p['image'] ?? ($p['product_image'] ?? ''));
                $img = htmlspecialchars(!empty($imgRaw) ? $imgRaw : 'Assets/Images/placeholder.jpg');
                
                $desc = trim($p['productDescription'] ?? ($p['description'] ?? ''));
                $isLongDesc = strlen($desc) > 80;
                $shortDesc = $isLongDesc ? substr($desc, 0, 80) . '...' : $desc;
              ?>
                <div class="lp-product-card">
                  <div id="toast-<?= $id ?>" class="item-added-toast">✓ Added!</div>
                  <div>
                    <img src="<?= $img ?>" alt="<?= $name ?>" onclick="viewFullscreenImage('<?= $img ?>')" title="Click to view full image" />
                    <h4><?= $name ?></h4>
                    <p style="color:var(--accent-pink); font-weight:700; font-size:16px;">₱<?= number_format($price, 2) ?></p>

                    <?php if ($qty > 0): ?>
                      <span class="stock-badge stock-available">Available: <?= $qty ?></span>
                    <?php else: ?>
                      <span class="stock-badge stock-out">Out of Stock</span>
                    <?php endif; ?>

                    <?php if ($desc !== ''): ?>
                      <div class="prod-desc">
                        <span id="desc-short-<?= $id ?>"><?= htmlspecialchars($shortDesc) ?></span>
                        <?php if ($isLongDesc): ?>
                          <span id="desc-full-<?= $id ?>" style="display: none;"><?= htmlspecialchars($desc) ?></span>
                          <button type="button" class="see-more-btn" onclick="toggleDesc(<?= $id ?>)" id="see-btn-<?= $id ?>">see more</button>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>

                  <button class="btn-add" 
                          onclick="addItemToOrder(<?= $id ?>, '<?= addslashes($name) ?>', <?= $price ?>, '<?= $img ?>', <?= $qty ?>)"
                          <?= ($qty <= 0) ? 'disabled' : '' ?>>
                    <?= ($qty > 0) ? '+ Add Item' : 'Out of Stock' ?>
                  </button>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div class="lp-modal-footer">
            <span style="font-size: 13px; color: var(--text-muted);">
              🛒 Selected Items Total: <strong id="modal-summary-count-<?= $modalId ?>" style="color: var(--accent-pink);">0 items</strong>
            </span>
            <button type="button" class="modal-checkout-btn" onclick="goToCheckout('<?= $modalId ?>')">
              Go to Checkout ➔
            </button>
          </div>
        </div>
      </div>
    <?php
    }

    foreach ($categories as $index => $cat) {
        $catId = $cat['categoryId'] ?? ($cat['id'] ?? ($cat['category_id'] ?? $index));
        $catKey = 'cat-' . $catId;
        $catTitle = $cat['categoryName'] ?? ($cat['categoryname'] ?? 'Category');
        $prods = $categories_products[$catKey] ?? [];
        renderCategoryProducts($catKey, $catTitle, $prods);
    }
    ?>

    <!-- Main Order Form -->
    <form class="lp-order-form" id="checkout-section" method="POST" action="U_OrderPage">
      
      <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?? '' ?>" />
      <input type="hidden" name="products_id" id="form_products_id" value="" />
      <input type="hidden" name="product_image" id="form_product_image" value="" />
      <input type="hidden" name="product_name" id="form_product_name" required />
      <input type="hidden" name="price" id="form_price" value="0.00" required />

      <!-- Step 1: Order Summary -->
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

          <!-- <div class="lp-grid-2" style="margin-top:8px;">
            <div class="lp-field">
              <label for="email">Email Address (for payment receipts)</label>
              <input class="lp-input" id="email" name="email" type="email" placeholder="name@example.com" />
            </div>
            <div></div>
          </div> -->
      </fieldset>

      <!-- Step 3: Fulfillment & Options -->
      <fieldset class="lp-fieldset">
        <legend class="lp-legend">3. Fulfillment & Options</legend>
        <div class="lp-grid-2">
          <div class="lp-field">
            <label for="mode_of_transpo">Fulfillment Option</label>
            <select class="lp-select" id="mode_of_transpo" name="mode_of_transpo" onchange="toggleFulfillmentMode()" required>
              <option value="1">Delivery (with minimal delivery fee)</option>
              <option value="2" selected>Store Pickup</option>
            </select>
          </div>
          
          <!-- Custom Calendar Picker Fields -->
          <div class="lp-field">
            <label id="date-label">Preferred Pickup Date & Time</label>
            <div style="display: flex; gap: 8px;">
              <input type="hidden" name="selected_date_val" id="selected_date_val" required />
              
              <div class="lp-input date-picker-trigger" id="custom-date-display" onclick="openPinkCalendar()" style="flex: 2;">
                <span id="date-display-text" style="color: #999;">Select Date...</span>
                <span>📅</span>
              </div>

              <select class="lp-select" name="selected_time_val" id="selected_time_val" style="flex: 1;" required>
                <option value="08:00">08:00 AM</option>
                <option value="09:00">09:00 AM</option>
                <option value="10:00" selected>10:00 AM</option>
                <option value="11:00">11:00 AM</option>
                <option value="12:00">12:00 PM</option>
                <option value="13:00">01:00 PM</option>
                <option value="14:00">02:00 PM</option>
                <option value="15:00">03:00 PM</option>
                <option value="16:00">04:00 PM</option>
                <option value="17:00">05:00 PM</option>
                <option value="18:00">06:00 PM</option>
              </select>
            </div>
          </div>
        </div>

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

          <input type="hidden" id="latitude" name="latitude" />
          <input type="hidden" id="longitude" name="longitude" />

          <div class="lp-field">
            <label for="address">Delivery Address Search & Pin Location</label>
            <div style="display: flex; gap: 8px;">
              <input class="lp-input" id="address" name="address" placeholder="Type Barangay / Street / Landmark" />
              <button type="button" onclick="searchLocation()" style="background: var(--accent-pink); color: #fff; border: none; padding: 0 16px; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; white-space: nowrap;">Find Location</button>
            </div>
            <span style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">📍 Search a location or drag the pink pin on the map to mark your exact doorstep.</span>
          </div>

          <div id="delivery-map"></div>
            <div style="margin-top:8px; display:flex; gap:10px; align-items:center;">
              <div id="delivery-coords" style="font-size:13px; color:var(--text-muted);">Lat: - , Lng: -</div>
            </div>
        </div>

        <div id="pickup-info" style="display: none; background: var(--bg-cream); border: 1px dashed var(--gold-border); padding: 18px; border-radius: var(--radius-md);">
          <div class="lp-field" style="margin-bottom: 14px;">
            <label for="pickup_branch" style="font-weight: 700; font-size: 14px; color: var(--text-dark);">Select Pickup Stall / Branch Location:</label>
            <select class="lp-select" id="pickup_branch" name="pickup_branch">
              <option value="">-- Select Date First --</option>
            </select>
            <small id="location_note" style="color: var(--accent-pink); font-size: 12px; font-weight: 600; display: none; margin-top: 6px;"></small>
          </div>
          <div style="margin-top:12px;">
            <div id="pickup-map" style="width:100%; height:220px; border-radius:var(--radius-md); border:1px solid var(--gold-border);"></div>
            <div style="margin-top:8px; display:flex; gap:12px; align-items:center;">
              <div id="pickup-coords" style="font-size:13px; color:var(--text-muted);">Lat: - , Lng: -</div>
              <a id="open-pickup-google" class="btn-nav" href="#" target="_blank" rel="noopener noreferrer">Open Maps</a>
            </div>

            
          </div>
        </div>

        <div class="lp-field">
          <label for="notes">Greeting Card Message / Special Instructions</label>
          <textarea class="lp-textarea" id="notes" name="notes" rows="3" placeholder="Write any card message or specific request..."></textarea>
        </div>

        <!-- Hidden input to submit the payment value (0 = COD, 1 = PayMongo) -->
<input type="hidden" name="mode_of_payment" id="mode_of_payment_input" value="0">

<!-- Payment Options UI -->
<div class="payment-selection-group">
  <label class="payment-option-card active" id="card-cod" onclick="selectPaymentMethod(0)">
    <input type="radio" name="payment_type" value="0" checked hidden>
    <div class="payment-info">
      <span class="payment-title">Cash on Delivery</span>
      <span class="payment-desc">Pay upon receiving</span>
    </div>
  </label>

  <label class="payment-option-card" id="card-paymongo" onclick="selectPaymentMethod(1)">
    <input type="radio" name="payment_type" value="1" hidden>
    <div class="payment-info">
      <span class="payment-title">Online Payment</span>
      <span class="payment-desc">GCash / Maya / Card</span>
    </div>
  </label>
</div>

<!-- Dynamic PayMongo QR / Online Payment Info Modal -->
<div id="paymongo-info-modal" class="payment-modal-overlay" style="display: none;">
  <div class="payment-modal-content">
    <button type="button" class="modal-close-btn" onclick="closePaymongoModal()">&times;</button>
    <div class="modal-header">
      <h3>Pay via Online Payment</h3>
      <p>Scan or complete payment after placing your order</p>
    </div>
    <div class="modal-body">
      <img src="Assets/Images/paymongo.png" 
     alt="PayMongo" 
     class="payment-qr-img" 
     style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit; display: block;">

      <p class="modal-note">You will be automatically redirected to the secure PayMongo payment gateway right after clicking <strong>Place Order</strong>.</p>
    </div>
    <button type="button" class="btn-confirm-modal" onclick="closePaymongoModal()">Got it</button>
  </div>
</div>

      </fieldset>

      <button type="submit" name="place_order" class="btn-submit">Place Your Order</button>
    </form>
  </main>



  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>

    
    // Data structures passed from PHP
    const blockedDatesData = <?= $js_blocked_dates ?>;
    const activeLocations = <?= $js_active_locations ?>;
    const dateLocationOverrides = <?= $js_location_overrides ?>;

    let cart = [];
    let map, marker;
    let calCurrentDate = new Date();

    // Custom Alert logic
    function customAlert(msg) {
      document.getElementById('lp-alert-message').innerText = msg;
      document.getElementById('lp-alert-modal').style.display = 'flex';
    }
    function closeCustomAlert() {
      document.getElementById('lp-alert-modal').style.display = 'none';
    }

    // Modal logic
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    // Lightbox logic
    function viewFullscreenImage(src) {
      document.getElementById('lightbox-img').src = src;
      document.getElementById('image-lightbox').style.display = 'flex';
    }
    function closeLightbox() { document.getElementById('image-lightbox').style.display = 'none'; }

    // Description expansion
    function toggleDesc(id) {
      const shortDesc = document.getElementById('desc-short-' + id);
      const fullDesc = document.getElementById('desc-full-' + id);
      const btn = document.getElementById('see-btn-' + id);

      if (fullDesc.style.display === 'none') {
        fullDesc.style.display = 'inline';
        shortDesc.style.display = 'none';
        btn.innerText = 'see less';
      } else {
        fullDesc.style.display = 'none';
        shortDesc.style.display = 'inline';
        btn.innerText = 'see more';
      }
    }

    // Dynamic Cart Management
    function addItemToOrder(id, name, price, image, maxStock) {
      let item = cart.find(i => i.id === id);
      if (item) {
        if (item.qty < maxStock) {
          item.qty++;
        } else {
          customAlert(`Sorry, you have reached the maximum available stock (${maxStock}) for this item.`);
          return;
        }
      } else {
        cart.push({ id, name, price, image, qty: 1, maxStock });
      }

      const toast = document.getElementById('toast-' + id);
      if (toast) {
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 1500);
      }

      updateCartUI();
    }

    function updateCartUI() {
      const container = document.getElementById('cart-list-container');
      let total = 0;
      let count = 0;

      if (cart.length === 0) {
        container.innerHTML = `<p style="color:var(--text-muted); font-size:14px;">No catalog items added yet. Click categories above to add items.</p>`;
        document.getElementById('form_products_id').value = '';
        document.getElementById('form_product_name').value = '';
        document.getElementById('form_product_image').value = '';
        document.getElementById('form_price').value = '0.00';
      } else {
        let html = '';
        let names = [];
        let ids = [];
        let imgs = [];

        cart.forEach(item => {
          let itemSubtotal = item.price * item.qty;
          total += itemSubtotal;
          count += item.qty;

          names.push(`${item.name} (x${item.qty})`);
          ids.push(item.id);
          imgs.push(item.image);

          html += `
            <div class="cart-item-row">
              <div>
                <strong>${item.name}</strong> 
                <span style="font-size:12px; color:var(--text-muted);">₱${item.price.toFixed(2)} x ${item.qty}</span>
              </div>
              <div>
                <span style="font-weight:700;">₱${itemSubtotal.toFixed(2)}</span>
                <button type="button" class="btn-remove" onclick="removeCartItem(${item.id})">✕</button>
              </div>
            </div>`;
        });

        container.innerHTML = html;
        document.getElementById('form_products_id').value = ids.join(',');
        document.getElementById('form_product_name').value = names.join(', ');
        document.getElementById('form_product_image').value = imgs[0] || '';
        document.getElementById('form_price').value = total.toFixed(2);
      }

      document.getElementById('summary-total-price').innerText = '₱' + total.toFixed(2);

      // Update counters on all open modals
      document.querySelectorAll('[id^="modal-summary-count-"]').forEach(el => {
        el.innerText = `${count} item(s)`;
      });
    }

    function removeCartItem(id) {
      cart = cart.filter(i => i.id !== id);
      updateCartUI();
    }

    function goToCheckout(modalId) {
      closeModal(modalId);
      document.getElementById('checkout-section').scrollIntoView({ behavior: 'smooth' });
    }

    // Fulfillment switching
    function toggleFulfillmentMode() {
      const mode = document.getElementById('mode_of_transpo').value;
      const deliveryFields = document.getElementById('delivery-fields');
      const pickupInfo = document.getElementById('pickup-info');
      const dateLabel = document.getElementById('date-label');
      const addressInput = document.getElementById('address');

      if (mode === '1') { // Delivery
        deliveryFields.style.display = 'flex';
        pickupInfo.style.display = 'none';
        dateLabel.innerText = "Preferred Delivery Date & Time";
        addressInput.setAttribute('required', 'required');
      } else { // Pickup
        deliveryFields.style.display = 'none';
        pickupInfo.style.display = 'block';
        dateLabel.innerText = "Preferred Pickup Date & Time";
        addressInput.removeAttribute('required');
      }

      // Re-evaluate location filters for current selected date
      updatePickupLocationsForSelectedDate();
      // Ensure maps render correctly after visibility changes
      setTimeout(() => {
        try { if (typeof deliveryMap !== 'undefined') deliveryMap.invalidateSize(); } catch(e){}
      }, 250);
    }

    // Dynamic Pickup Locations Filter
    function updatePickupLocationsForSelectedDate() {
      const selectedDate = document.getElementById('selected_date_val').value;
      const branchSelect = document.getElementById('pickup_branch');
      const noteEl = document.getElementById('location_note');

      if (!selectedDate) {
        branchSelect.innerHTML = '<option value="">-- Select Date First --</option>';
        noteEl.style.display = 'none';
        return;
      }

      // Filter active locations based on date availability overrides
      const availableLocs = activeLocations.filter(loc => {
        if (dateLocationOverrides[selectedDate] && dateLocationOverrides[selectedDate][loc.id] !== undefined) {
          return dateLocationOverrides[selectedDate][loc.id] === 1;
        }
        return true;
      });

      branchSelect.innerHTML = '';

      if (availableLocs.length === 0) {
        branchSelect.innerHTML = '<option value="">No pickup stalls available for this date</option>';
        noteEl.style.display = 'none';
        return;
      }

      // helper to ensure coordinates are valid numbers within range
      function clampCoord(val, fallback, min, max) {
        const n = parseFloat(val);
        if (!isFinite(n)) return fallback;
        if (n < min || n > max) return fallback;
        return n;
      }

      availableLocs.forEach(loc => {
        const option = document.createElement('option');
        option.value = loc.location_name;
        option.textContent = `${loc.location_name} (${loc.address})`;
        const lat = clampCoord(loc.latitude, 10.76498, -90, 90);
        const lng = clampCoord(loc.longitude, 124.91874, -180, 180);
        option.setAttribute('data-lat', lat);
        option.setAttribute('data-lng', lng);
        branchSelect.appendChild(option);
      });

      // Update pickup map to reflect currently selected branch
      updatePickupMapFromSelect();

      // Auto-default selection if only 1 stall is open on this date
      if (availableLocs.length === 1) {
        branchSelect.selectedIndex = 0;
        noteEl.innerText = `Note: Only ${availableLocs[0].location_name} is open for pickup on this date.`;
        noteEl.style.display = 'block';
      } else {
        noteEl.style.display = 'none';
      }
    }

    // Custom Calendar Logic
    function openPinkCalendar() {
      renderPinkCalendar();
      document.getElementById('pink-calendar-modal').style.display = 'flex';
    }

    function closePinkCalendar() {
      document.getElementById('pink-calendar-modal').style.display = 'none';
    }

    function navigateCalendarMonth(step) {
      calCurrentDate.setMonth(calCurrentDate.getMonth() + step);
      renderPinkCalendar();
    }

    function renderPinkCalendar() {
      const year = calCurrentDate.getFullYear();
      const month = calCurrentDate.getMonth();

      const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
      document.getElementById('cal-month-year').innerText = `${monthNames[month]} ${year}`;

      const firstDay = new Date(year, month, 1).getDay();
      const daysInMonth = new Date(year, month + 1, 0).getDate();

      const grid = document.getElementById('cal-days-container');
      grid.innerHTML = '';

      for (let i = 0; i < firstDay; i++) {
        const emptyCell = document.createElement('div');
        grid.appendChild(emptyCell);
      }

      const today = new Date();
      today.setHours(0,0,0,0);
      const currentMode = document.getElementById('mode_of_transpo').value;
      const selectedVal = document.getElementById('selected_date_val').value;

      for (let day = 1; day <= daysInMonth; day++) {
        const cellDate = new Date(year, month, day);
        const yyyy = year;
        const mm = String(month + 1).padStart(2, '0');
        const dd = String(day).padStart(2, '0');
        const dateStr = `${yyyy}-${mm}-${dd}`;

        const cell = document.createElement('div');
        cell.className = 'cal-day-cell';
        cell.innerHTML = `<span>${day}</span>`;

        let isDisabled = false;

        if (cellDate < today) {
          isDisabled = true;
        }

        const blockInfo = blockedDatesData.find(b => b.blocked_date === dateStr);
        if (blockInfo) {
          const service = blockInfo.service_type;
          if (service === 'both') {
            isDisabled = true;
            cell.innerHTML += `<span class="cal-tag tag-closed">CLOSED</span>`;
          } else if (service === 'delivery' && currentMode === '1') {
            isDisabled = true;
            cell.innerHTML += `<span class="cal-tag tag-nodel">NO DEL</span>`;
          } else if (service === 'pickup' && currentMode === '2') {
            isDisabled = true;
            cell.innerHTML += `<span class="cal-tag tag-nopick">NO PICK</span>`;
          }
        }

        if (selectedVal === dateStr) {
          cell.classList.add('selected');
        }

        if (isDisabled) {
          cell.classList.add('disabled');
        } else {
          cell.onclick = () => selectCalendarDate(dateStr, `${monthNames[month]} ${day}, ${year}`);
        }

        grid.appendChild(cell);
      }
    }

    function selectCalendarDate(dateStr, formattedText) {
      document.getElementById('selected_date_val').value = dateStr;
      document.getElementById('date-display-text').innerText = formattedText;
      document.getElementById('date-display-text').style.color = 'var(--text-dark)';
      closePinkCalendar();
      updatePickupLocationsForSelectedDate();
    }

    // Leaflet Map Initialization & Search (deliveryMap). Pickup uses Google embed for viewing.
    let deliveryMap, deliveryMarker;

    function initMaps() {
      const defaultLat = 10.76498;
      const defaultLng = 124.91874;

      // Shared tile layer
      const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      });

      // Pink icon
      const pinkIcon = L.divIcon({
        className: 'custom-pink-pin',
        html: '<div style="background-color:#d9658b; width:18px; height:18px; border-radius:50%; border:3px solid #fff; box-shadow:0 0 5px rgba(0,0,0,0.4);"></div>',
        iconSize: [18, 18],
        iconAnchor: [9, 9]
      });

      // Delivery map
      deliveryMap = L.map('delivery-map').setView([defaultLat, defaultLng], 13);
      tiles.addTo(deliveryMap);
      deliveryMarker = L.marker([defaultLat, defaultLng], { draggable: true, icon: pinkIcon }).addTo(deliveryMap);

      deliveryMarker.on('dragend', function () {
        const coord = deliveryMarker.getLatLng();
        document.getElementById('latitude').value = coord.lat;
        document.getElementById('longitude').value = coord.lng;
        // Reverse geocode to update address input
        reverseGeocode(coord.lat, coord.lng, function(addr){ if (addr) document.getElementById('address').value = addr; });
        updateCoordsDisplay('delivery', coord.lat, coord.lng);
      });

      // Click on map to place delivery marker and update address
      deliveryMap.on('click', function(e){
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        deliveryMarker.setLatLng([lat, lng]);
        deliveryMap.setView([lat, lng], 16);
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        reverseGeocode(lat, lng, function(addr){ if (addr) document.getElementById('address').value = addr; });
        updateCoordsDisplay('delivery', lat, lng);
      });

      document.getElementById('latitude').value = defaultLat;
      document.getElementById('longitude').value = defaultLng;

      // initialize delivery coords display
      updateCoordsDisplay('delivery', defaultLat, defaultLng);

      // Pickup map (Google embed) - readonly viewer; we'll set iframe src dynamically
      const pickupContainer = document.getElementById('pickup-map');
      if (pickupContainer) {
        const iframeSrc = `https://www.google.com/maps?q=${defaultLat},${defaultLng}&z=15&output=embed`;
        pickupContainer.innerHTML = `<iframe id="pickup-iframe" src="${iframeSrc}" class="map-embed" style="width:100%; height:220px; border:0; border-radius:12px;"></iframe>`;
      }

      // When pickup option changes selection, we will move this pickupMarker
      const branchSelect = document.getElementById('pickup_branch');
      branchSelect.addEventListener('change', function(){ updatePickupMapFromSelect(); });

      // When maps are created but container size may be hidden initially, invalidate size when shown
      setTimeout(() => { try { if (deliveryMap) deliveryMap.invalidateSize(); } catch(e){} }, 200);
      const gp = document.getElementById('open-pickup-google');
      if (gp) gp.href = `https://www.google.com/maps/search/?api=1&query=${defaultLat},${defaultLng}`;
      updateCoordsDisplay('pickup', defaultLat, defaultLng);
    }

    function searchLocation() {
      const query = document.getElementById('address').value;
      if (!query) {
        customAlert("Please type an address or location to search.");
        return;
      }

      fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
          if (data && data.length > 0) {
            const lat = parseFloat(data[0].lat);
            const lon = parseFloat(data[0].lon);
            deliveryMap.setView([lat, lon], 16);
            deliveryMarker.setLatLng([lat, lon]);
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lon;
            updateCoordsDisplay('delivery', lat, lon);
          } else {
            customAlert("Location not found. Please drag the pink pin to your target location on the map.");
          }
        })
        .catch(() => customAlert("Error searching location. Please pin manually."));
    }

    // Reverse geocode using Nominatim
    function reverseGeocode(lat, lon, cb) {
      fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}`)
        .then(r => r.json())
        .then(d => {
          if (d && d.display_name) cb(d.display_name);
          else cb(null);
        })
        .catch(() => cb(null));
    }

    // Debounced automatic search when typing address
    let addressTypingTimer = null;
    document.getElementById('address').addEventListener('input', function(){
      clearTimeout(addressTypingTimer);
      addressTypingTimer = setTimeout(() => {
        const q = document.getElementById('address').value;
        if (q && q.length > 3) {
          // try to search but don't annoy user; silent failures handled
          fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
              if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lon = parseFloat(data[0].lon);
                deliveryMap.setView([lat, lon], 16);
                deliveryMarker.setLatLng([lat, lon]);
                document.getElementById('latitude').value = lat;
                document.getElementById('longitude').value = lon;
                updateCoordsDisplay('delivery', lat, lon);
              }
            })
            .catch(()=>{});
        }
      }, 700);
    });

    // Update pickup map marker from the currently selected branch option
    function updatePickupMapFromSelect() {
      const sel = document.getElementById('pickup_branch');
      const opt = sel.options[sel.selectedIndex];
      if (!opt) return;
      const lat = parseFloat(opt.getAttribute('data-lat') || '10.76498');
      const lng = parseFloat(opt.getAttribute('data-lng') || '124.91874');
      // Validate ranges
      const validLat = (isFinite(lat) && lat >= -90 && lat <= 90) ? lat : 10.76498;
      const validLng = (isFinite(lng) && lng >= -180 && lng <= 180) ? lng : 124.91874;
      // update pickup iframe viewer
      const pickupFrame = document.getElementById('pickup-iframe');
      if (pickupFrame) {
        pickupFrame.src = `https://www.google.com/maps?q=${validLat},${validLng}&z=15&output=embed`;
      }
      // when pickup is selected, set the hidden latitude/longitude so server receives it
      document.getElementById('latitude').value = validLat;
      document.getElementById('longitude').value = validLng;
      const gp = document.getElementById('open-pickup-google');
      if (gp) gp.href = `https://www.google.com/maps/search/?api=1&query=${validLat},${validLng}`;
      updateCoordsDisplay('pickup', validLat, validLng);
    }

    // Update visible lat/lng readouts for delivery or pickup
    function updateCoordsDisplay(kind, lat, lng) {
      try {
        const el = document.getElementById(kind + '-coords');
        if (el) el.innerText = `Lat: ${parseFloat(lat).toFixed(6)}, Lng: ${parseFloat(lng).toFixed(6)}`;
      } catch (e) { }
    }

    // Format YYYY-MM-DD to a friendly display
    function formatDisplayDate(dateStr) {
      try {
        const d = new Date(dateStr + 'T00:00:00');
        const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
        return `${monthNames[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
      } catch (e) { return dateStr; }
    }

    // Find the next available date starting from today (checks blockedDatesData)
    function findNextAvailableDate() {
      const mode = document.getElementById('mode_of_transpo') ? document.getElementById('mode_of_transpo').value : '2';
      const today = new Date(); today.setHours(0,0,0,0);
      for (let i = 0; i < 60; i++) {
        const check = new Date(today);
        check.setDate(today.getDate() + i);
        const yyyy = check.getFullYear();
        const mm = String(check.getMonth() + 1).padStart(2, '0');
        const dd = String(check.getDate()).padStart(2, '0');
        const dateStr = `${yyyy}-${mm}-${dd}`;

        const blockInfo = (blockedDatesData || []).find(b => b.blocked_date === dateStr);
        if (blockInfo) {
          const svc = blockInfo.service_type;
          if (svc === 'both') continue;
          if (svc === 'delivery' && mode === '1') continue;
          if (svc === 'pickup' && mode === '2') continue;
        }

        // not blocked, return this date
        return dateStr;
      }
      return null;
    }

    window.onload = function() {
      initMaps();
      toggleFulfillmentMode();

      // set default time to 10:00 if not provided
      const timeSelect = document.getElementById('selected_time_val');
      if (timeSelect && !timeSelect.value) timeSelect.value = '10:00';

      // set default date to today if available, otherwise next available date
      const dateInput = document.getElementById('selected_date_val');
      const displayText = document.getElementById('date-display-text');
      if (dateInput && !dateInput.value) {
        const nextDate = findNextAvailableDate();
        if (nextDate) {
          dateInput.value = nextDate;
          if (displayText) {
            displayText.innerText = formatDisplayDate(nextDate);
            displayText.style.color = 'var(--text-dark)';
          }
          updatePickupLocationsForSelectedDate();
        }
      }

      // If URL contains ?category=ID then auto-open that category modal (ids: cat-<ID>)
      try {
        const params = new URLSearchParams(window.location.search);
        const catId = params.get('category');
        if (catId) {
          // Delay to allow modals to be rendered
          setTimeout(() => { const modalId = 'cat-' + catId; if (document.getElementById(modalId)) openModal(modalId); }, 300);
        }
      } catch (e) {}
    };

        function selectPaymentMethod(mode) {
      // Set the hidden input value
      document.getElementById('mode_of_payment_input').value = mode;

      // Toggle active CSS classes
      const codCard = document.getElementById('card-cod');
      const paymongoCard = document.getElementById('card-paymongo');

      if (mode === 1) {
        codCard.classList.remove('active');
        paymongoCard.classList.add('active');
        openPaymongoModal(); // Show modal when PayMongo is selected
      } else {
        paymongoCard.classList.remove('active');
        codCard.classList.add('active');
      }
    }

    function openPaymongoModal() {
      document.getElementById('paymongo-info-modal').style.display = 'flex';
    }

    function closePaymongoModal() {
      document.getElementById('paymongo-info-modal').style.display = 'none';
    }

    // Require email when choosing online payment
    document.getElementById('checkout-section').addEventListener('submit', function(e){
      const mode = parseInt(document.getElementById('mode_of_payment_input').value || '0', 10);
    });

  </script>
</body>
</html>