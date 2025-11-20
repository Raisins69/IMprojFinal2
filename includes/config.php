<?php

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 86400);
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

$host = "localhost";
$user = "root";
$pass = ""; 
$dbname = "urbanthrift_db";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

define('BASE_URL', '/IMprojFinal/public');
define('ADMIN_URL', BASE_URL . '/admin');

if (!defined('MAIL_FROM_EMAIL')) {
    define('MAIL_FROM_EMAIL', 'noreply@urbanthrift.com');
    define('MAIL_FROM_NAME', 'UrbanThrift');
}

if (($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') && !defined('MAIL_DRIVER')) {
    define('MAIL_DRIVER', 'smtp');
    define('MAIL_HOST', 'sandbox.smtp.mailtrap.io');
    define('MAIL_PORT', 2525); 
    define('MAIL_USERNAME', '634558ff2cfd2d');
    define('MAIL_PASSWORD', '051b5c3758eb67');
    define('MAIL_ENCRYPTION', 'tls');
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

function checkAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

function checkCustomer() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}
