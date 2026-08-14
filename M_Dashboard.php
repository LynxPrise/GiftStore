<?php
session_start();

// Security Guard: Check if the user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: U_Login.php');
    exit;
}

include 'U_db.php';

$current_date = date('Y-m-d');
$tomorrow_date = date('Y-m-d', strtotime('+1 day'));

// Helper function to resolve fulfillment type from mode_of_transpo
function getFulfillmentType($order) {
    if (!isset($order['mode_of_transpo'])) {
        return 'Pickup';
    }
    
    $val = strtolower(trim((string)$order['mode_of_transpo']));
    
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

// Fetch Active Dashboard Tables
$sql_today = "SELECT * FROM orders WHERE DATE(date_of_pickup) = :current_date ORDER BY date_of_pickup ASC";
$stmt_today = $pdo->prepare($sql_today);
$stmt_today->execute([':current_date' => $current_date]);
$today_orders = $stmt_today->fetchAll(PDO::FETCH_ASSOC);

$sql_tomorrow = "SELECT * FROM orders WHERE DATE(date_of_pickup) = :tomorrow_date ORDER BY date_of_pickup ASC";
$stmt_tomorrow = $pdo->prepare($sql_tomorrow);
$stmt_tomorrow->execute([':tomorrow_date' => $tomorrow_date]);
$tomorrow_orders = $stmt_tomorrow->fetchAll(PDO::FETCH_ASSOC);

$sql_future = "SELECT * FROM orders WHERE DATE(date_of_pickup) > :tomorrow_date ORDER BY date_of_pickup ASC";
$stmt_future = $pdo->prepare($sql_future);
$stmt_future->execute([':tomorrow_date' => $tomorrow_date]);
$future_orders = $stmt_future->fetchAll(PDO::FETCH_ASSOC);

// --- PREVIOUS CUSTOMER ORDERS QUERY ---
$sql_history = "SELECT * FROM orders WHERE DATE(date_of_pickup) < :current_date OR status IN ('completed', 'cancelled') ORDER BY date_of_pickup DESC";
$stmt_history = $pdo->prepare($sql_history);
$stmt_history->execute([':current_date' => $current_date]);
$previous_orders = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

// --- ANALYTICS DATA QUERIES ---
$stmt_count = $pdo->query("SELECT COUNT(*) as total FROM orders");
$total_orders_count = $stmt_count->fetchColumn();

// 1. Revenue by Month
$sql_monthly_revenue = "
    SELECT 
        DATE_FORMAT(date_of_pickup, '%b %Y') as month_name, 
        SUM(price) as total_revenue 
    FROM orders 
    WHERE status != 'cancelled' 
      AND YEAR(date_of_pickup) = YEAR(CURRENT_DATE())
    GROUP BY YEAR(date_of_pickup), MONTH(date_of_pickup), DATE_FORMAT(date_of_pickup, '%b %Y')
    ORDER BY YEAR(date_of_pickup) ASC, MONTH(date_of_pickup) ASC";
$stmt_revenue = $pdo->query($sql_monthly_revenue);
$revenue_data = $stmt_revenue->fetchAll(PDO::FETCH_ASSOC);

$revenue_labels = json_encode(array_column($revenue_data, 'month_name'));
$revenue_values = json_encode(array_map('floatval', array_column($revenue_data, 'total_revenue')));

// Handle optional date-range sales query (From / To)
$range_total = '0.00';
$range_label = '';
if (isset($_GET['from_date']) && isset($_GET['to_date']) && !empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    $from_date = $_GET['from_date'];
    $to_date = $_GET['to_date'];
    // Ensure valid date format (basic check)
    $from_ts = strtotime($from_date);
    $to_ts = strtotime($to_date);
    if ($from_ts !== false && $to_ts !== false) {
        // Normalize to Y-m-d
        $from_sql = date('Y-m-d', $from_ts);
        $to_sql = date('Y-m-d', $to_ts);
        $stmt_range = $pdo->prepare("SELECT COALESCE(SUM(price),0) as total FROM orders WHERE DATE(date_of_pickup) BETWEEN :from AND :to AND status != 'cancelled'");
        $stmt_range->execute([':from' => $from_sql, ':to' => $to_sql]);
        $row = $stmt_range->fetch(PDO::FETCH_ASSOC);
        $range_total = number_format((float)($row['total'] ?? 0), 2);
        $range_label = $from_sql . ' to ' . $to_sql;
    }
}

// 2. Order Breakdown by Status
$sql_status_summary = "
    SELECT 
        COALESCE(status, 'pending') as status, 
        COUNT(*) as total_count 
    FROM orders 
    GROUP BY status";
$stmt_status = $pdo->query($sql_status_summary);
$status_data = $stmt_status->fetchAll(PDO::FETCH_ASSOC);

$status_labels = json_encode(array_map('ucwords', array_column($status_data, 'status')));
$status_counts = json_encode(array_map('intval', array_column($status_data, 'total_count')));

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

// Handle Order Edit (from modal)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_order') {
    $order_id = $_POST['order_id'] ?? null;
    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Missing order ID']);
        exit;
    }

    $fields = [
        'full_name' => $_POST['full_name'] ?? null,
        'phone_number' => $_POST['phone_number'] ?? null,
        'date_of_pickup' => $_POST['date_of_pickup'] ?? null,
        'price' => isset($_POST['price']) ? (float)$_POST['price'] : null,
        'address' => $_POST['address'] ?? null,
    ];

    $updateParts = [];
    $params = [':order_id' => $order_id];
    foreach ($fields as $k => $v) {
        if ($v !== null) {
            $updateParts[] = "$k = :$k";
            $params[":$k"] = $v;
        }
    }

    if (count($updateParts) === 0) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }

    $sql = "UPDATE orders SET " . implode(', ', $updateParts) . " WHERE id = :order_id";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order']);
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
    <title>Dashboard & Analytics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..700;1,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sacramento&display=swap" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            --radius-btn: 30px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-soft-pink); color: var(--text-dark); line-height: 1.6; }

        /* HEADER & NAVIGATION STYLES */
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
        .lp-logo { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; color: var(--text-dark); text-decoration: none; }
        .lp-logo span { font-family: 'Sacramento', cursive; color: var(--accent-pink); font-size: 32px; margin-left: 2px; }
        .lp-nav-menu { display: flex; align-items: center; gap: 20px; }
        .lp-nav-links { display: flex; gap: 25px; list-style: none; }
        .lp-nav-links a { text-decoration: none; color: var(--text-dark); font-weight: 500; font-size: 15px; transition: color 0.2s; }
        .lp-nav-links a:hover { color: var(--accent-pink); }
        .btn-nav { background-color: var(--accent-pink); color: #fff; padding: 8px 20px; border-radius: var(--radius-btn); text-decoration: none; font-weight: 600; font-size: 14px; text-align: center; }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-dark);
            cursor: pointer;
        }

        /* Layout Containers */
        .page-wrapper { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .analytics-outer-container { background: white; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid var(--gold-border); }
        .container { background-color: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

        /* KPI Cards */
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .metric-card { background: var(--bg-cream); border: 1px solid var(--gold-border); padding: 15px; border-radius: 10px; text-align: center; }
        .metric-card h4 { color: var(--text-muted); font-size: 13px; text-transform: uppercase; margin-bottom: 5px; }
        .metric-card p { font-size: 20px; font-weight: 700; color: var(--accent-pink); }

        /* .analytics-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .chart-card { background: #ffffff; padding: 15px; border-radius: 12px; border: 1px solid var(--gold-border); } */

        /* Responsive Grid for Charts */
        .analytics-grid { 
            display: grid; 
            grid-template-columns: 2fr 1fr; 
            gap: 20px; 
        }

        .chart-card { 
            background: #ffffff; 
            padding: 15px; 
            border-radius: 12px; 
            border: 1px solid var(--gold-border); 
            min-width: 0; /* Critical for CSS Grid to prevent content overflow */
            width: 100%;
        }

        h2, h3 { color: #d81b60; margin-bottom: 15px; }

        .table-container { overflow-x: auto; margin-bottom: 25px; border: 1px solid #ddd; border-radius: 6px; }
        table { width: 100%; min-width: 650px; border-collapse: collapse; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px 12px; text-align: left; font-size: 14px; }
        th { background-color: #f8f8f8; color: #d81b60; }

        select.status-dropdown { width: 100%; padding: 6px; font-size: 13px; background-color: #fff; border: 1px solid #ccc; border-radius: 5px; }

        .details-btn { background-color: #4CAF50; color: white; padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; }
        .details-btn:hover { background-color: #43a047; }
        
        .receipt-btn { background-color: var(--accent-pink); color: white; padding: 8px 16px; border: none; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 13px; }
        .receipt-btn:hover { background-color: var(--accent-pink-hover); }

        .btn-delete { background-color: #ff4d4d; color: white; padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; margin-left: 8px; }
        .btn-delete:hover { background-color: #e60000; }

        /* MODAL STYLES */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(3px); z-index: 2000; overflow-y: auto; padding: 20px; }
        .modal-content { background: #fff; border-radius: 16px; width: 100%; max-width: 650px; margin: 30px auto; padding: 25px; position: relative; border: 1px solid var(--gold-border); box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        .close { position: absolute; top: 16px; right: 20px; font-size: 28px; border: none; background: none; cursor: pointer; color: var(--text-muted); }
        .close:hover { color: var(--accent-pink); }
        
        .modal-grid { display: flex; flex-wrap: wrap; gap: 10px 0; }
        .modal-grid p { width: 50%; margin-bottom: 8px; font-size: 14px; }
        .modal-grid p.full-width { width: 100%; }
        .modal-grid hr { width: 100%; border: 0; border-top: 1px solid #eee; margin: 10px 0; }
        .modal-grid textarea { width: 100%; min-height: 80px; padding: 10px; border: 1px solid var(--gold-border); border-radius: 8px; margin-top: 5px; font-family: inherit; font-size: 13px; outline: none; }

        /* Toast notifications */
        #toast-container { position: fixed; right: 20px; top: 20px; z-index: 4000; display: flex; flex-direction: column; gap: 10px; }
        .toast { min-width: 220px; max-width: 360px; padding: 10px 14px; border-radius: 8px; color: #fff; box-shadow: 0 6px 18px rgba(0,0,0,0.12); font-weight:600; }
        .toast.success { background: linear-gradient(90deg,#2ecc71,#27ae60); }
        .toast.error { background: linear-gradient(90deg,#e74c3c,#c0392b); }
        .toast.info { background: linear-gradient(90deg,#3498db,#2573a6); }
        .toast .close-toast { float:right; margin-left:8px; background:transparent; border:none; color:rgba(255,255,255,0.9); font-weight:700; cursor:pointer; }

        @media (max-width: 880px) {
            .mobile-menu-toggle { display: block; }
            .lp-nav-menu { display: none; width: 100%; flex-direction: column; gap: 16px; padding-top: 16px; border-top: 1px solid var(--gold-border); margin-top: 12px; }
            .lp-nav-menu.active { display: flex; }
            .lp-nav-links { flex-direction: column; align-items: center; gap: 12px; width: 100%; }
            .btn-nav { width: 100%; }
            .modal-grid p { width: 100%; }
        }

        @media screen and (max-width: 768px) {
            .analytics-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="lp-nav">
        <a href="#" class="lp-logo">Lynx<span>Prise</span></a>
        <button class="mobile-menu-toggle" id="menuToggle" aria-label="Toggle Navigation">&#9776;</button>
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

    <div class="page-wrapper">
        <!-- ANALYTICS CONTAINER -->
        <div class="analytics-outer-container">
            <h2>Business Analytics Overview</h2>

            <form method="get" style="margin:12px 0 18px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <label style="font-weight:600; color:var(--text-muted);">From: <input type="date" name="from_date" value="<?php echo htmlspecialchars($_GET['from_date'] ?? ''); ?>" style="padding:6px; border-radius:6px; border:1px solid #ddd;"></label>
                <label style="font-weight:600; color:var(--text-muted);">To: <input type="date" name="to_date" value="<?php echo htmlspecialchars($_GET['to_date'] ?? ''); ?>" style="padding:6px; border-radius:6px; border:1px solid #ddd;"></label>
                <button class="receipt-btn" type="submit">Search Sales</button>
                <?php if (!empty($range_label)): ?>
                    <div style="margin-left:12px; font-weight:700; color:var(--text-dark);">Range Total (<?php echo $range_label; ?>): ₱<?php echo $range_total; ?></div>
                <?php endif; ?>
            </form>

            <div class="metrics-grid">
                <div class="metric-card">
                    <h4>Total System Orders</h4>
                    <p><?php echo number_format($total_orders_count); ?></p>
                </div>
                <div class="metric-card">
                    <h4>Today's Sales</h4>
                    <p>₱<?php echo calculateTotalSales($today_orders); ?></p>
                </div>
                <div class="metric-card">
                    <h4>Tomorrow's Sales</h4>
                    <p>₱<?php echo calculateTotalSales($tomorrow_orders); ?></p>
                </div>
                <div class="metric-card">
                    <h4>Upcoming Sales</h4>
                    <p>₱<?php echo calculateTotalSales($future_orders); ?></p>
                </div>
            </div>

            <div class="analytics-grid">
                <div class="chart-card">
                    <h4 style="margin-bottom: 10px; color: var(--text-dark);">Monthly Revenue Overview</h4>
                    <canvas id="revenueChart" style="max-height: 240px;"></canvas>
                </div>
                <div class="chart-card">
                    <h4 style="margin-bottom: 10px; color: var(--text-dark);">Order Status Distribution</h4>
                    <canvas id="statusChart" style="max-height: 240px;"></canvas>
                </div>
            </div>
        </div>

        <!-- ORDER LIST & DASHBOARD CARD -->
        <div class="container">
            <h2>Order List & Dashboard</h2>

            <h3>Today's Orders</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>Product Name</th><th>Full Name</th><th>Phone Number</th><th>Type</th><th>Date</th><th>Price</th><th>Details</th><th>Status</th></tr>
                    </thead>
                    <tbody><?php echo renderOrderRows($today_orders); ?></tbody>
                </table>
            </div>

            <h3>Tomorrow's Orders</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>Product Name</th><th>Full Name</th><th>Phone Number</th><th>Type</th><th>Date</th><th>Price</th><th>Details</th><th>Status</th></tr>
                    </thead>
                    <tbody><?php echo renderOrderRows($tomorrow_orders); ?></tbody>
                </table>
            </div>

            <h3>Future Orders</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>Product Name</th><th>Full Name</th><th>Phone Number</th><th>Type</th><th>Date</th><th>Price</th><th>Details</th><th>Status</th></tr>
                    </thead>
                    <tbody><?php echo renderOrderRows($future_orders); ?></tbody>
                </table>
            </div>

            <h3>Previous Customer Orders & History</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>Product Name</th><th>Full Name</th><th>Phone Number</th><th>Type</th><th>Date</th><th>Price</th><th>Details</th><th>Status</th></tr>
                    </thead>
                    <tbody><?php echo renderOrderRows($previous_orders); ?></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- DYNAMIC ORDER MODAL -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-right: 30px;">
                <h2 style="margin: 0;">Order Details</h2>
                <div style="display:flex; gap:8px; align-items:center;">
                    <button class="details-btn" id="edit-order-btn" type="button">Edit</button>
                    <button class="receipt-btn" id="download-receipt-btn">🧾 Download Receipt</button>
                </div>
            </div>
            <div id="order-details"></div>
        </div>
    </div>

    <!-- Toast container for in-page notifications -->
    <div id="toast-container" aria-live="polite" aria-atomic="true"></div>

    <script>
        // Simple toast notification helper
        function showToast(message, type = 'info', timeout = 4000) {
            try {
                const container = document.getElementById('toast-container');
                if (!container) return;
                const toast = document.createElement('div');
                toast.className = 'toast ' + (type || 'info');
                toast.innerHTML = `<span>${message}</span><button class="close-toast" aria-label="Close">×</button>`;
                container.appendChild(toast);

                const btn = toast.querySelector('.close-toast');
                btn.addEventListener('click', () => { toast.remove(); });

                if (timeout > 0) {
                    setTimeout(() => { if (toast.parentNode) toast.remove(); }, timeout);
                }
            } catch (e) { console.error('Toast error', e); }
        }
        const STORE_LAT = 10.7936; 
        const STORE_LNG = 124.9378; 
        const STORE_ADDRESS = "Brgy. Malitbogay, Javier, Leyte";

        let currentActiveOrder = null;

        document.addEventListener("DOMContentLoaded", function() {
            // Revenue Bar Chart
            const revCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo $revenue_labels; ?>,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: <?php echo $revenue_values; ?>,
                        backgroundColor: 'rgba(217, 101, 139, 0.75)',
                        borderColor: '#d9658b',
                        borderWidth: 1.5,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, ticks: { callback: value => '₱' + value } } }
                }
            });

            // Status Doughnut Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo $status_labels; ?>,
                    datasets: [{
                        data: <?php echo $status_counts; ?>,
                        backgroundColor: ['#e2a0b5', '#4CAF50', '#f44336', '#ffb74d']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        });

        // Mobile Menu Toggle
        const menuToggle = document.getElementById('menuToggle');
        const navMenu = document.getElementById('navMenu');
        menuToggle.addEventListener('click', () => { navMenu.classList.toggle('active'); });

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
                        // Wire Edit button
                        const editBtn = document.getElementById('edit-order-btn');
                        if (editBtn) {
                            editBtn.onclick = function() { enableEdit(orderId); };
                        }

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
                                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                            <strong>Delivery Route & Location:</strong>
                                            <a href="${directionsUrl}" target="_blank" class="details-btn" style="text-decoration:none; display:inline-block; background-color:#1a73e8;">
                                                📍 Get Directions on Google Maps
                                            </a>
                                        </div>
                                        <iframe 
                                            width="100%" 
                                            height="230" 
                                            style="border:0; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.15);" 
                                            loading="lazy" 
                                            allowfullscreen 
                                            src="${mapEmbedUrl}">
                                        </iframe>
                                    </div>

                                    <p class="full-width" style="margin-top:10px;"><strong>Card Message / Instructions:</strong> ${cleanCardMessage || 'None'}</p>
                                ` : ''}

                                <div class="full-width" style="margin-top:10px;">
                                    <label for="notes"><strong>Note:</strong></label>
                                    <textarea id="notes">${cleanNotes}</textarea>
                                    <button type="button" class="details-btn" style="margin-top:8px;" onclick="updateNotes(${orderId})">Save Notes</button>
                                    <button type="button" class="btn-delete" onclick="deleteOrder(event, ${orderId})">Delete Order</button>
                                </div>
                            </div>
                        `;
                        modal.style.display = "block";
                    } else {
                        showToast("Error fetching order details.", 'error');
                    }
                })
                .catch(err => {
                    console.error("Error:", err);
                    showToast("Unable to fetch details.", 'error');
                });
        }

        function downloadReceipt(o, isDelivery, recipientName, recipientContact, displayAddress, cleanCardMessage) {
            const notesInput = document.getElementById('notes');
            const cleanNotes = notesInput ? notesInput.value : (o.notes || '');

            const lat = o.latitude || o.lat || null;
            const lng = o.longitude || o.lng || o.long || null;

            if (!window.jspdf || !window.jspdf.jsPDF) {
                showToast('PDF library is still loading. Please try again in a moment.', 'info');
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
                doc.text('Admin Notes', margin, y);
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
                        showToast("Order deleted successfully!", 'success');
                        location.reload();
                    } else {
                        showToast((data && data.message) || "Failed to delete order.", 'error');
                    }
            })
                .catch(err => {
                    console.error("Delete error:", err);
                    showToast("There was a problem deleting this order.", 'error');
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
                    if (!data.success) showToast("Failed to update order status.", 'error');
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
                if (data.success) showToast("Notes updated successfully!", 'success');
                else showToast("Failed to update notes.", 'error');
            });
        }

        function enableEdit(orderId) {
            if (!currentActiveOrder) return;
            const o = currentActiveOrder;
            const orderDetails = document.getElementById('order-details');
            orderDetails.innerHTML = `
                <div class="modal-grid">
                    <p class="full-width"><strong>Editing Order #${o.id}</strong></p>
                    <p class="full-width"><label><strong>Customer Name:</strong><br><input id="edit_full_name" value="${(o.full_name||'').replace(/"/g,'&quot;')}" style="width:100%; padding:6px; border-radius:6px; border:1px solid #ddd;"></label></p>
                    <p><label><strong>Phone:</strong><br><input id="edit_phone" value="${(o.phone_number||'').replace(/"/g,'&quot;')}" style="padding:6px; border-radius:6px; border:1px solid #ddd;"></label></p>
                    <p><label><strong>Date:</strong><br><input id="edit_date" type="date" value="${(o.date_of_pickup? o.date_of_pickup.split(' ')[0] : '')}" style="padding:6px; border-radius:6px; border:1px solid #ddd;"></label></p>
                    <p><label><strong>Price:</strong><br><input id="edit_price" type="number" step="0.01" value="${parseFloat(o.price||0).toFixed(2)}" style="padding:6px; border-radius:6px; border:1px solid #ddd;"></label></p>
                    <p class="full-width"><label><strong>Address:</strong><br><input id="edit_address" value="${(o.address||o.delivery_address||'').replace(/"/g,'&quot;')}" style="width:100%; padding:6px; border-radius:6px; border:1px solid #ddd;"></label></p>
                    <div class="full-width" style="margin-top:10px;">
                        <button class="details-btn" onclick="saveEdit(${orderId})">Save Changes</button>
                        <button class="btn-delete" onclick="cancelEdit(${orderId})">Cancel</button>
                    </div>
                </div>
            `;
        }

        function saveEdit(orderId) {
            const full_name = document.getElementById('edit_full_name').value;
            const phone_number = document.getElementById('edit_phone').value;
            const date_of_pickup = document.getElementById('edit_date').value;
            const price = document.getElementById('edit_price').value;
            const address = document.getElementById('edit_address').value;

            const body = `action=edit_order&order_id=${orderId}&full_name=${encodeURIComponent(full_name)}&phone_number=${encodeURIComponent(phone_number)}&date_of_pickup=${encodeURIComponent(date_of_pickup)}&price=${encodeURIComponent(price)}&address=${encodeURIComponent(address)}`;

            fetch('M_Dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Order updated successfully.', 'success');
                    // Refresh details from server to reflect changes
                    openModal(orderId);
                } else {
                    showToast(data.message || 'Failed to update order.', 'error');
                }
            })
            .catch(err => { console.error(err); showToast('Error updating order.', 'error'); });
        }

        function cancelEdit(orderId) {
            // Re-fetch original details
            openModal(orderId);
        }

        window.onclick = function(e) {
            const modal = document.getElementById("orderModal");
            if (e.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>