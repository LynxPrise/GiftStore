<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: U_Login.php');
    exit;
}

include 'U_db.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

$message = '';
$message_type = '';

// ==========================================
// 1. AUTO TABLE CREATION
// ==========================================
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS blocked_dates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        blocked_date DATE NOT NULL,
        service_type ENUM('delivery', 'pickup', 'both') NOT NULL DEFAULT 'both',
        reason VARCHAR(255) DEFAULT 'Service Unavailable',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY date_service_unique (blocked_date, service_type)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS store_locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        location_name VARCHAR(150) NOT NULL,
        address TEXT NOT NULL,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS date_location_availability (
        id INT AUTO_INCREMENT PRIMARY KEY,
        available_date DATE NOT NULL,
        location_id INT NOT NULL,
        is_available TINYINT(1) DEFAULT 1,
        UNIQUE KEY date_loc_unique (available_date, location_id)
    )");
} catch (Exception $e) {}


// ==========================================
// 2. HANDLE ACTIONS
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {

    // --- SAVE DATE AVAILABILITY & STALL RESTRICTIONS ---
    if ($_POST['action_type'] === 'update_date_availability') {
        $date = $_POST['selected_date'] ?? '';
        $block_delivery = isset($_POST['block_delivery']) ? 1 : 0;
        $reason = trim($_POST['reason'] ?? 'Service Restricted');
        $disabled_locations = $_POST['disabled_locations'] ?? []; // Array of location IDs turned off for this date

        if (!empty($date)) {
            try {
                // Clear existing date blocks & location restrictions for this date
                $pdo->prepare("DELETE FROM blocked_dates WHERE blocked_date = :bdate")->execute([':bdate' => $date]);
                $pdo->prepare("DELETE FROM date_location_availability WHERE available_date = :adate")->execute([':adate' => $date]);

                // Fetch all active store locations
                $allLocs = $pdo->query("SELECT id FROM store_locations WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);

                $blocked_loc_count = count($disabled_locations);
                $total_loc_count = count($allLocs);

                // FIX: Only block pickup entirely if ALL active stalls are disabled
                $block_pickup = ($blocked_loc_count >= $total_loc_count && $total_loc_count > 0);

                // 1. Record Blocked Services in `blocked_dates`
                if ($block_delivery && $block_pickup) {
                    $stmt = $pdo->prepare("INSERT INTO blocked_dates (blocked_date, service_type, reason) VALUES (:d, 'both', :r)");
                    $stmt->execute([':d' => $date, ':r' => $reason]);
                } elseif ($block_delivery) {
                    $stmt = $pdo->prepare("INSERT INTO blocked_dates (blocked_date, service_type, reason) VALUES (:d, 'delivery', :r)");
                    $stmt->execute([':d' => $date, ':r' => $reason]);
                } elseif ($block_pickup) {
                    $stmt = $pdo->prepare("INSERT INTO blocked_dates (blocked_date, service_type, reason) VALUES (:d, 'pickup', :r)");
                    $stmt->execute([':d' => $date, ':r' => $reason]);
                }

                // 2. Store specific location availability overrides
                $locStmt = $pdo->prepare("INSERT INTO date_location_availability (available_date, location_id, is_available) VALUES (:d, :lid, :avail)");
                foreach ($allLocs as $locId) {
                    $isAvailable = in_array($locId, $disabled_locations) ? 0 : 1;
                    $locStmt->execute([':d' => $date, ':lid' => $locId, ':avail' => $isAvailable]);
                }

                $message = "Settings saved for " . date('M d, Y', strtotime($date)) . "!";
                $message_type = "success";
            } catch (Exception $e) {
                $message = "Database error: " . $e->getMessage();
                $message_type = "error";
            }
        }
    }

    // --- SAVE STORE LOCATION ---
    if ($_POST['action_type'] === 'save_location') {
        $loc_id = $_POST['location_id'] ?? null;
        $loc_name = trim($_POST['location_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $lat = trim($_POST['latitude'] ?? '');
        $lng = trim($_POST['longitude'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $countStmt = $pdo->query("SELECT COUNT(*) FROM store_locations");
        $total_locations = $countStmt->fetchColumn();

        if (empty($loc_id) && $total_locations >= 3) {
            $message = "You can only configure up to 3 store locations.";
            $message_type = "error";
        } elseif (!empty($loc_name) && !empty($address) && is_numeric($lat) && is_numeric($lng)) {
            try {
                if (!empty($loc_id)) {
                    $stmt = $pdo->prepare("UPDATE store_locations SET location_name = :name, address = :addr, latitude = :lat, longitude = :lng, is_active = :active WHERE id = :id");
                    $stmt->execute([':name' => $loc_name, ':addr' => $address, ':lat' => $lat, ':lng' => $lng, ':active' => $is_active, ':id' => $loc_id]);
                    $message = "Location updated successfully!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO store_locations (location_name, address, latitude, longitude, is_active) VALUES (:name, :addr, :lat, :lng, :active)");
                    $stmt->execute([':name' => $loc_name, ':addr' => $address, ':lat' => $lat, ':lng' => $lng, ':active' => $is_active]);
                    $message = "New location added successfully!";
                }
                $message_type = "success";
            } catch (Exception $e) {
                $message = "Database error: " . $e->getMessage();
                $message_type = "error";
            }
        } else {
            $message = "Please provide valid coordinates and details.";
            $message_type = "error";
        }
    }
}

// Delete Location
if (isset($_GET['delete_location_id'])) {
    $del_id = $_GET['delete_location_id'];
    $stmt = $pdo->prepare("DELETE FROM store_locations WHERE id = :id");
    $stmt->execute([':id' => $del_id]);
    header("Location: M_Settings.php?status=loc_deleted");
    exit();
}

if (isset($_GET['status']) && $_GET['status'] === 'loc_deleted') {
    $message = "Store location removed successfully!";
    $message_type = "success";
}

// ==========================================
// 3. FETCH DATA
// ==========================================

// Locations
$locations = [];
try {
    $l_stmt = $pdo->query("SELECT * FROM store_locations ORDER BY id ASC LIMIT 3");
    $locations = $l_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Blocked Dates
$blocked_dates = [];
try {
    $b_stmt = $pdo->query("SELECT blocked_date, service_type, reason FROM blocked_dates");
    while ($row = $b_stmt->fetch(PDO::FETCH_ASSOC)) {
        $blocked_dates[$row['blocked_date']] = [
            'type' => $row['service_type'],
            'reason' => $row['reason']
        ];
    }
} catch (Exception $e) {}

// Date-Location Overrides
$date_location_overrides = [];
try {
    $dlo_stmt = $pdo->query("SELECT available_date, location_id, is_available FROM date_location_availability");
    while ($row = $dlo_stmt->fetch(PDO::FETCH_ASSOC)) {
        $date_location_overrides[$row['available_date']][$row['location_id']] = (int)$row['is_available'];
    }
} catch (Exception $e) {}

$js_blocked_dates = json_encode($blocked_dates);
$js_locations = json_encode($locations);
$js_location_overrides = json_encode($date_location_overrides);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Settings - LynxPrise</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sacramento&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-soft-pink: #fdeee8;
            --accent-pink: #d9658b;
            --accent-pink-hover: #c45075;
            --heading-color: #d81b60;
            --text-dark: #3b2219;
            --border-color: #eaeaea;
            --radius-btn: 30px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-soft-pink); color: var(--text-dark); padding-bottom: 50px; }

        .lp-nav { 
            position: sticky; top: 0; z-index: 1000; background: #fff; 
            padding: 14px 5%; display: flex; justify-content: space-between; 
            align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); flex-wrap: wrap;
        }
        .lp-logo { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; color: var(--text-dark); text-decoration: none; }
        .lp-logo span { font-family: 'Sacramento', cursive; color: var(--accent-pink); font-size: 32px; }
        .nav-toggle { display: none; background: none; border: none; font-size: 24px; color: var(--text-dark); cursor: pointer; padding: 5px; }
        .lp-nav-menu { display: flex; align-items: center; gap: 20px; }
        .lp-nav-links { display: flex; gap: 25px; list-style: none; margin: 0; padding: 0; }
        .lp-nav-links a { text-decoration: none; color: var(--text-dark); font-weight: 500; font-size: 15px; transition: color 0.2s; }
        .lp-nav-links a:hover, .lp-nav-links a.active { color: var(--accent-pink); font-weight: 600; }
        .btn-logout { background: var(--accent-pink); color: #fff; padding: 8px 20px; border-radius: var(--radius-btn); text-decoration: none; font-weight: 600; font-size: 14px; display: inline-block; text-align: center; }
        .btn-logout:hover { background: var(--accent-pink-hover); }

        .container { max-width: 1150px; margin: 30px auto 0; padding: 0 20px; }
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .card { background: #ffffff; border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); height: fit-content; }
        .card-header { margin-bottom: 20px; border-bottom: 2px solid #fdf2f4; padding-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .card-header h2 { color: var(--heading-color); font-size: 20px; font-weight: 700; }
        .card-header p { font-size: 13px; color: #777; margin-top: 4px; }

        /* CALENDAR STYLES */
        .calendar-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .calendar-controls button { background: #f0f0f0; border: none; padding: 6px 14px; border-radius: 20px; font-weight: 600; cursor: pointer; }
        .month-title { font-weight: 700; font-size: 16px; color: var(--text-dark); }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; text-align: center; }
        .day-name { font-weight: 700; font-size: 12px; color: #999; padding: 6px 0; }
        
        .day-cell {
            aspect-ratio: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
            border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer;
            background: #fafafa; border: 1px solid #eee; transition: all 0.2s; position: relative; gap: 2px;
        }
        .day-cell:hover:not(.empty) { border-color: var(--accent-pink); background: #fff5f8; }
        .day-cell.empty { background: transparent; border: none; cursor: default; }

        .block-tag {
            font-size: 9px; padding: 1px 4px; border-radius: 4px; color: white; font-weight: 700; text-transform: uppercase;
        }
        .bg-delivery { background-color: #ff9800; } 
        .bg-pickup { background-color: #00bcd4; }   
        .bg-both { background-color: #f44336; }     

        .calendar-legend { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 18px; font-size: 11px; color: #666; }
        .legend-item { display: flex; align-items: center; gap: 5px; }
        .legend-color { width: 12px; height: 12px; border-radius: 3px; }

        /* LOCATION STYLES */
        .location-item { border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; margin-bottom: 15px; background: #fafafa; }
        .location-item.inactive { opacity: 0.6; }
        .loc-title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .loc-name { font-weight: 700; font-size: 15px; color: #111; }
        .loc-badge { font-size: 11px; padding: 3px 8px; border-radius: 12px; font-weight: 600; }
        .badge-active { background: #e6f4ea; color: #1e7e34; }
        .badge-inactive { background: #feedd8; color: #b95000; }
        .loc-address { font-size: 13px; color: #555; margin-bottom: 8px; }
        .loc-coords { font-size: 11px; color: #888; font-family: monospace; background: #eee; padding: 3px 6px; border-radius: 4px; display: inline-block; }
        .loc-actions { margin-top: 10px; display: flex; gap: 8px; }

        .btn { padding: 8px 18px; border-radius: 30px; border: none; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; font-size: 13px; }
        .btn-add-primary { background-color: #e0688d; color: #fff; }
        .btn-add-primary:hover { background-color: #c45075; }
        .btn-edit-blue { background-color: #0088cc; color: white; padding: 5px 12px; border-radius: 6px; font-size: 12px; }
        .btn-delete-red { background-color: #ff4d4d; color: white; padding: 5px 12px; border-radius: 6px; font-size: 12px; }
        .btn-secondary { background-color: #ccc; color: #333; }

        .alert { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-error { background-color: #f8d7da; color: #721c24; }

        /* MODALS */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 2000; }
        .modal-content { background: #fff; padding: 25px; border-radius: 12px; width: 100%; max-width: 480px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .modal-header h3 { color: var(--heading-color); }
        .close-btn { font-size: 22px; cursor: pointer; border: none; background: none; color: #888; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px; }
        .form-group input, .form-group textarea { width: 100%; padding: 9px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 13px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .checkbox-card { background: #fdf2f4; border: 1px solid #f8d7da; padding: 12px; border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .checkbox-card input { width: 18px; height: 18px; cursor: pointer; }
        .section-title { font-size: 12px; font-weight: 700; color: #555; text-transform: uppercase; margin: 12px 0 6px; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }

        @media screen and (max-width: 850px) {
            .settings-grid { grid-template-columns: 1fr; }
            .nav-toggle { display: block; }
            .lp-nav-menu { display: none; flex-direction: column; width: 100%; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f0f0f0; align-items: stretch; gap: 15px; }
            .lp-nav-menu.active { display: flex; }
            .lp-nav-links { flex-direction: column; gap: 12px; text-align: center; }
        }
    </style>
</head>
<body>

    <nav class="lp-nav">
        <a href="#" class="lp-logo">Lynx<span>Prise</span></a>
        <button class="nav-toggle" onclick="toggleNav()">☰</button>
        <div class="lp-nav-menu" id="navMenu">
            <ul class="lp-nav-links">
                <li><a href="M_Dashboard.php">Orders</a></li>
                <li><a href="M_Products.php">Products</a></li>
                <li><a href="M_Categories.php">Categories</a></li>
                <li><a href="M_Feedbacks.php">Feedbacks</a></li>
                <li><a href="M_Settings.php" class="active">Settings</a></li>
                <li><a href="index.php">Back To Home</a></li>
            </ul>
            <a href="U_Logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $message_type; ?>">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="settings-grid">
            
            <!-- CALENDAR AVAILABILITY CARD -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>Calendar Availability</h2>
                        <p>Click a date to manage delivery & pickup stall availability.</p>
                    </div>
                </div>

                <div class="calendar-controls">
                    <button onclick="changeMonth(-1)">&lt; Prev</button>
                    <div class="month-title" id="monthDisplay"></div>
                    <button onclick="changeMonth(1)">Next &gt;</button>
                </div>

                <div class="calendar-grid">
                    <div class="day-name">Sun</div>
                    <div class="day-name">Mon</div>
                    <div class="day-name">Tue</div>
                    <div class="day-name">Wed</div>
                    <div class="day-name">Thu</div>
                    <div class="day-name">Fri</div>
                    <div class="day-name">Sat</div>
                </div>
                
                <div class="calendar-grid" id="calendarDays"></div>

                <div class="calendar-legend">
                    <div class="legend-item"><div class="legend-color" style="background: #fafafa; border: 1px solid #ccc;"></div><span>Available</span></div>
                    <div class="legend-item"><div class="legend-color bg-delivery"></div><span>No Delivery</span></div>
                    <div class="legend-item"><div class="legend-color bg-pickup"></div><span>No Pickup</span></div>
                    <div class="legend-item"><div class="legend-color bg-both"></div><span>Fully Blocked</span></div>
                </div>
            </div>

            <!-- STORE LOCATIONS CARD -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>Store Locations / Stalls</h2>
                        <p>Configure pickup stalls (e.g. Javier Stall, Abuyog Stall).</p>
                    </div>
                    <?php if (count($locations) < 3): ?>
                        <button class="btn btn-add-primary" onclick="openAddLocationModal()">+ Add Stall</button>
                    <?php endif; ?>
                </div>

                <div id="locationsList">
                    <?php if (count($locations) > 0): ?>
                        <?php foreach ($locations as $loc): ?>
                            <div class="location-item <?= $loc['is_active'] ? '' : 'inactive'; ?>">
                                <div class="loc-title-row">
                                    <span class="loc-name"><?= htmlspecialchars($loc['location_name']); ?></span>
                                    <span class="loc-badge <?= $loc['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?= $loc['is_active'] ? 'Active' : 'Disabled'; ?>
                                    </span>
                                </div>
                                <div class="loc-address"><?= htmlspecialchars($loc['address']); ?></div>
                                <div class="loc-coords">
                                    Lat: <?= htmlspecialchars($loc['latitude']); ?> | Lng: <?= htmlspecialchars($loc['longitude']); ?>
                                </div>
                                <div class="loc-actions">
                                    <button class="btn-edit-blue" onclick='openEditLocationModal(<?= json_encode($loc); ?>)'>Edit</button>
                                    <a href="M_Settings.php?delete_location_id=<?= $loc['id']; ?>" class="btn-delete-red" onclick="return confirm('Delete this store location?')">Remove</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #888; text-align: center; padding: 20px;">No store locations added yet.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL: DATE & STALL AVAILABILITY MANAGER -->
    <div class="modal" id="dateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Manage Date Availability</h3>
                <button class="close-btn" onclick="closeDateModal()">&times;</button>
            </div>
            <form action="M_Settings.php" method="POST">
                <input type="hidden" name="action_type" value="update_date_availability">
                <input type="hidden" name="selected_date" id="selected_date">

                <p id="dateModalTitleText" style="margin-bottom: 12px; font-weight: 700; color: #111;"></p>

                <div class="section-title">Fulfillment Options</div>
                <label class="checkbox-card">
                    <input type="checkbox" name="block_delivery" id="chk_block_delivery" value="1">
                    <div>
                        <strong>Block Delivery</strong>
                        <div style="font-size: 11px; color: #666;">Turn off delivery orders for this date.</div>
                    </div>
                </label>

                <div class="section-title">Disable Pickup Stalls for this Date</div>
                <div id="stallCheckboxesContainer">
                    <!-- Populated dynamically via JS -->
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label for="reason">Reason / Note</label>
                    <input type="text" name="reason" id="date_reason" placeholder="e.g., Abuyog Stall Closed, Delivery Full">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDateModal()">Cancel</button>
                    <button type="submit" class="btn btn-add-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: LOCATION FORM -->
    <div class="modal" id="locationModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="locModalTitle">Add Store Location</h3>
                <button class="close-btn" onclick="closeLocationModal()">&times;</button>
            </div>
            <form action="M_Settings.php" method="POST">
                <input type="hidden" name="action_type" value="save_location">
                <input type="hidden" name="location_id" id="loc_id">

                <div class="form-group">
                    <label>Stall / Branch Name *</label>
                    <input type="text" name="location_name" id="loc_name" placeholder="e.g. Javier Stall, Abuyog Stall" required>
                </div>

                <div class="form-group">
                    <label>Full Address *</label>
                    <textarea name="address" id="loc_address" rows="2" placeholder="e.g. Street No., Barangay, City" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Latitude *</label>
                        <input type="text" name="latitude" id="loc_lat" placeholder="e.g. 10.7811" required>
                    </div>
                    <div class="form-group">
                        <label>Longitude *</label>
                        <input type="text" name="longitude" id="loc_lng" placeholder="e.g. 124.9312" required>
                    </div>
                </div>

                <div style="display:flex; gap:8px; align-items:center; margin-top:10px;">
                    <input type="checkbox" name="is_active" id="loc_active" value="1" checked>
                    <label for="loc_active" style="margin-bottom:0; cursor:pointer; font-weight:600; font-size:12px;">Globally Active</label>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeLocationModal()">Cancel</button>
                    <button type="submit" class="btn btn-add-primary">Save Location</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleNav() { document.getElementById('navMenu').classList.toggle('active'); }

        const blockedDates = <?= $js_blocked_dates; ?>;
        const allLocations = <?= $js_locations; ?>;
        const locationOverrides = <?= $js_location_overrides; ?>;
        let currentDate = new Date();

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();

            const monthNames = ["January", "February", "March", "April", "May", "June", 
                                "July", "August", "September", "October", "November", "December"];
            
            document.getElementById('monthDisplay').innerText = `${monthNames[month]} ${year}`;

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            const container = document.getElementById('calendarDays');
            container.innerHTML = '';

            for (let i = 0; i < firstDay; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.className = 'day-cell empty';
                container.appendChild(emptyCell);
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const cell = document.createElement('div');
                cell.className = 'day-cell';

                const dayNum = document.createElement('span');
                dayNum.innerText = day;
                cell.appendChild(dayNum);

                const mStr = String(month + 1).padStart(2, '0');
                const dStr = String(day).padStart(2, '0');
                const dateKey = `${year}-${mStr}-${dStr}`;

                if (blockedDates[dateKey] !== undefined) {
                    const info = blockedDates[dateKey];
                    const tag = document.createElement('span');
                    tag.className = 'block-tag';

                    if (info.type === 'delivery') {
                        tag.innerText = 'No Del';
                        tag.classList.add('bg-delivery');
                    } else if (info.type === 'pickup') {
                        tag.innerText = 'No Pick';
                        tag.classList.add('bg-pickup');
                    } else {
                        tag.innerText = 'Closed';
                        tag.classList.add('bg-both');
                    }
                    cell.appendChild(tag);
                    cell.title = info.reason || 'Restricted';
                }

                cell.onclick = () => openDateModal(dateKey, blockedDates[dateKey]);
                container.appendChild(cell);
            }
        }

        function changeMonth(delta) {
            currentDate.setMonth(currentDate.getMonth() + delta);
            renderCalendar();
        }

        const dateModal = document.getElementById('dateModal');
        
        function openDateModal(dateStr, existingData) {
            document.getElementById('selected_date').value = dateStr;
            document.getElementById('dateModalTitleText').innerText = `Configure Date: ${dateStr}`;

            const chkDel = document.getElementById('chk_block_delivery');
            const inputReason = document.getElementById('date_reason');

            if (existingData) {
                chkDel.checked = (existingData.type === 'delivery' || existingData.type === 'both');
                inputReason.value = existingData.reason || '';
            } else {
                chkDel.checked = false;
                inputReason.value = '';
            }

            // Build stall checkboxes dynamically
            const stallContainer = document.getElementById('stallCheckboxesContainer');
            stallContainer.innerHTML = '';

            if (allLocations.length === 0) {
                stallContainer.innerHTML = '<p style="font-size:12px; color:#888;">No store locations defined yet.</p>';
            } else {
                allLocations.forEach(loc => {
                    const label = document.createElement('label');
                    label.className = 'checkbox-card';

                    const isDisabledForDate = (locationOverrides[dateStr] && locationOverrides[dateStr][loc.id] === 0);

                    label.innerHTML = `
                        <input type="checkbox" name="disabled_locations[]" value="${loc.id}" ${isDisabledForDate ? 'checked' : ''}>
                        <div>
                            <strong>Disable ${loc.location_name}</strong>
                            <div style="font-size: 11px; color: #666;">Check to disable pickup at this stall on this date.</div>
                        </div>
                    `;
                    stallContainer.appendChild(label);
                });
            }

            dateModal.style.display = 'flex';
        }

        function closeDateModal() { dateModal.style.display = 'none'; }

        const locationModal = document.getElementById('locationModal');

        function openAddLocationModal() {
            document.getElementById('locModalTitle').innerText = "Add Store Location";
            document.getElementById('loc_id').value = "";
            document.getElementById('loc_name').value = "";
            document.getElementById('loc_address').value = "";
            document.getElementById('loc_lat').value = "";
            document.getElementById('loc_lng').value = "";
            document.getElementById('loc_active').checked = true;
            locationModal.style.display = 'flex';
        }

        function openEditLocationModal(data) {
            document.getElementById('locModalTitle').innerText = "Edit Store Location";
            document.getElementById('loc_id').value = data.id;
            document.getElementById('loc_name').value = data.location_name;
            document.getElementById('loc_address').value = data.address;
            document.getElementById('loc_lat').value = data.latitude;
            document.getElementById('loc_lng').value = data.longitude;
            document.getElementById('loc_active').checked = parseInt(data.is_active) === 1;
            locationModal.style.display = 'flex';
        }

        function closeLocationModal() { locationModal.style.display = 'none'; }

        window.onclick = function(e) {
            if (e.target === dateModal) closeDateModal();
            if (e.target === locationModal) closeLocationModal();
        };

        renderCalendar();
    </script>
</body>
</html>