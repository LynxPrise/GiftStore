<?php
session_start();
require_once 'U_db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_firstname'] = $user['firstname'];
        $_SESSION['user_lastname'] = $user['lastname'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        
        header('Location: M_Dashboard');
        exit;
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LynxPrise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..700;1,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sacramento&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* LEFT SIDE IMAGE & BRAND OVERLAY */
        .left-side {
            flex: 1.2;
            background: linear-gradient(rgba(59, 34, 25, 0.45), rgba(59, 34, 25, 0.45)), 
                        url('assets/images/store3.jpg') no-repeat center center/cover;
            padding: 60px;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .lp-logo {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            color: #ffffff;
            text-decoration: none;
        }

        .lp-logo span {
            font-family: 'Sacramento', cursive;
            color: #fdeee8;
            font-size: 40px;
            margin-left: 2px;
        }

        .left-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: 44px;
            line-height: 1.2;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .left-text p {
            font-size: 16px;
            max-width: 450px;
            line-height: 1.6;
            opacity: 0.95;
        }

        .left-footer a {
            display: inline-block;
            font-size: 14px;
            background: #ffffff;
            padding: 12px 24px;
            border-radius: var(--radius-btn);
            text-decoration: none;
            color: var(--accent-pink);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .left-footer a:hover {
            background: var(--bg-soft-pink);
            color: var(--accent-pink-hover);
            transform: translateY(-2px);
        }

        /* RIGHT SIDE FORM CARD */
        .right-side {
            flex: 1;
            background: var(--bg-cream);
            padding: 60px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-left: 1px solid var(--gold-border);
        }

        .form-header {
            margin-bottom: 30px;
        }

        .title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 14px;
            color: var(--text-muted);
        }

        .form-group {
            margin-bottom: 22px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-dark);
        }

        .input-wrapper {
            position: relative;
            width: 100%;
        }

        .form-control {
            width: 100%;
            padding: 14px 45px 14px 16px;
            font-size: 14px;
            font-family: inherit;
            border-radius: 10px;
            border: 1px solid var(--gold-border);
            background: #ffffff;
            color: var(--text-dark);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: var(--accent-pink);
            box-shadow: 0 0 0 3px rgba(217, 101, 139, 0.15);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 15px;
            padding: 4px;
        }

        .password-toggle:hover {
            color: var(--accent-pink);
        }

        button[type=submit] {
            width: 100%;
            padding: 14px;
            background: var(--accent-pink);
            color: #ffffff;
            font-size: 15px;
            border: none;
            border-radius: var(--radius-btn);
            margin-top: 10px;
            cursor: pointer;
            font-weight: 600;
            font-family: inherit;
            transition: background 0.2s, transform 0.1s;
            box-shadow: 0 4px 12px rgba(217, 101, 139, 0.25);
        }

        button[type=submit]:hover {
            background: var(--accent-pink-hover);
        }

        .alert {
            background: #fde8e8;
            border: 1px solid #f8b4b4;
            padding: 12px 16px;
            border-radius: 8px;
            color: #c81e1e;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-links {
            margin-top: 25px;
            text-align: center;
        }

        .footer-links a {
            color: var(--text-muted);
            font-size: 14px;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--accent-pink);
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 992px) {
            .right-side {
                padding: 50px 40px;
            }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                min-height: 100vh;
            }
            .left-side {
                display: none;
            }
            .right-side {
                flex: 1;
                width: 100%;
                justify-content: center;
                padding: 40px 25px;
                border-left: none;
            }
            .title {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <!-- LEFT IMAGE SIDE -->
    <div class="left-side">
        <div>
            <a href="index" class="lp-logo">Lynx<span>Prise</span></a>
        </div>
        <div class="left-text">
            <h1>Celebrate Every Moment</h1>
            <p>At LynxPrise, we bring you the best in gifts, flowers, balloons, and surprises. Perfect for birthdays, anniversaries, or special moments.</p>
        </div>
        <div class="left-footer">
            <a href="Shop">Explore Our Shop</a>
        </div>
    </div>

    <!-- RIGHT LOGIN SIDE -->
    <div class="right-side">    

        <div class="form-header">
            <h2 class="title">Welcome Back</h2>
            <p class="subtitle">Sign in to manage your orders & store dashboard</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" class="form-control" required placeholder="name@example.com">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Enter password">
                    <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit">Sign In</button>
        </form>

        <div class="footer-links">
            <a href="#">Forgot Password?</a>
        </div>

    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById("password");
    const icon = document.querySelector(".password-toggle i");

    if (input.type === "password") {
        input.type = "text";
        icon.className = "fas fa-eye-slash";
    } else {
        input.type = "password";
        icon.className = "fas fa-eye";
    }
}
</script>

</body>
</html>