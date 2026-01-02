<?php
session_start();
header('Content-Type: application/json');

// Enable error logging for debugging
error_log("=== UPDATE CART REQUEST ===");
error_log("POST data: " . print_r($_POST, true));

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "foodpanda_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Get POST data
$cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$user_id = $_SESSION['user_id'];

error_log("Parsed values - cart_id: $cart_id, quantity: $quantity, user_id: $user_id");

if ($cart_id <= 0) {
    error_log("Invalid cart ID: $cart_id");
    echo json_encode(['success' => false, 'message' => 'Invalid cart ID']);
    exit();
}

// If quantity is 0 or negative, delete the item
if ($quantity <= 0) {
    error_log("Deleting cart item: $cart_id");
    
    $deleteQuery = "DELETE FROM cart WHERE Cart_ID = ? AND User_ID = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("ii", $cart_id, $user_id);
    
    if ($stmt->execute()) {
        error_log("Item deleted successfully");
        echo json_encode([
            'success' => true, 
            'message' => 'Item removed from cart', 
            'action' => 'deleted'
        ]);
    } else {
        error_log("Failed to delete item: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
    }
    
    $stmt->close();
    $conn->close();
    exit();
}

// Verify the cart item belongs to the user and get item details
$verifyQuery = "SELECT c.*, i.Stock, i.Item_Name FROM cart c 
                JOIN item i ON c.Item_ID = i.Item_ID 
                WHERE c.Cart_ID = ? AND c.User_ID = ?";
$stmt = $conn->prepare($verifyQuery);
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("Cart item not found or doesn't belong to user");
    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
    $stmt->close();
    $conn->close();
    exit();
}

$cartItem = $result->fetch_assoc();
error_log("Cart item found: " . print_r($cartItem, true));
$stmt->close();

// Check stock availability
if ($quantity > $cartItem['Stock']) {
    error_log("Insufficient stock. Requested: $quantity, Available: " . $cartItem['Stock']);
    echo json_encode([
        'success' => false, 
        'message' => 'Not enough stock available. Only ' . $cartItem['Stock'] . ' left.'
    ]);
    $conn->close();
    exit();
}

// Update quantity
$updateQuery = "UPDATE cart SET Quantity = ? WHERE Cart_ID = ? AND User_ID = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("iii", $quantity, $cart_id, $user_id);

if ($updateStmt->execute()) {
    error_log("Cart updated successfully to quantity: $quantity");
    echo json_encode([
        'success' => true, 
        'message' => 'Cart updated', 
        'new_quantity' => $quantity,
        'item_name' => $cartItem['Item_Name']
    ]);
} else {
    error_log("Failed to update cart: " . $updateStmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
}

$updateStmt->close();
$conn->close();

error_log("=== UPDATE CART REQUEST COMPLETE ===");
?>