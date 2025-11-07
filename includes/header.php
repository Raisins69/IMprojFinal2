<?php
if (!isset($_SESSION)) { session_start(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UrbanThrift</title>
    <link rel="stylesheet" href="/projectIManagement/public/css/style.css">
</head>
<body>

<header class="header">
    <div class="logo"><a href="/projectIManagement/public/index.php" style="color: inherit; text-decoration: none;">UrbanThrift</a></div>
    <nav>
        <ul>
            <li><a href="/projectIManagement/public/index.php">Home</a></li>
            <li><a href="/projectIManagement/public/shop.php">Shop</a></li>
            <li><a href="/projectIManagement/public/about.php">About</a></li>
            <li><a href="/projectIManagement/public/contact.php">Contact</a></li>

            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === "customer"): ?>
                <li><a href="/projectIManagement/public/customer/dashboard.php">My Dashboard</a></li>
                <li><a href="/projectIManagement/public/cart/cart.php">Cart</a></li>
                <li><a href="/projectIManagement/public/logout.php">Logout</a></li>
            <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === "admin"): ?>
                <li><a href="/projectIManagement/public/admin/dashboard.php">Admin</a></li>
                <li><a href="/projectIManagement/public/logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="/projectIManagement/public/login.php">Login</a></li>
                <li><a href="/projectIManagement/public/register.php">Register</a></li>
            <?php endif; ?>

        </ul>
    </nav>
</header>

<main class="main-container">
