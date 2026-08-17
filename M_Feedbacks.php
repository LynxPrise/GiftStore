<?php
session_start();

// Security Guard: Check if the user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: U_Login.php');
    exit;
}

include 'U_db.php';

include 'M_Header.php';
include 'M_Sidebar.php';

$message = '';
$message_type = '';

// If U_db.php uses mysqli ($conn) instead of PDO ($pdo), convert/alias it here safely
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn; 
}

// ==========================================
// 1. HANDLE FORM SUBMISSIONS (CRUD)
// ==========================================

// --- ADD / EDIT FEEDBACK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    
    $name = trim($_POST['name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $feedback = trim($_POST['feedback'] ?? '');
    $id = $_POST['feedback_id'] ?? null;
    
    // Handle Image Upload
    $image_path = $_POST['existing_image'] ?? ''; 
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/feedbacks/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        }
    }

    if (!empty($name) && !empty($title) && !empty($feedback)) {
        try {
            if ($_POST['action_type'] === 'add') {
                // INSERT
                $stmt = $pdo->prepare("INSERT INTO feedbacks (name, title, feedback, image) VALUES (:name, :title, :feedback, :image)");
                $stmt->execute([
                    ':name'     => $name,
                    ':title'    => $title,
                    ':feedback' => $feedback,
                    ':image'    => $image_path
                ]);
                $message = "Feedback added successfully!";
                $message_type = "success";
            } elseif ($_POST['action_type'] === 'edit' && !empty($id)) {
                // UPDATE
                $stmt = $pdo->prepare("UPDATE feedbacks SET name = :name, title = :title, feedback = :feedback, image = :image WHERE id = :id");
                $stmt->execute([
                    ':name'     => $name,
                    ':title'    => $title,
                    ':feedback' => $feedback,
                    ':image'    => $image_path,
                    ':id'       => $id
                ]);
                $message = "Feedback updated successfully!";
                $message_type = "success";
            }
        } catch (Exception $e) {
            $message = "Database error: " . $e->getMessage();
            $message_type = "error";
        }
    } else {
        $message = "Please fill in all required fields.";
        $message_type = "error";
    }
}

// --- DELETE FEEDBACK ---
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        // Optional: Remove image file from folder if exists
        $stmt_img = $pdo->prepare("SELECT image FROM feedbacks WHERE id = :id");
        $stmt_img->execute([':id' => $delete_id]);
        $row = $stmt_img->fetch();
        if ($row && !empty($row['image']) && file_exists($row['image'])) {
            unlink($row['image']);
        }

        $stmt = $pdo->prepare("DELETE FROM feedbacks WHERE id = :id");
        $stmt->execute([':id' => $delete_id]);
        
        header("Location: M_Feedbacks?status=deleted");
        exit();
    } catch (Exception $e) {
        $message = "Failed to delete: " . $e->getMessage();
        $message_type = "error";
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'deleted') {
    $message = "Feedback deleted successfully!";
    $message_type = "success";
}

