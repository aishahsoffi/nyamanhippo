<?php
// checkout.php - Checkout Page with Full Database Integration
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
if ($pdo && !$db_error) {
    try {
        $userId = $_SESSION['user_id'];
        $userQuery = "SELECT * FROM user WHERE User_ID = ?";
        $userStmt = $pdo->prepare($userQuery);
        $userStmt->execute([$userId]);
        $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentUser) {
            session_destroy();
            header("Location: login.php");
            exit();
        }
    } catch(PDOException $e) {
        error_log("User query failed: " . $e->getMessage());
    }
}

$userName = $currentUser ? htmlspecialchars($currentUser['Name']) : 'User';
$userEmail = $currentUser ? htmlspecialchars($currentUser['Email']) : 'user@example.com';
$userPhone = $currentUser ? htmlspecialchars($currentUser['PhoneNo']) : '+60 12-345 6789';

// Get cart count from database
$cartCount = 0;
if ($pdo && !$db_error) {
    try {
        $countQuery = "SELECT COUNT(*) as count FROM cart WHERE User_ID = ?";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute([$userId]);
        $result = $countStmt->fetch(PDO::FETCH_ASSOC);
        $cartCount = $result['count'];
    } catch(PDOException $e) {
        error_log("Cart count query failed: " . $e->getMessage());
    }
}

