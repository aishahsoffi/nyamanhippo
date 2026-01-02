<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'foodpanda_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_address'])) {
        $addressId = $_POST['address_id'];
        $userId = $_SESSION['user_id'];
        
        // Delete address (only if it belongs to the logged-in user)
        $deleteQuery = "DELETE FROM address WHERE Address_ID = ? AND User_ID = ?";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $deleteStmt->execute([$addressId, $userId]);
        
        if ($deleteStmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Address deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Address not found or unauthorized']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
    
} catch(PDOException $e) {
    error_log("Delete address failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>