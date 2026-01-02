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
    
    // Generate Report
    if ($action === 'generateReport') {
        try {
            $reportType = isset($_GET['reportType']) ? $_GET['reportType'] : 'sales';
            $timeFilter = isset($_GET['timeFilter']) ? $_GET['timeFilter'] : 'all';
            $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
            $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
            $status = isset($_GET['status']) ? $_GET['status'] : 'all';
            
            // Calculate date range based on time filter
            $today = date('Y-m-d');
            
            switch($timeFilter) {
                case 'daily':
                    $startDate = $today;
                    $endDate = $today;
                    break;
                case 'weekly':
                    $startDate = date('Y-m-d', strtotime('monday this week'));
                    $endDate = date('Y-m-d', strtotime('sunday this week'));
                    break;
                case 'monthly':
                    $startDate = date('Y-m-01');
                    $endDate = date('Y-m-t');
                    break;
                case 'custom':
                    // Use provided dates
                    if (!$startDate) $startDate = date('Y-m-d', strtotime('-30 days'));
                    if (!$endDate) $endDate = $today;
                    break;
                case 'all':
                default:
                    // No date restriction
                    break;
            }
            
            // Base SQL for sales report
            $sql = "SELECT 
                    o.Order_Date as date,
                    o.Order_ID as orderId,
                    u.Name as customer,
                    o.Total_Price as amount,
                    p.Payment_Method as paymentMethod,
                    CASE 
                        WHEN p.Status = 'Completed' THEN 'paid'
                        WHEN p.Status = 'Pending' THEN 'pending'
                        WHEN p.Status = 'Failed' THEN 'failed'
                        ELSE 'pending'
                    END as paymentStatus,
                    CASE 
                        WHEN o.Status = 'Delivered' THEN 'delivered'
                        WHEN o.Status = 'Pending' THEN 'pending'
                        WHEN o.Status = 'Confirmed' THEN 'processing'
                        WHEN o.Status = 'Preparing' THEN 'processing'
                        WHEN o.Status = 'Out for Delivery' THEN 'processing'
                        ELSE 'pending'
                    END as deliveryStatus
                    FROM `order` o
                    JOIN user u ON o.User_ID = u.User_ID
                    LEFT JOIN payment p ON o.Payment_ID = p.Payment_ID
                    WHERE 1=1";
            
            $params = [];
            
            // Add date filters
            if ($startDate && $timeFilter !== 'all') {
                $sql .= " AND DATE(o.Order_Date) >= :startDate";
                $params[':startDate'] = $startDate;
            }
            
            if ($endDate && $timeFilter !== 'all') {
                $sql .= " AND DATE(o.Order_Date) <= :endDate";
                $params[':endDate'] = $endDate;
            }
            
            // Add status filter
            if ($status !== 'all') {
                if ($status === 'paid') {
                    $sql .= " AND p.Status = 'Completed'";
                } elseif ($status === 'pending') {
                    $sql .= " AND (p.Status = 'Pending' OR o.Status = 'Pending')";
                } elseif ($status === 'delivered') {
                    $sql .= " AND o.Status = 'Delivered'";
                } elseif ($status === 'processing') {
                    $sql .= " AND o.Status IN ('Confirmed', 'Preparing', 'Out for Delivery')";
                }
            }
            
            $sql .= " ORDER BY o.Order_Date DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format data
            foreach ($orders as &$order) {
                $order['orderId'] = '#ORD-' . $order['orderId'];
                $order['amount'] = floatval($order['amount']);
                $order['items'] = 0; // Placeholder - would need order_items table
            }
            
            // Calculate statistics
            $totalRevenue = array_sum(array_column($orders, 'amount'));
            $totalOrders = count($orders);
            $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
            $uniqueCustomers = count(array_unique(array_column($orders, 'customer')));
            
            // Calculate completed transactions (paid orders)
            $completedTransactions = count(array_filter($orders, fn($o) => $o['paymentStatus'] === 'paid'));
            $completedRevenue = array_sum(array_map(fn($o) => $o['paymentStatus'] === 'paid' ? $o['amount'] : 0, $orders));
            
            // Calculate pending transactions
            $pendingTransactions = count(array_filter($orders, fn($o) => $o['paymentStatus'] === 'pending'));
            $pendingAmount = array_sum(array_map(fn($o) => $o['paymentStatus'] === 'pending' ? $o['amount'] : 0, $orders));
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'orders' => $orders,
                'stats' => [
                    'totalRevenue' => $totalRevenue,
                    'totalOrders' => $totalOrders,
                    'avgOrderValue' => $avgOrderValue,
                    'customers' => $uniqueCustomers,
                    'completedTransactions' => $completedTransactions,
                    'completedRevenue' => $completedRevenue,
                    'pendingTransactions' => $pendingTransactions,
                    'pendingAmount' => $pendingAmount
                ],
                'dateRange' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'filter' => $timeFilter
                ]
            ]);
        } catch(PDOException $e) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Get Inventory Report
    if ($action === 'getInventoryReport') {
        try {
            $sql = "SELECT 
                    i.Item_ID as id,
                    i.Item_Name as name,
                    c.Category_Name as category,
                    i.Stock as stock,
                    i.Price as price,
                    CASE 
                        WHEN i.Stock = 0 THEN 'Out of Stock'
                        WHEN i.Stock <= 10 THEN 'Low Stock'
                        ELSE 'In Stock'
                    END as stockStatus,
                    i.Is_Available as isAvailable
                    FROM item i
                    JOIN category c ON i.Category_ID = c.Category_ID
                    ORDER BY i.Stock ASC";
            
            $stmt = $pdo->query($sql);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format data
            foreach ($items as &$item) {
                $item['stock'] = intval($item['stock']);
                $item['price'] = floatval($item['price']);
                $item['isAvailable'] = intval($item['isAvailable']) === 1;
            }
            
            // Calculate stats
            $totalItems = count($items);
            $outOfStock = count(array_filter($items, fn($i) => $i['stock'] === 0));
            $lowStock = count(array_filter($items, fn($i) => $i['stock'] > 0 && $i['stock'] <= 10));
            $totalValue = array_sum(array_map(fn($i) => $i['price'] * $i['stock'], $items));
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'items' => $items,
                'stats' => [
                    'totalItems' => $totalItems,
                    'outOfStock' => $outOfStock,
                    'lowStock' => $lowStock,
                    'totalValue' => $totalValue
                ]
            ]);
        } catch(PDOException $e) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Get Customer Report
    if ($action === 'getCustomerReport') {
        try {
            $sql = "SELECT 
                    u.User_ID as id,
                    u.Name as name,
                    u.Email as email,
                    u.PhoneNo as phone,
                    COUNT(DISTINCT o.Order_ID) as totalOrders,
                    COALESCE(SUM(o.Total_Price), 0) as totalSpent,
                    MAX(o.Order_Date) as lastOrder,
                    u.Created_At as joinDate
                    FROM user u
                    LEFT JOIN `order` o ON u.User_ID = o.User_ID
                    GROUP BY u.User_ID
                    ORDER BY totalSpent DESC";
            
            $stmt = $pdo->query($sql);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format data
            foreach ($customers as &$customer) {
                $customer['id'] = intval($customer['id']);
                $customer['totalOrders'] = intval($customer['totalOrders']);
                $customer['totalSpent'] = floatval($customer['totalSpent']);
            }
            
            // Calculate stats
            $totalCustomers = count($customers);
            $activeCustomers = count(array_filter($customers, fn($c) => $c['totalOrders'] > 0));
            $totalRevenue = array_sum(array_column($customers, 'totalSpent'));
            $avgSpent = $activeCustomers > 0 ? $totalRevenue / $activeCustomers : 0;
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'customers' => $customers,
                'stats' => [
                    'totalCustomers' => $totalCustomers,
                    'activeCustomers' => $activeCustomers,
                    'totalRevenue' => $totalRevenue,
                    'avgSpent' => $avgSpent
                ]
            ]);
        } catch(PDOException $e) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Get Financial Report
    if ($action === 'getFinancialReport') {
        try {
            $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
            $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d');
            
            $sql = "SELECT 
                    DATE(o.Order_Date) as date,
                    COUNT(o.Order_ID) as orderCount,
                    COALESCE(SUM(CASE WHEN p.Status = 'Completed' THEN o.Total_Price ELSE 0 END), 0) as revenue,
                    COALESCE(SUM(CASE WHEN p.Status = 'Pending' THEN o.Total_Price ELSE 0 END), 0) as pending,
                    COALESCE(SUM(CASE WHEN p.Status = 'Failed' THEN o.Total_Price ELSE 0 END), 0) as failed
                    FROM `order` o
                    LEFT JOIN payment p ON o.Payment_ID = p.Payment_ID
                    WHERE DATE(o.Order_Date) BETWEEN :startDate AND :endDate
                    GROUP BY DATE(o.Order_Date)
                    ORDER BY DATE(o.Order_Date) DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':startDate' => $startDate, ':endDate' => $endDate]);
            $financials = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format data
            foreach ($financials as &$record) {
                $record['orderCount'] = intval($record['orderCount']);
                $record['revenue'] = floatval($record['revenue']);
                $record['pending'] = floatval($record['pending']);
                $record['failed'] = floatval($record['failed']);
            }
            
            // Calculate totals
            $totalRevenue = array_sum(array_column($financials, 'revenue'));
            $totalPending = array_sum(array_column($financials, 'pending'));
            $totalFailed = array_sum(array_column($financials, 'failed'));
            $totalOrders = array_sum(array_column($financials, 'orderCount'));
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'financials' => $financials,
                'stats' => [
                    'totalRevenue' => $totalRevenue,
                    'totalPending' => $totalPending,
                    'totalFailed' => $totalFailed,
                    'totalOrders' => $totalOrders
                ]
            ]);
        } catch(PDOException $e) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// Clear any remaining output buffer
ob_end_flush();
?>