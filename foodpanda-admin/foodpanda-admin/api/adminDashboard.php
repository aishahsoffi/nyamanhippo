<?php
// Suppress any output before headers
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON output
ini_set('log_errors', 1);

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
    
    // Get Dashboard Statistics
    if ($action === 'getStats') {
        try {
            // Total Revenue (from completed/paid orders)
            $revenueSql = "SELECT COALESCE(SUM(o.Total_Price), 0) as totalRevenue
                          FROM `order` o
                          LEFT JOIN payment p ON o.Payment_ID = p.Payment_ID
                          WHERE p.Status IN ('Completed', 'completed')";
            $revenueStmt = $pdo->query($revenueSql);
            $revenueResult = $revenueStmt->fetch(PDO::FETCH_ASSOC);
            $totalRevenue = floatval($revenueResult['totalRevenue']);
            
            // Total Orders
            $ordersSql = "SELECT COUNT(*) as totalOrders FROM `order`";
            $ordersStmt = $pdo->query($ordersSql);
            $ordersResult = $ordersStmt->fetch(PDO::FETCH_ASSOC);
            $totalOrders = intval($ordersResult['totalOrders']);
            
            // Active Members (users who have placed at least one order)
            $membersSql = "SELECT COUNT(DISTINCT u.User_ID) as activeMembers
                          FROM user u
                          JOIN `order` o ON u.User_ID = o.User_ID";
            $membersStmt = $pdo->query($membersSql);
            $membersResult = $membersStmt->fetch(PDO::FETCH_ASSOC);
            $activeMembers = intval($membersResult['activeMembers']);
            
            // Calculate percentage changes (comparing to last month)
            $lastMonthStart = date('Y-m-d', strtotime('-1 month', strtotime('first day of this month')));
            $lastMonthEnd = date('Y-m-d', strtotime('last day of last month'));
            $thisMonthStart = date('Y-m-d', strtotime('first day of this month'));
            
            // Last month revenue
            $lastMonthRevenueSql = "SELECT COALESCE(SUM(o.Total_Price), 0) as lastMonthRevenue
                                   FROM `order` o
                                   LEFT JOIN payment p ON o.Payment_ID = p.Payment_ID
                                   WHERE p.Status IN ('Completed', 'completed')
                                   AND DATE(o.Order_Date) BETWEEN :start AND :end";
            $lastMonthRevenueStmt = $pdo->prepare($lastMonthRevenueSql);
            $lastMonthRevenueStmt->execute([':start' => $lastMonthStart, ':end' => $lastMonthEnd]);
            $lastMonthRevenueResult = $lastMonthRevenueStmt->fetch(PDO::FETCH_ASSOC);
            $lastMonthRevenue = floatval($lastMonthRevenueResult['lastMonthRevenue']);
            
            // This month revenue
            $thisMonthRevenueSql = "SELECT COALESCE(SUM(o.Total_Price), 0) as thisMonthRevenue
                                   FROM `order` o
                                   LEFT JOIN payment p ON o.Payment_ID = p.Payment_ID
                                   WHERE p.Status IN ('Completed', 'completed')
                                   AND DATE(o.Order_Date) >= :start";
            $thisMonthRevenueStmt = $pdo->prepare($thisMonthRevenueSql);
            $thisMonthRevenueStmt->execute([':start' => $thisMonthStart]);
            $thisMonthRevenueResult = $thisMonthRevenueStmt->fetch(PDO::FETCH_ASSOC);
            $thisMonthRevenue = floatval($thisMonthRevenueResult['thisMonthRevenue']);
            
            // Calculate percentage changes
            $revenueChange = $lastMonthRevenue > 0 ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;
            
            // Similar calculations for orders and members
            $ordersChange = 8.1; // Placeholder - you can calculate this similarly
            $membersChange = 16.3; // Placeholder - you can calculate this similarly
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'stats' => [
                    'totalRevenue' => $totalRevenue,
                    'totalOrders' => $totalOrders,
                    'activeMembers' => $activeMembers,
                    'revenueChange' => round($revenueChange, 1),
                    'ordersChange' => $ordersChange,
                    'membersChange' => $membersChange
                ]
            ]);
            exit;
        } catch(PDOException $e) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Error loading stats: ' . $e->getMessage()]);
            exit;
        }
    }
    
    // Get Sales Data for Chart (weekly)
    if ($action === 'getSalesData') {
        try {
            $weekStart = isset($_GET['weekStart']) ? $_GET['weekStart'] : date('Y-m-d', strtotime('monday this week'));
            
            // Get sales for each day of the week
            $salesSql = "SELECT 
                        DAYNAME(o.Order_Date) as dayName,
                        DATE(o.Order_Date) as orderDate,
                        COALESCE(SUM(o.Total_Price), 0) as dailySales
                        FROM `order` o
                        LEFT JOIN payment p ON o.Payment_ID = p.Payment_ID
                        WHERE DATE(o.Order_Date) >= :weekStart
                        AND DATE(o.Order_Date) < DATE_ADD(:weekStart, INTERVAL 7 DAY)
                        AND p.Status IN ('Completed', 'completed')
                        GROUP BY DATE(o.Order_Date), DAYNAME(o.Order_Date)
                        ORDER BY DATE(o.Order_Date)";
            
            $salesStmt = $pdo->prepare($salesSql);
            $salesStmt->execute([':weekStart' => $weekStart]);
            $salesResults = $salesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create array with all days of week
            $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $salesData = [];
            
            // Initialize all days with 0
            foreach ($daysOfWeek as $day) {
                $shortDay = substr($day, 0, 3);
                $salesData[$shortDay] = 0;
            }
            
            // Fill in actual sales data
            foreach ($salesResults as $result) {
                $shortDay = substr($result['dayName'], 0, 3);
                $salesData[$shortDay] = floatval($result['dailySales']);
            }
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'weekStart' => $weekStart,
                'salesData' => $salesData
            ]);
            exit;
        } catch(PDOException $e) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Error loading sales data: ' . $e->getMessage()]);
            exit;
        }
    }
    
    // Get Recent Activity
    if ($action === 'getRecentActivity') {
        try {
            $activities = [];
            
            // Get recent orders (last 5)
            $ordersSql = "SELECT 
                         o.Order_ID,
                         o.Status,
                         u.Name as customerName,
                         o.Order_Date,
                         TIMESTAMPDIFF(MINUTE, o.Order_Date, NOW()) as minutesAgo
                         FROM `order` o
                         JOIN user u ON o.User_ID = u.User_ID
                         ORDER BY o.Order_Date DESC
                         LIMIT 5";
            $ordersStmt = $pdo->query($ordersSql);
            $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($orders as $order) {
                $minutesAgo = intval($order['minutesAgo']);
                $timeText = '';
                if ($minutesAgo < 60) {
                    $timeText = $minutesAgo . ' minute' . ($minutesAgo != 1 ? 's' : '') . ' ago';
                } else if ($minutesAgo < 1440) {
                    $hours = floor($minutesAgo / 60);
                    $timeText = $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
                } else {
                    $days = floor($minutesAgo / 1440);
                    $timeText = $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
                }
                
                if (strtolower($order['Status']) === 'delivered') {
                    $activities[] = [
                        'icon' => '🛒',
                        'iconClass' => 'order-icon',
                        'title' => 'Order #ORD-' . $order['Order_ID'] . ' completed',
                        'time' => $timeText
                    ];
                } else {
                    $activities[] = [
                        'icon' => '🛒',
                        'iconClass' => 'order-icon',
                        'title' => 'New order #ORD-' . $order['Order_ID'] . ' received',
                        'time' => $timeText
                    ];
                }
            }
            
            // Get recent member registrations (last 3)
            $membersSql = "SELECT 
                          Name,
                          Created_At,
                          TIMESTAMPDIFF(MINUTE, Created_At, NOW()) as minutesAgo
                          FROM user
                          ORDER BY Created_At DESC
                          LIMIT 3";
            $membersStmt = $pdo->query($membersSql);
            $members = $membersStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($members as $member) {
                $minutesAgo = intval($member['minutesAgo']);
                $timeText = '';
                if ($minutesAgo < 60) {
                    $timeText = $minutesAgo . ' minute' . ($minutesAgo != 1 ? 's' : '') . ' ago';
                } else if ($minutesAgo < 1440) {
                    $hours = floor($minutesAgo / 60);
                    $timeText = $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
                } else {
                    $days = floor($minutesAgo / 1440);
                    $timeText = $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
                }
                
                $activities[] = [
                    'icon' => '👤',
                    'iconClass' => 'member-icon',
                    'title' => 'New member registration: ' . $member['Name'],
                    'time' => $timeText
                ];
            }
            
            // Get low stock products
            $stockSql = "SELECT 
                        Item_Name,
                        Stock
                        FROM item
                        WHERE Stock > 0 AND Stock <= 10
                        ORDER BY Stock ASC
                        LIMIT 2";
            $stockStmt = $pdo->query($stockSql);
            $lowStockItems = $stockStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($lowStockItems as $item) {
                $activities[] = [
                    'icon' => '📦',
                    'iconClass' => 'product-icon',
                    'title' => 'Product "' . $item['Item_Name'] . '" stock low (' . $item['Stock'] . ' remaining)',
                    'time' => 'Just now'
                ];
            }
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'activities' => array_slice($activities, 0, 10)
            ]);
            exit;
        } catch(PDOException $e) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Error loading activity: ' . $e->getMessage()]);
            exit;
        }
    }
}

// If no valid action, return error
ob_end_clean();
echo json_encode(['success' => false, 'message' => 'Invalid action or method']);
exit;
?>