// Handle AJAX order placement with full database integration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    header('Content-Type: application/json');

    try {
        // Get POST data
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $paymentMethod = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
        $ordersData = isset($_POST['orders']) ? json_decode($_POST['orders'], true) : [];

        // Validate data
        if (empty($address) || empty($phone) || empty($paymentMethod) || empty($ordersData)) {
            echo json_encode(['success' => false, 'message' => 'Invalid order data']);
            exit();
        }

        // Begin transaction to ensure data integrity
        $pdo->beginTransaction();

        $orderIds = [];
        $totalAmount = 0;

        // Fetch cart items from database to get Item_IDs for order creation
        $cartQuery = "SELECT c.Cart_ID, c.Item_ID, c.Quantity, i.Item_Name, i.Price, i.Stock
                      FROM cart c
                      JOIN item i ON c.Item_ID = i.Item_ID
                      WHERE c.User_ID = ?";
        $cartStmt = $pdo->prepare($cartQuery);
        $cartStmt->execute([$userId]);
        $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cartItems)) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Cart is empty']);
            exit();
        }

        // Create orders for each store
        foreach ($ordersData as $orderData) {
            $store = $orderData['store'];
            $items = $orderData['items'];
            $subtotal = $orderData['subtotal'];
            $delivery = $orderData['delivery'];
            $service = $orderData['service'];
            $orderTotal = $orderData['total'];

            // Verify stock availability before creating order
            foreach ($items as $item) {
                $itemName = $item['name'];
                $requestedQty = $item['qty'];
                
                // Find the cart item
                $cartItem = null;
                foreach ($cartItems as $ci) {
                    if ($ci['Item_Name'] === $itemName) {
                        $cartItem = $ci;
                        break;
                    }
                }
                
                if ($cartItem) {
                    if ($cartItem['Stock'] < $requestedQty) {
                        $pdo->rollBack();
                        echo json_encode([
                            'success' => false,
                            'message' => "Insufficient stock for {$itemName}. Only {$cartItem['Stock']} available."
                        ]);
                        exit();
                    }
                }
            }

            // Insert into order table
            $orderQuery = "INSERT INTO `order` (User_ID, Total_Price, Status, Order_Date)
                           VALUES (?, ?, 'Pending', NOW())";
            $orderStmt = $pdo->prepare($orderQuery);
            $orderStmt->execute([$userId, $orderTotal]);
            $orderId = $pdo->lastInsertId();
            $orderIds[] = $orderId;

            // Insert into payment table
            $paymentQuery = "INSERT INTO payment (Order_ID, Payment_Method, Payment_Date, Amount, Status)
                             VALUES (?, ?, CURDATE(), ?, 'Completed')";
            $paymentStmt = $pdo->prepare($paymentQuery);
            $paymentStmt->execute([$orderId, $paymentMethod, $orderTotal]);

            $paymentId = $pdo->lastInsertId();

            // Update order with payment_id
            $updateOrderQuery = "UPDATE `order` SET Payment_ID = ? WHERE Order_ID = ?";
            $updateOrderStmt = $pdo->prepare($updateOrderQuery);
            $updateOrderStmt->execute([$paymentId, $orderId]);

            // Update stock for ordered items AND delete them from cart
            foreach ($items as $item) {
                $itemName = $item['name'];
                $requestedQty = $item['qty'];
                
                // Find the cart item to get Item_ID
                foreach ($cartItems as $ci) {
                    if ($ci['Item_Name'] === $itemName) {
                        // Decrease stock
                        $updateStockQuery = "UPDATE item SET Stock = Stock - ? WHERE Item_ID = ?";
                        $updateStockStmt = $pdo->prepare($updateStockQuery);
                        $updateStockStmt->execute([$requestedQty, $ci['Item_ID']]);
                        
                        // FIXED: Delete only this specific item from cart
                        $deleteItemQuery = "DELETE FROM cart WHERE Cart_ID = ?";
                        $deleteItemStmt = $pdo->prepare($deleteItemQuery);
                        $deleteItemStmt->execute([$ci['Cart_ID']]);
                        
                        break;
                    }
                }
            }

            $totalAmount += $orderTotal;
        }

        // NOTE: Items are now deleted individually above, not all at once
        // This preserves unchecked items in the cart

        // Commit transaction
        $pdo->commit();

        // Include the receipt sending function
        require_once 'send_receipt.php';
        
        // Send receipt emails for all orders
        $emailSuccess = true;
        foreach ($orderIds as $orderId) {
            $sent = sendOrderReceipt($orderId, $userId);
            if (!$sent) {
                $emailSuccess = false;
                error_log("Failed to send receipt for Order ID: " . $orderId);
            }
        }

        // Log email sending result
        if ($emailSuccess) {
            error_log("All receipt emails sent successfully for User ID: " . $userId);
        } else {
            error_log("Some receipt emails failed to send for User ID: " . $userId);
        }

        // Return success (even if email fails, order is still successful)
        echo json_encode([
            'success' => true,
            'message' => 'Orders placed successfully' . ($emailSuccess ? ' and receipt(s) sent to your email' : ''),
            'order_ids' => $orderIds,
            'email_sent' => $emailSuccess
        ]);
        exit();

    } catch(Exception $e) {
        // Rollback on error
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Order placement failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to place order: ' . $e->getMessage()]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - nyamanhippo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="navbar.css">
    <style>
        body { background-color: #fff; }

        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 40px;
        }

        .page-heading { margin-bottom: 2rem; color: var(--dark-color); }
        .page-heading h1 { font-size: 2rem; font-weight: 700; }

        /* Two Column Layout */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
            align-items: start;
        }

        /* --- LEFT COLUMN: Forms --- */
        .form-section {
            background: white;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .section-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 1.2rem; color: var(--dark-color); display: flex; align-items: center; gap: 10px;}
        .section-number { background: var(--dark-color); color: white; width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; }

        .form-group { margin-bottom: 1.2rem; }
        .form-label { display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.95rem; color: #333; }
        
        .form-input, .form-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            background: #fcfcfc;
            transition: 0.3s;
        }
        .form-input:focus, .form-textarea:focus { border-color: var(--primary-color); background: white; outline: none; }

        /* Payment Options */
        .payment-option {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: 0.2s;
        }
        .payment-option:hover { border-color: var(--primary-color); background: #fff0f5; }
        .payment-option input { accent-color: var(--primary-color); transform: scale(1.2); }

        /* --- RIGHT COLUMN: Order Summary --- */
        .summary-card {
            background: white;
            border: 1px solid #ffc4d6;
            border-radius: 12px;
            padding: 1.5rem;
            position: sticky;
            top: 80px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .summary-header { font-size: 1.3rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--dark-color); }

        /* Item List inside Summary */
        .summary-items { margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid #eee; }
        .summary-item-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.9rem; color: #555; }

        /* Totals */
        .cost-row { display: flex; justify-content: space-between; margin-bottom: 10px; color: #666; font-size: 0.95rem; }
        .cost-row.discount { color: #dc3545; }
        .total-row { 
            display: flex; justify-content: space-between; 
            margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;
            font-size: 1.4rem; font-weight: 700; color: var(--primary-color);
        }

        /* Place Order Button */
        .btn-place-order {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: background 0.3s;
            box-shadow: 0 4px 10px rgba(215, 15, 100, 0.2);
        }
        .btn-place-order:hover { background: var(--secondary-color); transform: translateY(-2px); }
        .btn-place-order:disabled { background: #ccc; cursor: not-allowed; transform: none; }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 900px) {
            .checkout-grid { grid-template-columns: 1fr; }
            .summary-card { order: -1; margin-bottom: 20px; }
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

    <div class="checkout-container">
        <div class="page-heading">
            <h1>Checkout</h1>
        </div>

        <form id="checkoutForm" onsubmit="handlePlaceOrder(event)">
            <div class="checkout-grid">
                
                <div class="left-column">
                    <div class="form-section">
                        <div class="section-title"><span class="section-number">1</span> Delivery Address</div>
                        
                        <div class="form-group">
                            <label class="form-label">Street Address</label>
                            <input id="addr" name="address" type="text" class="form-input" value="123 Main Street, Apartment 48" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Floor / Unit Number (Optional)</label>
                            <input type="text" class="form-input" placeholder="e.g. Level 2, Unit 5">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Note to Rider</label>
                            <input type="text" class="form-input" placeholder="e.g. Please leave at guardhouse">
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title"><span class="section-number">2</span> Personal Details</div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input" value="<?php echo $userEmail; ?>" readonly style="background:#eee;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mobile Number</label>
                            <input id="phone" name="phone" type="tel" class="form-input" value="<?php echo $userPhone; ?>" required>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title"><span class="section-number">3</span> Payment Method</div>
                        
                        <label class="payment-option">
                            <input type="radio" name="payment" value="Credit Card" checked>
                            <div>
                                <strong>Credit / Debit Card</strong><br>
                                <small>Visa, Mastercard</small>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment" value="E-Wallet">
                            <div>
                                <strong>Online Banking (FPX)</strong><br>
                                <small>Maybank2u, CIMB Clicks, etc.</small>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment" value="Cash on Delivery">
                            <div>
                                <strong>Cash on Delivery</strong><br>
                                <small>Pay directly to rider</small>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="right-column">
                    <div class="summary-card">
                        <h3 class="summary-header">Review & Confirm</h3>
                        <div class="summary-items">
                            <div class="summary-item-row"><span>Deliver to</span><span id="review-addr">123 Main Street, Apartment 48</span></div>
                            <div class="summary-item-row"><span>Contact</span><span id="review-phone"><?php echo $userPhone; ?></span></div>
                            <div class="summary-item-row"><span>Payment</span><span id="review-pay">Credit / Debit Card</span></div>
                        </div>
                        <div class="summary-items" id="co-items">
                            <!-- Items from cart selection will render here -->
                        </div>
                        <div class="cost-row"><span>Subtotal</span><span id="co-subtotal">RM 0.00</span></div>
                        <div class="cost-row"><span>Delivery Fee</span><span id="co-delivery">RM 0.00</span></div>
                        <div class="cost-row"><span>Service Fee</span><span id="co-service">RM 0.00</span></div>
                        <div class="total-row"><span style="color:black; font-size:1.1rem;">Total</span><span id="co-total">RM 0.00</span></div>
                        <button id="btnConfirm" type="submit" class="btn-place-order" disabled>Confirm Payment</button>
                        <p style="font-size: 0.8rem; color: #999; text-align: center; margin-top: 15px;">Button enables once required fields are complete.</p>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script>
        // Global order data
        let orderData = null;

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

        // UPDATED: Place order logic with full database integration
        function handlePlaceOrder(e) {
            e.preventDefault();
            
            const btn = document.getElementById('btnConfirm');
            const originalText = btn.innerHTML;
            
            // Show loading state
            btn.innerHTML = '<span class="spinner"></span>Processing Payment...';
            btn.disabled = true;
            btn.style.opacity = '0.7';
            
            // Get form data
            const address = document.getElementById('addr').value;
            const phone = document.getElementById('phone').value;
            const paymentMethod = document.querySelector('input[name="payment"]:checked').value;
            
            // Prepare order data from sessionStorage
            if (!orderData || !orderData.orders || orderData.orders.length === 0) {
                alert('No items in cart to checkout');
                btn.innerHTML = originalText;
                btn.disabled = false;
                btn.style.opacity = '1';
                return;
            }
            
            // Send to server with full database integration
            fetch('checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=place_order&address=${encodeURIComponent(address)}&phone=${encodeURIComponent(phone)}&payment_method=${encodeURIComponent(paymentMethod)}&orders=${encodeURIComponent(JSON.stringify(orderData.orders))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear checkout data from session storage
                    sessionStorage.removeItem('checkoutData');
                    
                    // Show success message with email notification
                    const emailMsg = data.email_sent ? '\n\nA receipt has been sent to your email address.' : '';
                    alert('✅ Order placed successfully!\n\nOrder IDs: ' + data.order_ids.map(id => '#ORD-' + String(id).padStart(4, '0')).join(', ') + emailMsg);
                    
                    // Redirect to dashboard
                    window.location.href = 'dashboard.php';
                } else {
                    // Show error message
                    alert('❌ Failed to place order: ' + (data.message || 'Unknown error'));
                    
                    // Restore button
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ An error occurred while placing your order. Please try again.');
                
                // Restore button
                btn.innerHTML = originalText;
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        }

        // Enable Confirm Payment when required fields are filled and update the review panel
        const addr = document.getElementById('addr');
        const phone = document.getElementById('phone');
        const payRadios = document.querySelectorAll('input[name="payment"]');
        const btnConfirm = document.getElementById('btnConfirm');
        const reviewAddr = document.getElementById('review-addr');
        const reviewPhone = document.getElementById('review-phone');
        const reviewPay = document.getElementById('review-pay');

        function updateReview() {
            reviewAddr.textContent = addr.value.trim() || '-';
            reviewPhone.textContent = phone.value.trim() || '-';
            const sel = Array.from(payRadios).find(r => r.checked);
            reviewPay.textContent = sel ? (sel.value === 'Credit Card' ? 'Credit / Debit Card' : sel.value === 'E-Wallet' ? 'Online Banking (FPX)' : 'Cash on Delivery') : '-';
        }

        function validate() {
            const addrOk = addr.value.trim().length > 0;
            const phoneOk = phone.value.trim().length > 0;
            const payOk = Array.from(payRadios).some(r => r.checked);
            btnConfirm.disabled = !(addrOk && phoneOk && payOk);
            updateReview();
        }

        document.addEventListener('input', (e) => {
            if (e.target === addr || e.target === phone) validate();
        });
        payRadios.forEach(r => r.addEventListener('change', validate));
        
        // Initialize
        validate();
        
        // Load cart selection from sessionStorage and populate the cost summary
        (function(){
            try {
                const raw = sessionStorage.getItem('checkoutData');
                if (!raw) {
                    console.warn('No checkout data found');
                    return;
                }

                const data = JSON.parse(raw);
                orderData = data; // Store globally

                const itemsEl = document.getElementById('co-items');
                if (itemsEl && Array.isArray(data.orders)) {
                    itemsEl.innerHTML = '';
                    data.orders.forEach(order => {
                        // Add restaurant header
                        const storeHeader = document.createElement('div');
                        storeHeader.className = 'summary-item-row';
                        storeHeader.style.fontWeight = 'bold';
                        storeHeader.style.borderBottom = '1px solid #eee';
                        storeHeader.style.marginBottom = '8px';
                        storeHeader.style.paddingBottom = '8px';
                        storeHeader.innerHTML = `<span>${order.store}</span><span></span>`;
                        itemsEl.appendChild(storeHeader);

                        // Add items for this restaurant
                        order.items.forEach(it => {
                            const row = document.createElement('div');
                            row.className = 'summary-item-row';
                            const qty = Number(it.qty) || 0;
                            const name = it.name || '';
                            const line = Number(it.price) * qty;
                            row.innerHTML = `<span style="padding-left: 15px;">${qty}x ${name}</span><span>RM ${line.toFixed(2)}</span>`;
                            itemsEl.appendChild(row);
                        });
                    });
                }

                const setText = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = `RM ${Number(val||0).toFixed(2)}`;
                };

                setText('co-subtotal', data.subtotal);
                setText('co-delivery', data.delivery);
                setText('co-service', data.service);
                setText('co-total', data.total);

                console.log('Checkout data loaded successfully');
            } catch(e) {
                console.error('Error loading checkout data:', e);
            }
        })();
    </script>
</body>
</html>
