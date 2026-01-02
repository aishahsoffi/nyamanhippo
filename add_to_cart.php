<?php
session_start();
header('Content-Type: application/json');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "foodpanda_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Get POST data
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$user_id = $_SESSION['user_id'];

if ($item_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item or quantity']);
    exit();
}

// Check if item exists and has stock (matching your database structure)
$itemQuery = "SELECT * FROM item WHERE Item_ID = ? AND Stock > 0";
$stmt = $conn->prepare($itemQuery);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Item not available']);
    $stmt->close();
    $conn->close();
    exit();
}

$item = $result->fetch_assoc();
$stmt->close();

// Check if item already in cart (matching your database structure)
$checkQuery = "SELECT * FROM cart WHERE User_ID = ? AND Item_ID = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("ii", $user_id, $item_id);
$stmt->execute();
$cartResult = $stmt->get_result();

if ($cartResult->num_rows > 0) {
    // Update quantity
    $cartItem = $cartResult->fetch_assoc();
    $newQuantity = $cartItem['Quantity'] + $quantity;
    
    // Check stock
    if ($newQuantity > $item['Stock']) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
        $stmt->close();
        $conn->close();
        exit();
    }
    
    $updateQuery = "UPDATE cart SET Quantity = ? WHERE User_ID = ? AND Item_ID = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("iii", $newQuantity, $user_id, $item_id);
    $updateStmt->execute();
    $updateStmt->close();
} else {
    // Insert new cart item
    if ($quantity > $item['Stock']) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
        $stmt->close();
        $conn->close();
        exit();
    }
    
    $insertQuery = "INSERT INTO cart (User_ID, Item_ID, Quantity) VALUES (?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("iii", $user_id, $item_id, $quantity);
    $insertStmt->execute();
    $insertStmt->close();
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'message' => 'Item added to cart']);
?>