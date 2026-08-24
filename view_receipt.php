<?php
// view_receipt.php
include 'U_db.php'; // Ensures PDO database connection ($pdo) is loaded

$order_id = $_GET['id'] ?? null;

if (!$order_id) {
    die("Error: Missing Order ID.");
}

// Fetch order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
$stmt->execute([':id' => $order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Error: Order not found.");
}

// Parse fulfillment type
$transpo = strtolower(trim((string)($order['mode_of_transpo'] ?? '')));
$isDelivery = ($transpo === '1' || $transpo === 'delivery' || $transpo === 'ship');
$fulfillmentType = $isDelivery ? 'Delivery' : 'Pickup';

// Parse Recipient Information if present in notes or card_message
$recipientName = $order['recipient_name'] ?? '';
$recipientContact = $order['recipient_phone'] ?? $order['recipient_contact'] ?? '';
$cleanCardMessage = $order['card_message'] ?? '';

if (preg_match('/\[Recipient:\s*([^|]+)\|\s*Contact:\s*([^\]]+)\]/i', $cleanCardMessage, $matches)) {
    if (!$recipientName) $recipientName = trim($matches[1]);
    if (!$recipientContact) $recipientContact = trim($matches[2]);
    $cleanCardMessage = trim(preg_replace('/\[Recipient:\s*[^|]+\|\s*Contact:\s*[^\]]+\]/i', '', $cleanCardMessage));
}

// Format Payment Details
$paymentModes = [
    '1' => 'GCash / Online Payment',
    '0' => 'Cash on Delivery / Pickup'
];
$paymentMode = $paymentModes[(string)($order['mode_of_payment'] ?? '')] ?? ($order['mode_of_payment'] ?: 'N/A');
$paymentStatus = strtoupper($order['payment_status'] ?? 'PENDING');
$price = number_format((float)($order['price'] ?? 0), 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo htmlspecialchars($order['id']); ?> - LynxPrise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sacramento&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --accent-pink: #d9658b;
            --text-dark: #3b2219;
            --text-muted: #785a50;
            --border-color: #e8c3b0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fdeee8; color: var(--text-dark); padding: 20px; }

        .receipt-card {
            max-width: 450px;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .logo { font-family: 'Playfair Display', serif; font-size: 26px; text-align: center; }
        .logo span { font-family: 'Sacramento', cursive; color: var(--accent-pink); font-size: 34px; }
        .subtitle { text-align: center; font-size: 12px; color: var(--text-muted); margin-bottom: 20px; }

        .divider { border: 0; border-top: 1px dashed var(--border-color); margin: 15px 0; }

        .row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .row .label { color: var(--text-muted); font-weight: 500; }
        .row .value { font-weight: 600; text-align: right; }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge-paid { background: #d4edda; color: #155724; }
        .badge-pending { background: #fff3cd; color: #856404; }

        .item-box { background: #fff9f6; padding: 12px; border-radius: 8px; border: 1px solid #f2d2c4; margin: 15px 0; }
        .item-name { font-weight: 700; font-size: 15px; }

        .btn-container { text-align: center; margin-top: 25px; }
        .btn-print {
            background-color: var(--accent-pink);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        .btn-print:hover { background-color: #c45075; }

        @media print {
            body { background: white; padding: 0; }
            .receipt-card { box-shadow: none; border: none; width: 100%; max-width: 100%; }
            .btn-container { display: none; }
        }
    </style>
</head>
<body>

<div class="receipt-card">
    <div class="logo">Lynx<span>Prise</span></div>
    <div class="subtitle">Brgy. Malitbogay, Javier, Leyte<br>Official Sales Receipt</div>

    <div class="row"><span class="label">Receipt #</span><span class="value"><?php echo htmlspecialchars($order['id']); ?></span></div>
    <div class="row"><span class="label">Date</span><span class="value"><?php echo date('M d, Y', strtotime($order['date_of_pickup'] ?? 'now')); ?></span></div>
    <div class="row"><span class="label">Fulfillment</span><span class="value"><?php echo $fulfillmentType; ?></span></div>

    <hr class="divider">

    <div class="row"><span class="label">Customer Name</span><span class="value"><?php echo htmlspecialchars($order['full_name'] ?? 'N/A'); ?></span></div>
    <div class="row"><span class="label">Phone</span><span class="value"><?php echo htmlspecialchars($order['phone_number'] ?? 'N/A'); ?></span></div>
    <?php if ($isDelivery && !empty($order['address'])): ?>
        <div class="row"><span class="label">Address</span><span class="value"><?php echo htmlspecialchars($order['address']); ?></span></div>
    <?php endif; ?>

    <?php if ($recipientName): ?>
        <hr class="divider">
        <div class="row"><span class="label">Recipient Name</span><span class="value"><?php echo htmlspecialchars($recipientName); ?></span></div>
        <?php if ($recipientContact): ?>
            <div class="row"><span class="label">Recipient Phone</span><span class="value"><?php echo htmlspecialchars($recipientContact); ?></span></div>
        <?php endif; ?>
    <?php endif; ?>

    <hr class="divider">

    <div class="item-box">
        <div class="row" style="margin-bottom:0;">
            <span class="item-name"><?php echo htmlspecialchars($order['product_name'] ?? 'Custom Order'); ?></span>
            <span class="item-name">₱<?php echo $price; ?></span>
        </div>
    </div>

    <div class="row"><span class="label">Payment Method</span><span class="value"><?php echo htmlspecialchars($paymentMode); ?></span></div>
    <div class="row">
        <span class="label">Payment Status</span>
        <span class="value">
            <span class="badge <?php echo ($paymentStatus === 'PAID' || $paymentStatus === 'COMPLETED') ? 'badge-paid' : 'badge-pending'; ?>">
                <?php echo $paymentStatus; ?>
            </span>
        </span>
    </div>

    <hr class="divider">

    <div class="row" style="font-size: 18px;">
        <span class="label" style="font-weight: 700; color: var(--text-dark);">Total Amount</span>
        <span class="value" style="color: var(--accent-pink); font-weight: 700;">₱<?php echo $price; ?></span>
    </div>

    <div class="btn-container">
        <button class="btn-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
</div>

</body>
</html>