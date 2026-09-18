<?php
session_start();
require_once 'U_db.php';

$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT) ?: 0;
$order = null;
$error = '';

if ($orderId > 0) {
    try {
        $stmt = $pdo->prepare('SELECT id, full_name, product_name, price, status, payment_status, date_of_pickup FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) $error = 'We could not find an order with that number.';
    } catch (PDOException $e) {
        $error = 'Tracking is temporarily unavailable. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Track Order | LynxPrise</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sacramento&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .lp-logo { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; text-decoration: none; color: #3b2219; }
    .lp-logo span { font-family: 'Sacramento', cursive; color: #d9658b; font-size: 32px; margin-left: 2px; }
    .lp-nav-links { display: flex; gap: 30px; list-style: none; }
    .lp-nav-links a { text-decoration: none; color: #3b2219; font-weight: 500; font-size: 15px; transition: color 0.2s; }
    .lp-nav-links a:hover { color: #d9658b; }
    .btn-nav { background-color: #d9658b; color: #fff; padding: 10px 24px; border-radius: 9999px; text-decoration: none; font-weight: 600; font-size: 14px; transition: background-color 0.2s; }
    .btn-nav:hover { background-color: #c45075; }
  </style>
</head>
<body class="min-h-screen bg-[#fdeee8] font-['Plus_Jakarta_Sans'] text-[#3b2219]">

  <!-- Full-width Sticky Header -->
  <nav class="sticky top-0 z-[1000] flex w-full items-center justify-between bg-[#fff9f6]/95 px-6 py-4 shadow-sm backdrop-blur md:px-12">
    <a href="index" class="lp-logo">Lynx<span>Prise</span></a>
    
    <ul class="lp-nav-links hidden md:flex">
      <li><a href="index#categories">Categories</a></li>
      <li><a href="index#testimonials">Feedbacks</a></li>
      <li><a href="u_order">Order</a></li>
      <li><a href="index#how-it-works">How it works</a></li>
    </ul>

    <div>
      <a href="u_order" class="btn-nav">Order Items</a>
    </div>
  </nav>

  <!-- Main Content Area -->
  <main class="mx-auto max-w-xl px-4 py-8">
    <section class="rounded-3xl border border-[#e8c3b0] bg-[#fff9f6] p-6 shadow-xl sm:p-9">
      <h1 class="font-['Playfair_Display'] text-4xl">Track Your Order</h1>
      <p class="mt-2 text-sm text-[#785a50]">Enter the order number from your confirmation page.</p>
      
      <form class="mt-6 flex gap-2" method="get">
        <input class="min-w-0 flex-1 rounded-2xl border border-[#e8c3b0] bg-white px-4 py-3 outline-none focus:border-[#d9658b]" type="number" name="order_id" min="1" value="<?= htmlspecialchars($orderId ?: '') ?>" placeholder="Order number" required>
        <button class="rounded-2xl bg-[#d9658b] px-5 py-3 font-semibold text-white transition hover:bg-[#c45075]" type="submit">Track</button>
      </form>

      <?php if ($error): ?>
        <p class="mt-5 rounded-2xl bg-red-50 p-4 text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <?php if ($order): ?>
        <div class="mt-6 space-y-4 rounded-2xl border border-[#e8c3b0] bg-white p-5">
          <div class="flex items-center justify-between"><span class="text-sm text-[#785a50]">Order #</span><strong><?= htmlspecialchars($order['id']) ?></strong></div>
          <div class="flex items-center justify-between"><span class="text-sm text-[#785a50]">Customer</span><strong><?= htmlspecialchars($order['full_name']) ?></strong></div>
          <div><span class="text-sm text-[#785a50]">Items</span><p class="mt-1 font-semibold"><?= htmlspecialchars($order['product_name']) ?></p></div>
          <div class="flex items-center justify-between"><span class="text-sm text-[#785a50]">Total</span><strong class="text-[#d9658b]">₱<?= number_format((float)$order['price'], 2) ?></strong></div>
          <div class="flex items-center justify-between border-t border-[#fdeee8] pt-4"><span class="text-sm text-[#785a50]">Status</span><span class="rounded-full bg-[#fff0f3] px-3 py-1 text-sm font-bold capitalize text-[#b84357]"><?= htmlspecialchars($order['status'] ?: 'pending') ?></span></div>
          <div class="flex items-center justify-between"><span class="text-sm text-[#785a50]">Payment</span><strong><?= htmlspecialchars($order['payment_status'] ?: 'UNPAID') ?></strong></div>
        </div>
      <?php endif; ?>
    </section>
  </main>

</body>
</html>