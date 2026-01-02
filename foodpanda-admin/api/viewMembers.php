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
    if ($action === 'getMembers') {
        try {
            $sql = "SELECT 
                    u.User_ID as id,
                    u.Name as name,
                    u.Email as email,
                    u.PhoneNo as phone,
                    u.Address as address,
                    u.Profile_Picture as avatar,
                    u.Created_At as joinDate,
                    'customer' as type,
                    'active' as status,
                    COALESCE(COUNT(DISTINCT o.Order_ID), 0) as totalOrders,
                    COALESCE(a.City, 'N/A') as city,
                    COALESCE(a.Postcode, 'N/A') as postal
                    FROM user u
                    LEFT JOIN `order` o ON u.User_ID = o.User_ID
                    LEFT JOIN address a ON u.User_ID = a.User_ID AND a.Is_Default = 1
                    GROUP BY u.User_ID, u.Name, u.Email, u.PhoneNo, u.Address, u.Profile_Picture, u.Created_At, a.City, a.Postcode
                    ORDER BY u.User_ID";
            
            $stmt = $pdo->query($sql);
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format data for frontend
            foreach ($members as &$member) {
                $member['id'] = intval($member['id']);
                $member['totalOrders'] = intval($member['totalOrders']);
                $member['joinDate'] = date('Y-m-d', strtotime($member['joinDate']));
                
                // Set default avatar if none exists
                if (empty($member['avatar']) || !file_exists('../' . $member['avatar'])) {
                    $member['avatar'] = 'https://i.pravatar.cc/150?img=' . ($member['id'] % 70);
                } else {
                    // Convert relative path to full URL
                    $member['avatar'] = 'http://localhost/foodpanda-admin/' . $member['avatar'];
                }
                
                // Use address from user table if no default address
                if ($member['city'] === 'N/A' && !empty($member['address'])) {
                    // Try to extract city from address
                    $addressParts = explode(',', $member['address']);
                    if (count($addressParts) > 1) {
                        $member['city'] = trim($addressParts[count($addressParts) - 1]);
                    }
                }
            }
            
            echo json_encode(['success' => true, 'members' => $members]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    if ($action === 'getMemberById') {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        
        if ($id) {
            try {
                $sql = "SELECT 
                        u.User_ID as id,
                        u.Name as name,
                        u.Email as email,
                        u.PhoneNo as phone,
                        u.Address as address,
                        u.Profile_Picture as avatar,
                        u.Created_At as joinDate,
                        'customer' as type,
                        'active' as status,
                        COALESCE(COUNT(DISTINCT o.Order_ID), 0) as totalOrders,
                        COALESCE(a.City, 'N/A') as city,
                        COALESCE(a.Postcode, 'N/A') as postal,
                        COALESCE(a.Street, u.Address) as street
                        FROM user u
                        LEFT JOIN `order` o ON u.User_ID = o.User_ID
                        LEFT JOIN address a ON u.User_ID = a.User_ID AND a.Is_Default = 1
                        WHERE u.User_ID = :id
                        GROUP BY u.User_ID";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $id]);
                $member = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($member) {
                    $member['id'] = intval($member['id']);
                    $member['totalOrders'] = intval($member['totalOrders']);
                    $member['joinDate'] = date('Y-m-d', strtotime($member['joinDate']));
                    
                    if (empty($member['avatar']) || !file_exists('../' . $member['avatar'])) {
                        $member['avatar'] = 'https://i.pravatar.cc/150?img=' . ($member['id'] % 70);
                    } else {
                        $member['avatar'] = 'http://localhost/foodpanda-admin/' . $member['avatar'];
                    }
                    
                    echo json_encode(['success' => true, 'member' => $member]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Member not found']);
                }
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Member ID is required']);
        }
    }
}

// POST operations (Create)
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'addMember') {
        try {
            // Generate a random username from email
            $username = explode('@', $data['email'])[0] . rand(100, 999);
            
            // Generate a default password (in production, this should be more secure)
            $defaultPassword = 'User@123';
            
            // Insert into user table
            $sql = "INSERT INTO user (Username, Name, Email, Password, PhoneNo, Address) 
                    VALUES (:username, :name, :email, :password, :phone, :address)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':password' => $defaultPassword,
                ':phone' => $data['phone'],
                ':address' => $data['address']
            ]);
            
            $newId = $pdo->lastInsertId();
            
            // Insert address if city and postal are provided
            if (!empty($data['city']) && !empty($data['postal'])) {
                $addressSql = "INSERT INTO address (User_ID, Label, Street, City, Postcode, Is_Default) 
                              VALUES (:user_id, 'Home', :street, :city, :postal, 1)";
                
                $addressStmt = $pdo->prepare($addressSql);
                $addressStmt->execute([
                    ':user_id' => $newId,
                    ':street' => $data['address'],
                    ':city' => $data['city'],
                    ':postal' => $data['postal']
                ]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Member added successfully', 'id' => $newId]);
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'message' => 'Email or username already exists']);
            } else {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }
}

// PUT operations (Update)
if ($method === 'PUT' || ($method === 'POST' && $action === 'updateMember')) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'updateMember') {
        try {
            // Update user table
            $sql = "UPDATE user 
                    SET Name = :name, Email = :email, PhoneNo = :phone, Address = :address
                    WHERE User_ID = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':phone' => $data['phone'],
                ':address' => $data['address'],
                ':id' => $data['id']
            ]);
            
            // Update or insert address
            if (!empty($data['city']) && !empty($data['postal'])) {
                // Check if address exists
                $checkSql = "SELECT Address_ID FROM address WHERE User_ID = :user_id AND Is_Default = 1";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([':user_id' => $data['id']]);
                $existingAddress = $checkStmt->fetch();
                
                if ($existingAddress) {
                    // Update existing address
                    $addressSql = "UPDATE address 
                                  SET Street = :street, City = :city, Postcode = :postal
                                  WHERE User_ID = :user_id AND Is_Default = 1";
                } else {
                    // Insert new address
                    $addressSql = "INSERT INTO address (User_ID, Label, Street, City, Postcode, Is_Default) 
                                  VALUES (:user_id, 'Home', :street, :city, :postal, 1)";
                }
                
                $addressStmt = $pdo->prepare($addressSql);
                $addressStmt->execute([
                    ':user_id' => $data['id'],
                    ':street' => $data['address'],
                    ':city' => $data['city'],
                    ':postal' => $data['postal']
                ]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Member updated successfully']);
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
            } else {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }
}

// DELETE operations
if ($method === 'DELETE' || ($method === 'POST' && $action === 'deleteMember')) {
    if ($action === 'deleteMember') {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        
        if (!$id) {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = isset($data['id']) ? $data['id'] : null;
        }
        
        if ($id) {
            try {
                // Delete user (cascading will handle related records)
                $sql = "DELETE FROM user WHERE User_ID = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $id]);
                
                echo json_encode(['success' => true, 'message' => 'Member deleted successfully']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Member ID is required']);
        }
    }
}

?>