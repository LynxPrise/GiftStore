<?php
require_once 'U_db.php';

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    // Check password match
    if ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        // Check if email exists
        $check = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $error = "Email is already registered!";
        } else {
            // Insert user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users(firstname, lastname, email, password_hash, role) VALUES(?, ?, ?, ?, 'User')");

            if ($stmt->execute([$firstname, $lastname, $email, $hashed])) {
                $success = "Account created successfully! Redirecting...";
                header("refresh:2; url=index");
            } else {
                $error = "Something went wrong, try again!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - RouteScape</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { margin: 0; font-family: "Poppins", sans-serif; }
        .container { display: flex; min-height: 100vh; }

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
            font-size: 45px; font-weight: 700; line-height: 1.2;
        }

        .right-side {
            flex: 1;
            background: #d81b60;; /* Pink background */
            color: white;
            padding: 60px 70px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .title { font-size: 32px; font-weight: 700; margin-bottom: 10px; }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; }

        .form-control {
            width: 95%;
            padding: 14px;
            font-size: 16px;
            border-radius: 8px;
            border: none;
        }

        button[type=submit] {
            width: 100%;
            padding: 15px;
            background: #e86c97ff;;
            color: white;
            font-size: 18px;
            border: none;
            border-radius: 10px;
            margin-top: 15px;
            cursor: pointer;
            font-weight: 600;
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

        .success {
            background: #d4edda;
            padding: 12px;
            border-radius: 8px;
            color: #155724;
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
            .container { flex-direction: column; }
            .left-side { display: none; }
            .right-side { padding: 40px; }
        }
    </style>
</head>

<body>

<div class="container">

    <!-- LEFT SIDE IMAGE -->
    <div class="left-side">
    <div class="left-text">
        <h1>Start Your Celebration</h1>
        <p>Discover the perfect gift, bouquet, or surprise at LynxPrise. Whether it's for a birthday, an anniversary, or just because – we make every moment special!</p>
    </div>
</div>


    <!-- RIGHT SIDE REGISTER FORM -->
    <div class="right-side">

        <h2 class="title">Create Account</h2>

        <?php if ($error): ?>
            <div class="alert">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="firstname" class="form-control" required placeholder="First Name">
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lastname" class="form-control" required placeholder="Last Name">
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="name@example.com">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Enter password">
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm" class="form-control" required placeholder="Repeat password">
            </div>

            <button type="submit">Create Account</button>
        </form>

        <div class="footer-links">
            <a href="U_Login">Already have an account? Sign In</a>
        </div>

    </div>
</div>

</body>
</html>
