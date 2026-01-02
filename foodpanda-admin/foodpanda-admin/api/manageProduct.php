<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$host = 'localhost';
$dbname = 'foodpanda_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// GET operations
if ($method === 'GET') {
    if ($action === 'getProducts') {
        try {
            $sql = "SELECT i.Item_ID as id, i.Item_Name as name, c.Category_Name as category, 
                    i.Price as price, i.Stock as stock, i.Image as image, i.Description as description,
                    CASE WHEN i.Is_Available = 1 THEN 'available' ELSE 'unavailable' END as status
                    FROM item i
                    JOIN category c ON i.Category_ID = c.Category_ID
                    ORDER BY i.Item_ID";
            
            $stmt = $pdo->query($sql);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert category names to lowercase to match frontend
            foreach ($products as &$product) {
                $product['category'] = strtolower($product['category']);
                $product['price'] = floatval($product['price']);
                $product['stock'] = intval($product['stock']);
                $product['id'] = intval($product['id']);
            }
            
            echo json_encode(['success' => true, 'products' => $products]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    if ($action === 'getCategories') {
        try {
            $sql = "SELECT Category_ID as id, Category_Name as name FROM category";
            $stmt = $pdo->query($sql);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'categories' => $categories]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// POST operations (Create)
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'addProduct') {
        try {
            // Get category ID from category name
            $categoryMap = [
                'burgers' => 1,
                'pizza' => 2,
                'chicken' => 3,
                'desserts' => 4,
                'beverages' => 5,
                'sides' => 6
            ];
            
            $categoryId = isset($categoryMap[$data['category']]) ? $categoryMap[$data['category']] : 1;
            $isAvailable = ($data['status'] === 'available') ? 1 : 0;
            
            $sql = "INSERT INTO item (Item_Name, Category_ID, Price, Stock, Description, Image, Is_Available) 
                    VALUES (:name, :category_id, :price, :stock, :description, :image, :is_available)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':category_id' => $categoryId,
                ':price' => $data['price'],
                ':stock' => $data['stock'],
                ':description' => $data['description'],
                ':image' => $data['image'],
                ':is_available' => $isAvailable
            ]);
            
            $newId = $pdo->lastInsertId();
            
            echo json_encode(['success' => true, 'message' => 'Product added successfully', 'id' => $newId]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// PUT operations (Update)
if ($method === 'PUT' || ($method === 'POST' && $action === 'updateProduct')) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'updateProduct') {
        try {
            // Get category ID from category name
            $categoryMap = [
                'burgers' => 1,
                'pizza' => 2,
                'chicken' => 3,
                'desserts' => 4,
                'beverages' => 5,
                'sides' => 6
            ];
            
            $categoryId = isset($categoryMap[$data['category']]) ? $categoryMap[$data['category']] : 1;
            $isAvailable = ($data['status'] === 'available') ? 1 : 0;
            
            $sql = "UPDATE item 
                    SET Item_Name = :name, Category_ID = :category_id, Price = :price, 
                        Stock = :stock, Description = :description, Image = :image, 
                        Is_Available = :is_available
                    WHERE Item_ID = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':category_id' => $categoryId,
                ':price' => $data['price'],
                ':stock' => $data['stock'],
                ':description' => $data['description'],
                ':image' => $data['image'],
                ':is_available' => $isAvailable,
                ':id' => $data['id']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// DELETE operations
if ($method === 'DELETE' || ($method === 'POST' && $action === 'deleteProduct')) {
    if ($action === 'deleteProduct') {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        
        if (!$id) {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = isset($data['id']) ? $data['id'] : null;
        }
        
        if ($id) {
            try {
                $sql = "DELETE FROM item WHERE Item_ID = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $id]);
                
                echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        }
    }
}

?>