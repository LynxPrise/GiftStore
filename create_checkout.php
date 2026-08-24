<?php
session_start();
require_once 'U_db.php';

// Set header to return JSON response for AJAX checkout calls
header('Content-Type: application/json');

// --- PayMongo keys ---
define('PAYMONGO_SECRET_KEY', $_ENV['PAYMONGO_SECRET_KEY'] ?? getenv('PAYMONGO_SECRET_KEY'));
define('PAYMONGO_PUBLIC_KEY', $_ENV['PAYMONGO_PUBLIC_KEY'] ?? getenv('PAYMONGO_PUBLIC_KEY'));
define('PAYMONGO_WEBHOOK_SECRET', $_ENV['PAYMONGO_WEBHOOK_SECRET'] ?? getenv('PAYMONGO_WEBHOOK_SECRET'));




// Allow GET redirects from U_OrderPage.php (after order insert) to initiate PayMongo flow
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['order_id'])) {
    $orderId = intval($_GET['order_id']);
    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order id.']);
        exit;
    }

    // Fetch order details from DB to build checkout payload
    $stmt = $pdo->prepare("SELECT product_name, price, quantity, full_name, phone_number, email FROM orders WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

  

    $productName = $order['product_name'] ?? 'Order #' . $orderId;
    $email       = !empty($order['email']) ? $order['email'] : 'lanelynmacasait@gmail.com';
    $quantity    = intval($order['quantity'] ?? 1);
    $price       = floatval($order['price'] ?? 0);

    $paymongoSecretKey = defined('PAYMONGO_SECRET_KEY') ? PAYMONGO_SECRET_KEY : '';
    if (empty($paymongoSecretKey)) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<h2>PayMongo not configured</h2><p>Please set your PayMongo secret key in create_checkout.php to enable online payments.</p>';
        exit;
    }

    $totalAmountCents = intval(($price * $quantity) * 100);

    if ($totalAmountCents < 1000) {
        echo json_encode(['success' => false, 'message' => 'Total order amount must be at least PHP 10.00 for online checkout.']);
        exit;
    }

    $payload = [
        'data' => [
            'attributes' => [
                'payment_method_types' => ['qrph', 'gcash', 'paymaya'], // Added 'qrph' as required
                'line_items' => [
                    [
                        'currency'    => 'PHP',
                        'amount'      => $totalAmountCents,
                        'description' => $productName,
                        'name'        => $productName,
                        'quantity'    => $quantity,
                    ]
                ],
                'success_url' => 'http://localhost/Lynxprise/U_ThankYou.php?order_id=' . $orderId . '&payment=success',
'cancel_url'  => 'http://localhost/Lynxprise/U_PaymentFailed.php?order_id=' . $orderId . '&error=Payment+was+cancelled+or+declined',
                'description'   => "LynxPrise Order #" . $orderId,
                'customer_email'=> $email
            ]
        ]
    ];

    $ch = curl_init('https://api.paymongo.com/v1/checkout_sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($paymongoSecretKey . ':')
    ]);

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        echo json_encode(['success' => false, 'message' => 'PayMongo Connection Error: ' . $err]);
        exit;
    }

    $responseData = json_decode($response, true);

    if (isset($responseData['data']['attributes']['checkout_url'])) {
        $checkoutUrl = $responseData['data']['attributes']['checkout_url'];
        header('Location: ' . $checkoutUrl);
        exit;
    }

    echo json_encode([
        'success'         => false,
        'message'         => 'Failed to initialize online payment session.',
        'http_code'       => $httpCode,
        'raw_response'    => $response,
        'parsed_response' => $responseData
    ]);
    exit;
}

