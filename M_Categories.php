<?php
session_start();

// Security Guard: Check if the user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: U_Login.php');
    exit;
}

// Include database connection (adjust path/filename if needed)
require_once 'U_db.php'; 

$message = "";
$message_type = "";

// Directory where uploaded category images will be stored
$upload_dir = 'Assets/Images/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// -------------------------------------------------------------
// 1. HANDLE ADD CATEGORY
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $categoryName = trim($_POST['categoryName']);
    $description  = trim($_POST['description']);
    $imagePath    = '';

    // Handle File Upload
    if (isset($_FILES['categoryImage']) && $_FILES['categoryImage']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['categoryImage']['tmp_name'];
        $fileName    = $_FILES['categoryImage']['name'];
        $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExt, $allowedExtensions)) {
            $newFileName = 'cat_' . time() . '_' . mt_rand(1000, 9999) . '.' . $fileExt;
            $destination = $upload_dir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destination)) {
                $imagePath = $destination;
            } else {
                $message = "Error moving the uploaded file.";
                $message_type = "error";
            }
        } else {
            $message = "Invalid image file type. Allowed: JPG, PNG, WEBP.";
            $message_type = "error";
        }
    }

    if (empty($message_type) && !empty($categoryName)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (categoryName, description, categoryImage) VALUES (:name, :desc, :img)");
            $stmt->execute([
                ':name' => $categoryName,
                ':desc' => $description,
                ':img'  => $imagePath
            ]);
            $_SESSION['flash_msg'] = "Category '{$categoryName}' added successfully!";
            header("Location: M_Categories.php");
            exit;
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// -------------------------------------------------------------
// 2. HANDLE EDIT CATEGORY
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $catId        = intval($_POST['cat_id']);
    $categoryName = trim($_POST['categoryName']);
    $description  = trim($_POST['description']);
    $existingImg  = trim($_POST['existing_image']);
    $imagePath    = $existingImg;

    // Check if new image is uploaded
    if (isset($_FILES['categoryImage']) && $_FILES['categoryImage']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['categoryImage']['tmp_name'];
        $fileName    = $_FILES['categoryImage']['name'];
        $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExt, $allowedExtensions)) {
            $newFileName = 'cat_' . time() . '_' . mt_rand(1000, 9999) . '.' . $fileExt;
            $destination = $upload_dir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destination)) {
                $imagePath = $destination;
                if ($existingImg && file_exists($existingImg) && strpos($existingImg, 'default') === false) {
                    @unlink($existingImg);
                }
            }
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE categories SET categoryName = :name, description = :desc, categoryImage = :img WHERE id = :id");
        $stmt->execute([
            ':name' => $categoryName,
            ':desc' => $description,
            ':img'  => $imagePath,
            ':id'   => $catId
        ]);
        $_SESSION['flash_msg'] = "Category updated successfully!";
        header("Location: M_Categories.php");
        exit;
    } catch (PDOException $e) {
        $message = "Database Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// -------------------------------------------------------------
// 3. HANDLE DELETE CATEGORY
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = intval($_GET['id']);
    try {
        $stmtFetch = $pdo->prepare("SELECT categoryImage FROM categories WHERE id = :id");
        $stmtFetch->execute([':id' => $delId]);
        $cat = $stmtFetch->fetch();

        if ($cat && !empty($cat['categoryImage']) && file_exists($cat['categoryImage'])) {
            @unlink($cat['categoryImage']);
        }

        $stmtDel = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $stmtDel->execute([':id' => $delId]);

        $_SESSION['flash_msg'] = "Category deleted successfully!";
        header("Location: M_Categories.php");
        exit;
    } catch (PDOException $e) {
        $message = "Error deleting category: " . $e->getMessage();
        $message_type = "error";
    }
}

