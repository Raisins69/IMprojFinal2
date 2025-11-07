<?php
// Place this file in: public/admin/debug.php
// Access it at: http://localhost/projectIManagement/public/admin/debug.php

session_start();

echo "<h2>Session Debug Information</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n\n";

echo "Session Variables:\n";
print_r($_SESSION);

echo "\n\nServer Variables:\n";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "\n";

echo "\n\nPHP Version: " . phpversion() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "</pre>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red; font-weight: bold;'>❌ No user_id in session - NOT LOGGED IN</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p style='color: green; font-weight: bold;'>✅ Role: " . $_SESSION['role'] . "</p>";
}

echo "<hr>";
echo "<a href='dashboard.php'>Go to Dashboard</a> | ";
echo "<a href='../login.php'>Go to Login</a>";
?>