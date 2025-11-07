<?php
// includes/config.php

// Start session at the very beginning
session_start();

// Database configuration
$host = "localhost:3306";
$user = "root";
$pass = ""; 
$dbname = "urbanthrift_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Define base paths for consistent navigation
define('BASE_URL', '/projectIManagement/public');
define('ADMIN_URL', BASE_URL . '/admin');

// Function: Check Login Redirect
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

// Function: Check Admin Access
function checkAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

// Function: Check Customer Access
function checkCustomer() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}