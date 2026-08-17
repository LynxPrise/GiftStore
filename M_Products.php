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
$messageType = '';

// Ensure upload directory exists
$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle Product Upload (Create)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $productName = trim($_POST['productName'] ?? '');
    $productDescription = !empty(trim($_POST['productDescription'])) ? trim($_POST['productDescription']) : null;
    $productPrice = (float)($_POST['productPrice'] ?? 0);
    $productStock = (int)($_POST['productStock'] ?? 0);
    $categoryId = (int)($_POST['categoryId'] ?? 0);
    
    $imagePath = null;

    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['productImage']['tmp_name'];
        $fileName = $_FILES['productImage']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $imagePath = $destPath;
            } else {
                $message = "Error moving uploaded file.";
                $messageType = "error";
            }
        } else {
            $message = "Invalid image type. Allowed: JPG, JPEG, PNG, WEBP.";
            $messageType = "error";
        }
    }

    if ($productName && $productPrice > 0 && $categoryId > 0 && !$message) {
        $stmt = $pdo->prepare("INSERT INTO products (productName, productDescription, productPrice, productStock, categoryId, productImage) VALUES (:name, :desc, :price, :stock, :catId, :img)");
        $executed = $stmt->execute([
            ':name'  => $productName,
            ':desc'  => $productDescription,
            ':price' => $productPrice,
            ':stock' => $productStock,
            ':catId' => $categoryId,
            ':img'   => $imagePath
        ]);

        if ($executed) {
            $message = "Product added successfully!";
            $messageType = "success";
        } else {
            $message = "Database error: Could not add product.";
            $messageType = "error";
        }
    }
}

// Handle Product Update (Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_product') {
    $pId = (int)$_POST['productId'];
    $productName = trim($_POST['productName'] ?? '');
    $productDescription = !empty(trim($_POST['productDescription'])) ? trim($_POST['productDescription']) : null;
    $productPrice = (float)($_POST['productPrice'] ?? 0);
    $productStock = (int)($_POST['productStock'] ?? 0);
    $categoryId = (int)($_POST['categoryId'] ?? 0);

    // Fetch existing image path
    $imgStmt = $pdo->prepare("SELECT productImage FROM products WHERE productId = :id");
    $imgStmt->execute([':id' => $pId]);
    $existingProduct = $imgStmt->fetch();
    $imagePath = $existingProduct['productImage'] ?? null;

    // Handle new image upload if provided
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['productImage']['tmp_name'];
        $fileName = $_FILES['productImage']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Delete old image if exists
                if ($imagePath && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $imagePath = $destPath;
            }
        }
    }

    if ($pId > 0 && $productName && $productPrice > 0 && $categoryId > 0) {
        $stmt = $pdo->prepare("UPDATE products SET productName = :name, productDescription = :desc, productPrice = :price, productStock = :stock, categoryId = :catId, productImage = :img WHERE productId = :id");
        $executed = $stmt->execute([
            ':name'  => $productName,
            ':desc'  => $productDescription,
            ':price' => $productPrice,
            ':stock' => $productStock,
            ':catId' => $categoryId,
            ':img'   => $imagePath,
            ':id'    => $pId
        ]);

        if ($executed) {
            $message = "Product updated successfully!";
            $messageType = "success";
        } else {
            $message = "Database error: Could not update product.";
            $messageType = "error";
        }
    }
}

// Handle Product Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_product') {
    $pId = (int)$_POST['productId'];
    
    $imgStmt = $pdo->prepare("SELECT productImage FROM products WHERE productId = :id");
    $imgStmt->execute([':id' => $pId]);
    $prod = $imgStmt->fetch();
    if ($prod && $prod['productImage'] && file_exists($prod['productImage'])) {
        unlink($prod['productImage']);
    }

    $delStmt = $pdo->prepare("DELETE FROM products WHERE productId = :id");
    if ($delStmt->execute([':id' => $pId])) {
        $message = "Product deleted successfully.";
        $messageType = "success";
    }
}

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY categoryName ASC")->fetchAll();

// Fetch products
$sql = "SELECT p.*, c.categoryName 
        FROM products p 
        LEFT JOIN categories c ON p.categoryId = c.id 
        ORDER BY p.productId DESC";
