<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LynxPrise - Gifts, Balloons, Cakes & Surprises</title>
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
    }

    /* Navigation Bar */
    .lp-nav {
      position: sticky;
      top: 0;
      z-index: 1000;
      background: var(--bg-cream);
      padding: 18px 5%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 10px rgba(59, 34, 25, 0.05);
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

    /* Container Wrapper */
    .lp-wrapper {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Hero Section */
    .lp-hero-section {
      padding: 60px 0 80px;
    }

    .lp-hero-grid {
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap: 40px;
      align-items: center;
    }

    .lp-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.7);
      border: 1px solid var(--gold-border);
      padding: 6px 16px;
      border-radius: 50px;
      font-size: 13px;
      color: var(--gold-accent);
      font-weight: 600;
      margin-bottom: 24px;
    }

    .lp-hero-title {
      font-family: 'Playfair Display', serif;
      font-size: 52px;
      line-height: 1.15;
      color: var(--text-dark);
      font-weight: 500;
      margin-bottom: 20px;
    }

    .lp-hero-title span {
      font-family: 'Sacramento', cursive;
      color: var(--accent-pink);
      font-size: 68px;
      font-weight: 400;
    }

    .lp-hero-subtitle {
      font-size: 16px;
      color: var(--text-muted);
      margin-bottom: 32px;
      max-width: 500px;
    }

    .lp-hero-actions {
      display: flex;
      gap: 16px;
      margin-bottom: 40px;
      flex-wrap: wrap;
    }

    .btn-hero-primary {
      background-color: var(--accent-pink);
      color: #fff;
      padding: 14px 28px;
      border-radius: var(--radius-btn);
      text-decoration: none;
      font-weight: 600;
      box-shadow: 0 4px 15px rgba(217, 101, 139, 0.25);
      transition: all 0.2s;
    }

    .btn-hero-primary:hover {
      background-color: var(--accent-pink-hover);
      transform: translateY(-1px);
    }

    .btn-hero-secondary {
      background-color: transparent;
      color: var(--text-dark);
      border: 1px solid var(--gold-border);
      padding: 14px 28px;
      border-radius: var(--radius-btn);
      text-decoration: none;
      font-weight: 600;
      transition: background-color 0.2s;
    }

    .btn-hero-secondary:hover {
      background-color: rgba(255, 255, 255, 0.5);
    }

    .lp-hero-trust {
      display: flex;
      flex-direction: column;
      gap: 8px;
      font-size: 13px;
      color: var(--text-muted);
      font-weight: 500;
    }

    .lp-hero-trust-row {
      display: flex;
      gap: 20px;
    }

    .lp-hero-image-card {
      position: relative;
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
    }

    .lp-hero-image-card img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    /* Pain Point / Solution Section */
    .lp-solution-banner {
      background-color: var(--bg-cream);
      padding: 70px 20px;
      text-align: center;
    }

    .lp-solution-content {
      max-width: 780px;
      margin: 0 auto;
    }

    .lp-solution-title {
      font-family: 'Playfair Display', serif;
      font-size: 36px;
      color: var(--text-dark);
      margin-bottom: 24px;
      font-weight: 500;
    }

    .lp-solution-text {
      font-size: 15px;
      color: var(--text-muted);
      line-height: 1.7;
      margin-bottom: 16px;
    }

    .lp-solution-text:last-child {
      margin-bottom: 0;
    }

    /* Core Categories Section */
    .lp-categories-section {
      padding: 80px 0;
      text-align: center;
    }

    .lp-section-subtitle {
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: var(--gold-accent);
      font-weight: 700;
      margin-bottom: 8px;
    }

    .lp-section-title {
      font-family: 'Playfair Display', serif;
      font-size: 38px;
      color: var(--text-dark);
      margin-bottom: 48px;
    }

    .lp-categories-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
    }

    .lp-category-card {
      background: var(--card-bg);
      border-radius: var(--radius-lg);
      padding: 24px;
      text-align: left;
      border: 1px solid rgba(232, 195, 176, 0.4);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      box-shadow: 0 4px 20px rgba(59, 34, 25, 0.02);
      transition: transform 0.2s, box-shadow 0.2s;
      overflow: hidden;
    }

    .lp-category-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 25px rgba(59, 34, 25, 0.05);
    }

    .lp-cat-image-wrap {
      width: 100%;
      height: 180px;
      border-radius: var(--radius-md);
      overflow: hidden;
      margin-bottom: 20px;
    }

    .lp-cat-image-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.3s ease;
    }

    .lp-category-card:hover .lp-cat-image-wrap img {
      transform: scale(1.04);
    }

    .lp-cat-title {
      font-family: 'Playfair Display', serif;
      font-size: 22px;
      color: var(--text-dark);
      margin-bottom: 12px;
    }

    .lp-cat-desc {
      font-size: 14px;
      color: var(--text-muted);
      margin-bottom: 24px;
      line-height: 1.5;
    }

    .lp-cat-tags {
      font-size: 12px;
      color: var(--gold-accent);
      font-weight: 600;
      border-top: 1px solid var(--bg-soft-pink);
      padding-top: 16px;
    }

    /* How It Works Section */
    .lp-how-section {
      background-color: var(--bg-cream);
      padding: 80px 0;
      text-align: center;
    }

    .lp-steps-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
      margin-top: 48px;
      margin-bottom: 48px;
    }

    .lp-step-item {
      padding: 0 10px;
    }

    .lp-step-badge {
      width: 44px;
      height: 44px;
      background-color: var(--accent-pink);
      color: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 16px;
      margin: 0 auto 20px auto;
    }

    .lp-step-title {
      font-family: 'Playfair Display', serif;
      font-size: 20px;
      color: var(--text-dark);
      margin-bottom: 10px;
    }

    .lp-step-desc {
      font-size: 14px;
      color: var(--text-muted);
    }

    /* Testimonials Section */
    .lp-testimonials-section {
      padding: 80px 0;
      text-align: center;
      background-color: var(--bg-soft-pink);
    }

    .lp-testimonials-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      margin-top: 48px;
    }

    .lp-testimonial-card {
      background: var(--card-bg);
      border-radius: var(--radius-lg);
      padding: 32px 28px;
      text-align: left;
      border: 1px solid rgba(232, 195, 176, 0.4);
      box-shadow: 0 4px 20px rgba(59, 34, 25, 0.02);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .lp-stars {
      color: var(--gold-accent);
      font-size: 16px;
      margin-bottom: 16px;
      letter-spacing: 2px;
    }

    .lp-testimonial-quote {
      font-size: 14px;
      color: var(--text-dark);
      line-height: 1.6;
      margin-bottom: 24px;
      font-style: italic;
    }

    .lp-testimonial-author {
      border-top: 1px solid var(--bg-soft-pink);
      padding-top: 16px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .lp-author-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background-color: var(--gold-border);
      color: var(--text-dark);
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
    }

    .lp-author-name {
      font-weight: 700;
      font-size: 14px;
      color: var(--text-dark);
    }

    .lp-author-details {
      font-size: 12px;
      color: var(--text-muted);
    }

    /* Footer Section */
    .lp-footer {
      background-color: var(--gold-border);
      border-top: 1px solid rgba(232, 195, 176, 0.4);
      padding: 50px 0 30px;
      font-size: 13px;
      color: var(--text-muted);
    }

    .lp-footer-grid {
      display: grid;
      grid-template-columns: 1.5fr 1fr 1fr;
      gap: 40px;
      margin-bottom: 40px;
    }

    .lp-footer-col h5 {
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 10px;
      font-size: 14px;
    }

    .lp-footer-bottom {
      text-align: center;
      border-top: 1px solid rgba(139, 136, 135, 0.3);
      padding-top: 24px;
      font-size: 12px;
    }

    /* Responsive Design */
    @media (max-width: 900px) {
      .lp-hero-grid,
      .lp-categories-grid,
      .lp-steps-grid,
      .lp-testimonials-grid,
      .lp-footer-grid {
        grid-template-columns: 1fr;
      }

      .lp-hero-title {
        font-size: 38px;
      }

      .lp-hero-title span {
        font-size: 50px;
      }

      .lp-nav-links {
        display: none;
      }
    }
  </style>
</head>
<body>

  <!-- Navigation Bar -->
  <nav class="lp-nav">
    <a href="#" class="lp-logo">Lynx<span>Prise</span></a>
    <ul class="lp-nav-links">
      <li><a href="#categories">Categories</a></li>
      <li><a href="#how-it-works">How it works</a></li>
      <li><a href="U_OrderPage.php">Order</a></li>
    </ul>
    <a href="U_OrderPage.php" class="btn-nav">Order now</a>
  </nav>

  <main>
    <!-- Hero Section -->
    <div class="lp-wrapper">
      <section class="lp-hero-section">
        <div class="lp-hero-grid">
          <div class="lp-hero-content">
            <div class="lp-badge">
              📍 Eastern Visayas’ Premier Gift & Surprise Specialists
            </div>
            <h1 class="lp-hero-title">
              Unforgettable <span>Surprises</span> & Custom Gifts Crafted for Your Special Moments
            </h1>
            <p class="lp-hero-subtitle">
              From handcrafted floral arrangements and custom balloon setups to delicious cakes and curated gift boxes — we bring your grandest celebration ideas to life.
            </p>
            <div class="lp-hero-actions">
              <a href="#categories" class="btn-hero-primary">Browse Surprise Packages</a>
              <a href="U_OrderPage.php" class="btn-hero-secondary">Customize Your Order</a>
            </div>
            <div class="lp-hero-trust">
              <div class="lp-hero-trust-row">
                <span>⭐ 100+ Happy Celebrations</span>
                <span>🚚 On-Time Local Delivery</span>
              </div>
              <div>
                <span>💳 Easy GCash, Maya & COD</span>
              </div>
            </div>
          </div>
          <div class="lp-hero-image-card">
            <img src="Assets/Images/hero-surprise.jpg" alt="LynxPrise Floral Arrangement and Surprise Box">
          </div>
        </div>
      </section>
    </div>

    <!-- Pain Point & Solution Section -->
    <section class="lp-solution-banner">
      <div class="lp-solution-content">
        <h2 class="lp-solution-title">Planning a Special Surprise Shouldn't Be Stressful</h2>
        <p class="lp-solution-text">
          Trying to coordinate flowers from one shop, balloons from another, and a custom cake from somewhere else is time-consuming and risky. One late delivery can ruin an entire moment.
        </p>
        <p class="lp-solution-text">
          At LynxPrise, we handle everything under one roof. Whether it's a romantic gesture, a milestone birthday, or a grand event surprise, we design, assemble, and deliver a complete, seamless experience straight to your recipient's door.
        </p>
      </div>
    </section>

    <!-- Core Categories Section -->
    <section class="lp-categories-section" id="categories">
      <div class="lp-wrapper">
        <div class="lp-section-subtitle">Choose Your Experience</div>
        <h2 class="lp-section-title">Our Core Categories</h2>
        
        <div class="lp-categories-grid">
          <!-- 1. Flowers -->
          <div class="lp-category-card">
            <div>
              <div class="lp-cat-image-wrap">
                <img src="Assets/Images/category-flowers.jpg" alt="Flowers">
              </div>
              <h3 class="lp-cat-title">Flowers</h3>
              <p class="lp-cat-desc">Fresh, dried, and styled floral arrangements for every occasion — from romantic roses to creative money bouquets.</p>
            </div>
            <div class="lp-cat-tags">Roses, tulips, sunflowers, money bouquets, custom wraps</div>
          </div>

          <!-- 2. Custom Flower Bouquets -->
          <div class="lp-category-card">
            <div>
              <div class="lp-cat-image-wrap">
                <img src="Assets/Images/category-bouquets.jpg" alt="Custom Flower Bouquets">
              </div>
              <h3 class="lp-cat-title">Custom Bouquets</h3>
              <p class="lp-cat-desc">Creative non-floral arrangements featuring cash, treats, coffee, chocolates, or skincare items tailored for any gift occasion.</p>
            </div>
            <div class="lp-cat-tags">Money bouquets, chocolate bouquets, coffee bouquets, candy & skincare bouquets</div>
          </div>

          <!-- 3. Balloon Decor & Setups -->
          <div class="lp-category-card">
            <div>
              <div class="lp-cat-image-wrap">
                <img src="Assets/Images/category-balloons.jpg" alt="Balloon Decor & Setups">
              </div>
              <h3 class="lp-cat-title">Balloon Decor & Bobo Gifts</h3>
              <p class="lp-cat-desc">Vibrant balloon styling, customized vinyl lettering, and clear Bobo gift globes filled with surprises, lights, and treats.</p>
            </div>
            <div class="lp-cat-tags">Bobo balloons for gifts, personalized vinyl prints, helium bunches, LED light integration</div>
          </div>

          <!-- 4. Celebration Cakes & Sweets -->
          <div class="lp-category-card">
            <div>
              <div class="lp-cat-image-wrap">
                <img src="Assets/Images/category-cakes.jpg" alt="Celebration Cakes & Sweets">
              </div>
              <h3 class="lp-cat-title">Celebration Cakes & Sweets</h3>
              <p class="lp-cat-desc">Delicious, beautifully styled cakes designed to match your specific surprise theme.</p>
            </div>
            <div class="lp-cat-tags">Bento cakes, multi-tier custom designs, cupcake add-ons</div>
          </div>

          <!-- 5. Curated Gifts & Souvenirs -->
          <div class="lp-category-card">
            <div>
              <div class="lp-cat-image-wrap">
                <img src="Assets/Images/category-gifts.jpg" alt="Curated Gifts & Souvenirs">
              </div>
              <h3 class="lp-cat-title">Curated Gifts & Souvenirs</h3>
              <p class="lp-cat-desc">Memorable keepsakes, personalized gift boxes, and event favors tailored to your recipient.</p>
            </div>
            <div class="lp-cat-tags">Custom mugs, photo prints, luxury baskets, event souvenirs</div>
          </div>

          <!-- 6. Full Surprise Packages -->
          <div class="lp-category-card">
            <div>
              <div class="lp-cat-image-wrap">
                <img src="Assets/Images/category-packages.jpg" alt="Full Surprise Packages">
              </div>
              <h3 class="lp-cat-title">Full Surprise Packages</h3>
              <p class="lp-cat-desc">The ultimate hassle-free combination of flowers, balloons, cake, and a coordinated delivery setup.</p>
            </div>
            <div class="lp-cat-tags">All-in-one sets for birthdays, anniversaries and grand proposals</div>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section class="lp-how-section" id="how-it-works">
      <div class="lp-wrapper">
        <h2 class="lp-section-title">How It Works</h2>
        <div class="lp-steps-grid">
          <div class="lp-step-item">
            <div class="lp-step-badge">1</div>
            <h3 class="lp-step-title">Select your package or items</h3>
            <p class="lp-step-desc">Choose from our ready-made surprise combinations or select individual items to build a custom order.</p>
          </div>
          <div class="lp-step-item">
            <div class="lp-step-badge">2</div>
            <h3 class="lp-step-title">Set delivery & custom details</h3>
            <p class="lp-step-desc">Pick your delivery date, time slot, recipient address, and write a personalized card message.</p>
          </div>
          <div class="lp-step-item">
            <div class="lp-step-badge">3</div>
            <h3 class="lp-step-title">We handle the magic</h3>
            <p class="lp-step-desc">Our team prepares your arrangements and delivers the surprise — with instant order confirmation sent straight to your Messenger.</p>
          </div>
        </div>
        <a href="U_OrderPage.php" class="btn-hero-primary">Start Your Order</a>
      </div>
    </section>

    <!-- Testimonials HTML Section -->
    <section class="lp-testimonials-section" id="testimonials">
      <div class="lp-wrapper">
        <div class="lp-section-subtitle">Real Moments & Reactions</div>
        <h2 class="lp-section-title">Loved by Our Clients</h2>

        <div class="lp-testimonials-grid">
          <!-- Testimonial 1 -->
          <div class="lp-testimonial-card">
            <div>
              <div class="lp-stars">★★★★★</div>
              <p class="lp-testimonial-quote">
                "Ordering from LynxPrise made my sister's birthday so seamless! The flower bouquet and custom Bobo balloon were stunning, and delivery was right on time."
              </p>
            </div>
            <div class="lp-testimonial-author">
              <div class="lp-author-avatar">MS</div>
              <div>
                <div class="lp-author-name">Maria S.</div>
                <div class="lp-author-details">Birthday Surprise Package</div>
              </div>
            </div>
          </div>

          <!-- Testimonial 2 -->
          <div class="lp-testimonial-card">
            <div>
              <div class="lp-stars">★★★★★</div>
              <p class="lp-testimonial-quote">
                "The money bouquet was assembled so neatly and safely! Super responsive customer service through Messenger. Will definitely order again!"
              </p>
            </div>
            <div class="lp-testimonial-author">
              <div class="lp-author-avatar">JR</div>
              <div>
                <div class="lp-author-name">Jan R.</div>
                <div class="lp-author-details">Custom Money Bouquet</div>
              </div>
            </div>
          </div>

          <!-- Testimonial 3 -->
          <div class="lp-testimonial-card">
            <div>
              <div class="lp-stars">★★★★★</div>
              <p class="lp-testimonial-quote">
                "Having everything in one place—cake, balloons, and fresh roses—saved me so much stress. The recipient was literally brought to tears!"
              </p>
            </div>
            <div class="lp-testimonial-author">
              <div class="lp-author-avatar">CL</div>
              <div>
                <div class="lp-author-name">Christine L.</div>
                <div class="lp-author-details">Full Surprise Package</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer Section -->
  <footer class="lp-footer">
    <div class="lp-wrapper">
      <div class="lp-footer-grid">
        <div>
          <a href="#" class="lp-logo" style="font-size: 20px;">Lynx<span>Prise</span></a>
          <p style="margin-top: 8px;">Gifts ▪ Souvenirs ▪ Balloons ▪ Cakes ▪ Surprises</p>
        </div>
        <div class="lp-footer-col">
          <h5>Delivery</h5>
          <p>Serving Eastern Visayas with same-day and scheduled delivery slots.</p>
        </div>
        <div class="lp-footer-col">
          <h5>Payments</h5>
          <p>GCash ▪ Maya ▪ Cash on Delivery</p>
        </div>
      </div>
      <div class="lp-footer-bottom">
        &copy; 2026 LynxPrise. All rights reserved.
      </div>
    </div>
  </footer>

</body>
</html>