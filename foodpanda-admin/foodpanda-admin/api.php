<?php
// api.php - FIXED VERSION
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include 'database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// Handle different operations
if ($method === 'GET') {
    handleGet();
} elseif ($method === 'POST') {
    handlePost();
} elseif ($method === 'PUT') {
    handlePut();
} elseif ($method === 'DELETE') {
    handleDelete();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}

function handleGet() {
    global $conn;
    
    $sql = "SELECT 
                i.Item_ID as id,
                i.Item_Name as name,
                c.Category_Name as category,
                i.Price as price,
                i.Stock as stock,
                i.Image as image,
                i.Description as description,
                CASE 
                    WHEN i.Is_Available = 1 THEN 'available'
                    ELSE 'unavailable'
                END as status
            FROM item i
            LEFT JOIN category c ON i.Category_ID = c.Category_ID
            ORDER BY i.Item_ID DESC";
    
    $result = $conn->query($sql);
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    echo json_encode(["success" => true, "data" => $products]);
}

function handlePost() {
    global $conn;
    
    // Get POST data
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $description = $_POST['description'] ?? '';
    $image = $_POST['image'] ?? '';
    $status = $_POST['status'] ?? 'available';
    
    // Map category name to ID
    $categoryMap = [
        'burgers' => 1,
        'pizza' => 2,
        'chicken' => 3,
        'desserts' => 4,
        'beverages' => 5,
        'sides' => 6
    ];
    
    $categoryId = $categoryMap[strtolower($category)] ?? 1;
    
    // Escape strings
    $name = $conn->real_escape_string($name);
    $price = floatval($price);
    $stock = intval($stock);
    $description = $conn->real_escape_string($description);
    $image = $conn->real_escape_string($image);
    $isAvailable = ($status === 'available') ? 1 : 0;
    
    // Insert into database
    $sql = "INSERT INTO item (Item_Name, Category_ID, Price, Description, Stock, Image, Is_Available) 
            VALUES ('$name', '$categoryId', '$price', '$description', '$stock', '$image', '$isAvailable')";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true, "message" => "Product added successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
    }
}

function handlePut() {
    global $conn;
    
    // Get raw input for PUT
    $rawData = file_get_contents("php://input");
    $data = [];
    
    // Try to parse as JSON first
    $jsonData = json_decode($rawData, true);
    if ($jsonData !== null) {
        $data = $jsonData;
    } else {
        // If not JSON, parse as form data
        parse_str($rawData, $data);
    }
    
    $id = intval($data['id'] ?? 0);
    $name = $data['name'] ?? '';
    $category = $data['category'] ?? '';
    $price = $data['price'] ?? 0;
    $stock = $data['stock'] ?? 0;
    $description = $data['description'] ?? '';
    $image = $data['image'] ?? '';
    $status = $data['status'] ?? 'available';
    
    // Map category name to ID
    $categoryMap = [
        'burgers' => 1,
        'pizza' => 2,
        'chicken' => 3,
        'desserts' => 4,
        'beverages' => 5,
        'sides' => 6
    ];
    
    $categoryId = $categoryMap[strtolower($category)] ?? 1;
    
    // Escape strings
    $name = $conn->real_escape_string($name);
    $price = floatval($price);
    $stock = intval($stock);
    $description = $conn->real_escape_string($description);
    $image = $conn->real_escape_string($image);
    $isAvailable = ($status === 'available') ? 1 : 0;
    
    // Update in database
    $sql = "UPDATE item SET 
            Item_Name = '$name',
            Category_ID = '$categoryId',
            Price = '$price',
            Description = '$description',
            Stock = '$stock',
            Image = '$image',
            Is_Available = '$isAvailable'
            WHERE Item_ID = $id";
    
    if ($conn->query($sql) === TRUE) {
        if ($conn->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "Product updated successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "No product found with ID $id"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
    }
}

function handleDelete() {
    global $conn;
    
    // Get raw input for DELETE
    $rawData = file_get_contents("php://input");
    $data = [];
    
    // Try to parse as JSON first
    $jsonData = json_decode($rawData, true);
    if ($jsonData !== null) {
        $data = $jsonData;
    } else {
        // If not JSON, parse as form data
        parse_str($rawData, $data);
    }
    
    $id = intval($data['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid product ID"]);
        return;
    }
    
    echo "DEBUG: Attempting to delete product ID: $id<br>"; // Debug line
    
    // First check if product exists
    $checkSql = "SELECT Item_ID FROM item WHERE Item_ID = $id";
    $result = $conn->query($checkSql);
    
    if ($result->num_rows == 0) {
        echo json_encode(["success" => false, "message" => "Product not found"]);
        return;
    }
    
    // Delete from database
    $sql = "DELETE FROM item WHERE Item_ID = $id";
    
    echo "DEBUG: SQL: $sql<br>"; // Debug line
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true, "message" => "Product deleted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
    }
}

$conn->close();
?>