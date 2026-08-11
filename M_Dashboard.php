<?php
include 'U_db.php';

$current_date = date('Y-m-d');
$tomorrow_date = date('Y-m-d', strtotime('+1 day'));

// Helper function to resolve fulfillment type from mode_of_transpo
function getFulfillmentType($order) {
    if (!isset($order['mode_of_transpo'])) {
        return 'Pickup';
    }
    
    $val = strtolower(trim((string)$order['mode_of_transpo']));
    
    // Treat '1', 'delivery', or 'ship' as Delivery. Treat '0' or 'pickup' as Pickup.
    if ($val === '1' || $val === 'delivery' || $val === 'ship') {
        return 'Delivery';
    }
    
    return 'Pickup';
}

function formatDate($date) {
    if (empty($date)) return 'N/A';
    return date('F j, Y', strtotime($date));
}

function calculateTotalSales($orders) {
    $total = 0;
    foreach ($orders as $order) {
        $price = isset($order['price']) ? (float)$order['price'] : 0;
        $total += $price;
    }
    return number_format($total, 2);
}

// Fetch queries
$sql_today = "SELECT * FROM orders WHERE DATE(date_of_pickup) = :current_date ORDER BY date_of_pickup ASC";
$stmt_today = $pdo->prepare($sql_today);
$stmt_today->execute([':current_date' => $current_date]);
$today_orders = $stmt_today->fetchAll();

$sql_tomorrow = "SELECT * FROM orders WHERE DATE(date_of_pickup) = :tomorrow_date ORDER BY date_of_pickup ASC";
$stmt_tomorrow = $pdo->prepare($sql_tomorrow);
$stmt_tomorrow->execute([':tomorrow_date' => $tomorrow_date]);
$tomorrow_orders = $stmt_tomorrow->fetchAll();

