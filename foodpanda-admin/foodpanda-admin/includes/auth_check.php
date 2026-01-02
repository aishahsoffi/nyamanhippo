<?php
// Auth check file to protect admin pages
// Include this at the top of every protected admin page

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // User is not logged in, redirect to login page
    header("Location: login_admin.php");
    exit();
}

// Optional: Check for session timeout (30 minutes)
$timeout_duration = 1800; // 30 minutes in seconds

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    // Last request was more than 30 minutes ago
    session_unset();
    session_destroy();
    header("Location: login_admin.php?timeout=1");
    exit();
}

// Update last activity time
$_SESSION['LAST_ACTIVITY'] = time();

// Optional: Check remember me cookie
if (!isset($_SESSION['admin_logged_in']) && isset($_COOKIE['admin_remember'])) {
    // Verify the remember me token from database
    // If valid, restore the session
    // This is simplified - in production, verify token from database
    $_SESSION['admin_logged_in'] = true;
}
?>