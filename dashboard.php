<?php
// dashboard.php - User Dashboard Page with Database Integration
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'foodpanda_db';
$username = 'root';
$password = '';

$pdo = null;
$db_error = false;

// Create database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    $db_error = true;
}

// Get user information from database
$currentUser = null;
$orders = [];
$totalOrders = 0;
$totalSpent = 0;
$membershipPoints = 0;
$cartCount = 0;

if ($pdo && !$db_error) {
    try {
        $userId = $_SESSION['user_id'];
        
        // Get user data
        $userQuery = "SELECT * FROM user WHERE User_ID = ?";
        $userStmt = $pdo->prepare($userQuery);
        $userStmt->execute([$userId]);
        $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentUser) {
            session_destroy();
            header("Location: login.php");
            exit();
        }
        
        // Get order statistics
        $statsQuery = "SELECT 
                        COUNT(*) as total_orders,
                        COALESCE(SUM(Total_Price), 0) as total_spent
                       FROM `order` 
                       WHERE User_ID = ?";
        $statsStmt = $pdo->prepare($statsQuery);
        $statsStmt->execute([$userId]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        $totalOrders = $stats['total_orders'];
        $totalSpent = $stats['total_spent'];
        $membershipPoints = floor($totalSpent * 0.1); // 10% of spending as points
        
        // Get order history with payment info
        $ordersQuery = "SELECT 
                            o.Order_ID,
                            o.Total_Price,
                            o.Status,
                            o.Order_Date,
                            p.Payment_Method
                        FROM `order` o
                        LEFT JOIN payment p ON o.Payment_ID = p.Payment_ID
                        WHERE o.User_ID = ?
                        ORDER BY o.Order_Date DESC
                        LIMIT 10";
        $ordersStmt = $pdo->prepare($ordersQuery);
        $ordersStmt->execute([$userId]);
        $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get cart count
        $cartCountQuery = "SELECT COUNT(*) as count FROM cart WHERE User_ID = ?";
        $cartCountStmt = $pdo->prepare($cartCountQuery);
        $cartCountStmt->execute([$userId]);
        $cartResult = $cartCountStmt->fetch(PDO::FETCH_ASSOC);
        $cartCount = $cartResult['count'];
        
    } catch(PDOException $e) {
        error_log("Dashboard query failed: " . $e->getMessage());
        $db_error = true;
    }
}