$sql_future = "SELECT * FROM orders WHERE DATE(date_of_pickup) > :tomorrow_date ORDER BY date_of_pickup ASC";
$stmt_future = $pdo->prepare($sql_future);
$stmt_future->execute([':tomorrow_date' => $tomorrow_date]);
$future_orders = $stmt_future->fetchAll();

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];

    if (!in_array($status, ['pending', 'completed', 'cancelled', 'all_set'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :order_id");
    if ($stmt->execute([':status' => $status, ':order_id' => $order_id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
    }
    exit;
}

// Handle Notes Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id']) && isset($_POST['notes'])) {
    $stmt = $pdo->prepare("UPDATE orders SET notes = :notes WHERE id = :order_id");
    if ($stmt->execute([':notes' => $_POST['notes'], ':order_id' => $_POST['order_id']])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notes']);
    }
    exit;
}

function renderOrderRows($orders_list) {
    if (count($orders_list) === 0) {
        return "<tr><td colspan='8'>No orders found.</td></tr>";
    }

    $html = '';
    foreach ($orders_list as $order) {
        $fulfillment = getFulfillmentType($order);
        
        $html .= '<tr>
            <td>' . htmlspecialchars($order['product_name'] ?? '') . '</td>
            <td>' . htmlspecialchars($order['full_name'] ?? '') . '</td>
            <td>' . htmlspecialchars($order['phone_number'] ?? '') . '</td>
            <td>' . htmlspecialchars($fulfillment) . '</td>
            <td>' . formatDate($order['date_of_pickup'] ?? '') . '</td>
            <td>₱' . number_format((float)($order['price'] ?? 0), 2) . '</td>
            <td><button class="details-btn" onclick="openModal(' . $order['id'] . ')">Details</button></td>
            <td class="status-cell">
                <select class="status-dropdown" onchange="updateOrderStatus(this, ' . $order['id'] . ')">
                    <option value="pending"' . (($order['status'] ?? '') == 'pending' ? ' selected' : '') . '>Pending</option>
                    <option value="all_set"' . (($order['status'] ?? '') == 'all_set' ? ' selected' : '') . '>All Set</option>
                    <option value="completed"' . (($order['status'] ?? '') == 'completed' ? ' selected' : '') . '>Completed</option>
                    <option value="cancelled"' . (($order['status'] ?? '') == 'cancelled' ? ' selected' : '') . '>Cancelled</option>
                </select>
            </td>
        </tr>';
    }
    return $html;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Assets/Css/style.css">
    <title>Order List</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..700;1,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sacramento&display=swap" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background-color: #fce4ec; margin: 0; padding: 0; }
        .container { max-width: 100%; margin: 0px auto; padding: 20px; background-color: white; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h2, h3 { color: #d81b60; }
        table { width: 100%; min-width: 750px; margin-bottom: 20px; border-collapse: collapse; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 12px; text-align: left; font-size: 14px; }
        th { background-color: #f8f8f8; color: #d81b60; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); padding-top: 40px; }
        .modal-content { background-color: #fff; margin: 2% auto; padding: 25px; border: 1px solid #888; width: 90%; max-width: 650px; border-radius: 8px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: black; }
        .details-btn { background-color: #4CAF50; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer; }
        .sales-section { margin-bottom: 20px; background-color: #f8f8f8; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
        select.status-dropdown { width: 100%; padding: 5px; font-size: 12px; background-color: #fff; border: 1px solid #ddd; border-radius: 5px; }
        .table-container { overflow-x: auto; margin-bottom: 20px; }
        .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; }
        .modal-grid p { margin: 5px 0; font-size: 14px; }
        .full-width { grid-column: span 2; }
        textarea#notes { width: 100%; height: 70px; margin-top: 5px; padding: 8px; box-sizing: border-box; }

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

        * {
          box-sizing: border-box;
          margin: 0;
          padding: 0;
        }

        body {
          font-family: 'Plus Jakarta Sans', sans-serif;
          background-color: var(--bg-soft-pink);
          color: var(--text-dark);
          line-height: 1.6;
        }

        /* Navigation Bar */
        .lp-nav {
          position: sticky;
          top: 0;
          z-index: 1000;
          
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
          gap: 30px;
          list-style: none;
        }

        .lp-nav-links a {
          text-decoration: none;
          color: var(--text-dark);
          font-weight: 500;
          font-size: 15px;
          transition: color 0.2s;
        }

        .lp-nav-links a:hover {
          color: var(--accent-pink);
        }

        .btn-nav {
          background-color: var(--accent-pink);
          color: #fff;
          padding: 10px 24px;
          border-radius: var(--radius-btn);
          text-decoration: none;
          font-weight: 600;
          font-size: 14px;
          transition: background-color 0.2s;
        }

        .btn-nav:hover {
          background-color: var(--accent-pink-hover);
        }

        /* Container Wrapper */
        .lp-wrapper {
          max-width: 1200px;
          margin: 0 auto;
          padding: 0 20px;
        }
    </style>
</head>
<body>
  <!-- Navigation Bar -->
  <nav class="lp-nav">
    <a href="#" class="lp-logo">Lynx<span>Prise</span></a>
    <ul class="lp-nav-links">
      <li><a href="/index.php#categories">Categories</a></li>
      <li><a href="/index.php#how-it-works">How it works</a></li>
      <li><a href="U_OrderPage.php">Order</a></li>
    </ul>
    <a href="U_OrderPage.php" class="btn-nav">Order now</a>
  </nav>

    <div class="container">
        <h2>Order List</h2>

        <div class="sales-section">
            <h3>Total Sales</h3>
            <p><strong>Today's Sales:</strong> ₱<?php echo calculateTotalSales($today_orders); ?></p>
            <p><strong>Tomorrow's Sales:</strong> ₱<?php echo calculateTotalSales($tomorrow_orders); ?></p>
            <p><strong>Future Sales:</strong> ₱<?php echo calculateTotalSales($future_orders); ?></p>
        </div>

        <h3>Today's Orders</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th><th>Full Name</th><th>Phone Number</th><th>Type</th><th>Date</th><th>Price</th><th>Details</th><th>Status</th>
                    </tr>
                </thead>
                <tbody><?php echo renderOrderRows($today_orders); ?></tbody>
            </table>
        </div>

        <h3>Tomorrow's Orders</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th><th>Full Name</th><th>Phone Number</th><th>Type</th><th>Date</th><th>Price</th><th>Details</th><th>Status</th>
                    </tr>
                </thead>
                <tbody><?php echo renderOrderRows($tomorrow_orders); ?></tbody>
            </table>
        </div>

        <h3>Future Orders</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th><th>Full Name</th><th>Phone Number</th><th>Type</th><th>Date</th><th>Price</th><th>Details</th><th>Status</th>
                    </tr>
                </thead>
                <tbody><?php echo renderOrderRows($future_orders); ?></tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Order Details</h2>
            <div id="order-details"></div>
        </div>
    </div>

    <script>
        function openModal(orderId) {
    var modal = document.getElementById("orderModal");
    var orderDetails = document.getElementById("order-details");

    fetch("M_GetOrderDetails.php?id=" + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const o = data.order;
                
                // Fixed transport type resolution matching PHP backend logic
                const val = String(o.mode_of_transpo || '').trim().toLowerCase();
                const isDelivery = (val === '1' || val === 'delivery' || val === 'ship');

                let cleanNotes = o.notes || '';
                let cleanCardMessage = o.card_message || '';
                
                // Extract recipient details from explicit database columns or fall back to card_message / notes
                let recipientName = o.recipient_name || '';
                let recipientContact = o.recipient_phone || o.recipient_contact || '';

                // Extract recipient details from card_message if contained within [Recipient: ... | Contact: ...]
                const cardMatch = cleanCardMessage.match(/\[Recipient:\s*([^|]+)\|\s*Contact:\s*([^\]]+)\]/i);
                if (cardMatch) {
                    if (!recipientName) recipientName = cardMatch[1].trim();
                    if (!recipientContact) recipientContact = cardMatch[2].trim();
                    cleanCardMessage = cleanCardMessage.replace(/\[Recipient:\s*[^|]+\|\s*Contact:\s*[^\]]+\]/gi, '').trim();
                }

                // Fallback check on notes if recipient tag was saved there
                const notesMatch = cleanNotes.match(/\[Recipient:\s*([^|]+)\|\s*Contact:\s*([^\]]+)\]/i);
                if (notesMatch) {
                    if (!recipientName) recipientName = notesMatch[1].trim();
                    if (!recipientContact) recipientContact = notesMatch[2].trim();
                    cleanNotes = cleanNotes.replace(/\[Recipient:\s*[^|]+\|\s*Contact:\s*[^\]]+\]/gi, '').trim();
                }

                const displayAddress = o.address || o.delivery_address || 'N/A';

                orderDetails.innerHTML = `
                    <div class="modal-grid">
                        <p class="full-width"><strong>Order Type:</strong> <span style="color:#d81b60; font-weight:bold;">${isDelivery ? 'Delivery' : 'Pickup'}</span></p>
                        <p><strong>Customer Name:</strong> ${o.full_name || 'N/A'}</p>
                        <p><strong>Customer Phone:</strong> ${o.phone_number || 'N/A'}</p>
                       
                        <hr class="full-width">
                        <p><strong>Product:</strong> ${o.product_name || 'N/A'}</p>
                        <p><strong>Price:</strong> ₱${o.price || '0.00'}</p>
                        <p><strong>Pickup/Delivery Date:</strong> ${o.date_of_pickup || 'N/A'}</p>
                        
                        ${isDelivery ? `
                            <hr class="full-width">
                            <p class="full-width"><strong>--- Delivery Information ---</strong></p>
                            <p><strong>Recipient's Name:</strong> ${recipientName || 'N/A'}</p>
                            <p><strong>Recipient's Contact Number:</strong> ${recipientContact || 'N/A'}</p>
                            <p class="full-width"><strong>Delivery Address:</strong> ${displayAddress}</p>
                            
                            <p class="full-width"><strong>Card Message / Instructions:</strong> ${cleanCardMessage || 'None'}</p>
                        ` : ''}

                        <div class="full-width">
                            <label for="notes"><strong>Admin Notes:</strong></label>
                            <textarea id="notes">${cleanNotes}</textarea>
                            <button class="details-btn" style="margin-top:8px;" onclick="updateNotes(${orderId})">Save Notes</button>
                        </div>
                    </div>
                `;
                modal.style.display = "block";
            } else {
                alert("Error fetching order details.");
            }
        })
        .catch(err => {
            console.error("Error:", err);
            alert("Unable to fetch details.");
        });
}

        function closeModal() {
            document.getElementById("orderModal").style.display = "none";
        }

        function updateOrderStatus(selectElement, orderId) {
            const status = selectElement.value;
            fetch("M_Dashboard.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "order_id=" + orderId + "&status=" + status
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) alert("Failed to update order status.");
            });
        }

        function updateNotes(orderId) {
            const notes = document.getElementById('notes').value;
            fetch("M_Dashboard.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "order_id=" + orderId + "&notes=" + encodeURIComponent(notes)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) alert("Notes updated successfully!");
                else alert("Failed to update notes.");
            });
        }
    </script>
</body>
</html>