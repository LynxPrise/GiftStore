<!doctype html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LynxPrise - Gift Store</title>
  <script src="/_sdk/element_sdk.js"></script>
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
    
    /* Header Styles */
    .header-section {
      width: 100%;
      background: rgba(255, 255, 255, 0.95);
      padding: 20px 0;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    
    .header-content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
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
    
    .store-info h1 {
      font-size: 28px;
      color: #d81b60;
      margin-bottom: 5px;
    }
    
    .store-info p {
      font-size: 14px;
      color: #666;
    }
    
    .nav-menu {
      display: flex;
      gap: 30px;
      list-style: none;
    }
    
    .nav-menu a {
      text-decoration: none;
      color: #333;
      font-weight: 500;
      transition: color 0.3s;
    }
    
    .nav-menu a:hover {
      color: #d81b60;
    }
    
    .mobile-menu-btn {
      display: none;
      background: none;
      border: none;
      font-size: 28px;
      color: #d81b60;
      cursor: pointer;
    }
    
    /* Hero Section */
    .hero-section {
      width: 100%;
      padding: 80px 20px;
      text-align: center;
    }
    
    .hero-content {
      max-width: 800px;
      margin: 0 auto;
    }
    
    .hero-content h2 {
      font-size: 48px;
      color: #c2185b;
      margin-bottom: 20px;
    }
    
    .hero-content p {
      font-size: 20px;
      color: #555;
      margin-bottom: 30px;
    }
    
    .cta-button {
      display: inline-block;
      padding: 15px 40px;
      background: #d81b60;
      color: white;
      text-decoration: none;
      border-radius: 30px;
      font-size: 18px;
      font-weight: 600;
      transition: all 0.3s;
      border: none;
      cursor: pointer;
    }
    
    .cta-button:hover {
      background: #c2185b;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(216, 27, 96, 0.3);
    }
    
    /* Promo Section */
    .promo-section {
      width: 100%;
      padding: 40px 20px;
      background: linear-gradient(135deg, #d81b60 0%, #c2185b 100%);
    }
    
    .promo-content {
      max-width: 800px;
      margin: 0 auto;
      text-align: center;
      color: white;
    }
    
    .promo-badge {
      display: inline-block;
      background: rgba(255, 255, 255, 0.2);
      padding: 8px 20px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 15px;
      letter-spacing: 1px;
    }
    
    .promo-content h3 {
      font-size: 32px;
      margin-bottom: 10px;
    }
    
    .promo-content p {
      font-size: 18px;
      margin-bottom: 25px;
      opacity: 0.95;
    }
    
    .promo-button {
      padding: 12px 35px;
      background: white;
      color: #d81b60;
      border: none;
      border-radius: 25px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .promo-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
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
    
    @keyframes slideOut {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(400px);
        opacity: 0;
      }
    }
    
    /* Categories Section */
    .categories-section {
      width: 100%;
      padding: 60px 20px;
      background: white;
    }
    
    .categories-content {
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .section-title {
      text-align: center;
      font-size: 36px;
      color: #c2185b;
      margin-bottom: 50px;
    }
    
    .categories-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 30px;
    }
    
    .category-card {
      background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%);
      padding: 40px 30px;
      border-radius: 15px;
      text-align: center;
      transition: transform 0.3s, box-shadow 0.3s;
      cursor: pointer;
    }
    
    .category-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(216, 27, 96, 0.2);
    }
    
    .category-icon {
      font-size: 60px;
      margin-bottom: 20px;
    }
    
    .category-card h3 {
      font-size: 24px;
      color: #c2185b;
      margin-bottom: 10px;
    }
    
    .category-card p {
      color: #666;
      font-size: 14px;
    }
    
    /* Best Sellers Section */
    .bestsellers-section {
      width: 100%;
      padding: 60px 20px;
      background: #f5f5f5;
    }
    
    .bestsellers-content {
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 30px;
    }
    
    .product-card {
      background: white;
      border-radius: 15px;
      overflow: hidden;
      transition: transform 0.3s, box-shadow 0.3s;
      cursor: pointer;
    }
    
    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(216, 27, 96, 0.15);
    }
    
    .product-image {
      width: 100%;
      height: 200px;
      background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 80px;
    }
    
    .product-info {
      padding: 20px;
    }
    
    .product-info h3 {
      font-size: 20px;
      color: #c2185b;
      margin-bottom: 8px;
    }
    
    .product-description {
      color: #666;
      font-size: 14px;
      margin-bottom: 15px;
      min-height: 40px;
    }
    
    .product-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .product-price {
      font-size: 24px;
      font-weight: 700;
      color: #d81b60;
    }
    
    .add-to-cart {
      padding: 10px 20px;
      background: #d81b60;
      color: white;
      border: none;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .add-to-cart:hover {
      background: #c2185b;
      transform: scale(1.05);
    }
    
    /* About Section */
    .about-section {
      width: 100%;
      padding: 60px 20px;
      background: linear-gradient(135deg, #f8bbd0 0%, #fce4ec 100%);
    }
    
    .about-content {
      max-width: 800px;
      margin: 0 auto;
      text-align: center;
    }
    
    .about-content h2 {
      font-size: 36px;
      color: #c2185b;
      margin-bottom: 30px;
    }
    
    .about-content p {
      font-size: 18px;
      color: #555;
      line-height: 1.8;
    }
    
    /* Contact Section */
    .contact-section {
      width: 100%;
      padding: 60px 20px;
      background: white;
    }
    
    .contact-content {
      max-width: 600px;
      margin: 0 auto;
    }
    
    .contact-content h2 {
      text-align: center;
      font-size: 36px;
      color: #c2185b;
      margin-bottom: 15px;
    }
    
    .contact-content > p {
      text-align: center;
      color: #666;
      margin-bottom: 40px;
    }
    
    .contact-form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    
    .form-group label {
      font-weight: 600;
      color: #333;
    }
    
    .form-group input,
    .form-group textarea {
      padding: 12px;
      border: 2px solid #f8bbd0;
      border-radius: 8px;
      font-size: 16px;
      font-family: inherit;
      transition: border-color 0.3s;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #d81b60;
    }
    
    .form-group textarea {
      resize: vertical;
      min-height: 120px;
    }
    
    .submit-btn {
      padding: 15px 40px;
      background: #d81b60;
      color: white;
      border: none;
      border-radius: 30px;
      font-size: 18px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .submit-btn:hover {
      background: #c2185b;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(216, 27, 96, 0.3);
    }
    
    .submit-btn:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
    }
    
    .form-message {
      padding: 15px;
      border-radius: 8px;
      text-align: center;
      font-weight: 500;
      display: none;
    }
    
    .form-message.success {
      background: #c8e6c9;
      color: #2e7d32;
      display: block;
    }
    
    .form-message.error {
      background: #ffcdd2;
      color: #c62828;
      display: block;
    }
    
    /* Footer */
    .footer-section {
      width: 100%;
      background: #c2185b;
      color: white;
      padding: 30px 20px;
      text-align: center;
    }
    
    .footer-content {
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .footer-content p {
      font-size: 14px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .nav-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        flex-direction: column;
        padding: 20px;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
      }
      
      .nav-menu.active {
        display: flex;
      }
      
      .mobile-menu-btn {
        display: block;
      }
      
      .header-content {
        position: relative;
      }
      
      .hero-content h2 {
        font-size: 32px;
      }
      
      .hero-content p {
        font-size: 16px;
      }
      
      .section-title {
        font-size: 28px;
      }
      
      .categories-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
  <style>@view-transition { navigation: auto; }</style>
  <script src="/_sdk/data_sdk.js" type="text/javascript"></script>
  <script src="https://cdn.tailwindcss.com" type="text/javascript"></script>
 </head>
 <body>
  <div class="main-wrapper">
  
  
  <!-- Header -->
   <header class="header-section">
    <div class="header-content">
     <div class="logo-section">
      <svg class="logo-icon" viewbox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="45" fill="#d81b60" /> <path d="M50 25 L35 40 L35 60 L50 75 L65 60 L65 40 Z" fill="#ffffff" /> <circle cx="50" cy="50" r="10" fill="#fce4ec" />
      </svg>
      <div class="store-info">
       <h1 id="store-name">LynxPrise</h1>
       <p id="tagline">Your Gift Destination</p>
      </div>
     </div>
     <nav>
      <ul class="nav-menu" id="navMenu">
       <li><a href="index.php">Home</a></li>
       <li><a href="U_Category.php">Categories</a></li>
       <li><a href="U_Bestseller.php">Best Sellers</a></li>
       <li><a href="U_About.php">About</a></li>
       <li><a href="U_Contact.php">Contact</a></li>
        <li><a href="U_Login.php">LogIn/SignUp</a></li>

      </ul><button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
     </nav>
    </div>
   </header>
   
   

   </section><!-- About Section -->
   <section class="about-section" id="about">
    <div class="about-content">
     <h2 id="about-title">About LynxPrise</h2>
     <p id="about-description">At LynxPrise, we believe every moment deserves to be celebrated. With our carefully curated collection of gifts, flowers, souvenirs, balloons, and surprises, we help you create unforgettable memories. Whether it's a birthday, anniversary, or just because, we have the perfect item to express your love and appreciation.</p>
    </div>
   </section>
   
   
   
   <!-- Footer -->
   <footer class="footer-section">
    <div class="footer-content">
     <p id="footer-text">© 2024 LynxPrise. All rights reserved.</p>
    </div>
   </footer>
  </div>
  <script>
    // Configuration
    const defaultConfig = {
      store_name: "LynxPrise",
      tagline: "Your Gift Destination",
      hero_title: "Make Every Moment Special",
      hero_subtitle: "Discover the perfect gifts, flowers, and surprises for your loved ones",
      cta_button: "Explore Our Collection",
      promo_title: "Get 25% Off Your First Order!",
      promo_subtitle: "Use code: LYNX25 at checkout",
      promo_button: "Shop Now",
      about_title: "About LynxPrise",
      about_description: "At LynxPrise, we believe every moment deserves to be celebrated. With our carefully curated collection of gifts, flowers, souvenirs, balloons, and surprises, we help you create unforgettable memories. Whether it's a birthday, anniversary, or just because, we have the perfect item to express your love and appreciation.",
      contact_title: "Get In Touch",
      contact_subtitle: "Have questions? We'd love to hear from you!",
      footer_text: "© 2024 LynxPrise. All rights reserved."
    };

    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navMenu = document.getElementById('navMenu');

    mobileMenuBtn.addEventListener('click', () => {
      navMenu.classList.toggle('active');
    });

    // Smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        navMenu.classList.remove('active');
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({ behavior: 'smooth' });
        }
      });
    });

    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(button => {
      button.addEventListener('click', (e) => {
        e.stopPropagation();
        const productName = button.closest('.product-card').querySelector('h3').textContent;
        showNotification(`${productName} added to cart!`);
      });
    });

    // Promo button functionality
    const promoButton = document.getElementById('promo-button');
    if (promoButton) {
      promoButton.addEventListener('click', () => {
        document.getElementById('bestsellers').scrollIntoView({ behavior: 'smooth' });
      });
    }

    // Notification system
    function showNotification(message) {
      const notification = document.createElement('div');
      notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #c2185b;
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        z-index: 10000;
        font-weight: 600;
        animation: slideIn 0.3s ease-out;
      `;
      notification.textContent = message;
      document.body.appendChild(notification);
      
      setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
      }, 3000);
    }

    // Contact form handling
    const contactForm = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const formMessage = document.getElementById('formMessage');

    contactForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      submitBtn.disabled = true;
      submitBtn.textContent = 'Sending...';
      formMessage.className = 'form-message';
      formMessage.style.display = 'none';

      // Simulate form submission
      setTimeout(() => {
        formMessage.textContent = 'Thank you! Your message has been sent successfully.';
        formMessage.className = 'form-message success';
        contactForm.reset();
        submitBtn.disabled = false;
        submitBtn.textContent = 'Send Message';
        
        setTimeout(() => {
          formMessage.style.display = 'none';
        }, 5000);
      }, 1500);
    });

    // Element SDK integration
    async function onConfigChange(config) {
      document.getElementById('store-name').textContent = config.store_name || defaultConfig.store_name;
      document.getElementById('tagline').textContent = config.tagline || defaultConfig.tagline;
      document.getElementById('hero-title').textContent = config.hero_title || defaultConfig.hero_title;
      document.getElementById('hero-subtitle').textContent = config.hero_subtitle || defaultConfig.hero_subtitle;
      document.getElementById('cta-button').textContent = config.cta_button || defaultConfig.cta_button;
      document.getElementById('promo-title').textContent = config.promo_title || defaultConfig.promo_title;
      document.getElementById('promo-subtitle').textContent = config.promo_subtitle || defaultConfig.promo_subtitle;
      document.getElementById('promo-button').textContent = config.promo_button || defaultConfig.promo_button;
      document.getElementById('about-title').textContent = config.about_title || defaultConfig.about_title;
      document.getElementById('about-description').textContent = config.about_description || defaultConfig.about_description;
      document.getElementById('contact-title').textContent = config.contact_title || defaultConfig.contact_title;
      document.getElementById('contact-subtitle').textContent = config.contact_subtitle || defaultConfig.contact_subtitle;
      document.getElementById('footer-text').textContent = config.footer_text || defaultConfig.footer_text;
    }

    function mapToCapabilities(config) {
      return {
        recolorables: [],
        borderables: [],
        fontEditable: undefined,
        fontSizeable: undefined
      };
    }

    function mapToEditPanelValues(config) {
      return new Map([
        ["store_name", config.store_name || defaultConfig.store_name],
        ["tagline", config.tagline || defaultConfig.tagline],
        ["hero_title", config.hero_title || defaultConfig.hero_title],
        ["hero_subtitle", config.hero_subtitle || defaultConfig.hero_subtitle],
        ["cta_button", config.cta_button || defaultConfig.cta_button],
        ["promo_title", config.promo_title || defaultConfig.promo_title],
        ["promo_subtitle", config.promo_subtitle || defaultConfig.promo_subtitle],
        ["promo_button", config.promo_button || defaultConfig.promo_button],
        ["about_title", config.about_title || defaultConfig.about_title],
        ["about_description", config.about_description || defaultConfig.about_description],
        ["contact_title", config.contact_title || defaultConfig.contact_title],
        ["contact_subtitle", config.contact_subtitle || defaultConfig.contact_subtitle],
        ["footer_text", config.footer_text || defaultConfig.footer_text]
      ]);
    }

    if (window.elementSdk) {
      window.elementSdk.init({
        defaultConfig,
        onConfigChange,
        mapToCapabilities,
        mapToEditPanelValues
      });
    }
  </script>
 <script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'9a5f1fce117be2e0',t:'MTc2NDM4ODA1Mi4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>