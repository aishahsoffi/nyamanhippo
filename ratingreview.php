<?php
// ratingreview.php - Rating & Review Page with Database Integration
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    header('Location: dashboard.php');
    exit();
}

$orderId = intval($_GET['order_id']);

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

// Get order and user details
$order = null;
$currentUser = null;
$cartCount = 0;
$existingReview = null;
$restaurant = null; // Single restaurant details

if ($pdo && !$db_error) {
    try {
        $userId = $_SESSION['user_id'];
        
        // Get user data
        $userQuery = "SELECT * FROM user WHERE User_ID = ?";
        $userStmt = $pdo->prepare($userQuery);
        $userStmt->execute([$userId]);
        $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get order details - verify it belongs to the logged-in user and is delivered
        $orderQuery = "SELECT o.*, p.Payment_Method
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
        
        // Get restaurant details for items in this specific order
        $restaurantQuery = "SELECT DISTINCT r.id, r.name, r.logo, c.Category_Name,
                           GROUP_CONCAT(DISTINCT i.Item_Name SEPARATOR ', ') as items_ordered
                           FROM item i
                           INNER JOIN restaurants r ON i.Restaurant_ID = r.id
                           INNER JOIN category c ON r.category_id = c.Category_ID
                           INNER JOIN cart cart_items ON i.Item_ID = cart_items.Item_ID
                           WHERE cart_items.Cart_ID = ? 
                           GROUP BY r.id, r.name, r.logo, c.Category_Name
                           LIMIT 1";
        $restaurantStmt = $pdo->prepare($restaurantQuery);
        $restaurantStmt->execute([$order['Cart_ID']]);
        $restaurant = $restaurantStmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if review already exists
        $reviewCheckQuery = "SELECT * FROM review WHERE Order_ID = ? AND User_ID = ?";
        $reviewCheckStmt = $pdo->prepare($reviewCheckQuery);
        $reviewCheckStmt->execute([$orderId, $userId]);
        $existingReview = $reviewCheckStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get cart count
        $cartCountQuery = "SELECT COUNT(*) as count FROM cart WHERE User_ID = ?";
        $cartCountStmt = $pdo->prepare($cartCountQuery);
        $cartCountStmt->execute([$userId]);
        $cartResult = $cartCountStmt->fetch(PDO::FETCH_ASSOC);
        $cartCount = $cartResult['count'];
        
    } catch(PDOException $e) {
        error_log("Review page query failed: " . $e->getMessage());
        $db_error = true;
    }
}

