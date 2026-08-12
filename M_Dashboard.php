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

// Handle Order Deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_order') {
    $order_id = $_POST['order_id'] ?? null;
    if ($order_id) {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = :order_id");
        if ($stmt->execute([':order_id' => $order_id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete order']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing order ID']);
    }
    exit;
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
    
    <!-- jsPDF for generating dependable PDF receipts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

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
            margin: 0; 
            padding: 0; 
        }

        /* Navigation Bar */
        .lp-nav {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: #fff;
            padding: 14px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(59, 34, 25, 0.05);
            flex-wrap: wrap;
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

        .nav-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-dark);
            cursor: pointer;
            padding: 5px;
        }

        .lp-nav-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .lp-nav-links {
            display: flex;
            gap: 25px;
            list-style: none;
            margin: 0;
            padding: 0;
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
            padding: 8px 20px;
            border-radius: var(--radius-btn);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: background-color 0.2s;
            display: inline-block;
            text-align: center;
        }

        .btn-nav:hover {
            background-color: var(--accent-pink-hover);
        }

        /* Responsive Layout Container */
        .container { 
            max-width: 1200px; 
            margin: 20px auto; 
            padding: 20px; 
            background-color: white; 
            border-radius: 10px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
        }

        h2, h3 { color: #d81b60; margin-bottom: 15px; }

        .table-container { 
            overflow-x: auto; 
            -webkit-overflow-scrolling: touch;
            margin-bottom: 20px; 
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        table { width: 100%; min-width: 650px; border-collapse: collapse; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px 12px; text-align: left; font-size: 14px; }
        th { background-color: #f8f8f8; color: #d81b60; white-space: nowrap; }

        .sales-section { 
            margin-bottom: 20px; 
            background-color: #f8f8f8; 
            padding: 15px; 
            border-radius: 8px; 
            border: 1px solid #ddd; 
        }

        select.status-dropdown { 
            width: 100%; 
            padding: 6px; 
            font-size: 13px; 
            background-color: #fff; 
            border: 1px solid #ccc; 
            border-radius: 5px; 
        }

        /* Modal Styles */
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1001; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); 
            padding: 15px; 
        }

        .modal-content { 
            background-color: #fff; 
            margin: 2% auto; 
            padding: 20px; 
            border: 1px solid #888; 
            width: 100%; 
            max-width: 650px; 
            border-radius: 12px; 
            position: relative; 
        }

        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 1; }
        .close:hover { color: black; }
        
        .details-btn { 
            background-color: #4CAF50; 
            color: white; 
            padding: 8px 14px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 13px; 
        }
        .details-btn:hover { opacity: 0.9; }

        .btn-delete { 
            background-color: #f44336; 
            color: white; 
            border: none; 
            padding: 8px 14px; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 13px; 
            font-weight: 600;
        }
        .btn-delete:hover { background-color: #d32f2f; }

        .receipt-btn { 
            background-color: #d81b60; 
            color: white; 
            padding: 8px 14px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 13px; 
            font-weight: 600; 
            display: inline-flex; 
            align-items: center; 
            gap: 5px; 
        }
        .receipt-btn:hover { background-color: #c2185b; }

        .modal-header-actions {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 15px; 
            padding-right: 30px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; }
        .modal-grid p { margin: 5px 0; font-size: 14px; word-break: break-word; }
        .full-width { grid-column: span 2; }
        textarea#notes { width: 100%; height: 80px; margin-top: 5px; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        #printable-receipt {
            position: absolute;
            left: -9999px;
            top: 0;
            width: 140mm;
            min-height: 200mm;
            background: #ffffff;
            color: #3b2219;
            box-sizing: border-box;
            padding: 20px;
            visibility: visible;
            pointer-events: none;
            z-index: 0;
            display: block;
        }

        /* Mobile Breakpoint Adjustments */
        @media screen and (max-width: 768px) {
            .nav-toggle {
                display: block;
            }

            .lp-nav-menu {
                display: none;
                flex-direction: column;
                width: 100%;
                margin-top: 12px;
                padding-top: 12px;
                border-top: 1px solid #f0f0f0;
                align-items: stretch;
                gap: 15px;
            }

            .lp-nav-menu.active {
                display: flex;
            }

            .lp-nav-links {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }

            .container {
                margin: 10px;
                padding: 15px;
            }

            .modal {
                padding: 10px;
            }

            .modal-content {
                margin: 5% auto;
                padding: 15px;
            }

            .modal-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: span 1;
            }

            .modal-actions button {
                width: 100%;
                margin-left: 0 !important;
            }

            .map-container-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 8px;
            }

            .map-container-header a {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="lp-nav">
        <a href="#" class="lp-logo">Lynx<span>Prise</span></a>
        <button class="nav-toggle" onclick="toggleNav()" aria-label="Toggle navigation">☰</button>
        <div class="lp-nav-menu" id="navMenu">
            <ul class="lp-nav-links">
                <li><a href="M_Dashboard.php">Orders</a></li>
                <li><a href="M_Products.php">Products</a></li>
                <li><a href="M_Categories.php">Categories</a></li>
                <li><a href="index.php">Back To Home</a></li>
            </ul>
            <a href="U_Logout.php" class="btn-nav">Logout</a>
        </div>
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
            <div class="modal-header-actions">
                <h2 style="margin: 0;">Order Details</h2>
                <button class="receipt-btn" id="download-receipt-btn">🧾 Download Receipt</button>
            </div>
            <div id="order-details"></div>
        </div>
    </div>

    <!-- Hidden Printable Receipt -->
    <div id="printable-receipt"></div>

    <script>
        const STORE_LAT = 10.7936; 
        const STORE_LNG = 124.9378; 
        const STORE_ADDRESS = "Brgy. Malitbogay, Javier, Leyte";

        let currentActiveOrder = null;

        function toggleNav() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.toggle('active');
        }

        function openModal(orderId) {
            var modal = document.getElementById("orderModal");
            var orderDetails = document.getElementById("order-details");

            fetch("M_GetOrderDetails.php?id=" + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const o = data.order;
                        currentActiveOrder = o;
                        
                        const val = String(o.mode_of_transpo || '').trim().toLowerCase();
                        const isDelivery = (val === '1' || val === 'delivery' || val === 'ship');

                        let cleanNotes = o.notes || '';
                        let cleanCardMessage = o.card_message || '';
                        
                        let recipientName = o.recipient_name || '';
                        let recipientContact = o.recipient_phone || o.recipient_contact || '';

                        const cardMatch = cleanCardMessage.match(/\[Recipient:\s*([^|]+)\|\s*Contact:\s*([^\]]+)\]/i);
                        if (cardMatch) {
                            if (!recipientName) recipientName = cardMatch[1].trim();
                            if (!recipientContact) recipientContact = cardMatch[2].trim();
                            cleanCardMessage = cleanCardMessage.replace(/\[Recipient:\s*[^|]+\|\s*Contact:\s*[^\]]+\]/gi, '').trim();
                        }

                        const notesMatch = cleanNotes.match(/\[Recipient:\s*([^|]+)\|\s*Contact:\s*([^\]]+)\]/i);
                        if (notesMatch) {
                            if (!recipientName) recipientName = notesMatch[1].trim();
                            if (!recipientContact) recipientContact = notesMatch[2].trim();
                            cleanNotes = cleanNotes.replace(/\[Recipient:\s*[^|]+\|\s*Contact:\s*[^\]]+\]/gi, '').trim();
                        }

                        const displayAddress = o.address || o.delivery_address || 'N/A';
                        const lat = o.latitude || o.lat || null;
                        const lng = o.longitude || o.lng || o.long || null;

                        let mapEmbedUrl = '';
                        let directionsUrl = '';

                        if (lat && lng) {
                            mapEmbedUrl = `https://maps.google.com/maps?q=${lat},${lng}&z=15&output=embed`;
                            directionsUrl = `https://www.google.com/maps/dir/?api=1&origin=${STORE_LAT},${STORE_LNG}&destination=${lat},${lng}&travelmode=driving`;
                        } else {
                            const encodedAddr = encodeURIComponent(displayAddress);
                            mapEmbedUrl = `https://maps.google.com/maps?q=${encodedAddr}&z=15&output=embed`;
                            directionsUrl = `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(STORE_ADDRESS)}&destination=${encodedAddr}&travelmode=driving`;
                        }

                        document.getElementById('download-receipt-btn').onclick = function() {
                            downloadReceipt(o, isDelivery, recipientName, recipientContact, displayAddress, cleanCardMessage);
                        };

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
                                    <p class="full-width"><strong>Coordinates:</strong> ${lat && lng ? `${lat}, ${lng}` : 'Not specified'}</p>
                                    
                                    <div class="full-width" style="margin-top:10px;">
                                        <div class="map-container-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                            <strong>Delivery Route & Location:</strong>
                                            <a href="${directionsUrl}" target="_blank" class="details-btn" style="text-decoration:none; display:inline-block; background-color:#1a73e8;">
                                                📍 Get Directions
                                            </a>
                                        </div>
                                        <iframe 
                                            width="100%" 
                                            height="200" 
                                            style="border:0; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.15);" 
                                            loading="lazy" 
                                            allowfullscreen 
                                            src="${mapEmbedUrl}">
                                        </iframe>
                                    </div>

                                    <p class="full-width" style="margin-top:10px;"><strong>Card Message / Instructions:</strong> ${cleanCardMessage || 'None'}</p>
                                ` : ''}

                                <div class="full-width" style="margin-top:10px;">
                                    <label for="notes"><strong>Admin Notes:</strong></label>
                                    <textarea id="notes">${cleanNotes}</textarea>
                                    <div class="modal-actions">
                                        <button type="button" class="details-btn" onclick="updateNotes(${orderId})">Save Notes</button>
                                        <button type="button" class="btn-delete" onclick="deleteOrder(event, ${orderId})">Delete Order</button>
                                    </div>
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

        function downloadReceipt(o, isDelivery, recipientName, recipientContact, displayAddress, cleanCardMessage) {
            const notesInput = document.getElementById('notes');
            const cleanNotes = notesInput ? notesInput.value : (o.notes || '');

            const lat = o.latitude || o.lat || null;
            const lng = o.longitude || o.lng || o.long || null;

            if (!window.jspdf || !window.jspdf.jsPDF) {
                alert('PDF library is still loading. Please try again in a moment.');
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ unit: 'mm', format: 'a5', orientation: 'portrait' });

            const pageWidth = doc.internal.pageSize.getWidth();
            const margin = 12;
            let y = 12;

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(20);
            doc.text('LynxPrise', margin, y);

            y += 7;
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text('OFFICIAL ORDER RECEIPT', margin, y);
            y += 5;
            doc.text(STORE_ADDRESS, margin, y);
            y += 10;

            doc.setDrawColor(217, 97, 139);
            doc.setLineWidth(0.5);
            doc.line(margin, y, pageWidth - margin, y);
            y += 6;

            const fields = [
                ['Order ID', `#${o.id || 'N/A'}`],
                ['Fulfillment', isDelivery ? 'Delivery' : 'Pickup'],
                ['Customer Name', o.full_name || 'N/A'],
                ['Customer Contact', o.phone_number || 'N/A'],
                ['Pickup / Delivery Date', o.date_of_pickup || 'N/A']
            ];

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(9);
            fields.forEach(([label, value]) => {
                const wrapped = doc.splitTextToSize(`${label}: ${value}`, pageWidth - margin * 2);
                doc.text(wrapped, margin, y);
                y += wrapped.length * 5;
            });

            y += 3;
            doc.setDrawColor(232, 195, 176);
            doc.setLineWidth(0.5);
            doc.line(margin, y, pageWidth - margin, y);
            y += 6;

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(10);
            doc.text('Order Item', margin, y);
            doc.text('Price', pageWidth - margin, y, { align: 'right' });
            y += 6;

            doc.setDrawColor(232, 195, 176);
            doc.setLineWidth(0.3);
            doc.line(margin, y, pageWidth - margin, y);
            y += 5;

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            const productText = doc.splitTextToSize(`${o.product_name || 'N/A'}`, pageWidth - margin * 2 - 30);
            doc.text(productText, margin, y);
            doc.text(`PHP ${parseFloat(o.price || 0).toFixed(2)}`, pageWidth - margin, y, { align: 'right' });
            y += productText.length * 4.5 + 4;

            if (isDelivery) {
                doc.setDrawColor(232, 195, 176);
                doc.setLineWidth(0.5);
                doc.line(margin, y, pageWidth - margin, y);
                y += 6;

                doc.setFont('helvetica', 'bold');
                doc.setFontSize(10);
                doc.text('Delivery Information', margin, y);
                y += 6;

                doc.setFont('helvetica', 'normal');
                doc.setFontSize(9);
                const deliveryFields = [
                    ['Recipient Name', recipientName || 'N/A'],
                    ['Recipient Contact', recipientContact || 'N/A'],
                    ['Address', displayAddress || 'N/A'],
                    ['Coordinates', lat && lng ? `${lat}, ${lng}` : 'Not specified'],
                    ['Maps Link', 'Open GPS route']
                ];

                deliveryFields.forEach(([label, value]) => {
                    const text = doc.splitTextToSize(`${label}: ${value}`, pageWidth - margin * 2);
                    doc.text(text, margin, y);
                    y += text.length * 4.5;
                });

                if (cleanCardMessage) {
                    y += 2;
                    const messageText = doc.splitTextToSize(`Card Message: ${cleanCardMessage}`, pageWidth - margin * 2);
                    doc.text(messageText, margin, y);
                    y += messageText.length * 4.5;
                }
            }

            if (cleanNotes) {
                y += 3;
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(9);
                doc.text('Note', margin, y);
                y += 5;
                doc.setFont('helvetica', 'normal');
                const noteText = doc.splitTextToSize(cleanNotes, pageWidth - margin * 2);
                doc.text(noteText, margin, y);
                y += noteText.length * 4.5;
            }

            y += 6;
            doc.setDrawColor(217, 97, 139);
            doc.setLineWidth(0.5);
            doc.line(margin, y, pageWidth - margin, y);
            y += 6;

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(12);
            doc.text(`Total Amount: PHP ${parseFloat(o.price || 0).toFixed(2)}`, margin, y, { align: 'left' });
            y += 10;

            doc.setFont('helvetica', 'italic');
            doc.setFontSize(8);
            doc.text('Thank you for ordering with LynxPrise!', pageWidth / 2, y, { align: 'center' });

            doc.save(`LynxPrise_Receipt_Order_${o.id}.pdf`);
        }

        function deleteOrder(event, orderId) {
            if (event && typeof event.preventDefault === 'function') {
                event.preventDefault();
            }

            if (!confirm("Are you sure you want to delete this order? This action cannot be undone.")) {
                return;
            }

            fetch("M_Dashboard.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "action=delete_order&order_id=" + orderId
            })
            .then(async res => {
                const text = await res.text();
                try {
                    return { ok: res.ok, data: JSON.parse(text) };
                } catch (err) {
                    return { ok: res.ok, data: { success: false, message: text || 'Unexpected server response' } };
                }
            })
            .then(({ ok, data }) => {
                if (data && data.success) {
                    closeModal();
                    alert("Order deleted successfully!");
                    location.reload();
                } else {
                    alert((data && data.message) || "Failed to delete order.");
                }
            })
            .catch(err => {
                console.error("Delete error:", err);
                alert("There was a problem deleting this order.");
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