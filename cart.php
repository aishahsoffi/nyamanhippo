<?php
// cart.php - Shopping Cart Page with Database Integration
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user information from session
$userName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User';

// Database configuration
$host = 'localhost';
$dbname = 'foodpanda_db';
$username = 'root';
$password = '';

$pdo = null;
$db_error = false;
$cartItems = [];
$cartCount = 0;

// Create database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    $db_error = true;
}

// Fetch cart items for logged-in user
if ($pdo && !$db_error) {
    try {
        $userId = $_SESSION['user_id'];
        
        // FIXED: Use correct PascalCase column names from your database
        $cartQuery = "SELECT c.Cart_ID, c.Item_ID, c.Quantity, 
                             i.Item_ID, i.Item_Name, i.Price, i.Image AS Image_Path, 
                             r.name AS Restaurant_Name
                      FROM cart c
                      JOIN item i ON c.Item_ID = i.Item_ID
                      LEFT JOIN restaurants r ON i.Restaurant_ID = r.id
                      WHERE c.User_ID = ?
                      ORDER BY r.name";
        
        $cartStmt = $pdo->prepare($cartQuery);
        $cartStmt->execute([$userId]);
        $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
        $cartCount = count($cartItems);
        
        // Debug: Log what we found
        error_log("Cart items found: " . $cartCount);
        
    } catch(PDOException $e) {
        error_log("Cart query failed: " . $e->getMessage());
        $db_error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - nyamanhippo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="navbar.css">
    
    <style>
        /* 1. RESET */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            background-color: #fff; 
            width: 100%; 
            overflow-x: hidden; 
        }

        /* 2. HEADER FIX */
        .navbar { width: 100%; display: block; }

        /* 3. CONTAINER */
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 40px;
        }
        
        .page-heading { color: var(--dark-color); margin-bottom: 2rem; }
        .page-heading h1 { font-weight: 700; font-size: 2rem; }

        /* --- CART LAYOUT --- */
        .cart-grid {
            display: grid;
            grid-template-columns: 2fr 1fr; 
            gap: 2rem;
            align-items: start;
        }

        /* --- LEFT COLUMN (SCROLLABLE) --- */
        .cart-items-column {
            max-height: 550px;       /* Limit the height */
            overflow-y: auto;        /* Add scroll capability */
            padding-right: 15px;     /* Space for the scrollbar */
        }

        /* --- CUSTOM PINK SCROLLBAR --- */
        .cart-items-column::-webkit-scrollbar { width: 8px; }
        .cart-items-column::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .cart-items-column::-webkit-scrollbar-thumb { background: #d70f64; border-radius: 4px; }
        .cart-items-column::-webkit-scrollbar-thumb:hover { background: #b00c50; }

        /* Cart Card Style */
        .cart-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .store-header { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; font-weight: 700; color: #2e2e2e; }
        .store-logo { width: 55px; height: 55px; object-fit: contain; border-radius: 4px; }
        .store-select { margin-left: auto; display: flex; align-items: center; gap: 6px; font-weight: 600; }
        .store-radio { width: 18px; height: 18px; accent-color: #d70f64; }

        .item-row { display: flex; gap: 15px; margin-top: 15px; align-items: center; border-bottom: 1px solid #f9f9f9; padding-bottom: 15px; }
        .item-row:last-child { border-bottom: none; padding-bottom: 0; }
        .item-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .item-details { flex-grow: 1; font-size: 0.95rem; color: #333; }
        .qty-controls { display: flex; align-items: center; gap: 8px; }
        .qty-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid #ddd; background: #fff; cursor: pointer; font-weight: 700; color: #d70f64; }
        .qty-input { width: 40px; text-align: center; border: 1px solid #ddd; border-radius: 6px; padding: 4px; }
        .line-price { font-weight: 700; color: #d70f64; min-width: 80px; text-align: right; }

        /* Right Column */
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
        .summary-items { margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid #eee; }
        .summary-item-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.9rem; color: #555; }
        .cost-row { display: flex; justify-content: space-between; margin-bottom: 10px; color: #666; font-size: 0.95rem; }
        .cost-row.discount { color: #dc3545; }
        .total-row { display: flex; justify-content: space-between; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee; font-size: 1.4rem; font-weight: 700; color: var(--primary-color); }
        .btn-place-order { width: 100%; background: var(--primary-color); color: white; border: none; padding: 1rem; border-radius: 8px; font-weight: 700; font-size: 1.1rem; cursor: pointer; margin-top: 1.2rem; transition: background 0.3s; box-shadow: 0 4px 10px rgba(215, 15, 100, 0.2); }
        .btn-place-order:hover { background: var(--secondary-color); transform: translateY(-2px); }

        .suggestions-panel { background: #fafafa; border-radius: 12px; padding: 1.5rem; border: 1px solid #eee; margin-top: 20px; }
        .suggested-item { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; background: white; padding: 10px; border-radius: 8px; border: 1px solid #eee; }
        .suggested-thumb { width: 40px; height: 40px; border-radius: 4px; object-fit: cover; }
        .add-btn-small { background: white; border: 1px solid #d70f64; color: #d70f64; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center; }
        .add-btn-small:hover { background: #d70f64; color: white; }

        @media (max-width: 900px) {
            .cart-grid { grid-template-columns: 1fr; }
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

    <div class="page-container">
        <div class="page-heading">
            <h1>Your Cart</h1>
        </div>

        <?php if ($db_error): ?>
            <div style="padding: 2rem; text-align: center; color: #d70f64; background: #fff3f3; border-radius: 8px; border: 1px solid #ffc4c4;">
                <p style="font-size: 1.1rem; margin-bottom: 1rem;">⚠️ Database connection error</p>
                <p style="color: #666;">Please try again later or contact support.</p>
            </div>
        <?php endif; ?>

        <div class="cart-grid">
            <div class="cart-items-column">
                <?php
                if (empty($cartItems) && !$db_error) {
                    echo '<div style="padding: 2rem; text-align: center; color: #999;">';
                    echo '<p style="font-size: 1.1rem; margin-bottom: 1rem;">Your cart is empty</p>';
                    echo '<a href="userBrowsing.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">Continue Shopping →</a>';
                    echo '</div>';
                } else if (!$db_error) {
                    // Group items by restaurant
                    $groupedByRestaurant = [];
                    foreach ($cartItems as $item) {
                        $restaurantName = $item['Restaurant_Name'] ?? 'Unknown Restaurant';
                        if (!isset($groupedByRestaurant[$restaurantName])) {
                            $groupedByRestaurant[$restaurantName] = [];
                        }
                        $groupedByRestaurant[$restaurantName][] = $item;
                    }
                    
                    // Display items grouped by restaurant
                    $restaurantIndex = 0;
                    foreach ($groupedByRestaurant as $restaurantName => $items) {
                        $restaurantId = strtolower(str_replace(' ', '-', $restaurantName));
                        $isFirst = ($restaurantIndex === 0) ? 'checked' : '';
                        $restaurantIndex++;
                        ?>
                        <div class="cart-card" data-store="<?php echo $restaurantId; ?>">
                            <div class="store-header">
                                <span><?php echo htmlspecialchars($restaurantName); ?></span>
                                <label class="store-select">
                                    <input type="checkbox" name="activeStore[]" class="store-checkbox" value="<?php echo $restaurantId; ?>" <?php echo $isFirst ? 'checked' : ''; ?>>
                                    Select
                                </label>
                            </div>

                            <?php foreach ($items as $item) { ?>
                                <div class="item-row" data-item-id="<?php echo $item['Item_ID']; ?>" data-name="<?php echo htmlspecialchars($item['Item_Name']); ?>" data-price="<?php echo $item['Price']; ?>" data-cart-id="<?php echo $item['Cart_ID']; ?>">
                                    <img src="<?php echo htmlspecialchars($item['Image_Path'] ?? 'placeholder.jpg'); ?>" class="item-thumb" onerror="this.src='https://via.placeholder.com/60'">
                                    <div class="item-details">
                                        <strong><?php echo htmlspecialchars($item['Item_Name']); ?></strong>
                                    </div>
                                    <div class="qty-controls">
                                        <button class="qty-btn" data-action="dec">-</button>
                                        <input class="qty-input" type="number" value="<?php echo $item['Quantity']; ?>" min="1">
                                        <button class="qty-btn" data-action="inc">+</button>
                                    </div>
                                    <span class="line-price">RM <?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></span>
                                </div>
                            <?php } ?>
                        </div>
                        <?php
                    }
                }
                ?>

            </div>

            <div class="right-column">
                <div class="summary-card">
                    <h3 class="summary-header">Order Summary</h3>
                    
                    <div class="summary-items" data-items>
                        <!-- JS will render items here -->
                    </div>

                    <div class="cost-row">
                        <span>Subtotal</span>
                        <span data-subtotal>RM 0.00</span>
                    </div>
                    <div class="cost-row">
                        <span>Delivery Fee</span>
                        <span data-delivery>RM 0.00</span>
                    </div>
                    <div class="cost-row">
                        <span>Service Fee</span>
                        <span data-service>RM 0.00</span>
                    </div>
                    <div class="cost-row discount">
                        <span>Voucher Discount</span>
                        <span data-discount>-RM 0.00</span>
                    </div>

                    <div class="total-row">
                        <span style="color:black; font-size:1.1rem;">Total</span>
                        <span data-total>RM 0.00</span>
                    </div>

                    <button id="btnCheckout" class="btn-place-order">Proceed to Checkout</button>
                    <p style="font-size: 0.8rem; color: #999; text-align: center; margin-top: 15px;">
                        Select stores above to review and checkout items from selected stores.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Utility to format RM amounts
function formatRM(value) { return `RM ${value.toFixed(2)}`; }

function recalcSummary() {
    // Update line prices for all rows regardless of store
    document.querySelectorAll('.item-row').forEach(row => {
        const price = parseFloat(row.dataset.price);
        const qty = parseInt(row.querySelector('.qty-input').value, 10) || 0;
        const line = price * qty;
        row.querySelector('.line-price').textContent = formatRM(line);
    });

    // Get all selected stores
    const selectedStores = Array.from(document.querySelectorAll('.store-checkbox:checked')).map(cb => cb.value);

    let subtotal = 0;
    let numberOfRestaurants = 0; // FIXED: Count restaurants with items
    const summary = document.querySelector('.summary-card');
    if (summary) {
        const list = summary.querySelector('[data-items]');
        list.innerHTML = '';

        selectedStores.forEach(store => {
            let restaurantHasItems = false;
            document.querySelectorAll(`.cart-card[data-store="${store}"] .item-row`).forEach(row => {
                const name = row.dataset.name;
                const price = parseFloat(row.dataset.price);
                const qty = parseInt(row.querySelector('.qty-input').value, 10) || 0;
                if (qty > 0) {
                    subtotal += price * qty;
                    restaurantHasItems = true;
                    const item = document.createElement('div');
                    item.className = 'summary-item-row';
                    item.innerHTML = `<span>${qty}x ${name}</span><span>${formatRM(price * qty)}</span>`;
                    list.appendChild(item);
                }
            });
            
            // FIXED: Count this restaurant if it has items
            if (restaurantHasItems) {
                numberOfRestaurants++;
            }
        });

        // FIXED: Calculate fees per restaurant (multiply by number of restaurants)
        const deliveryPerRestaurant = 5.00;
        const servicePerRestaurant = 2.00;
        const delivery = numberOfRestaurants > 0 ? deliveryPerRestaurant * numberOfRestaurants : 0.00;
        const service = numberOfRestaurants > 0 ? servicePerRestaurant * numberOfRestaurants : 0.00;
        const discount = 0.00;
        const total = subtotal + delivery + service - discount;

        summary.querySelector('[data-subtotal]').textContent = formatRM(subtotal);
        summary.querySelector('[data-delivery]').textContent = formatRM(delivery);
        summary.querySelector('[data-service]').textContent = formatRM(service);
        summary.querySelector('[data-discount]').textContent = `-` + formatRM(discount);
        summary.querySelector('[data-total]').textContent = formatRM(total);
    }

    // Update cart count in navbar
    updateNavbarCartCount();
}

// Update cart quantity in database
function updateCartQuantity(cartId, quantity) {
    console.log('Updating cart:', cartId, 'to quantity:', quantity);
    
    // Show loading indicator (optional)
    const row = document.querySelector(`[data-cart-id="${cartId}"]`);
    if (row) {
        row.style.opacity = '0.6';
    }
    
    return fetch('update_cart.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded' 
        },
        body: `cart_id=${cartId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Update response:', data);
        
        // Restore opacity
        if (row) {
            row.style.opacity = '1';
        }
        
        if (data.success) {
            // If item was deleted (quantity = 0), remove the row
            if (data.action === 'deleted') {
                if (row) {
                    // FIXED: Get the store card BEFORE removing the row
                    const storeCard = row.closest('.cart-card');
                    const storeId = storeCard ? storeCard.dataset.store : null;
                    
                    row.style.transition = 'all 0.3s';
                    row.style.transform = 'translateX(-100%)';
                    row.style.opacity = '0';
                    
                    setTimeout(() => {
                        row.remove();

                        // FIXED: Check if this restaurant has any items left
                        if (storeCard) {
                            const remainingItems = storeCard.querySelectorAll('.item-row');
                            
                            // If no items left in this restaurant, remove the entire restaurant card
                            if (remainingItems.length === 0) {
                                console.log('No items left in store:', storeId, '- removing restaurant card');
                                storeCard.style.transition = 'all 0.3s';
                                storeCard.style.transform = 'scale(0.8)';
                                storeCard.style.opacity = '0';
                                
                                setTimeout(() => {
                                    storeCard.remove();
                                    
                                    // Auto-select first remaining store if none selected
                                    if (document.querySelectorAll('.store-checkbox:checked').length === 0) {
                                        const firstRemaining = document.querySelector('.store-checkbox');
                                        if (firstRemaining) {
                                            firstRemaining.checked = true;
                                            console.log('Auto-selected first remaining store');
                                        }
                                    }
                                    
                                    recalcSummary();
                                    checkIfCartEmpty();
                                }, 300);
                            } else {
                                // Items still exist, just recalculate
                                recalcSummary();
                            }
                        } else {
                            recalcSummary();
                            checkIfCartEmpty();
                        }
                    }, 300);
                }
            } else {
                // Success - just recalculate
                recalcSummary();
            }
            return true;
        } else {
            // Error - show message and revert
            alert(data.message || 'Failed to update cart');
            if (row) {
                const input = row.querySelector('.qty-input');
                // Revert to previous value
                input.value = parseInt(input.dataset.previousValue || 1);
            }
            recalcSummary();
            return false;
        }
    })
    .catch(error => {
        console.error('Error updating cart:', error);
        alert('An error occurred while updating the cart');
        
        // Restore opacity and revert
        if (row) {
            row.style.opacity = '1';
            const input = row.querySelector('.qty-input');
            input.value = parseInt(input.dataset.previousValue || 1);
        }
        recalcSummary();
        return false;
    });
}

// Update navbar cart count
function updateNavbarCartCount() {
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            const cartCountEl = document.getElementById('cart-count');
            if (cartCountEl) {
                cartCountEl.textContent = data.count || 0;
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
}

// Check if cart is empty after deletion
function checkIfCartEmpty() {
    const itemRows = document.querySelectorAll('.item-row');
    if (itemRows.length === 0) {
        const cartColumn = document.querySelector('.cart-items-column');
        if (cartColumn) {
            cartColumn.innerHTML = `
                <div style="padding: 2rem; text-align: center; color: #999;">
                    <p style="font-size: 1.1rem; margin-bottom: 1rem;">Your cart is empty</p>
                    <a href="userBrowsing.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">Continue Shopping →</a>
                </div>
            `;
        }
        
        // Also update summary to show 0
        recalcSummary();
    }
}

function attachQtyHandlers() {
    document.querySelectorAll('.item-row').forEach(row => {
        const dec = row.querySelector('.qty-btn[data-action="dec"]');
        const inc = row.querySelector('.qty-btn[data-action="inc"]');
        const input = row.querySelector('.qty-input');
        const cartId = row.dataset.cartId;
        
        // Store initial value
        input.dataset.previousValue = input.value;
        
        // Decrease quantity
        dec.addEventListener('click', () => {
            const currentVal = parseInt(input.value, 10) || 1;
            const newVal = currentVal - 1;
            
            if (newVal <= 0) {
                // Confirm deletion
                if (confirm('Remove this item from cart?')) {
                    input.dataset.previousValue = input.value;
                    input.value = 0;
                    updateCartQuantity(cartId, 0);
                }
            } else {
                input.dataset.previousValue = input.value;
                input.value = newVal;
                updateCartQuantity(cartId, newVal);
            }
        });
        
        // Increase quantity
        inc.addEventListener('click', () => {
            const currentVal = parseInt(input.value, 10) || 1;
            const newVal = currentVal + 1;
            
            input.dataset.previousValue = input.value;
            input.value = newVal;
            updateCartQuantity(cartId, newVal);
        });
        
        // Manual input change
        input.addEventListener('change', () => {
            const v = parseInt(input.value, 10);
            let finalVal = isNaN(v) || v < 0 ? 0 : v;
            
            if (finalVal === 0) {
                if (confirm('Remove this item from cart?')) {
                    input.dataset.previousValue = input.value;
                    input.value = 0;
                    updateCartQuantity(cartId, 0);
                } else {
                    // Revert to previous value
                    input.value = input.dataset.previousValue || 1;
                }
            } else {
                input.dataset.previousValue = input.value;
                input.value = finalVal;
                updateCartQuantity(cartId, finalVal);
            }
        });
        
        // Prevent negative values on input
        input.addEventListener('input', () => {
            if (parseInt(input.value) < 0) {
                input.value = 0;
            }
        });
    });
}

function buildCheckoutData() {
    const selectedStores = Array.from(document.querySelectorAll('.store-checkbox:checked')).map(cb => cb.value);
    const orders = [];
    let totalSubtotal = 0;
    let totalDelivery = 0;
    let totalService = 0;

    selectedStores.forEach(store => {
        const items = [];
        let subtotal = 0;

        document.querySelectorAll(`.cart-card[data-store="${store}"] .item-row`).forEach(row => {
            const name = row.dataset.name;
            const price = parseFloat(row.dataset.price);
            const qty = parseInt(row.querySelector('.qty-input').value, 10) || 0;

            if (qty > 0) {
                items.push({ name, price, qty });
                subtotal += price * qty;
            }
        });

        if (items.length > 0) {
            const delivery = subtotal > 0 ? 5.0 : 0.0;
            const service = subtotal > 0 ? 2.0 : 0.0;
            const total = subtotal + delivery + service;

            orders.push({
                store,
                items,
                subtotal,
                delivery,
                service,
                total
            });

            totalSubtotal += subtotal;
            totalDelivery += delivery;
            totalService += service;
        }
    });

    const grandTotal = totalSubtotal + totalDelivery + totalService;

    return {
        orders,
        subtotal: totalSubtotal,
        delivery: totalDelivery,
        service: totalService,
        total: grandTotal
    };
}

// Setup after DOM ready
document.addEventListener('DOMContentLoaded', () => {
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
    
    // Attach quantity handlers
    attachQtyHandlers();
    
    // Default select first store if none selected
    const firstStore = document.querySelector('.store-checkbox');
    if (firstStore && !document.querySelector('.store-checkbox:checked')) {
        firstStore.checked = true;
    }
    
    // Store checkbox change handler
    document.querySelectorAll('.store-checkbox').forEach(cb => {
        cb.addEventListener('change', recalcSummary);
    });
    
    // Initial calculation
    recalcSummary();

    // Checkout button handler
    const btn = document.getElementById('btnCheckout');
    if (btn) {
        btn.addEventListener('click', () => {
            const data = buildCheckoutData();

            if (!data.orders || data.orders.length === 0) {
                alert('Please select stores and add items to checkout.');
                return;
            }

            sessionStorage.setItem('checkoutData', JSON.stringify(data));
            window.location.href = 'checkout.php';
        });
    }
    
    console.log('Cart page initialized successfully');
});
    </script>
</body>
</html>
