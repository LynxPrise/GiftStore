<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: U_Login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'LynxPrise Admin'; ?></title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..700;1,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sacramento&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --topbar-height: 65px;
            --sidebar-width: 240px;
            --bg-soft-pink: #fdeee8;
            --bg-cream: #fff9f6;
            --card-bg: #ffffff;
            --accent-pink: #d9658b;
            --accent-pink-hover: #c45075;
            --text-dark: #3b2219;
            --text-muted: #785a50;
            --gold-border: #e8c3b0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-soft-pink); color: var(--text-dark); }

        /* Top Bar */
        .top-navbar {
            height: var(--topbar-height);
            background-color: var(--bg-cream);
            border-bottom: 1px solid var(--gold-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 25px;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
        }

        .lp-logo { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; color: var(--text-dark); text-decoration: none; }
        .lp-logo span { font-family: 'Sacramento', cursive; color: var(--accent-pink); font-size: 32px; margin-left: 2px; }

        .top-nav-controls { display: flex; align-items: center; gap: 15px; }
        .admin-badge { background-color: var(--bg-soft-pink); border: 1px solid var(--gold-border); color: var(--text-dark); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .btn-top-logout { background-color: var(--accent-pink); color: #fff; padding: 8px 20px; border-radius: 20px; text-decoration: none; font-size: 13px; font-weight: 600; transition: background-color 0.2s; }
        .btn-top-logout:hover { background-color: var(--accent-pink-hover); }

        /* Layout & Sidebar */
        .app-layout { display: flex; margin-top: var(--topbar-height); min-height: calc(100vh - var(--topbar-height)); }
        .sidebar { width: var(--sidebar-width); background-color: var(--bg-cream); border-right: 1px solid var(--gold-border); padding: 20px 0; position: fixed; top: var(--topbar-height); bottom: 0; left: 0; z-index: 900; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: var(--text-dark); text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.2s; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background-color: var(--accent-pink); color: #fff; }

        /* Workspace Content */
        .main-workspace { margin-left: var(--sidebar-width); flex: 1; padding: 1px; width: calc(100% - var(--sidebar-width)); }

        @media (max-width: 900px) {
            .sidebar { display: none; }
            .main-workspace { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>

    <header class="top-navbar">
        <a href="M_Dashboard.php" class="lp-logo">Lynx<span>Prise</span></a>
        <div class="top-nav-controls">
            <div class="admin-badge"><i class="fa-solid fa-user-shield"></i> Owner Dashboard</div>
            <a href="U_Logout.php" class="btn-top-logout">Logout</a>
        </div>
    </header>

    <div class="app-layout">