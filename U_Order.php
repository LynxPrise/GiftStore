<?php
// Start the session at the beginning of the script
session_start();

// Include the database connection file
include 'U_db.php';  // Make sure U_db.php is included for database connection
$success = false; // Initialize success flag

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $full_name = $_POST['full_name'];
    $phone_number = $_POST['phone_number']; 
    $product_name = $_POST['product_name'];
    $price = $_POST['price'];
    $comment = $_POST['comment'];
    $quantity = $_POST['quantity'];
    $date_of_pickup = $_POST['date_of_pickup'];

    // Prepare SQL query to insert order data into the 'orders' table
    $sql = "INSERT INTO orders (full_name, phone_number, product_name, price, comment, quantity, date_of_pickup) 
            VALUES (:full_name, :phone_number, :product_name, :price, :comment, :quantity,:date_of_pickup)";

    // Prepare the statement
    $stmt = $pdo->prepare($sql);

    // Execute the query with form data
    if ($stmt->execute([
        ':full_name' => $full_name,
        ':phone_number' => $phone_number,
        ':product_name' => $product_name,
        ':price' => $price,
        ':comment' => $comment,
        ':quantity' => $quantity,
        ':date_of_pickup' => $date_of_pickup,
    ])) {
        $success = true;  // Set the success flag to true if insert is successful
    } else {
        // Output error if the query fails
        print_r($stmt->errorInfo());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Form</title>
    <link rel="stylesheet" href="Assets/Css/style.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fce4ec;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #d81b60;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: inline-block;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="url"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        textarea {
            resize: vertical;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="url"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            padding-right: 10px;  /* Ensure the padding on the right is the same as the left */
            margin: 10px 0;  /* 10px margin on top and bottom */
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;  /* Ensures padding and border are included in width */
        }

        input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #d81b60;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
        }

        input[type="submit"]:hover {
            background-color: #c2185b;
        }

        /* Style for the pop-up notification */
        #success-notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%); /* Centering the notification */
            background-color: #4caf50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            display: none;
            font-size: 16px;
            z-index: 9999; /* Ensure the notification is on top */
        }

        
        /* Style for the pop-up notification */
        #success-notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #4caf50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            display: none;
            font-size: 16px;
        }
        @media screen and (max-width: 600px) {
            .form-container {
                padding: 15px;
                margin: 20px;
            }

            input[type="submit"] {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

  <!-- Header -->
   <header class="header-section">
    <div class="header-content">
    <div class="logo-section">
      
      <div class="logo-section">
    <img src="Assets/Images/logo.png" alt="Logo" class="logo-icon" />
</div>

      <div class="store-info">
       <h1 id="store-name">LynxPrise Gifts & Souvenirs</h1>
       <p id="tagline"></p>
      </div>
     </div>
     <nav>
      <ul class="nav-menu" id="navMenu">
       <li><a href="#">Home</a></li>
       <li><a href="#">Categories</a></li>
       <li><a href="#">Best Sellers</a></li>
       <li><a href="#">About</a></li>
       <li><a href="#">Contact</a></li>
        <li><a href="U_Login.php">LogIn</a></li>

      </ul><button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
     </nav>
    </div>
   </header>
   
    <div class="form-container">
        <h2>Place Your Order</h2>
        <form method="POST" enctype="multipart/form-data">
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" required>

            <label for="phone_number">Phone Number:</label>
            <input type="text" id="phone_number" name="phone_number" required pattern="^\+?\d{0,13}" title="Phone number should be numeric and may include a country code">

            <label for="product_name">Product Name:</label>
            <input type="text" id="product_name" name="product_name" required>

            <label for="price">Price:</label>
            <input type="number" step="0.01" id="price" name="price" required>

            <label for="quantity">Quantity:</label>
            <input type="number" id="quantity" name="quantity" required>
            
            <label for="date_of_pickup">Date of Pickup/Delivery:</label>
            <input type="date" id="date_of_pickup" name="date_of_pickup" required>

            <label for="comment">Notes:</label>
            <textarea id="comment" name="comment"></textarea>

           

            <input type="submit" value="Submit Order">
        </form>
    </div>
    <!-- Success Notification -->
    <div id="success-notification">Order Successfully Created!</div>

    <script>
        // Check if the success flag is set (from PHP)
        <?php if ($success): ?>
            // Show success notification
            var notification = document.getElementById('success-notification');
            notification.style.display = 'block';

            // Hide the notification after 5 seconds
            setTimeout(function() {
                notification.style.display = 'none';
            }, 5000);
        <?php endif; ?>
    </script>

</body>
</html>
<script>
        // Check if the success flag is set (from PHP session)
        <?php if (isset($_SESSION['order_success']) && $_SESSION['order_success']): ?>
            // Show success notification
            var notification = document.getElementById('success-notification');
            notification.style.display = 'block';

            // Hide the notification after 5 seconds
            setTimeout(function() {
                notification.style.display = 'none';
            }, 5000);

            // Clear the session variable to avoid the popup showing on page reload
            <?php unset($_SESSION['order_success']); ?>
        <?php endif; ?>
    </script>
<script src="Assets/JS/index.js"></script>
