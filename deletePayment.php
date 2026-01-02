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
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_payment'])) {
        $paymentId = $_POST['payment_id'];
        $userId = $_SESSION['user_id'];
        
        // Delete payment method (only if it belongs to the logged-in user)
        $deleteQuery = "DELETE FROM payment_method WHERE Payment_Method_ID = ? AND User_ID = ?";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $deleteStmt->execute([$paymentId, $userId]);
        
        if ($deleteStmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Payment method deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Payment method not found or unauthorized']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
    
} catch(PDOException $e) {
    error_log("Delete payment failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>