<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <ul class="sidebar-menu">
        <li>
            <a href="M_Dashboard" class="<?php echo ($current_page == 'M_Dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="M_Products" class="<?php echo ($current_page == 'M_Products.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-boxes-stacked"></i> Products
            </a>
            
        </li>
        <li>
            <a href="M_Categories" class="<?php echo ($current_page == 'M_Categories.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-list"></i> Categories
            </a>
        </li>
        <li>
            <a href="M_Feedbacks" class="<?php echo ($current_page == 'M_Feedbacks.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-comments"></i> Feedbacks
            </a>
        </li>
        <li>
            <a href="M_Settings" class="<?php echo ($current_page == 'M_Settings.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gear"></i> Settings
            </a>
            
        </li>
        <li>
            <a href="index">
                <i class="fa-solid fa-house"></i> Back To Home
            </a>
        </li>
    </ul>
</aside>