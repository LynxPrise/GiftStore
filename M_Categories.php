<?php
session_start();
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
                // Optionally remove old file if it exists
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
        // Fetch image path to delete file from directory
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

    .admin-container { 
        max-width: 1100px; 
        margin: 0 auto; 
        padding: 0 16px 40px 16px;
    }

    .header-bar { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 24px; 
        margin-top: 24px; 
        flex-wrap: wrap;
        gap: 16px;
    }

    .header-bar h1 { 
        font-family: 'Playfair Display', serif; 
        font-size: 32px; 
        color: var(--text-dark); 
    }

    .btn-add-modal { 
        background: var(--accent-pink); 
        color: #fff; 
        border: none; 
        padding: 12px 22px; 
        border-radius: 30px; 
        font-weight: 600; 
        cursor: pointer; 
        text-decoration: none; 
        display: inline-block; 
        text-align: center;
        transition: background-color 0.2s ease;
    }
    .btn-add-modal:hover { background: var(--accent-pink-hover); }

    /* Alert Banner */
    .alert-banner { padding: 14px 18px; border-radius: var(--radius-md); font-size: 14px; font-weight: 600; margin-bottom: 20px; }
    .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
    .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }

    /* Table Container & Responsiveness */
    .table-card { 
        background: var(--card-bg); 
        border: 1px solid var(--gold-border); 
        border-radius: var(--radius-lg); 
        overflow-x: auto; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.03); 
        -webkit-overflow-scrolling: touch;
    }

    table { 
        width: 100%; 
        border-collapse: collapse; 
        text-align: left; 
        min-width: 600px;
    }

    th, td { padding: 16px 20px; border-bottom: 1px solid #f2e3dc; vertical-align: middle; }
    th { background: var(--bg-cream); font-weight: 700; color: var(--text-dark); font-size: 14px; white-space: nowrap; }
    tr:last-child td { border-bottom: none; }

    .cat-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid var(--gold-border); background: #f8f8f8; }
    
    .action-btn { 
        padding: 6px 14px; 
        border-radius: 20px; 
        font-size: 12px; 
        font-weight: 600; 
        text-decoration: none; 
        display: inline-block; 
        margin: 2px 3px; 
        cursor: pointer; 
        border: none; 
        white-space: nowrap;
    }
    .btn-edit { background: #e3f2fd; color: #1565c0; }
    .btn-delete { background: #ffebee; color: #c62828; }

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
        background: var(--bg-cream); 
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
    .modal-title { font-family: 'Playfair Display', serif; font-size: 22px; margin-bottom: 20px; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--gold-border); border-radius: var(--radius-md); font-family: inherit; font-size: 14px; outline: none; background: #fff; }
    textarea.form-control { resize: vertical; min-height: 80px; }

    .btn-submit { width: 100%; background: var(--accent-pink); color: #fff; border: none; padding: 12px; border-radius: 25px; font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 10px; }
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

        .header-bar h1 {
            font-size: 26px;
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
                <li><a href="index.php">Back To Home</a></li>
            </ul>
            <a href="U_Logout.php" class="btn-nav">Logout</a>
        </div>
    </nav>

  <div class="admin-container">
    <div class="header-bar">
      <h1>Manage Categories</h1>
      <button class="btn-add-modal" onclick="openAddModal()">+ Add New Category</button>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert-banner alert-<?= $message_type ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Category Name</th>
            <th>Description</th>
            <th style="width: 160px; text-align: center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($categories)): ?>
            <tr>
              <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">
                No categories found. Click "+ Add New Category" to create one.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td><strong>#<?= $cat['id'] ?></strong></td>
                <td>
                  <?php if (!empty($cat['categoryImage']) && file_exists($cat['categoryImage'])): ?>
                    <img src="<?= htmlspecialchars($cat['categoryImage']) ?>" class="cat-thumb" alt="Category Image" />
                  <?php else: ?>
                    <div class="cat-thumb" style="display:flex; align-items:center; justify-content:center; font-size:10px; color:#aaa;">No Image</div>
                  <?php endif; ?>
                </td>
                <td><strong><?= htmlspecialchars($cat['categoryName']) ?></strong></td>
                <td>
                  <span style="font-size: 13px; color: var(--text-muted);">
                    <?= !empty($cat['description']) ? htmlspecialchars(substr($cat['description'], 0, 90)) . (strlen($cat['description']) > 90 ? '...' : '') : '<em>No description</em>' ?>
                  </span>
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