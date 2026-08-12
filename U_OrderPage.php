<?php
session_start();

// Security Guard: Check if the user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: U_Login.php');
    exit;
}

require_once 'U_db.php'; // Includes $pdo from U_db.php

$order_status_message = "";

// Display styled session notification if order was successful
if (isset($_SESSION['order_success'])) {
    $order_status_message = "<div style='background: var(--bg-cream); color: var(--accent-pink); border: 1px solid var(--gold-border); padding:14px; border-radius:16px; text-align:center; margin-bottom:20px; font-weight:600;'>🌸 " . htmlspecialchars($_SESSION['order_success']) . "</div>";
    unset($_SESSION['order_success']);
}

// Fetch dynamic categories and products from database
$categories = [];
$categories_products = [];

$min_datetime = date('Y-m-d\TH:i');

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
} catch (PDOException $e) {
    $order_status_message = "<div style='background:#ffebee; color:#d32f2f; padding:12px; border-radius:12px; text-align:center; margin-bottom:20px; font-weight:600;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

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
        }

        $final_card_message = $recipient_details . trim($_POST['notes'] ?? '');
        $lat = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : NULL;
        $lng = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : NULL;

        $sql = "INSERT INTO orders (
                    user_id, products_id, full_name, phone_number, address, 
                    latitude, longitude, product_name, price, mode_of_transpo, 
                    date_of_pickup, product_image, status, card_message
                ) VALUES (
                    :user_id, :products_id, :full_name, :phone_number, :address, 
                    :latitude, :longitude, :product_name, :price, :mode_of_transpo, 
                    :date_of_pickup, :product_image, 'pending', :card_message
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
            ':date_of_pickup'   => !empty($_POST['date_of_pickup']) ? $_POST['date_of_pickup'] : NULL,
            ':product_image'    => trim($_POST['product_image'] ?? ''),
            ':card_message'     => $final_card_message
        ]);

        // Get the last inserted order ID to pass to U_ThankYou.php
        $last_id = $pdo->lastInsertId();

        // Redirect directly to the Thank You page with order_id in URL query
        header("Location: U_ThankYou.php?order_id=" . $last_id);
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

    /* Category Cards Grid */
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

    /* Modal Footer & Checkout Bar */
    .lp-modal-footer { position: sticky; bottom: -32px; background: var(--bg-cream); border-top: 1px solid var(--gold-border); padding: 16px 0 0; margin-top: auto; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; z-index: 10; }
    .modal-checkout-btn { background-color: var(--text-dark); color: #fff; border: none; padding: 12px 24px; border-radius: var(--radius-btn); font-weight: 700; font-size: 14px; cursor: pointer; transition: background-color 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .modal-checkout-btn:hover { background-color: #24140e; }

    /* Toast Notification inside Modal */
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

    

    @media (max-width: 640px) {
    /* Hide navigation links on mobile */
    .lp-nav-links {
      display: none;
    }

    /* Adjust header padding to keep logo & button nicely spaced */
    .lp-nav {
      padding: 12px 4%;
    }

    .lp-grid-2, .lp-category-grid { 
      grid-template-columns: 1fr; 
    }
    
    .lp-order-form { 
      padding: 24px 18px; 
    }
    
    .lp-modal-footer { 
      flex-direction: column; 
      align-items: stretch; 
      text-align: center; 
    }
    
    .modal-checkout-btn { 
      justify-content: center; 
    }
  }
  </style>
</head>
<body>

  <nav class="lp-nav">
    <a href="#" class="lp-logo">Lynx<span>Prise</span></a>
    <ul class="lp-nav-links">
      <li><a href="index.php#categories">Categories</a></li>
      <li><a href="index.php#testimonials">Feedbacks</a></li>
      <li><a href="U_OrderPage.php">Order</a></li>
    </ul>
    <a href="index.php#how-it-works" class="btn-nav">How this works</a>
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

          <!-- Sticky Modal Footer -->
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
    <form class="lp-order-form" id="checkout-section" method="POST" action="U_OrderPage.php">
      
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
          <div class="lp-field">
            <label for="date_of_pickup" id="date-label">Preferred Delivery Date & Time</label>
            <input 
              class="lp-input" 
              id="date_of_pickup" 
              name="date_of_pickup" 
              type="datetime-local" 
              min="<?= date('Y-m-d\TH:i') ?>" 
              required 
            />
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
              <input class="lp-input" id="address" name="address" required placeholder="Type Barangay / Street / Landmark (e.g. Malitbogay)" />
              <button type="button" onclick="searchLocation()" style="background: var(--accent-pink); color: #fff; border: none; padding: 0 16px; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; white-space: nowrap;">Find Location</button>
            </div>
            <span style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">📍 Search a location or drag the pink pin on the map to mark your exact doorstep.</span>
          </div>

          <div id="delivery-map"></div>
        </div>

        <div id="pickup-info" style="display: none; background: var(--bg-cream); border: 1px dashed var(--gold-border); padding: 18px; border-radius: var(--radius-md);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
            <h4 style="color: var(--text-dark); font-family: 'Playfair Display', serif;">Store Pickup Location</h4>
            <a href="https://www.google.com/maps/search/?api=1&query=10.76498,124.91874" 
               target="_blank" 
               rel="noopener noreferrer" 
               style="background-color: var(--accent-pink); color: #fff; padding: 6px 14px; border-radius: var(--radius-btn); text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
              📍 Open in Maps
            </a>
          </div>

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
              src="https://maps.google.com/maps?q=10.76498,124.91874&z=17&output=embed">
            </iframe>
          </div>
        </div>
      </fieldset>

      <!-- Step 4: Notes -->
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

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script>
    let orderCart = [];
    let map, marker;

    const defaultLat = 10.76498;
    const defaultLng = 124.91874;

    function openModal(modalId) {
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.style.display = 'flex';
      }
    }

    function closeModal(modalId) {
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.style.display = 'none';
      }
    }

   
  // AUTO-OPEN MODAL ON PAGE LOAD (Fix for landing page redirect)
  document.addEventListener('DOMContentLoaded', () => {
    // 1. Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const categoryParam = urlParams.get('category') || urlParams.get('cat') || urlParams.get('id');

    if (categoryParam) {
      // Clean up parameter in case it includes 'cat-' prefix or just raw ID
      const formattedModalId = categoryParam.startsWith('cat-') ? categoryParam : 'cat-' + categoryParam;
      
      // Attempt to open the corresponding modal
      openModal(formattedModalId);
    }

    // Initialize pickup/delivery view defaults
    toggleFulfillmentMode();
  });

    function showCustomAlert(message) {
      document.getElementById('lp-alert-message').textContent = message;
      document.getElementById('lp-alert-modal').style.display = 'flex';
    }

    function closeCustomAlert() {
      document.getElementById('lp-alert-modal').style.display = 'none';
    }

    function viewFullscreenImage(imgSrc) {
      const lightbox = document.getElementById('image-lightbox');
      const lightboxImg = document.getElementById('lightbox-img');
      lightboxImg.src = imgSrc;
      lightbox.style.display = 'flex';
    }

    function closeLightbox() {
      document.getElementById('image-lightbox').style.display = 'none';
    }

    function toggleDesc(id) {
      const shortSpan = document.getElementById('desc-short-' + id);
      const fullSpan = document.getElementById('desc-full-' + id);
      const btn = document.getElementById('see-btn-' + id);

      if (fullSpan.style.display === 'none') {
        fullSpan.style.display = 'inline';
        shortSpan.style.display = 'none';
        btn.textContent = 'see less';
      } else {
        fullSpan.style.display = 'none';
        shortSpan.style.display = 'inline';
        btn.textContent = 'see more';
      }
    }

    // Add item to cart without closing modal + trigger toast notification
    function addItemToOrder(id, name, price, img, qty) {
      orderCart.push({ id, name, price, img });
      updateCartSummary();

      // Show temporary pop-up badge on product card
      const toast = document.getElementById('toast-' + id);
      if (toast) {
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 1500);
      }
    }

    // Closes modal and scrolls user straight to the checkout/order summary form
    function goToCheckout(modalId) {
      closeModal(modalId);
      const checkoutSection = document.getElementById('checkout-section');
      if (checkoutSection) {
        checkoutSection.scrollIntoView({ behavior: 'smooth' });
      }
    }

    function removeItemFromOrder(index) {
      orderCart.splice(index, 1);
      updateCartSummary();
    }

    function updateCartSummary() {
      const container = document.getElementById('cart-list-container');
      const totalPriceEl = document.getElementById('summary-total-price');

      // Update all modal footer counters dynamically
      const countText = orderCart.length === 1 ? '1 item' : `${orderCart.length} items`;
      document.querySelectorAll('[id^="modal-summary-count-"]').forEach(el => {
        el.textContent = countText;
      });

      if (orderCart.length === 0) {
        container.innerHTML = '<p style="color:var(--text-muted); font-size:14px;">No catalog items added yet. Click categories above to add items.</p>';
        totalPriceEl.textContent = '₱0.00';
        document.getElementById('form_products_id').value = '';
        document.getElementById('form_product_name').value = '';
        document.getElementById('form_product_image').value = '';
        document.getElementById('form_price').value = '0.00';
        return;
      }

      let total = 0;
      let names = [];
      let ids = [];
      let imgs = [];
      let html = '';

      orderCart.forEach((item, index) => {
        total += parseFloat(item.price);
        names.push(item.name);
        ids.push(item.id);
        if (item.img) imgs.push(item.img);

        html += `
          <div class="cart-item-row">
            <span>🌸 ${item.name}</span>
            <div>
              <strong>₱${parseFloat(item.price).toFixed(2)}</strong>
              <button type="button" class="btn-remove" onclick="removeItemFromOrder(${index})">✕ Remove</button>
            </div>
          </div>
        `;
      });

      container.innerHTML = html;
      totalPriceEl.textContent = '₱' + total.toFixed(2);

      // Map cart to hidden inputs
      document.getElementById('form_products_id').value = ids.join(',');
      document.getElementById('form_product_name').value = names.join(', ');
      document.getElementById('form_product_image').value = imgs[0] || '';
      document.getElementById('form_price').value = total.toFixed(2);
    }

    function toggleFulfillmentMode() {
      const mode = document.getElementById('mode_of_transpo').value;
      const deliveryFields = document.getElementById('delivery-fields');
      const pickupInfo = document.getElementById('pickup-info');
      const dateLabel = document.getElementById('date-label');
      const addressInput = document.getElementById('address');

      if (mode === '1') { // Delivery
        deliveryFields.style.display = 'flex';
        pickupInfo.style.display = 'none';
        dateLabel.textContent = 'Preferred Delivery Date & Time';
        addressInput.setAttribute('required', 'required');
        setTimeout(() => { if (map) map.invalidateSize(); }, 200);
      } else { // Pickup
        deliveryFields.style.display = 'none';
        pickupInfo.style.display = 'block';
        dateLabel.textContent = 'Preferred Pickup Date & Time';
        addressInput.removeAttribute('required');
      }
    }
    

    function initMap() {
      map = L.map('delivery-map').setView([defaultLat, defaultLng], 14);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

      updateCoords(defaultLat, defaultLng);

      marker.on('dragend', function (e) {
        const coord = e.target.getLatLng();
        updateCoords(coord.lat, coord.lng);
        reverseGeocode(coord.lat, coord.lng);
      });

      map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        updateCoords(e.latlng.lat, e.latlng.lng);
        reverseGeocode(e.latlng.lat, e.latlng.lng);
      });
    }

    function updateCoords(lat, lng) {
      document.getElementById('latitude').value = lat;
      document.getElementById('longitude').value = lng;
    }

    function reverseGeocode(lat, lng) {
      fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}`)
        .then(response => response.json())
        .then(data => {
          if (data && data.display_name) {
            document.getElementById('address').value = data.display_name;
          }
        })
        .catch(() => {
          // keep current address value if reverse lookup fails
        });
    }

    function searchLocation() {
      const query = document.getElementById('address').value;
      if (!query) {
        showCustomAlert("Please enter a location to search.");
        return;
      }

      const fullQuery = query + ", Javier, Leyte, Philippines";
      fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullQuery)}`)
        .then(response => response.json())
        .then(data => {
          if (data && data.length > 0) {
            const lat = parseFloat(data[0].lat);
            const lon = parseFloat(data[0].lon);
            const displayName = data[0].display_name || query;

            map.setView([lat, lon], 16);
            marker.setLatLng([lat, lon]);
            updateCoords(lat, lon);
            document.getElementById('address').value = displayName;
          } else {
            showCustomAlert("Location not found. Please try a different landmark or click on the map.");
          }
        })
        .catch(() => {
          showCustomAlert("Unable to connect to map services right now.");
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
      initMap();
      toggleFulfillmentMode();
    });

    document.addEventListener('DOMContentLoaded', () => {
      const dateInput = document.getElementById('date_of_pickup');
      if (dateInput) {
        const now = new Date();
        // Offset local time format to ISO string (YYYY-MM-DDTHH:mm)
        const localIso = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
                          .toISOString()
                          .slice(0, 16);
        dateInput.min = localIso;
      }
    });
  </script>

</body>
</html>