// Flash Message Handling
if (isset($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $message_type = "success";
    unset($_SESSION['flash_msg']);
}

// Fetch All Categories
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching categories: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Categories | LynxPrise Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..700;1,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sacramento&display=swap" rel="stylesheet">
    
  <style>
    :root {
        --bg-cream: #fff9f6;
        --bg-soft-pink: #fdeee8;
        --card-bg: #ffffff;
        --accent-pink: #d9658b;
        --accent-pink-hover: #c45075;
        --text-dark: #3b2219;
        --text-muted: #785a50;
        --gold-border: #e8c3b0;
        --radius-lg: 16px;
        --radius-md: 12px;
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

    .admin-container { 
        max-width: 1150px; 
        margin: 30px auto; 
        padding: 0 20px 40px 20px;
    }

    /* Main Container Card matching M_Products */
    .main-card {
        background: #ffffff;
        border-radius: var(--radius-lg);
        padding: 32px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        border: 1px solid #f2e3dc;
    }

    .header-bar { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .header-bar h1 { 
        font-family: 'Playfair Display', serif; 
        font-size: 28px; 
        color: #c2185b; 
        font-weight: 700;
    }

    .btn-add-modal { 
        background: #e0668b; 
        color: #fff; 
        border: none; 
        padding: 10px 22px; 
        border-radius: 20px; 
        font-weight: 600; 
        cursor: pointer; 
        text-decoration: none; 
        display: inline-block; 
        text-align: center;
        font-size: 14px;
        transition: background-color 0.2s ease;
    }
    .btn-add-modal:hover { background: #c45075; }

    /* Subheader & Search Container */
    .catalog-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .catalog-bar h2 {
        font-family: 'Playfair Display', serif;
        font-size: 20px;
        color: #c2185b;
        font-weight: 700;
    }

    .search-box {
        position: relative;
        width: 100%;
        max-width: 320px;
    }

    .search-box input {
        width: 100%;
        padding: 8px 16px 8px 36px;
        border: 1px solid #e0e0e0;
        border-radius: 20px;
        font-size: 13px;
        outline: none;
        background-color: #fafafa;
        color: #333;
    }

    .search-box input::placeholder {
        color: #9e9e9e;
    }

    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9e9e9e;
        font-size: 14px;
    }

    /* Alert Banner */
    .alert-banner { padding: 12px 18px; border-radius: 8px; font-size: 14px; font-weight: 600; margin-bottom: 20px; }
    .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
    .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }

    /* Table Container & Layout */
    .table-container {
        border: 1px solid #eee;
        border-radius: 4px;
        overflow-x: auto;
    }

    table { 
        width: 100%; 
        border-collapse: collapse; 
        text-align: left; 
        min-width: 650px;
    }

    th, td { padding: 16px 20px; border-bottom: 1px solid #eee; border-right: 1px solid #eee; vertical-align: top; }
    th:last-child, td:last-child { border-right: none; }
    th { background: #fafafa; font-weight: 700; color: #c2185b; font-size: 13px; white-space: nowrap; }
    tr:last-child td { border-bottom: none; }

    .cat-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; background: #f8f8f8; }
    
    .cat-name {
        font-weight: 700;
        color: #222;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .cat-desc {
        font-size: 12px;
        color: #666;
        line-height: 1.4;
    }

    /* Matching Action Buttons (Blue & Red) */
    .action-btn { 
        padding: 6px 16px; 
        border-radius: 4px; 
        font-size: 12px; 
        font-weight: 500; 
        text-decoration: none; 
        display: inline-block; 
        margin: 2px 2px; 
        cursor: pointer; 
        border: none; 
        color: #fff;
    }
    .btn-edit { background: #008be3; }
    .btn-edit:hover { background: #0076c2; }
    .btn-delete { background: #ff4d4d; }
    .btn-delete:hover { background: #e03e3e; }

    /* Modal Styling */
    .modal-overlay { 
        display: none; 
        position: fixed; 
        top:0; 
        left:0; 
        width:100%; 
        height:100%; 
        background: rgba(0,0,0,0.5); 
        backdrop-filter: blur(3px); 
        z-index: 2000; 
        justify-content: center; 
        align-items: center; 
        padding: 16px;
    }

    .modal-box { 
        background: #fff; 
        border-radius: var(--radius-lg); 
        width: 100%; 
        max-width: 500px; 
        padding: 28px; 
        position: relative; 
        border: 1px solid var(--gold-border); 
        box-shadow: 0 10px 30px rgba(0,0,0,0.15); 
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-close { position: absolute; top: 16px; right: 20px; font-size: 24px; border: none; background: none; cursor: pointer; color: var(--text-muted); }
    .modal-title { font-family: 'Playfair Display', serif; font-size: 22px; margin-bottom: 20px; color: #c2185b; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--gold-border); border-radius: 8px; font-family: inherit; font-size: 14px; outline: none; background: #fff; }
    textarea.form-control { resize: vertical; min-height: 80px; }

    .btn-submit { width: 100%; background: #e0668b; color: #fff; border: none; padding: 12px; border-radius: 20px; font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 10px; }
    .btn-submit:hover { background: var(--accent-pink-hover); }

    /* Navigation Bar */
    .lp-nav {
        position: sticky;
        top: 0;
        z-index: 1000;
        background-color: #fff;
        padding: 18px 5%;
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

    .lp-nav-menu {
        display: flex;
        align-items: center;
        gap: 30px;
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
        background-color: #e0668b;
        color: #fff;
        padding: 8px 22px;
        border-radius: var(--radius-btn);
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: background-color 0.2s;
    }

    .btn-nav:hover {
        background-color: var(--accent-pink-hover);
    }

    /* Mobile Menu Toggle Button */
    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 24px;
        color: var(--text-dark);
        cursor: pointer;
    }

    /* Responsive Breakpoints */
    @media (max-width: 880px) {
        .mobile-menu-toggle {
            display: block;
        }

        .lp-nav-menu {
            display: none;
            width: 100%;
            flex-direction: column;
            gap: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--gold-border);
            margin-top: 12px;
        }

        .lp-nav-menu.active {
            display: flex;
        }

        .lp-nav-links {
            flex-direction: column;
            align-items: center;
            gap: 12px;
            width: 100%;
        }

        .btn-nav {
            width: 100%;
            text-align: center;
        }
    }

    @media (max-width: 576px) {
        .header-bar {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }

        .catalog-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .search-box {
            max-width: 100%;
        }

        .header-bar h1 {
            font-size: 24px;
        }

        .btn-add-modal {
            width: 100%;
        }

        th, td {
            padding: 12px 14px;
        }

        .modal-box {
            padding: 20px;
        }
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
                 <li><a href="M_Feedbacks.php">Feedbacks</a></li>
                <li><a href="M_Settings.php">Settings</a></li>
                <li><a href="index.php">Back To Home</a></li>
            </ul>
            <a href="U_Logout.php" class="btn-nav">Logout</a>
        </div>
    </nav>

  <div class="admin-container">
    <div class="main-card">
        <!-- HEADER INSIDE CARD -->
        <div class="header-bar">
          <h1>Manage Categories</h1>
          <button class="btn-add-modal" onclick="openAddModal()">+ Add New Category</button>
        </div>

        <!-- CATALOG SUBHEADER & SEARCH BAR INSIDE CARD -->
        <div class="catalog-bar">
          <h2>Category Catalog</h2>
          <div class="search-box">
             <span class="search-icon">🔍</span>
             <input type="text" id="searchInput" onkeyup="filterCategories()" placeholder="Search category...">
          </div>
        </div>

        <?php if (!empty($message)): ?>
          <div class="alert-banner alert-<?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
          </div>
        <?php endif; ?>

        <!-- TABLE CATALOG INSIDE CARD -->
        <div class="table-container">
          <table id="categoriesTable">
            <thead>
              <tr>
                <th style="width: 100px;">Image</th>
                <th>Name & Description</th>
                <th style="width: 140px; text-align: center;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($categories)): ?>
                <tr>
                  <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 30px;">
                    No categories found. Click "+ Add New Category" to create one.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                  <tr>
                    <td>
                      <?php if (!empty($cat['categoryImage']) && file_exists($cat['categoryImage'])): ?>
                        <img src="<?= htmlspecialchars($cat['categoryImage']) ?>" class="cat-thumb" alt="Category Image" />
                      <?php else: ?>
                        <div class="cat-thumb" style="display:flex; align-items:center; justify-content:center; font-size:10px; color:#aaa;">No Image</div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="cat-name"><?= htmlspecialchars($cat['categoryName']) ?></div>
                      <div class="cat-desc">
                        <?= !empty($cat['description']) ? htmlspecialchars($cat['description']) : '<em>No description available.</em>' ?>
                      </div>
                    </td>
                    <td style="text-align: center;">
                      <button type="button" class="action-btn btn-edit" 
                              onclick='openEditModal(<?= json_encode($cat) ?>)'>
                        Edit
                      </button>
                      <a href="M_Categories.php?action=delete&id=<?= $cat['id'] ?>" 
                         class="action-btn btn-delete" 
                         onclick="return confirm('Are you sure you want to delete this category?');">
                        Delete
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
    </div>
  </div>

  <!-- ADD CATEGORY MODAL -->
  <div id="addModal" class="modal-overlay">
    <div class="modal-box">
      <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
      <h2 class="modal-title">Add New Category</h2>
      
      <form method="POST" action="M_Categories.php" enctype="multipart/form-data">
        <input type="hidden" name="add_category" value="1" />

        <div class="form-group">
          <label for="add_categoryName">Category Name</label>
          <input type="text" class="form-control" id="add_categoryName" name="categoryName" required placeholder="e.g. Flower Bouquets" />
        </div>

        <div class="form-group">
          <label for="add_description">Description</label>
          <textarea class="form-control" id="add_description" name="description" placeholder="Short description for this category..."></textarea>
        </div>

        <div class="form-group">
          <label for="add_categoryImage">Category Image</label>
          <input type="file" class="form-control" id="add_categoryImage" name="categoryImage" accept="image/*" />
        </div>

        <button type="submit" class="btn-submit">Save Category</button>
      </form>
    </div>
  </div>

  <!-- EDIT CATEGORY MODAL -->
  <div id="editModal" class="modal-overlay">
    <div class="modal-box">
      <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
      <h2 class="modal-title">Edit Category</h2>
      
      <form method="POST" action="M_Categories.php" enctype="multipart/form-data">
        <input type="hidden" name="edit_category" value="1" />
        <input type="hidden" id="edit_cat_id" name="cat_id" />
        <input type="hidden" id="edit_existing_image" name="existing_image" />

        <div class="form-group">
          <label for="edit_categoryName">Category Name</label>
          <input type="text" class="form-control" id="edit_categoryName" name="categoryName" required />
        </div>

        <div class="form-group">
          <label for="edit_description">Description</label>
          <textarea class="form-control" id="edit_description" name="description"></textarea>
        </div>

        <div class="form-group">
          <label>Current Image</label>
          <div id="edit_image_preview" style="margin-bottom: 8px;"></div>
          <label for="edit_categoryImage">Upload New Image (Optional)</label>
          <input type="file" class="form-control" id="edit_categoryImage" name="categoryImage" accept="image/*" />
        </div>

        <button type="submit" class="btn-submit">Update Category</button>
      </form>
    </div>
  </div>

  <script>
    // Mobile navigation toggle
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    menuToggle.addEventListener('click', () => {
      navMenu.classList.toggle('active');
    });

    // Client-side Category Search Filtering
    function filterCategories() {
      const input = document.getElementById('searchInput');
      const filter = input.value.toLowerCase();
      const table = document.getElementById('categoriesTable');
      const tr = table.getElementsByTagName('tr');

      for (let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td')[1]; // Name & Description column
        if (td) {
          const textValue = td.textContent || td.innerText;
          if (textValue.toLowerCase().indexOf(filter) > -1) {
            tr[i].style.display = "";
          } else {
            tr[i].style.display = "none";
          }
        }
      }
    }

    function openAddModal() {
      document.getElementById('addModal').style.display = 'flex';
    }

    function openEditModal(cat) {
      document.getElementById('edit_cat_id').value = cat.id;
      document.getElementById('edit_categoryName').value = cat.categoryName;
      document.getElementById('edit_description').value = cat.description || '';
      document.getElementById('edit_existing_image').value = cat.categoryImage || '';

      const previewDiv = document.getElementById('edit_image_preview');
      if (cat.categoryImage) {
        previewDiv.innerHTML = `<img src="${cat.categoryImage}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #e8c3b0;">`;
      } else {
        previewDiv.innerHTML = `<span style="font-size: 12px; color: #888;">No image uploaded yet</span>`;
      }

      document.getElementById('editModal').style.display = 'flex';
    }

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
    }

    // Close modals when clicking outside
    window.onclick = function(e) {
      if (e.target.classList.contains('modal-overlay')) {
        e.target.style.display = 'none';
      }
    }
  </script>
</body>
</html>