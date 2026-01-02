<?php
// order-details.php - Order Details Page
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$orderId = intval($_GET['id']);

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

// Get order details
$order = null;
$payment = null;
$currentUser = null;
$cartCount = 0;

if ($pdo && !$db_error) {
    try {
        $userId = $_SESSION['user_id'];
        
        // Get user data
        $userQuery = "SELECT * FROM user WHERE User_ID = ?";
        $userStmt = $pdo->prepare($userQuery);
        $userStmt->execute([$userId]);
        $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get order details - verify it belongs to the logged-in user
        $orderQuery = "SELECT o.*, p.Payment_Method, p.Payment_Date, p.Status as Payment_Status
                       FROM `order` o
                       LEFT JOIN payment p ON o.Payment_ID = p.Payment_ID
                       WHERE o.Order_ID = ? AND o.User_ID = ?";
        $orderStmt = $pdo->prepare($orderQuery);
        $orderStmt->execute([$orderId, $userId]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        
        // If order not found or doesn't belong to user, redirect
        if (!$order) {
            header('Location: dashboard.php');
            exit();
        }
        
        // Get cart count
        $cartCountQuery = "SELECT COUNT(*) as count FROM cart WHERE User_ID = ?";
        $cartCountStmt = $pdo->prepare($cartCountQuery);
        $cartCountStmt->execute([$userId]);
        $cartResult = $cartCountStmt->fetch(PDO::FETCH_ASSOC);
        $cartCount = $cartResult['count'];
        
    } catch(PDOException $e) {
        error_log("Order details query failed: " . $e->getMessage());
        $db_error = true;
    }
}

$userName = $currentUser ? htmlspecialchars($currentUser['Name']) : 'User';
$userEmail = $currentUser ? htmlspecialchars($currentUser['Email']) : '';
$userPhone = $currentUser ? htmlspecialchars($currentUser['PhoneNo']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - nyamanhippo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="navbar.css">
    <style>
        body { background-color: #f8f9fa; }
        
        .order-details-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 40px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1.5rem;
            transition: opacity 0.3s;
        }
        .back-link:hover { opacity: 0.7; }

        .page-header {
            margin-bottom: 2rem;
        }
        .page-header h1 {
            font-size: 2rem;
            color: #333;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .order-date {
            color: #666;
            font-size: 1rem;
        }

        /* Status Timeline */
        .status-timeline {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .timeline-header {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .timeline {
            position: relative;
            padding-left: 40px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -29px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #e0e0e0;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #e0e0e0;
        }
        
        .timeline-item.active .timeline-dot {
            background: var(--primary-color);
            box-shadow: 0 0 0 2px var(--primary-color);
        }

        .timeline-content h4 {
            font-size: 1rem;
            color: #333;
            margin-bottom: 0.3rem;
        }
        
        .timeline-content p {
            color: #999;
            font-size: 0.9rem;
        }
        
        .timeline-item.active .timeline-content h4 {
            color: var(--primary-color);
            font-weight: 700;
        }

        /* Order Info Cards */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .info-card h3 {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-card p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 0.5rem;
        }
        
        .info-card strong {
            color: #333;
            font-weight: 600;
        }

        /* Order Summary */
        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .summary-header {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .cost-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: #666;
            font-size: 1rem;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding-top: 1rem;
            margin-top: 1rem;
            border-top: 2px solid #eee;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            flex: 1;
            padding: 1rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        .btn-secondary:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 700;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #cce5ff; color: #004085; }
        .status-preparing { background: #e2e3e5; color: #383d41; }
        .status-out-for-delivery { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar" style="background: var(--primary-color); box-shadow: 0 2px 8px rgba(0,0,0,0.08); position: sticky; top: 0; z-index: 1000;">
        <div class="nav-wrapper" style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 60px;">
            <div class="logo" style="display: flex; align-items: center; gap: 0.8rem;">
                <img src="foodpanda-logo.jpg" alt="FoodPanda Logo" class="logo-img" style="height: 40px; width: auto;" onerror="this.style.display='none';">
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

    <div class="order-details-container">
        <a href="dashboard.php" class="back-link">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
        </a>

        <?php if ($db_error || !$order): ?>
            <div style="padding: 2rem; text-align: center; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <p style="color: #d70f64; font-size: 1.2rem; margin-bottom: 1rem;">❌ Unable to load order details</p>
                <a href="dashboard.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">Return to Dashboard →</a>
            </div>
        <?php else: ?>
            <?php
                // Format date
                $orderDate = new DateTime($order['Order_Date']);
                $formattedDate = $orderDate->format('F j, Y \a\t g:i A');
                $shortDate = $orderDate->format('d/m/Y');
                
                // Order ID
                $displayOrderId = '#ORD-' . str_pad($order['Order_ID'], 4, '0', STR_PAD_LEFT);
                
                // Status
                $status = $order['Status'];
                $statusClass = 'status-' . strtolower(str_replace(' ', '-', $status));
                
                // Calculate fees
                $subtotal = $order['Total_Price'] - 7.00; // Remove delivery + service fee
                $deliveryFee = 5.00;
                $serviceFee = 2.00;
            ?>

            <div class="page-header">
                <h1>Order <?php echo htmlspecialchars($displayOrderId); ?></h1>
                <p class="order-date"><?php echo $formattedDate; ?></p>
            </div>

            <!-- Status Timeline -->
            <div class="status-timeline">
                <h3 class="timeline-header">Order Status</h3>
                <div class="timeline">
                    <div class="timeline-item <?php echo in_array($status, ['Pending', 'Confirmed', 'Preparing', 'Out for Delivery', 'Delivered']) ? 'active' : ''; ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <h4>Order Placed</h4>
                            <p><?php echo $shortDate; ?></p>
                        </div>
                    </div>
                    <div class="timeline-item <?php echo in_array($status, ['Confirmed', 'Preparing', 'Out for Delivery', 'Delivered']) ? 'active' : ''; ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <h4>Order Confirmed</h4>
                            <p><?php echo $status == 'Confirmed' ? 'Current Status' : ($status == 'Pending' ? 'Waiting' : 'Completed'); ?></p>
                        </div>
                    </div>
                    <div class="timeline-item <?php echo in_array($status, ['Preparing', 'Out for Delivery', 'Delivered']) ? 'active' : ''; ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <h4>Preparing Your Order</h4>
                            <p><?php echo $status == 'Preparing' ? 'Current Status' : (in_array($status, ['Pending', 'Confirmed']) ? 'Waiting' : 'Completed'); ?></p>
                        </div>
                    </div>
                    <div class="timeline-item <?php echo in_array($status, ['Out for Delivery', 'Delivered']) ? 'active' : ''; ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <h4>Out for Delivery</h4>
                            <p><?php echo $status == 'Out for Delivery' ? 'Current Status' : ($status == 'Delivered' ? 'Completed' : 'Waiting'); ?></p>
                        </div>
                    </div>
                    <div class="timeline-item <?php echo $status == 'Delivered' ? 'active' : ''; ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <h4>Delivered</h4>
                            <p><?php echo $status == 'Delivered' ? 'Order Completed' : 'Pending'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Info Grid -->
            <div class="info-grid">
                <div class="info-card">
                    <h3><i class="fa fa-map-marker-alt"></i> Delivery Address</h3>
                    <p><strong>Address:</strong> 123 Main Street, Apartment 48</p>
                    <p><strong>Phone:</strong> <?php echo $userPhone; ?></p>
                </div>

                <div class="info-card">
                    <h3><i class="fa fa-credit-card"></i> Payment Details</h3>
                    <p><strong>Method:</strong> <?php echo htmlspecialchars($order['Payment_Method']); ?></p>
                    <p><strong>Status:</strong> <span class="status-badge" style="background: #d4edda; color: #155724;"><?php echo htmlspecialchars($order['Payment_Status']); ?></span></p>
                    <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($order['Payment_Date'])); ?></p>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="summary-card">
                <h3 class="summary-header">Order Summary</h3>
                
                <div class="cost-row">
                    <span>Subtotal</span>
                    <span>RM <?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="cost-row">
                    <span>Delivery Fee</span>
                    <span>RM <?php echo number_format($deliveryFee, 2); ?></span>
                </div>
                <div class="cost-row">
                    <span>Service Fee</span>
                    <span>RM <?php echo number_format($serviceFee, 2); ?></span>
                </div>
                
                <div class="total-row">
                    <span>Total</span>
                    <span>RM <?php echo number_format($order['Total_Price'], 2); ?></span>
                </div>

                <div class="action-buttons">
                    <?php if ($status == 'Delivered'): ?>
                        <button class="btn btn-primary" onclick="window.location.href='ratingreview.php?order_id=<?php echo $order['Order_ID']; ?>'">
                            <i class="fa fa-star"></i> Rate Order
                        </button>
                    <?php endif; ?>
                    <button class="btn <?php echo $status == 'Delivered' ? 'btn-secondary' : 'btn-primary'; ?>" onclick="window.location.href='userBrowsing.php'">
                        <i class="fa fa-shopping-bag"></i> Order Again
                    </button>
                    <button class="btn btn-secondary" onclick="window.print()">
                        <i class="fa fa-print"></i> Print Receipt
                    </button>
                </div>
            </div>
        <?php endif; ?>
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
    </script>
</body>
</html>