$userName = $currentUser ? htmlspecialchars($currentUser['Name']) : 'Guest User';

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - nyamanhippo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="navbar.css">
    <style>
        body { background-color: #fff; }
        
        /* Layout Container */
        .dashboard-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 40px;
        }

        /* --- 1. WELCOME BANNER --- */
        .welcome-card {
            background: #fff0f5; /* Light Pink Background */
            padding: 2.5rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .welcome-text h1 { color: #333; margin-bottom: 0.5rem; font-size: 1.8rem; font-weight: 700; }
        .welcome-text p { color: #666; font-size: 1rem; }
        
        .membership-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
            transition: all 0.3s;
        }
        
        .membership-badge.gold {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #5a4a00;
        }
        
        .membership-badge.silver {
            background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%);
            color: #4a4a4a;
        }

        /* --- 2. STATS CARDS --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #f0f0f0;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-5px); }

        .stat-icon { font-size: 2.5rem; margin-bottom: 15px; display: block; }
        .stat-value { font-size: 2rem; font-weight: 800; color: #333; display: block; margin-bottom: 5px; }
        .stat-label { color: #777; font-size: 0.9rem; font-weight: 500; }

        /* --- 3. ORDER HISTORY TABLE --- */
        .history-section { margin-top: 2rem; }
        .history-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .history-header h2 { font-size: 1.5rem; color: #333; font-weight: 700; }

        .order-table-container {
            background: #fff0f5; /* Light pink background for table area as per design */
            padding: 20px;
            border-radius: 12px;
        }

        .order-table { width: 100%; border-collapse: collapse; }
        
        .order-table th { 
            text-align: left; padding: 15px; 
            color: #555; font-weight: 600; font-size: 0.95rem;
            border-bottom: 1px solid #e0c0c0;
        }
        
        .order-table td { padding: 15px; border-bottom: 1px solid #e0c0c0; color: #333; font-size: 0.95rem; }
        .order-table tr:last-child td { border-bottom: none; }

        /* Badges */
        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #cce5ff; color: #004085; }
        .status-preparing { background: #e2e3e5; color: #383d41; }
        .status-out-for-delivery { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
        }
        .empty-state i { font-size: 4rem; margin-bottom: 1rem; color: #ddd; }
        .empty-state p { font-size: 1.1rem; margin-bottom: 1.5rem; }
        .empty-state a { 
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }
        .empty-state a:hover { background: var(--secondary-color); }

        /* Buttons */
        .btn-view {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background 0.3s;
        }
        .btn-view:hover { background: var(--secondary-color); }

        .btn-logout {
            background: white; border: 1px solid #dc3545; color: #dc3545;
            padding: 8px 15px; border-radius: 6px; cursor: pointer; font-weight: 600;
            margin-top: 10px; font-size: 0.9rem;
            transition: all 0.3s;
        }
        .btn-logout:hover { background: #dc3545; color: white; }

        /* Responsive */
        @media (max-width: 900px) {
            .stats-grid { grid-template-columns: 1fr; }
            .welcome-card { flex-direction: column; text-align: center; gap: 20px; }
            .order-table-container { overflow-x: auto; }
        }
    </style>
</head>
<body>

    <nav class="navbar" style="background: var(--primary-color); box-shadow: 0 2px 8px rgba(0,0,0,0.08); position: sticky; top: 0; z-index: 1000;">
        <div class="nav-wrapper" style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 60px;">
            <div class="logo" style="display: flex; align-items: center; gap: 0.8rem;">
                <img src="nyamanhippo-logo.jpg" alt="Nyamanhippo Logo" class="logo-img" style="height: 40px; width: auto;" onerror="this.style.display='none';">
                <h1 style="color: white; font-size: 1.5rem; font-weight: 400; margin: 0;">nyamanhippo</h1>
            </div>
            <div class="nav-links" style="display: flex; gap: 2rem; align-items: center;">
                <a href="userIndex.php" style="text-decoration: none; color: white; font-weight: 500; font-size: 0.95rem; transition: opacity 0.3s;">Home</a>
                <a href="userBrowsing.php" style="text-decoration: none; color: white; font-weight: 500; font-size: 0.95rem; transition: opacity 0.3s;">Menu</a>
                <a href="cart.php" class="cart-icon" style="position: relative; display: flex; align-items: center; gap: 5px; text-decoration: none; color: white; font-weight: 500; font-size: 0.95rem;">
                    <i class="fa fa-shopping-cart" style="font-size: 1.2rem;"></i>
                    <span class="cart-count" id="cart-count" style="position: absolute; top: -8px; right: -10px; background: white; color: #d70f64; border: 2px solid #d70f64; font-size: 10px; font-weight: 700; padding: 2px 5px; border-radius: 50%; min-width: 18px; text-align: center;"><?php echo $cartCount; ?></span>
                </a>
                <div class="user-dropdown" style="position: relative;">
                    <button class="user-profile-btn" id="userDropdownBtn" style="background: rgba(255, 255, 255, 0.15); border: none; color: white; display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500; padding: 8px 14px; border-radius: 20px; transition: all 0.3s; font-size: 0.95rem;">
                        <i class="fa-regular fa-user"></i>
                        <span id="navUserName"><?php echo $userName; ?></span>
                        <i class="fa fa-caret-down"></i>
                    </button>
                    <div class="dropdown-menu" id="userDropdownMenu" style="position: absolute; right: 0; top: 120%; background: white; border-radius: 8px; min-width: 200px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: none; flex-direction: column; z-index: 1000; overflow: hidden;">
                        <a href="userProfile.php" style="padding: 12px 16px; text-decoration: none; color: #333; font-size: 14px; display: flex; align-items: center; gap: 12px; transition: all 0.2s;">
                            <i class="fa fa-user"></i> My Profile
                        </a>
                        <a href="dashboard.php" style="padding: 12px 16px; text-decoration: none; color: #333; font-size: 14px; display: flex; align-items: center; gap: 12px; transition: all 0.2s;">
                            <i class="fa fa-chart-line"></i> Dashboard
                        </a>
                        <hr>
                        <a href="logout.php" style="padding: 12px 16px; text-decoration: none; color: #333; font-size: 14px; display: flex; align-items: center; gap: 12px; transition: all 0.2s;">
                            <i class="fa fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        
        <div class="welcome-card">
            <div class="welcome-text">
                <h1 id="welcomeTitle">Welcome back, <?php echo $userName; ?>! 👋</h1>
                <p>Here's what's happening with your orders</p>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="logout" value="1" class="btn-logout">Logout</button>
                </form>
            </div>
            <div class="membership-badge <?php echo $membershipPoints >= 500 ? 'gold' : 'silver'; ?>">
                <span>⭐</span> <?php echo $membershipPoints >= 500 ? 'Gold' : 'Silver'; ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">📦</span>
                <span class="stat-value"><?php echo $totalOrders; ?></span>
                <span class="stat-label">Total Orders</span>
            </div>
            <div class="stat-card">
                <span class="stat-icon">🎖️</span>
                <span class="stat-value"><?php echo $membershipPoints; ?></span>
                <span class="stat-label">Membership Points</span>
            </div>
            <div class="stat-card">
                <span class="stat-icon">💰</span>
                <span class="stat-value">RM <?php echo number_format($totalSpent, 2); ?></span>
                <span class="stat-label">Total Spent</span>
            </div>
        </div>

        <div class="history-section">
            <div class="history-header">
                <h2>Order History</h2>
            </div>

            <div class="order-table-container">
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <i class="fa fa-shopping-bag"></i>
                        <p>You haven't placed any orders yet</p>
                        <a href="userBrowsing.php">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Payment Method</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ordersBody">
                            <?php foreach ($orders as $order): ?>
                                <?php
                                    // Format date
                                    $orderDate = new DateTime($order['Order_Date']);
                                    $formattedDate = $orderDate->format('d/m/Y');
                                    
                                    // Format order ID
                                    $orderId = '#ORD-' . str_pad($order['Order_ID'], 4, '0', STR_PAD_LEFT);
                                    
                                    // Status badge class
                                    $statusClass = 'status-' . strtolower(str_replace(' ', '-', $order['Status']));
                                    
                                    // Payment method
                                    $paymentMethod = $order['Payment_Method'] ?? 'N/A';
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($orderId); ?></strong></td>
                                    <td><?php echo $formattedDate; ?></td>
                                    <td><?php echo htmlspecialchars($paymentMethod); ?></td>
                                    <td><strong>RM <?php echo number_format($order['Total_Price'], 2); ?></strong></td>
                                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($order['Status']); ?></span></td>
                                    <td><button type="button" class="btn-view" onclick="viewOrder(<?php echo $order['Order_ID']; ?>)">View</button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script>
        
        // User dropdown toggle functionality
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        
        if (userDropdownBtn && userDropdownMenu) {
            userDropdownBtn.addEventListener('click', () => {
                userDropdownMenu.style.display = userDropdownMenu.style.display === 'flex' ? 'none' : 'flex';
            });
            
            document.addEventListener('click', (e) => {
                if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                    userDropdownMenu.style.display = 'none';
                }
            });
        }
        
        // View order details
        function viewOrder(orderId) {
            window.location.href = 'order-details.php?id=' + orderId;
        }

        // Clear checkout data after viewing dashboard
        sessionStorage.removeItem('checkoutData');
        
        console.log('Dashboard loaded successfully');
    </script>
</body>
</html>