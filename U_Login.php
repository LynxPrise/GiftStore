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
        
        header('Location: M_Dashboard.php');
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #d81b60; /* Pink color */
            --primary-light: #e86c97ff; /* Light pink color */
            --gray: #6c757d;
            --light-gray: #dddddd;
        }

        body {
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: #fce4ec; /* Light pink background */
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* LEFT SIDE IMAGE */
        .left-side {
            flex: 1.2;
            background: url('assets/images/store3.jpg') no-repeat center center/cover;
            padding: 60px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .left-text h1 {
            font-size: 45px;
            line-height: 1.2;
            font-weight: 700;
        }

        .left-text p {
            font-size: 18px;
            max-width: 350px;
            margin-top: 10px;
        }

        .left-footer a {
            font-size: 15px;
            background: rgba(255,255,255,0.95);
            padding: 10px 18px;
            border-radius: 30px;
            text-decoration: none;
            color:  #d81b60;
            font-weight: 600;
        }

        /* RIGHT SIDE LOGIN FORM */
        .right-side {
            flex: 1;
            background: var(--primary); /* Pink background for the form */
            color: white;
            padding: 60px 70px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 15px;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 87%;
            padding: 14px 45px 14px 15px;
            font-size: 16px;
            border-radius: 8px;
            border: none;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 47px;
            background: none;
            border: none;
            color: #777;
            cursor: pointer;
            font-size: 1rem;
        }

        button[type=submit] {
            width: 100%;
            padding: 15px;
            background: var(--primary-light); /* Light pink for the button */
            color: white;
            font-size: 18px;
            border: none;
            border-radius: 10px;
            margin-top: 15px;
            cursor: pointer;
            font-weight: 600;
        }

        button[type=submit]:hover {
            background: var(--primary); /* Darken on hover */
        }

        .alert {
            background: #ffdddd;
            padding: 12px;
            border-radius: 8px;
            color: #b30000;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .footer-links {
            margin-top: 25px;
            text-align: center;
        }

        .footer-links a {
            color: #9cd3ff;
            text-decoration: none;
        }

        @media (max-width: 900px) {
            .container {
                flex-direction: column;
            }
            .right-side {
                padding: 40px;
            }
            .left-side {
                display: none;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <!-- LEFT IMAGE SIDE -->
    <div class="left-side">
    <div class="left-text">
        <h1>Celebrate Every Moment</h1>
        <p>At LynxPrise, we bring you the best in gifts, flowers, balloons, and surprises. Perfect for birthdays, anniversaries, or just because! Let us help you add a little extra joy to every special occasion.</p>
    </div>
    <div class="left-footer">
        <a href="Shop.php">Explore Our Amazing Products</a>
    </div>

</div>


    <!-- RIGHT LOGIN SIDE -->
    <div class="right-side">    

        <h2 class="title">Welcome Back</h2>
        <p class="subtitle">Sign in to manage your orders</p>

        <?php if (isset($error)): ?>
            <div class="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="name@example.com">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Enter password">
                <button type="button" class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <button type="submit">Sign In</button>
        </form>

        <div class="footer-links">
            <a href="#">Forgot Password?</a><br><br>
            <!-- <a href="U_Register.php">Don’t have an account? Create one</a> -->
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