$products = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - LynxPrise</title>
    <link rel="stylesheet" href="Assets/Css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sacramento&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-soft-pink: #fdeee8;
            --accent-pink: #d9658b;
            --accent-pink-hover: #c45075;
            --text-dark: #3b2219;
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
            line-height: 1.5;
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

        .lp-nav-links a:hover {
            color: var(--accent-pink);
        }

        /* Updated Pink Logout Button */
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

        /* Container Card Layout */
        .container { 
            max-width: 1100px; 
            margin: 20px auto; 
            padding: 20px; 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        h2, h3 { color: #d81b60; margin: 0; }
        
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }

        /* Product Form Inside Modal */
        .product-form { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 15px; 
            background: #fff; 
            padding: 10px 0 0 0; 
        }

        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { font-weight: 600; margin-bottom: 5px; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 6px; 
            font-size: 14px; 
            font-family: inherit; 
            width: 100%;
        }

        .btn-add-trigger {
            background: var(--accent-pink);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--radius-btn);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-add-trigger:hover {
            background: var(--accent-pink-hover);
        }

        .btn-submit { 
            background: var(--accent-pink); 
            color: white; 
            border: none; 
            padding: 12px 20px; 
            border-radius: 6px; 
            font-size: 15px; 
            font-weight: 600; 
            cursor: pointer; 
            grid-column: span 2; 
            transition: background-color 0.2s;
        }

        .btn-submit:hover {
            background: var(--accent-pink-hover);
        }

        /* Catalog Header & Search Input */
        .catalog-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .search-box { width: 300px; padding: 10px 14px; border: 1px solid #ccc; border-radius: 20px; font-size: 14px; outline: none; transition: border-color 0.2s; }
        .search-box:focus { border-color: var(--accent-pink); box-shadow: 0 0 5px rgba(217, 101, 139, 0.3); }

        /* Responsive Table Wrapper */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        table { width: 100%; min-width: 650px; border-collapse: collapse; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; vertical-align: middle; font-size: 14px; }
        th { background: #f8f8f8; color: #d81b60; white-space: nowrap; }
        .product-img-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; }
        
        /* Action Buttons */
        .action-btns { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-edit { background-color: #0288d1; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 13px; text-decoration: none; }
        .btn-edit:hover { background-color: #01579b; }
        .btn-delete { background-color: #f44336; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 13px; }
        .btn-delete:hover { background-color: #d32f2f; }
        .optional-tag { font-size: 12px; color: #888; font-weight: normal; }

        /* Modal Layout */
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.5); 
            justify-content: center; 
            align-items: center; 
            padding: 15px;
        }

        .modal-content { 
            background-color: #fff; 
            padding: 25px; 
            border-radius: 10px; 
            width: 100%; 
            max-width: 600px; 
            position: relative; 
            max-height: 90vh; 
            overflow-y: auto; 
        }

        .close-btn { position: absolute; top: 15px; right: 20px; font-size: 22px; font-weight: bold; cursor: pointer; color: #aaa; }
        .close-btn:hover { color: #000; }

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
                padding: 15px;
            }

            .product-form {
                grid-template-columns: 1fr;
            }

            .form-group.full-width, .btn-submit {
                grid-column: span 1;
            }

            .search-box {
                width: 100%;
            }

            .modal-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

    <!-- <nav class="lp-nav">
        <a href="#" class="lp-logo">Lynx<span>Prise</span></a>
        <button class="nav-toggle" onclick="toggleNav()" aria-label="Toggle Navigation">☰</button>
        <div class="lp-nav-menu" id="navMenu">
            <ul class="lp-nav-links">
                <li><a href="M_Dashboard.php">Orders</a></li>
                <li><a href="M_Products.php">Products</a></li>
                <li><a href="M_Categories.php">Categories</a></li>
                 <li><a href="M_Feedbacks.php">Feedbacks</a></li>
                <li><a href="M_Settings.php">Settings</a></li>
                <li><a href="index.php">Back To Home</a></li>
            </ul>
            <a href="U_Logout.php" class="btn-logout">Logout</a>
        </div>
    </nav> -->




<main class="main-workspace">

    <div class="container">
        <div class="header-actions">
            <h2>Manage Products</h2>
            <button class="btn-add-trigger" onclick="openAddModal()">+ Add New Product</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="catalog-header">
            <h3>Product Catalog</h3>
            <input type="text" id="searchInput" class="search-box" placeholder="🔍 Search product or category..." onkeyup="filterProducts()">
        </div>

        <div class="table-responsive">
            <table id="productTable">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name & Description</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr id="noProductsRow"><td colspan="6" style="text-align:center;">No products found in database.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr class="product-row">
                                <td>
                                    <?php if ($p['productImage'] && file_exists($p['productImage'])): ?>
                                        <img src="<?php echo htmlspecialchars($p['productImage']); ?>" class="product-img-thumb" alt="Product">
                                    <?php else: ?>
                                        <span style="color:#aaa;">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="searchable-name"><?php echo htmlspecialchars($p['productName']); ?></strong>
                                    <?php if (!empty($p['productDescription'])): ?>
                                        <br><small class="searchable-desc" style="color: #666;"><?php echo nl2br(htmlspecialchars($p['productDescription'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="searchable-cat"><?php echo htmlspecialchars($p['categoryName'] ?? 'Uncategorized'); ?></td>
                                <td>₱<?php echo number_format((float)$p['productPrice'], 2); ?></td>
                                <td><?php echo (int)$p['productStock']; ?></td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-edit" onclick='openEditModal(<?php echo json_encode($p); ?>)'>Edit</button>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');" style="margin:0;">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="productId" value="<?php echo $p['productId']; ?>">
                                            <button type="submit" class="btn-delete">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="noResultsRow" style="display: none;"><td colspan="6" style="text-align:center; color:#888;">No matching products found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Adding Product -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeAddModal()">&times;</span>
            <h3>Add New Product</h3>
            <form method="POST" enctype="multipart/form-data" class="product-form">
                <input type="hidden" name="action" value="add_product">
                
                <div class="form-group">
                    <label for="productName">Product Name</label>
                    <input type="text" id="productName" name="productName" required placeholder="e.g. Red Rose Bouquet">
                </div>

                <div class="form-group">
                    <label for="categoryId">Category</label>
                    <select id="categoryId" name="categoryId" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['categoryName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="productDescription">Product Description <span class="optional-tag">(Optional)</span></label>
                    <textarea id="productDescription" name="productDescription" rows="3" placeholder="Write a short description about this product..."></textarea>
                </div>

                <div class="form-group">
                    <label for="productPrice">Price (₱)</label>
                    <input type="number" step="0.01" id="productPrice" name="productPrice" required placeholder="0.00">
                </div>

                <div class="form-group">
                    <label for="productStock">Stock Quantity</label>
                    <input type="number" id="productStock" name="productStock" value="10" required>
                </div>

                <div class="form-group full-width">
                    <label for="productImage">Product Image</label>
                    <input type="file" id="productImage" name="productImage" accept="image/*" required>
                </div>

                <button type="submit" class="btn-submit">Upload Product</button>
            </form>
        </div>
    </div>

    <!-- Modal for Editing Product -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeEditModal()">&times;</span>
            <h3>Edit Product</h3>
            <form method="POST" enctype="multipart/form-data" class="product-form">
                <input type="hidden" name="action" value="edit_product">
                <input type="hidden" id="edit_productId" name="productId">

                <div class="form-group">
                    <label for="edit_productName">Product Name</label>
                    <input type="text" id="edit_productName" name="productName" required>
                </div>

                <div class="form-group">
                    <label for="edit_categoryId">Category</label>
                    <select id="edit_categoryId" name="categoryId" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['categoryName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="edit_productDescription">Product Description <span class="optional-tag">(Optional)</span></label>
                    <textarea id="edit_productDescription" name="productDescription" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_productPrice">Price (₱)</label>
                    <input type="number" step="0.01" id="edit_productPrice" name="productPrice" required>
                </div>

                <div class="form-group">
                    <label for="edit_productStock">Stock Quantity</label>
                    <input type="number" id="edit_productStock" name="productStock" required>
                </div>

                <div class="form-group full-width">
                    <label for="edit_productImage">Change Image <span class="optional-tag">(Leave empty to keep current image)</span></label>
                    <input type="file" id="edit_productImage" name="productImage" accept="image/*">
                </div>

                <button type="submit" class="btn-submit">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        // Toggle Nav Menu on Small Screens
        function toggleNav() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.toggle('active');
        }

        // Real-time Product Search Filter
        function filterProducts() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const rows = document.getElementsByClassName('product-row');
            const noResultsRow = document.getElementById('noResultsRow');
            let visibleCount = 0;

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent || row.innerText;

                if (text.toLowerCase().indexOf(filter) > -1) {
                    row.style.display = "";
                    visibleCount++;
                } else {
                    row.style.display = "none";
                }
            }

            if (noResultsRow) {
                noResultsRow.style.display = (visibleCount === 0 && rows.length > 0) ? "" : "none";
            }
        }

        // Add Modal Functions
        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        // Edit Modal Functions
        function openEditModal(product) {
            document.getElementById('edit_productId').value = product.productId;
            document.getElementById('edit_productName').value = product.productName;
            document.getElementById('edit_categoryId').value = product.categoryId;
            document.getElementById('edit_productDescription').value = product.productDescription || '';
            document.getElementById('edit_productPrice').value = product.productPrice;
            document.getElementById('edit_productStock').value = product.productStock;
            
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modals when clicking outside modal contents
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            if (event.target === addModal) {
                closeAddModal();
            }
            if (event.target === editModal) {
                closeEditModal();
            }
        }
    </script>
    </main>
</body>
</html>