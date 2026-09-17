<?php
session_start();
require_once 'U_db.php';

// Get order_id passed via URL query parameter (e.g., U_PaymentFailed.php?order_id=39)
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$errorMessage = $_GET['error'] ?? 'Your payment attempt was cancelled or could not be processed.';

// Fallback values
$customerName = "Valued Customer";
$orderTotal = "0.00";
$productName = "Your Order";

if ($orderId > 0) {
    try {
        // Fetch order details
        $stmt = $pdo->prepare("SELECT full_name, price, quantity, product_name FROM orders WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            if (!empty($order['full_name'])) {
                $customerName = $order['full_name'];
            }
            if (!empty($order['product_name'])) {
                $productName = $order['product_name'];
            }
            $quantity = intval($order['quantity'] ?? 1);
            $price = floatval($order['price'] ?? 0);
            $orderTotal = number_format($price * $quantity, 2);

            // Update payment status in database to signify cancellation
            $updateStmt = $pdo->prepare("UPDATE orders SET payment_status = 'CANCELLED' WHERE id = :id AND payment_status = 'PENDING'");
            $updateStmt->execute([':id' => $orderId]);
        }
    } catch (PDOException $e) {
        // Silently handle database connection errors
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Unsuccessful | LynxPrise</title>
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
      --error-red: #d9534f;
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

    .lp-failed-wrapper {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }

    .lp-failed-card {
      background: var(--card-bg);
      border: 1px solid rgba(217, 83, 79, 0.2);
      border-radius: var(--radius-lg);
      padding: 48px 32px;
      max-width: 580px;
      width: 100%;
      text-align: center;
      box-shadow: 0 10px 30px rgba(59, 34, 25, 0.05);
    }

    .lp-failed-icon {
      width: 64px;
      height: 64px;
      background-color: rgba(217, 83, 79, 0.15);
      color: var(--error-red);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      margin: 0 auto 20px;
    }

    .lp-failed-title {
      font-family: 'Playfair Display', serif;
      font-size: 30px;
      color: var(--text-dark);
      margin-bottom: 8px;
    }

    .lp-order-number {
      font-size: 14px;
      font-weight: 600;
      color: var(--error-red);
      margin-bottom: 16px;
    }

    .lp-failed-desc {
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.6;
      margin-bottom: 24px;
    }

    .lp-order-box {
      background: var(--bg-cream);
      border: 1px dashed var(--gold-border);
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 24px;
      text-align: left;
      font-size: 14px;
    }

    .lp-order-box div {
      display: flex;
      justify-content: space-between;
      margin-bottom: 6px;
    }

    .lp-order-box div:last-child {
      margin-bottom: 0;
      font-weight: 700;
    }

    .lp-action-group {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-top: 24px;
    }

    .btn-retry {
      display: inline-block;
      background-color: var(--accent-pink);
      color: #ffffff;
      padding: 14px 28px;
      border-radius: var(--radius-btn);
      text-decoration: none;
      font-weight: 700;
      font-size: 15px;
      transition: background-color 0.2s;
    }

    .btn-retry:hover {
      background-color: var(--accent-pink-hover);
    }

    .btn-secondary {
      display: inline-block;
      color: var(--text-muted);
      font-size: 14px;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }

    .btn-secondary:hover {
      color: var(--text-dark);
    }
  </style>
</head>
<body>

  <nav class="lp-nav">
    <a href="index" class="lp-logo">Lynx<span>Prise</span></a>
  </nav>

  <div class="lp-failed-wrapper">
    <div class="lp-failed-card">
      <div class="lp-failed-icon">❌</div>
      <h1 class="lp-failed-title">Payment Unsuccessful</h1>
      
      <?php if ($orderId > 0): ?>
        <p class="lp-order-number">Order Reference: #<?= htmlspecialchars($orderId) ?></p>
      <?php endif; ?>

      <p class="lp-failed-desc">
        <?= htmlspecialchars($errorMessage) ?><br>
        Don't worry, your order draft has been saved and your payment account was not charged.
      </p>

      <?php if ($orderId > 0): ?>
      <div class="lp-order-box">
        <div><span>Customer:</span> <strong><?= htmlspecialchars($customerName) ?></strong></div>
        <div><span>Item:</span> <strong><?= htmlspecialchars($productName) ?></strong></div>
        <div><span>Total Amount:</span> <strong>₱<?= htmlspecialchars($orderTotal) ?></strong></div>
      </div>
      <?php endif; ?>

      <div class="lp-action-group">
        <?php if ($orderId > 0): ?>
          <a href="create_checkout?order_id=<?= $orderId ?>" class="btn-retry">Try Payment Again (QR Ph)</a>
        <?php else: ?>
          <a href="U_OrderPage" class="btn-retry">Return to Order Page</a>
        <?php endif; ?>
        <a href="index" class="btn-secondary">← Cancel and Return to Home</a>
      </div>
    </div>
  </div>

</body>
</html>