try {
    // 1. CAPTURE AND SANITIZE FORM INPUTS
    $userId        = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    $productId     = isset($_POST['products_id']) ? intval($_POST['products_id']) : null;
    $fullName      = trim($_POST['full_name'] ?? '');
    // Default to your Gmail if form input is missing
    $email         = !empty($_POST['email']) ? trim($_POST['email']) : 'lanelynmacasait@gmail.com';
    $phoneNumber   = trim($_POST['phone_number'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $latitude      = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude     = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    $productName   = trim($_POST['product_name'] ?? '');
    $price         = floatval($_POST['price'] ?? 0);
    $quantity      = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $modeOfTranspo = isset($_POST['mode_of_transpo']) ? intval($_POST['mode_of_transpo']) : 0;
    $modeOfPayment = isset($_POST['mode_of_payment']) ? intval($_POST['mode_of_payment']) : 0; // 0 = COD, 1 = Online
    $dateOfPickup  = !empty($_POST['date_of_pickup']) ? $_POST['date_of_pickup'] : null;
    $facebookLink  = trim($_POST['facebook_link'] ?? '');
    $productImage  = trim($_POST['product_image'] ?? '');
    $cardMessage   = trim($_POST['card_message'] ?? '');

   

    if (empty($fullName) || empty($productName) || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields with valid values.']);
    exit;
}

    $paymentStatus = ($modeOfPayment === 0) ? 'UNPAID' : 'PENDING';

    // 2. INSERT ORDER INTO DATABASE
    $sql = "INSERT INTO orders (
                user_id, products_id, full_name, email, phone_number, 
                address, latitude, longitude, product_name, price, 
                quantity, mode_of_payment, mode_of_transpo, date_of_pickup, 
                facebook_link, product_image, status, card_message, payment_status
            ) VALUES (
                :user_id, :products_id, :full_name, :email, :phone_number, 
                :address, :latitude, :longitude, :product_name, :price, 
                :quantity, :mode_of_payment, :mode_of_transpo, :date_of_pickup, 
                :facebook_link, :product_image, 'pending', :card_message, :payment_status
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id'         => $userId,
        ':products_id'     => $productId,
        ':full_name'       => $fullName,
        ':email'           => $email,
        ':phone_number'    => $phoneNumber,
        ':address'         => $address,
        ':latitude'        => $latitude,
        ':longitude'       => $longitude,
        ':product_name'    => $productName,
        ':price'           => $price,
        ':quantity'        => $quantity,
        ':mode_of_payment' => $modeOfPayment,
        ':mode_of_transpo' => $modeOfTranspo,
        ':date_of_pickup'  => $dateOfPickup,
        ':facebook_link'   => $facebookLink,
        ':product_image'   => $productImage,
        ':card_message'    => $cardMessage,
        ':payment_status'  => $paymentStatus
    ]);

    $orderId = $pdo->lastInsertId();

    // 3. ROUTE BASED ON PAYMENT METHOD
    if ($modeOfPayment === 0) {
        // --- CASH ON DELIVERY (COD) ---
        echo json_encode([
            'success'      => true,
            'redirect_url' => "U_ThankYou.php?order_id=" . $orderId
        ]);
        exit;

    } else {
        // --- PAYMONGO / ONLINE PAYMENT ---
        $paymongoSecretKey = defined('PAYMONGO_SECRET_KEY') ? PAYMONGO_SECRET_KEY : '';
        $totalAmountCents  = intval(($price * $quantity) * 100);

        if ($totalAmountCents < 1000) {
            echo json_encode(['success' => false, 'message' => 'Total order amount must be at least PHP 10.00 for online payment.']);
            exit;
        }

        $payload = [
            'data' => [
                'attributes' => [
                    'payment_method_types' => ['qrph', 'gcash', 'paymaya'], // Added 'qrph' as required
                    'line_items' => [
                        [
                            'currency'    => 'PHP',
                            'amount'      => $totalAmountCents,
                            'description' => $productName,
                            'name'        => $productName,
                            'quantity'    => $quantity,
                        ]
                    ],
                    'success_url'   => "http://" . $_SERVER['HTTP_HOST'] . "/U_ThankYou.php?order_id=" . $orderId . "&payment=success",
                    'cancel_url'    => "http://" . $_SERVER['HTTP_HOST'] . "/U_PaymentFailed.php?order_id=" . $orderId . "&error=Payment+was+cancelled+or+declined",
                    'description'   => "LynxPrise Order #" . $orderId,
                    'customer_email'=> $email

//                     'success_url'   => "http://" . $_SERVER['HTTP_HOST'] . "/Lynxprise/U_ThankYou.php?order_id=" . $orderId . "&payment=success",
// 'cancel_url'    => "http://" . $_SERVER['HTTP_HOST'] . "/Lynxprise/U_PaymentFailed.php?order_id=" . $orderId . "&error=Payment+was+cancelled+or+declined",
// 'description'   => "LynxPrise Order #" . $orderId,
// 'customer_email'=> $email

                ]
            ]
        ];

        $ch = curl_init('https://api.paymongo.com/v1/checkout_sessions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($paymongoSecretKey . ':')
        ]);

        $response = curl_exec($ch);
        $err      = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            echo json_encode(['success' => false, 'message' => 'PayMongo Connection Error: ' . $err]);
            exit;
        }

        $responseData = json_decode($response, true);

        if (isset($responseData['data']['attributes']['checkout_url'])) {
            $checkoutUrl = $responseData['data']['attributes']['checkout_url'];
            echo json_encode([
                'success'      => true,
                'redirect_url' => $checkoutUrl
            ]);
            exit;
        } else {
            echo json_encode([
                'success'         => false,
                'message'         => 'Failed to initialize online payment session.',
                'http_code'       => $httpCode,
                'raw_response'    => $response,
                'parsed_response' => $responseData
            ]);
            exit;
        }
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    exit;
}