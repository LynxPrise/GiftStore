<?php
 session_start();
 
$success = "";
$error = "";
 // Check if admin is logged in
//  if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//      header('Location: M_Login.php');
//      exit;
//  }
 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
     // Handle product addition via AJAX
     // This part will be handled in the JavaScript section
    $productName = $_POST['name'];
    $productPrice = $_POST['price'];
    $productStock = $_POST['stock'];
    $productCategory = $_POST['category'];

    $stmt = $pdo->prepare("INSERT INTO products(productName, productPrice, productStock, productCategory) VALUES(?, ?, ?, ?)");

            if ($stmt->execute([$productName, $productPrice, $productStock, $productCategory])) {
                $success = "Account created successfully! Redirecting...";
                header("refresh:2; url=Home.php");
            } else {
                $error = "Something went wrong, try again!";
            }
 }

?>

<!doctype html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LynxPrise Management System</title>
  <script src="/_sdk/element_sdk.js"></script>
  <script src="/_sdk/data_sdk.js"></script>
  <style>
    body {
      box-sizing: border-box;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    html, body {
      height: 100%;
      width: 100%;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #333;
      overflow-x: hidden;
    }
    
    .main-wrapper {
      width: 100%;
      min-height: 100%;
      background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%);
    }
    
    /* Header */
    .header-section {
      width: 100%;
      background: rgba(255, 255, 255, 0.95);
      padding: 20px 0;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .header-content {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .logo-section {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .logo-icon {
      width: 50px;
      height: 50px;
    }
    
    .system-info h1 {
      font-size: 28px;
      color: #d81b60;
      margin-bottom: 5px;
    }
    
    .system-info p {
      font-size: 14px;
      color: #666;
    }
    
    /* Main Content */
    .main-content {
      max-width: 1400px;
      margin: 0 auto;
      padding: 30px 20px;
    }
    
    /* Dashboard Stats */
    .dashboard-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background: white;
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .stat-icon {
      font-size: 40px;
      margin-bottom: 10px;
    }
    
    .stat-value {
      font-size: 32px;
      font-weight: 700;
      color: #d81b60;
      margin-bottom: 5px;
    }
    
    .stat-label {
      font-size: 14px;
      color: #666;
    }
    
    /* Tabs */
    .tabs-container {
      background: white;
      border-radius: 15px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }
    
    .tabs-header {
      display: flex;
      border-bottom: 2px solid #f8bbd0;
      overflow-x: auto;
    }
    
    .tab-button {
      padding: 15px 30px;
      background: none;
      border: none;
      font-size: 16px;
      font-weight: 600;
      color: #666;
      cursor: pointer;
      transition: all 0.3s;
      white-space: nowrap;
    }
    
    .tab-button.active {
      color: #d81b60;
      background: #fce4ec;
    }
    
    .tab-button:hover {
      background: #fce4ec;
    }
    
    .tab-content {
      padding: 30px;
      display: none;
    }
    
    .tab-content.active {
      display: block;
    }
    
    /* Forms */
    .form-section {
      background: #f8bbd0;
      padding: 25px;
      border-radius: 10px;
      margin-bottom: 30px;
    }
    
    .form-section h3 {
      color: #c2185b;
      margin-bottom: 20px;
      font-size: 20px;
    }
    
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }
    
    .form-group label {
      font-weight: 600;
      font-size: 14px;
      color: #333;
    }
    
    .form-group input,
    .form-group select {
      padding: 10px;
      border: 2px solid #fff;
      border-radius: 8px;
      font-size: 14px;
      font-family: inherit;
    }
    
    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #d81b60;
    }
    
    .btn {
      padding: 12px 30px;
      background: #d81b60;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .btn:hover {
      background: #c2185b;
      transform: translateY(-2px);
    }
    
    .btn:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
    }
    
    .btn-secondary {
      background: #666;
      margin-left: 10px;
    }
    
    .btn-secondary:hover {
      background: #555;
    }
    
    .btn-danger {
      background: #e53935;
      padding: 8px 15px;
      font-size: 14px;
    }
    
    .btn-danger:hover {
      background: #c62828;
    }
    
    /* Table */
    .table-container {
      overflow-x: auto;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
    }
    
    th {
      background: #fce4ec;
      color: #c2185b;
      padding: 15px;
      text-align: left;
      font-weight: 600;
    }
    
    td {
      padding: 15px;
      border-bottom: 1px solid #f5f5f5;
    }
    
    tr:hover {
      background: #fafafa;
    }
    
    .actions {
      display: flex;
      gap: 10px;
    }
    
    .edit-btn, .delete-btn {
      padding: 6px 12px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 13px;
      font-weight: 600;
      transition: all 0.3s;
    }
    
    .edit-btn {
      background: #4caf50;
      color: white;
    }
    
    .edit-btn:hover {
      background: #388e3c;
    }
    
    .delete-btn {
      background: #e53935;
      color: white;
    }
    
    .delete-btn:hover {
      background: #c62828;
    }
    
    .empty-state {
      text-align: center;
      padding: 40px;
      color: #666;
    }
    
    .empty-state-icon {
      font-size: 60px;
      margin-bottom: 15px;
      opacity: 0.5;
    }
    
    /* Notification */
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #c2185b;
      color: white;
      padding: 15px 25px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
      z-index: 10000;
      font-weight: 600;
      display: none;
    }
    
    .notification.show {
      display: block;
      animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    
    /* Loading */
    .loading {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid #f3f3f3;
      border-top: 3px solid #d81b60;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-left: 10px;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
      }
      
      .dashboard-stats {
        grid-template-columns: 1fr;
      }
      
      .tabs-header {
        flex-wrap: wrap;
      }
      
      .tab-button {
        flex: 1;
        min-width: 150px;
      }
    }
  </style>
  <style>@view-transition { navigation: auto; }</style>
  <script src="https://cdn.tailwindcss.com" type="text/javascript"></script>
 </head>
 <body>
  <div class="main-wrapper"><!-- Header -->
   <header class="header-section">
    <div class="header-content">
     <div class="logo-section">
      <svg class="logo-icon" viewbox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="45" fill="#d81b60" /> <path d="M50 25 L35 40 L35 60 L50 75 L65 60 L65 40 Z" fill="#ffffff" /> <circle cx="50" cy="50" r="10" fill="#fce4ec" />
      </svg>
      <div class="system-info">
       <h1 id="system-title">LynxPrise Management</h1>
       <p id="dashboard-subtitle">Admin Dashboard</p>

       
      </div>
      <div style="margin-left: 600px;"> 
        <div class="nav-links" style="display: flex; gap: 20px; font-weight: 600; font-size: 15px;">
        <a href="M_Dashboard.php">Dashboard</a>

        <a href="M_Products.php">Products</a>
      <a href="M_Orders.php">Orders</a>
      <a href="M_Customers.php">Customers</a>

      <a href="U_Logout.php">Logout</a>
      </div>
      </div>
     </div>
    </div>
   </header>
   
   
   <!-- Main Content -->
   <main class="main-content"><!-- Dashboard Stats -->
    
    
    <!-- Tabs -->
    <div class="tabs-container">
     <div class="tabs-header">
     <button class="tab-button " data-tab="products">Products</button>
      
     </div>
     
     
     
     
     
     
     
     
     
     
     
     <!-- Products Tab -->
     <div class="tab-content active" id="products">
      <div class="form-section">

       <h3>Add New Product</h3>
       <form id="product-form" method="POST">
        <div class="form-grid">

         <div class="form-group">
         <label for="product-name">Product Name</label>
          <input type="text" name="name" id="product-name" required>
         </div>

         <div class="form-group">
         <label for="product-price">Price ($)</label> 
         <input type="number" name="price" id="product-price" step="0.01" required>
         </div>

         <div class="form-group">
         <label for="product-stock">Stock</label> 
         <input type="number" name="stock" id="product-stock" required>
         </div>

         <div class="form-group">
         <label for="product-category">Category</label> 
         <select id="product-category" name="category" required> 
         <option value="">Select Category</option> 
         <option value="Gifts">Gifts</option>
          <option value="Flowers">Flowers</option> 
          <option value="Souvenirs">Souvenirs</option> 
          <option value="Balloons">Balloons</option> 
          <option value="Surprises">Surprises</option> </select>
         </div>
        
         </div><button type="submit" class="btn" id="add-product-btn">Add Product</button>
         </form>
      </div>


      <div class="table-container">
       <table id="products-table">
        <thead>
         <tr>
          <th>Name</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Category</th>
          <th>Actions</th>
         </tr>
        </thead>
        <tbody id="products-tbody">
         <tr>
          <td colspan="5" class="empty-state">
           <div class="empty-state-icon">
            📦
           </div><p>No products yet. Add your first product above!</p></td>
         </tr>
        </tbody>
       </table>
      </div>
     </div>
     
     
     
     
     
     
     
     

   <div class="notification" id="notification"></div>
  </div>



  </body>
  </html>