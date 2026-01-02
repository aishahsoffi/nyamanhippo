<?php
// Suppress any output before headers
ob_start();

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
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// GET operations
if ($method === 'GET') {
    if ($action === 'getOrders') {
        try {
            $sql = "SELECT 
                    o.Order_ID as id,
                    u.Name as customer,
                    CONCAT('CUST-', LPAD(u.User_ID, 3, '0')) as customerId,
                    u.Email as email,
                    u.PhoneNo as phone,
                    o.Total_Price as total,
                    o.Status as orderStatus,
                    o.Order_Date as orderDate,
                    p.Payment_Method as paymentMethod,
                    p.Status as paymentStatus,
                    COALESCE(a.Street, u.Address) as deliveryAddress,
                    COALESCE(a.City, '') as city,
                    COALESCE(a.Postcode, '') as postcode
                    FROM `order` o
                    JOIN user u ON o.User_ID = u.User_ID
                    LEFT JOIN payment p ON o.Payment_ID = p.Payment_ID
                    LEFT JOIN address a ON u.User_ID = a.User_ID AND a.Is_Default = 1
                    ORDER BY o.Order_Date DESC";
            
            $stmt = $pdo->query($sql);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format data and get items for each order
            foreach ($orders as &$order) {
                $order['id'] = intval($order['id']);
                $order['total'] = floatval($order['total']);
                
                // Format delivery address
                if (!empty($order['city']) && !empty($order['postcode'])) {
                    $order['deliveryAddress'] .= ', ' . $order['city'] . ', ' . $order['postcode'];
                }
                
                // Convert status to lowercase with hyphens
                $order['orderStatus'] = strtolower(str_replace(' ', '-', $order['orderStatus']));
                $order['paymentStatus'] = strtolower($order['paymentStatus'] ?? 'pending');
                
                // Get order items (Note: You'll need an order_items table for this)
                // For now, using placeholder data
                $order['items'] = [];
                $order['subtotal'] = $order['total'] * 0.85; // Estimate
                $order['deliveryFee'] = 5.00;
                $order['tax'] = $order['total'] - $order['subtotal'] - $order['deliveryFee'];
                $order['notes'] = '';
            }
            
            ob_end_clean();
            echo json_encode(['success' => true, 'orders' => $orders]);
        } catch(PDOException $e) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    if ($action === 'getOrderById') {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        
        if ($id) {
            try {
                $sql = "SELECT 
                        o.Order_ID as id,
                        u.Name as customer,
                        CONCAT('CUST-', LPAD(u.User_ID, 3, '0')) as customerId,
                        u.Email as email,
                        u.PhoneNo as phone,
                        o.Total_Price as total,
                        o.Status as orderStatus,
                        o.Order_Date as orderDate,
                        p.Payment_Method as paymentMethod,
                        p.Status as paymentStatus,
                        COALESCE(a.Street, u.Address) as deliveryAddress,
                        COALESCE(a.City, '') as city,
                        COALESCE(a.Postcode, '') as postcode
                        FROM `order` o
                        JOIN user u ON o.User_ID = u.User_ID
                        LEFT JOIN payment p ON o.Payment_ID = p.Payment_ID
                        LEFT JOIN address a ON u.User_ID = a.User_ID AND a.Is_Default = 1
                        WHERE o.Order_ID = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $id]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($order) {
                    $order['id'] = intval($order['id']);
                    $order['total'] = floatval($order['total']);
                    
                    if (!empty($order['city']) && !empty($order['postcode'])) {
                        $order['deliveryAddress'] .= ', ' . $order['city'] . ', ' . $order['postcode'];
                    }
                    
                    $order['orderStatus'] = strtolower(str_replace(' ', '-', $order['orderStatus']));
                    $order['paymentStatus'] = strtolower($order['paymentStatus'] ?? 'pending');
                    
                    $order['items'] = [];
                    $order['subtotal'] = $order['total'] * 0.85;
                    $order['deliveryFee'] = 5.00;
                    $order['tax'] = $order['total'] - $order['subtotal'] - $order['deliveryFee'];
                    $order['notes'] = '';
                    
                    ob_end_clean();
                    echo json_encode(['success' => true, 'order' => $order]);
                } else {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Order not found']);
                }
            } catch(PDOException $e) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Order ID is required']);
        }
    }
}

// POST operations (Update Status)
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'updateStatus') {
        try {
            // Map frontend status format to database format
            $statusMap = [
                'pending' => 'Pending',
                'confirmed' => 'Confirmed',
                'preparing' => 'Preparing',
                'out-for-delivery' => 'Out for Delivery',
                'delivered' => 'Delivered',
                'cancelled' => 'Cancelled'
            ];
            
            $dbStatus = isset($statusMap[$data['status']]) ? $statusMap[$data['status']] : 'Pending';
            
            $sql = "UPDATE `order` 
                    SET Status = :status
                    WHERE Order_ID = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':status' => $dbStatus,
                ':id' => $data['id']
            ]);
            
            // Update payment status if order is delivered
            if ($data['status'] === 'delivered') {
                $paymentSql = "UPDATE payment p
                              JOIN `order` o ON p.Order_ID = o.Order_ID
                              SET p.Status = 'Completed'
                              WHERE o.Order_ID = :id";
                $paymentStmt = $pdo->prepare($paymentSql);
                $paymentStmt->execute([':id' => $data['id']]);
            }
            
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
        } catch(PDOException $e) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// Clear any remaining output buffer
ob_end_flush();
?>