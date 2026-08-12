<?php
session_start();
require_once 'U_db.php';

// Get order_id passed via URL query parameter (e.g., U_ThankYou.php?order_id=18)
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Fallback default values
$customerName = "Valued Customer";
$orderTotal = "0.00";
$fulfillmentType = "Pick-Up / Delivery";

if ($orderId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT full_name, price, mode_of_transpo FROM orders WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Match 'full_name' column
            if (!empty($order['full_name'])) {
                $customerName = $order['full_name'];
            }

            // Match 'price' column
            if (isset($order['price'])) {
                $orderTotal = number_format(floatval($order['price']), 2);
            }

            // Map 'mode_of_transpo' column (1 = Delivery, 2 = Pick-Up)
            if (isset($order['mode_of_transpo'])) {
                $fulfillmentType = ($order['mode_of_transpo'] == 1) ? "Delivery" : "Store Pick-Up";
            }
        }
    } catch (PDOException $e) {
        // Silently handle error to render soft fallback UI
    }
}

// --------------------------------------------------------------------------
// MESSENGER DEEP LINK CONFIGURATION (Using your exact Page ID)
// --------------------------------------------------------------------------
$pageId = "278284815370670"; 

// Dynamic message pre-filled in Messenger chat box
$rawMessage = "New Order From Customer #" . ($orderId > 0 ? $orderId : 'N/A') . "\n\n"
            . "Name: " . $customerName . "\n"
            . "Total Amount: ₱" . $orderTotal . "\n"
            . "Fulfillment: " . $fulfillmentType . "\n\n"
            . "I'm messaging to confirm my receipt and order details!";

// Encodes the message string so spaces and newlines parse correctly in the URL
$encodedMessage = rawurlencode($rawMessage);

// Direct m.me link using your Page ID (Opens Messenger App on mobile / Messenger Web on desktop)
$messengerUrl = "https://m.me/" . $pageId . "?text=" . $encodedMessage;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thank You for Your Order! | LynxPrise</title>
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
      --messenger-blue: #0084ff;
      --messenger-blue-hover: #006bce;
      --radius-lg: 24px;
      --radius-btn: 30px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: var(--bg-soft-pink);
      color: var(--text-dark);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .lp-nav {
      background: var(--bg-cream);
      padding: 18px 5%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 10px rgba(59, 34, 25, 0.05);
    }

    .lp-logo {
      font-family: 'Playfair Display', serif;
      font-size: 24px;
      font-weight: 700;
      color: var(--text-dark);
      text-decoration: none;
    }

    .lp-logo span {
      font-family: 'Sacramento', cursive;
      color: var(--accent-pink);
      font-size: 32px;
      margin-left: 2px;
    }

    .lp-nav-links {
      display: flex;
      list-style: none;
      gap: 24px;
      align-items: center;
    }

    .lp-nav-links a {
      text-decoration: none;
      color: var(--text-dark);
      font-weight: 600;
      font-size: 14px;
      transition: color 0.2s;
    }

    .lp-nav-links a:hover {
      color: var(--accent-pink);
    }

    .btn-nav {
      background-color: var(--accent-pink);
      color: #ffffff;
      padding: 10px 20px;
      border-radius: var(--radius-btn);
      text-decoration: none;
      font-size: 14px;
      font-weight: 600;
      transition: background-color 0.2s;
    }

    .btn-nav:hover {
      background-color: var(--accent-pink-hover);
    }

    .lp-thankyou-wrapper {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }

    .lp-thankyou-card {
      background: var(--card-bg);
      border: 1px solid rgba(232, 195, 176, 0.5);
      border-radius: var(--radius-lg);
      padding: 48px 32px;
      max-width: 580px;
      width: 100%;
      text-align: center;
      box-shadow: 0 10px 30px rgba(59, 34, 25, 0.05);
    }

    .lp-success-icon {
      width: 64px;
      height: 64px;
      background-color: rgba(217, 101, 139, 0.15);
      color: var(--accent-pink);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      margin: 0 auto 20px;
    }

    .lp-thankyou-title {
      font-family: 'Playfair Display', serif;
      font-size: 32px;
      color: var(--text-dark);
      margin-bottom: 8px;
    }

    .lp-order-number {
      font-size: 14px;
      font-weight: 600;
      color: var(--accent-pink);
      margin-bottom: 20px;
    }

    .lp-thankyou-desc {
      font-size: 15px;
      color: var(--text-muted);
      line-height: 1.6;
      margin-bottom: 28px;
    }

    .lp-summary-box {
      background-color: var(--bg-cream);
      border: 1px dashed var(--gold-border);
      border-radius: 16px;
      padding: 16px 20px;
      margin-bottom: 28px;
      text-align: left;
    }

    .lp-summary-row {
      display: flex;
      justify-content: space-between;
      font-size: 14px;
      margin-bottom: 8px;
      color: var(--text-muted);
    }

    .lp-summary-row:last-child {
      margin-bottom: 0;
      font-weight: 700;
      color: var(--text-dark);
      border-top: 1px solid rgba(232, 195, 176, 0.4);
      padding-top: 8px;
    }

    .lp-messenger-box {
      background: #f0f7ff;
      border: 1px solid #cce4ff;
      border-radius: 16px;
      padding: 24px;
      margin-bottom: 24px;
    }

    .lp-messenger-box h4 {
      font-size: 16px;
      color: var(--text-dark);
      margin-bottom: 6px;
    }

    .lp-messenger-box p {
      font-size: 13px;
      color: var(--text-muted);
      margin-bottom: 16px;
    }

    .btn-messenger {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      background-color: var(--messenger-blue);
      color: #ffffff;
      padding: 14px 28px;
      border-radius: var(--radius-btn);
      text-decoration: none;
      font-weight: 700;
      font-size: 15px;
      transition: background-color 0.2s, transform 0.2s;
      width: 100%;
    }

    .btn-messenger:hover {
      background-color: var(--messenger-blue-hover);
      transform: translateY(-2px);
    }

    .btn-home {
      display: inline-block;
      color: var(--text-muted);
      font-size: 14px;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }

    .btn-home:hover {
      color: var(--accent-pink);
    }
  </style>
