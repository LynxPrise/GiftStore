<?php
session_start();
require_once 'U_db.php';

// Get order_id and payment flag passed via URL query parameters
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$paymentFlag = $_GET['payment'] ?? ''; // 'success' when redirected back from PayMongo

// Fallback default values
$customerName = "Valued Customer";
$orderTotal = "0.00";
$fulfillmentType = "Pick-Up / Delivery";
$paymentMethodText = "Cash on Delivery (COD)";
$paymentStatusDisplay = "UNPAID";
$paymentBadgeColor = "#e67e22"; // Orange badge for UNPAID

if ($orderId > 0) {
    try {
        // 1. UPDATE PAYMENT STATUS IF RETURNING FROM PAYMONGO WITH SUCCESS FLAG
        if ($paymentFlag === 'success') {
            $updateStmt = $pdo->prepare("UPDATE orders SET payment_status = 'PAID', status = 'processing' WHERE id = :id");
            $updateStmt->execute([':id' => $orderId]);
        }

        // 2. FETCH UPDATED ORDER DETAILS FROM DATABASE
        $stmt = $pdo->prepare("SELECT full_name, price, mode_of_transpo, mode_of_payment, payment_status FROM orders WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Customer Name
            if (!empty($order['full_name'])) {
                $customerName = $order['full_name'];
            }

            // Total Price
            if (isset($order['price'])) {
                $orderTotal = number_format(floatval($order['price']), 2);
            }

            // Fulfillment Mode (1 = Delivery, 0 or other = Store Pick-Up)
            if (isset($order['mode_of_transpo'])) {
                $fulfillmentType = ($order['mode_of_transpo'] == 1) ? "Delivery" : "Store Pick-Up";
            }

            // Payment Mode (1 = Online Payment / QR Ph, 0 = COD)
            if (isset($order['mode_of_payment'])) {
                $paymentMethodText = ($order['mode_of_payment'] == 1) ? "Online Payment (QR Ph / PayMongo)" : "Cash on Delivery (COD)";
            }

            // Payment Status Display & Styling
            if (!empty($order['payment_status'])) {
                $statusUpper = strtoupper($order['payment_status']);
                if ($statusUpper === 'PAID') {
                    $paymentStatusDisplay = "PAID";
                    $paymentBadgeColor = "#27ae60"; // Green badge for PAID
                } elseif ($statusUpper === 'PENDING') {
                    $paymentStatusDisplay = "PENDING";
                    $paymentBadgeColor = "#f39c12"; // Yellow badge for PENDING
                } else {
                    $paymentStatusDisplay = "UNPAID";
                    $paymentBadgeColor = "#e67e22"; // Orange badge for UNPAID
                }
            }
        }
    } catch (PDOException $e) {
        // Silently handle database errors to preserve UI fallback
    }
}

// Dynamic Description Message Based on Payment Status
if ($paymentStatusDisplay === 'PAID') {
    $thankYouDescription = "We have received your payment via QR Ph! To confirm your preparation slot and get real-time order updates, send us a quick message on Messenger below.";
} else {
    $thankYouDescription = "We have successfully received your order details! To confirm your preparation slot, and track real-time updates, please send us a quick message on Messenger below.";
}

// --------------------------------------------------------------------------
// MESSENGER DEEP LINK CONFIGURATION WITH DIRECT RECEIPT LINK
// --------------------------------------------------------------------------
$pageId = "278284815370670"; 

// Construct Receipt Link dynamically (Change domain when deploying live)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$receiptUrl = $protocol . $domainName . "/view_receipt?id=" . $orderId;

// Dynamic message pre-filled in Messenger chat box
$rawMessage = "New Order Confirmation #" . ($orderId > 0 ? $orderId : 'N/A') . "\n\n"
            . "Name: " . $customerName . "\n"
            . "Total Amount: ₱" . $orderTotal . "\n"
            . "Payment Method: " . $paymentMethodText . "\n"
            . "Payment Status: " . $paymentStatusDisplay . "\n"
            . "Fulfillment: " . $fulfillmentType . "\n\n"
            . "📄 View Online Receipt:\n" . $receiptUrl . "\n\n"
            . "Hi! I'm messaging to confirm my receipt and order details!";

// Encodes the message string for URL
$encodedMessage = rawurlencode($rawMessage);

// Direct m.me link using Page ID
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
      background-color: rgba(39, 174, 96, 0.15);
      color: #27ae60;
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
      align-items: center;
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

    .status-badge {
      font-size: 11px;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 12px;
      color: #ffffff;
      letter-spacing: 0.5px;
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

    .btn-receipt {
      display: inline-block;
      margin-top: 12px;
      color: var(--accent-pink);
      font-size: 14px;
      text-decoration: underline;
      font-weight: 600;
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

    @media (max-width: 640px) {
      .lp-nav-links {
        display: none;
      }

      .lp-nav {
        padding: 12px 4%;
      }

      .lp-thankyou-card {
        padding: 32px 20px;
      }

      .lp-thankyou-title {
        font-size: 26px;
      }
    }
  </style>
</head>
<body>

  <nav class="lp-nav">
    <a href="index" class="lp-logo">Lynx<span>Prise</span></a>
    <ul class="lp-nav-links">
      <li><a href="index#categories">Categories</a></li>
      <li><a href="index#testimonials">Feedbacks</a></li>
      <li><a href="U_OrderPage">Order</a></li>
    </ul>
    <a href="U_OrderPage" class="btn-nav">Order Again!</a>
  </nav>

  <div class="lp-thankyou-wrapper">
    <div class="lp-thankyou-card">
      <div class="lp-success-icon">✓</div>
      <h1 class="lp-thankyou-title">Thank You for Your Order!</h1>
      
      <?php if ($orderId > 0): ?>
        <p class="lp-order-number">Order Reference: #<?= htmlspecialchars($orderId) ?></p>
      <?php endif; ?>

      <p class="lp-thankyou-desc">
        <?= htmlspecialchars($thankYouDescription) ?>
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
          <span>Payment Method:</span>
          <span><?= htmlspecialchars($paymentMethodText) ?></span>
        </div>
        <div class="lp-summary-row">
          <span>Payment Status:</span>
          <span class="status-badge" style="background-color: <?= $paymentBadgeColor ?>;"><?= htmlspecialchars($paymentStatusDisplay) ?></span>
        </div>
        <div class="lp-summary-row">
          <span>Total Amount:</span>
          <span>₱<?= $orderTotal ?></span>
        </div>
      </div>

      <div class="lp-messenger-box">
        <h4>Connect on Messenger for Receipts & Updates</h4>
        <p>Click below to open Messenger. Your order details and receipt link will automatically populate in your message box!</p>
        <a href="<?= $messengerUrl ?>" target="_blank" class="btn-messenger">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C6.477 2 2 6.145 2 11.258c0 2.91 1.45 5.512 3.715 7.202V22l3.39-1.861c.928.257 1.91.396 2.895.396 5.523 0 10-4.145 10-9.258C22 6.145 17.523 2 12 2zm1.09 12.445l-2.543-2.714-4.966 2.714 5.464-5.798 2.583 2.714 4.926-2.714-5.464 5.798z"/>
          </svg>
          Message us on Messenger
        </a>
      </div>

      <!-- <div>
        <a href="view_receipt?id=<?= $orderId ?>" target="_blank" class="btn-receipt">📄 View / Print Official Receipt</a>
      </div> -->
      <br>
      <a href="index" class="btn-home">← Return to Home Page</a>
    </div>
  </div>

</body>
</html>