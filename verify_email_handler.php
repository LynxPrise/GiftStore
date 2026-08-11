<?php
session_start();
require_once 'U_db.php'; // Uses $pdo

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address provided.']);
    exit;
}

if ($action === 'send_otp') {
    // Generate random 6-digit OTP
    $otp = sprintf("%06d", mt_rand(100000, 999999));
    
    // Save OTP details in session (expires in 10 minutes)
    $_SESSION['otp_code'] = $otp;
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_expires'] = time() + 600;

    // Email headers and configuration
    $to = $email;
    $subject = "LynxPrise - Verification Code";
    $message = "Your verification code to complete your order is: " . $otp . "\n\nThis code expires in 10 minutes.";
    $headers = "From: LynxPrise <no-reply@lynxprise.com>\r\n" .
               "Reply-To: no-reply@lynxprise.com\r\n" .
               "X-Mailer: PHP/" . phpversion();

    if (@mail($to, $subject, $message, $headers)) {
        echo json_encode(['success' => true, 'message' => 'OTP sent successfully to your email.']);
    } else {
        // Fallback for local XAMPP setup
        echo json_encode([
            'success' => true, 
            'message' => 'OTP generated! (Local Dev Code: ' . $otp . ')'
        ]);
    }
    exit;
}

if ($action === 'verify_otp') {
    $user_otp = trim($_POST['otp'] ?? '');

    if (!isset($_SESSION['otp_code']) || $_SESSION['otp_email'] !== $email) {
        echo json_encode(['success' => false, 'message' => 'No OTP request found for this email address.']);
        exit;
    }

    if (time() > $_SESSION['otp_expires']) {
        echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please request a new code.']);
        exit;
    }

    if ($user_otp === $_SESSION['otp_code']) {
        $_SESSION['email_verified'] = true;
        $_SESSION['verified_email_address'] = $email;
        echo json_encode(['success' => true, 'message' => 'Email verified successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect verification code.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action requested.']);