$userName = $currentUser ? htmlspecialchars($currentUser['Name']) : 'User';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    header('Content-Type: application/json');
    
    try {
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
        
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Invalid rating']);
            exit();
        }
        
        // Check if review already exists
        if ($existingReview) {
            // Update existing review
            $updateQuery = "UPDATE review SET Rating = ?, Comment = ? WHERE Review_ID = ?";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$rating, $comment, $existingReview['Review_ID']]);
            
            echo json_encode(['success' => true, 'message' => 'Review updated successfully']);
        } else {
            // Insert new review
            $insertQuery = "INSERT INTO review (User_ID, Order_ID, Rating, Comment, Created_At) 
                           VALUES (?, ?, ?, ?, NOW())";
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([$userId, $orderId, $rating, $comment]);
            
            echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
        }
        exit();
        
    } catch(Exception $e) {
        error_log("Review submission failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Order - nyamanhippo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --panda-pink: #D70F64;
            --bg-gray: #f2f2f2;
            --text-dark: #333;
            --text-light: #666;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-gray);
            margin: 0;
            line-height: 1.6;
        }

        .content {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 40px;
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

        .card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .card h3 { 
            font-size: 1.8rem; 
            margin: 0 0 10px 0;
            color: var(--text-dark);
        }

        .date { 
            font-size: 1rem; 
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            background: #d4edda;
            color: #155724;
            margin-left: 10px;
        }

        hr { 
            border: 0; 
            border-top: 1.5px solid #f0f0f0; 
            margin: 30px 0; 
        }

        .restaurant-header {
            display: flex;
            gap: 20px;
            align-items: center;
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
        }

        .restaurant-logo {
            width: 90px;
            height: 90px;
            border-radius: 12px;
            object-fit: cover;
            background: white;
            padding: 8px;
        }

        .restaurant-details {
            flex: 1;
        }

        .restaurant-details h2 {
            margin: 0 0 8px 0;
            font-size: 2rem;
            font-weight: 600;
        }

        .restaurant-details .category {
            font-size: 1.1rem;
            opacity: 0.95;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .restaurant-info {
            display: flex;
            gap: 40px;
            align-items: center;
        }

        .order-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
        }

        .rating-section {
            flex: 1;
        }

        .rating-section h4 { 
            font-size: 1.6rem; 
            margin-bottom: 15px;
            color: var(--text-dark);
        }

        .order-details {
            color: var(--text-light);
            margin-bottom: 20px;
            line-height: 1.8;
        }

        .stars { 
            font-size: 3rem;
            color: #ddd;
            cursor: pointer;
            display: flex;
            gap: 10px;
        }

        .stars i {
            transition: all 0.2s ease;
        }

        .stars i:hover {
            transform: scale(1.1);
        }

        .stars .fa-star.active {
            color: var(--panda-pink);
        }

        .label {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 10px;
            font-weight: 600;
        }

        textarea {
            width: 100%;
            height: 250px;
            border: 1.5px solid #ddd;
            border-radius: 12px;
            padding: 20px;
            font-size: 1rem;
            box-sizing: border-box;
            resize: vertical;
            margin: 20px 0 30px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        textarea:focus {
            outline: none;
            border-color: var(--panda-pink);
        }

        .button-group {
            display: flex;
            justify-content: flex-end;
            gap: 20px;
        }

        .btn {
            padding: 15px 45px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-cancel { 
            background: #999; 
            color: white;
        }

        .btn-cancel:hover {
            background: #777;
        }

        .btn-submit { 
            background: var(--panda-pink); 
            color: white;
        }

        .btn-submit:hover { 
            opacity: 0.9; 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(215, 15, 100, 0.3);
        }

        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

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

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        @media (max-width: 768px) {
            .content {
                padding: 0 20px;
            }

            .restaurant-header {
                flex-direction: column;
                text-align: center;
            }

            .restaurant-info {
                flex-direction: column;
                gap: 20px;
            }

            .order-icon {
                width: 100px;
                height: 100px;
                font-size: 3rem;
            }

            .stars {
                font-size: 2rem;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
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

    <main class="content">
        <a href="order-details.php?id=<?php echo $orderId; ?>" class="back-link">
            <i class="fa fa-arrow-left"></i> Back to Order Details
        </a>

        <?php if ($db_error || !$order): ?>
            <div class="card">
                <p style="color: #d70f64; font-size: 1.2rem; text-align: center;">❌ Unable to load order information</p>
                <p style="text-align: center; margin-top: 1rem;">
                    <a href="dashboard.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">Return to Dashboard →</a>
                </p>
            </div>
        <?php else: ?>
            <?php
                // Format date
                $orderDate = new DateTime($order['Order_Date']);
                $formattedDate = $orderDate->format('F j, Y');
                $displayOrderId = '#ORD-' . str_pad($order['Order_ID'], 4, '0', STR_PAD_LEFT);
            ?>

            <?php if ($restaurant): ?>
                <div class="card">
                    <div class="restaurant-header">
                        <img src="<?php echo htmlspecialchars($restaurant['logo']); ?>" 
                             alt="<?php echo htmlspecialchars($restaurant['name']); ?>"
                             class="restaurant-logo"
                             onerror="this.src='placeholder-restaurant.jpg'">
                        <div class="restaurant-details">
                            <h2><?php echo htmlspecialchars($restaurant['name']); ?></h2>
                            <div class="category">
                                <i class="fa fa-utensils"></i>
                                <?php echo htmlspecialchars($restaurant['Category_Name']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($order['Status'] !== 'Delivered'): ?>
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Note:</strong> You can only rate orders that have been delivered.
                </div>
            <?php endif; ?>

            <?php if ($existingReview): ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    <strong>You've already reviewed this order.</strong> You can update your review below.
                </div>
            <?php endif; ?>

            <div class="card order-card">
                <h3>Order <?php echo htmlspecialchars($displayOrderId); ?> 
                    <span class="status-badge"><?php echo htmlspecialchars($order['Status']); ?></span>
                </h3>
                <p class="date">Ordered on <?php echo $formattedDate; ?></p>
                <hr>
                <div class="restaurant-info">
                    <div class="order-icon">
                        <i class="fa fa-utensils"></i>
                    </div>
                    <div class="rating-section">
                        <h4>Rate Your Order</h4>
                        <div class="order-details">
                            <p><strong>Order Total:</strong> RM <?php echo number_format($order['Total_Price'], 2); ?></p>
                            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['Payment_Method']); ?></p>
                        </div>
                        <div class="stars" id="star-rating">
                            <i class="fa-regular fa-star" data-value="1"></i>
                            <i class="fa-regular fa-star" data-value="2"></i>
                            <i class="fa-regular fa-star" data-value="3"></i>
                            <i class="fa-regular fa-star" data-value="4"></i>
                            <i class="fa-regular fa-star" data-value="5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card experience-card">
                <h3>Share your experience</h3>
                <p class="label">Write your review (Optional)</p>
                <textarea id="review-text" placeholder="Tell us what you loved or what could be improved..."><?php echo $existingReview ? htmlspecialchars($existingReview['Comment']) : ''; ?></textarea>
                
                <div class="button-group">
                    <button class="btn btn-cancel" onclick="cancelReview()">Cancel</button>
                    <button class="btn btn-submit" id="submit-btn" <?php echo $order['Status'] !== 'Delivered' ? 'disabled' : ''; ?>>
                        <?php echo $existingReview ? 'Update Review' : 'Submit Review'; ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </main>

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

        // Star rating functionality
        const stars = document.querySelectorAll('#star-rating i');
        let currentRating = <?php echo $existingReview ? $existingReview['Rating'] : 0; ?>;

        // Initialize stars if there's an existing review
        if (currentRating > 0) {
            updateStars(currentRating);
        }

        // Star Click Interaction
        stars.forEach(star => {
            star.addEventListener('click', () => {
                currentRating = star.getAttribute('data-value');
                updateStars(currentRating);
            });

            // Hover effect
            star.addEventListener('mouseover', () => {
                updateStars(star.getAttribute('data-value'));
            });
        });

        // Reset stars to actual rating when mouse leaves the container
        document.getElementById('star-rating').addEventListener('mouseleave', () => {
            updateStars(currentRating);
        });

        function updateStars(rating) {
            stars.forEach(star => {
                if (star.getAttribute('data-value') <= rating) {
                    star.classList.remove('fa-regular');
                    star.classList.add('fa-solid', 'active');
                } else {
                    star.classList.remove('fa-solid', 'active');
                    star.classList.add('fa-regular');
                }
            });
        }

        // Submit Review Logic
        document.getElementById('submit-btn').addEventListener('click', function() {
            const review = document.getElementById('review-text').value.trim();
            const btn = this;
            const originalText = btn.innerHTML;
            
            if (currentRating === 0) {
                alert('Please select a star rating before submitting!');
                return;
            }

            // Show loading state
            btn.innerHTML = '<span class="spinner"></span>Processing...';
            btn.disabled = true;

            // Send review to server
            fetch('ratingreview.php?order_id=<?php echo $orderId; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=submit_review&rating=${currentRating}&comment=${encodeURIComponent(review)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                } else {
                    alert('❌ ' + (data.message || 'Failed to submit review'));
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ An error occurred while submitting your review');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });

        // Cancel Review Function
        function cancelReview() {
            if (confirm('Are you sure you want to cancel? Your review will not be saved.')) {
                window.location.href = 'order-details.php?id=<?php echo $orderId; ?>';
            }
        }
    </script>
</body>
</html>