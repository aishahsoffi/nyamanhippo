<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Log session data for debugging (remove in production)
error_log("Session data: " . print_r($_SESSION, true));

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated',
        'redirect' => 'login_admin.html',
        'debug' => [
            'session_exists' => isset($_SESSION['admin_logged_in']),
            'session_value' => isset($_SESSION['admin_logged_in']) ? $_SESSION['admin_logged_in'] : null
        ]
    ]);
    exit;
}

// Check if required session variables exist
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_name']) || !isset($_SESSION['admin_email'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Incomplete session data',
        'redirect' => 'login_admin.html',
        'debug' => [
            'has_id' => isset($_SESSION['admin_id']),
            'has_name' => isset($_SESSION['admin_name']),
            'has_email' => isset($_SESSION['admin_email'])
        ]
    ]);
    exit;
}

// Return admin information
echo json_encode([
    'success' => true,
    'admin' => [
        'id' => $_SESSION['admin_id'],
        'name' => $_SESSION['admin_name'],
        'email' => $_SESSION['admin_email']
    ]
]);
?>