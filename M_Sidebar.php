<!-- Mobile Navigation Bar (Displays only on screens <= 768px) -->
<div class="admin-mobile-bar">
    <a href="M_Dashboard.php" class="lp-logo">Lynx<span>Prise</span></a>
    <button class="hamburger-btn" id="hamburgerBtn" onclick="toggleAdminSidebar()">☰</button>
</div>

<!-- Main Admin Sidebar Navigation -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <a href="M_Dashboard.php" class="lp-logo">Lynx<span>Prise</span></a>
    </div>

    <ul class="sidebar-menu">
        <li><a href="M_Dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'M_Dashboard.php' ? 'active' : '' ?>">Orders</a></li>
        <li><a href="M_Products.php" class="<?= basename($_SERVER['PHP_SELF']) == 'M_Products.php' ? 'active' : '' ?>">Products</a></li>
        <li><a href="M_Categories.php" class="<?= basename($_SERVER['PHP_SELF']) == 'M_Categories.php' ? 'active' : '' ?>">Categories</a></li>
        <li><a href="M_Feedbacks.php" class="<?= basename($_SERVER['PHP_SELF']) == 'M_Feedbacks.php' ? 'active' : '' ?>">Feedbacks</a></li>
        <li><a href="M_Settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'M_Settings.php' ? 'active' : '' ?>">Settings</a></li>
        <li><a href="U_OrderPage.php">Back To Home</a></li>
    </ul>

    <div class="sidebar-footer">
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</aside>

<!-- Dark Overlay for Mobile Drawer -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleAdminSidebar()"></div>

<script>
function toggleAdminSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
}
</script>