// ==========================================
// 2. FETCH FEEDBACKS FOR TABLE
// ==========================================
try {
    $stmt = $pdo->query("SELECT * FROM feedbacks ORDER BY id DESC");
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $feedbacks = [];
    $message = "Error fetching records: " . $e->getMessage();
    $message_type = "error";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - LynxPrise</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sacramento&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-soft-pink: #fdeee8;
            --accent-pink: #d9658b;
            --accent-hover: #c45075;
            --heading-color: #d81b60;
            --text-dark: #3b2219;
            --border-color: #e5e5e5;
            --radius-btn: 30px;
            --accent-pink-hover: #c45075;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-soft-pink);
            color: var(--text-dark);
            padding-bottom: 40px;
        }

        /* Navbar Header */
        .lp-nav { 
            position: sticky; 
            top: 0; 
            z-index: 1000;
            background: #fff; 
            padding: 14px 5%; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
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

        .lp-nav-links a:hover,
        .lp-nav-links a.active {
            color: var(--accent-pink);
            font-weight: 600;
        }

        .btn-logout { 
            background: var(--accent-pink); 
            color: #fff; 
            padding: 8px 20px; 
            border-radius: var(--radius-btn); 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 14px;
            display: inline-block;
            text-align: center;
            transition: background-color 0.2s;
        }

        .btn-logout:hover {
            background: var(--accent-pink-hover);
        }

        /* --- CONTAINER & CARD --- */
        .container {
            max-width: 1150px;
            margin: 30px auto 0;
            padding: 0 20px;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            padding: 35px 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header-actions h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #d81b60;
            font-size: 24px;
            font-weight: 700;
        }

        .sub-header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .sub-header-actions h3 {
            color: #d81b60;
            font-size: 18px;
            font-weight: 700;
        }

        /* --- SEARCH BOX --- */
        .search-box-wrapper {
            position: relative;
            width: 280px;
        }

        .search-box-wrapper input {
            width: 100%;
            padding: 8px 16px 8px 36px;
            border-radius: 30px;
            border: 1px solid #ddd;
            font-size: 13px;
            outline: none;
            color: #555;
            background-color: #fafafa;
        }

        .search-box-wrapper input::placeholder {
            color: #aaa;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #3b82f6;
            font-size: 14px;
        }

        /* --- BUTTONS --- */
        .btn {
            padding: 8px 22px;
            border-radius: 30px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        .btn-add-primary {
            background-color: #e0688d;
            color: #fff;
        }

        .btn-add-primary:hover {
            background-color: #c45075;
        }

        .btn-action {
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-edit-blue {
            background-color: #0088cc;
            color: white;
            margin-right: 6px;
        }

        .btn-delete-red {
            background-color: #ff4d4d;
            color: white;
        }

        .btn-secondary {
            background-color: #ccc;
            color: #333;
        }

        /* --- TABLE STYLING (MATCHING SCREENSHOT) --- */
        .table-responsive {
            overflow-x: auto;
        }

        table.styled-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            border: 1px solid #eaeaea;
        }

        table.styled-table th {
            background-color: #fcfcfc;
            color: #d81b60;
            padding: 14px 16px;
            font-size: 14px;
            font-weight: 700;
            border: 1px solid #eaeaea;
        }

        table.styled-table td {
            padding: 16px;
            border: 1px solid #eaeaea;
            vertical-align: top;
            font-size: 13px;
            color: #333;
        }

        /* Avatar / Image Box Style */
        .thumb-img {
            width: 58px;
            height: 58px;
            border-radius: 10px;
            object-fit: cover;
        }

        .thumb-placeholder {
            width: 58px;
            height: 58px;
            border-radius: 10px;
            background-color: #000;
            color: #ff007f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .feedback-name {
            font-weight: 700;
            font-size: 14px;
            color: #111;
            margin-bottom: 4px;
        }

        .feedback-desc {
            color: #666;
            font-size: 12px;
            line-height: 1.5;
        }

        /* --- ALERTS --- */
        .alert {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-error { background-color: #f8d7da; color: #721c24; }

        /* --- MODAL --- */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: var(--heading-color);
            font-family: 'Playfair Display', serif;
        }

        .close-btn {
            font-size: 24px;
            cursor: pointer;
            border: none;
            background: none;
            color: #888;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 13px;
        }

        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            height: 100px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        /* Mobile Responsive Adjustments */
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
                padding: 10px;
            }

            .sub-header-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .search-box-wrapper {
                width: 100%;
            }

            .modal-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

    <!-- Header / Navigation -->
    <!-- <nav class="lp-nav">
        <a href="#" class="lp-logo">Lynx<span>Prise</span></a>
        <button class="nav-toggle" onclick="toggleNav()" aria-label="Toggle Navigation">☰</button>
        <div class="lp-nav-menu" id="navMenu">
            <ul class="lp-nav-links">
                <li><a href="M_Dashboard.php">Orders</a></li>
                <li><a href="M_Products.php">Products</a></li>
                <li><a href="M_Categories.php">Categories</a></li>
                <li><a href="M_Feedbacks.php" class="active">Feedbacks</a></li>
                <li><a href="M_Settings.php">Settings</a></li>
                <li><a href="index.php">Back To Home</a></li>
            </ul>
            <a href="U_Logout.php" class="btn-logout">Logout</a>
        </div>
    </nav> -->

    
<main class="main-workspace">

    <!-- Main Container -->
    <div class="container">
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $message_type; ?>">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <!-- Top Action Header -->
            <div class="header-actions">
                <h2>Customer Feedbacks</h2>
                <button class="btn btn-add-primary" onclick="openAddModal()">+ Add New Feedback</button>
            </div>

            <!-- Sub Header Search Section -->
            <div class="sub-header-actions">
                <h3>Feedback List</h3>
                <div class="search-box-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search feedback or customer...">
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="styled-table" id="feedbackTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Image</th>
                            <th style="width: 32%;">Customer & Feedback</th>
                            <th style="width: 22%;">Title</th>
                            <th style="width: 16%;">Date</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($feedbacks) > 0): ?>
                            <?php foreach ($feedbacks as $row): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($row['image']) && file_exists($row['image'])): ?>
                                            <img src="<?= htmlspecialchars($row['image']); ?>" class="thumb-img" alt="Avatar">
                                        <?php else: ?>
                                            <div class="thumb-placeholder">
                                                ♥
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="feedback-name"><?= htmlspecialchars($row['name']); ?></div>
                                        <div class="feedback-desc"><?= htmlspecialchars($row['feedback']); ?></div>
                                    </td>
                                    <td style="font-weight: 500; color: #444;">
                                        <?= htmlspecialchars($row['title']); ?>
                                    </td>
                                    <td style="color: #555;">
                                        <?= date('M d, Y', strtotime($row['created_at'])); ?>
                                    </td>
                                    <td>
                                        <button class="btn-action btn-edit-blue" onclick='openEditModal(<?= json_encode($row); ?>)'>Edit</button>
                                        <a href="M_Feedbacks.php?delete_id=<?= $row['id']; ?>" class="btn-action btn-delete-red" onclick="return confirm('Are you sure you want to delete this feedback?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #888; padding: 30px;">No feedback entries found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Form (Used for both Add & Edit) -->
    <div class="modal" id="feedbackModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Feedback</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form action="M_Feedbacks.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action_type" id="action_type" value="add">
                <input type="hidden" name="feedback_id" id="feedback_id" value="">
                <input type="hidden" name="existing_image" id="existing_image" value="">

                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" name="name" id="form_name" required>
                </div>

                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" name="title" id="form_title" placeholder="e.g., Great Service!" required>
                </div>

                <div class="form-group">
                    <label for="feedback">Feedback Content *</label>
                    <textarea name="feedback" id="form_feedback" required></textarea>
                </div>

                <div class="form-group">
                    <label for="image">Customer Image / Avatar</label>
                    <input type="file" name="image" id="form_image" accept="image/*">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-add-primary" id="submitBtn">Save Feedback</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal & Search Script Handling -->
    <script>
        // Toggle Nav Menu on Small Screens
        function toggleNav() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.toggle('active');
        }

        // Live Search Filter for Table
        function filterTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toLowerCase();
            const table = document.getElementById("feedbackTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName("td");
                if (td.length > 0) {
                    let textContent = tr[i].textContent || tr[i].innerText;
                    if (textContent.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        const modal = document.getElementById('feedbackModal');
        const modalTitle = document.getElementById('modalTitle');
        const actionType = document.getElementById('action_type');
        const feedbackId = document.getElementById('feedback_id');
        const existingImage = document.getElementById('existing_image');
        
        const formName = document.getElementById('form_name');
        const formTitle = document.getElementById('form_title');
        const formFeedback = document.getElementById('form_feedback');

        function openAddModal() {
            modalTitle.innerText = "Add Feedback";
            actionType.value = "add";
            feedbackId.value = "";
            existingImage.value = "";
            
            formName.value = "";
            formTitle.value = "";
            formFeedback.value = "";
            
            modal.style.display = "flex";
        }

        function openEditModal(data) {
            modalTitle.innerText = "Edit Feedback";
            actionType.value = "edit";
            feedbackId.value = data.id;
            existingImage.value = data.image || "";
            
            formName.value = data.name;
            formTitle.value = data.title;
            formFeedback.value = data.feedback;
            
            modal.style.display = "flex";
        }

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</main>
</body>
</html>