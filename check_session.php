<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Return user information if logged in
if ($loggedIn) {
    echo json_encode([
        'loggedIn' => true,
        'userId' => $_SESSION['user_id'],
        'userName' => $_SESSION['name'] ?? 'User',
        'userEmail' => $_SESSION['email'] ?? ''
    ]);
} else {
    echo json_encode([
        'loggedIn' => false
    ]);
}
?>