</head>
<body>

  <nav class="lp-nav">
    <a href="index.php" class="lp-logo">Lynx<span>Prise</span></a>
    <ul class="lp-nav-links">
      <li><a href="index.php#categories">Categories</a></li>
      <li><a href="index.php#testimonials">Feedbacks</a></li>
      <li><a href="U_OrderPage.php">Order</a></li>
    </ul>
    <a href="U_OrderPage.php" class="btn-nav">Order Again!</a>
  </nav>

  <div class="lp-thankyou-wrapper">
    <div class="lp-thankyou-card">
      <div class="lp-success-icon">🌸</div>
      <h1 class="lp-thankyou-title">Thank You for Your Order!</h1>
      
      <?php if ($orderId > 0): ?>
        <p class="lp-order-number">Order Reference: #<?= htmlspecialchars($orderId) ?></p>
      <?php endif; ?>

      <p class="lp-thankyou-desc">
        We have successfully received your order details! To confirm your preparation slot, receive your official receipt, and track real-time updates, please send us a quick message on Messenger.
      </p>

      <div class="lp-summary-box">
        <div class="lp-summary-row">
          <span>Customer:</span>
          <span><?= htmlspecialchars($customerName) ?></span>
        </div>
        <div class="lp-summary-row">
          <span>Fulfillment:</span>
          <span><?= htmlspecialchars($fulfillmentType) ?></span>
        </div>
        <div class="lp-summary-row">
          <span>Total Amount:</span>
          <span>₱<?= $orderTotal ?></span>
        </div>
      </div>

      <div class="lp-messenger-box">
        <h4>Connect on Messenger for Receipts & Updates</h4>
        <p>Click below to open Messenger. Your order details will automatically load inside your chat!</p>
        <a href="<?= $messengerUrl ?>" target="_blank" class="btn-messenger">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C6.477 2 2 6.145 2 11.258c0 2.91 1.45 5.512 3.715 7.202V22l3.39-1.861c.928.257 1.91.396 2.895.396 5.523 0 10-4.145 10-9.258C22 6.145 17.523 2 12 2zm1.09 12.445l-2.543-2.714-4.966 2.714 5.464-5.798 2.583 2.714 4.926-2.714-5.464 5.798z"/>
          </svg>
          Message us on Messenger
        </a>
      </div>

      <a href="index.php" class="btn-home">← Return to Home Page</a>
    </div>
  </div>

</body>
</html>