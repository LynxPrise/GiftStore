<?php
// Detect if running on localhost (XAMPP) or live server
$is_local = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1');

if ($is_local) {
    // ----------------------------------------------------
    // LOCALHOST (XAMPP) CONFIGURATION
    // ----------------------------------------------------
    $host     = '127.0.0.1';
    $dbname   = 'lynxprisegifts';
    $username = 'root';
    $password = '';
    $port     = 3309; // Your XAMPP custom MySQL port
} else {
    // ----------------------------------------------------
    // LIVE HOSTINGER CONFIGURATION
    // ----------------------------------------------------
    $host     = 'localhost'; 
    $dbname   = 'u914267632_lynxprisegifts';
    $username = 'u914267632_lynxprise';
    $password = 'Lanelyn#13'; // Replace with your actual password
    $port     = 3306; // Standard Hostinger MySQL port
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>