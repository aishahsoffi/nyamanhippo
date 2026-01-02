<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'foodpanda_db';
$username = 'root';
$password = '';

$pdo = null;
$db_error = false;
$debug_info = [];

// Create database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $debug_info[] = " Database connected successfully";
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    $db_error = true;
    $debug_info[] = " Database connection failed: " . $e->getMessage();
}

// Get current user data
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
        $debug_info[] = " User logged in: " . $currentUser['Name'];
    } catch(PDOException $e) {
        error_log("User query failed: " . $e->getMessage());
        $debug_info[] = " User query failed: " . $e->getMessage();
    }
}

// Fetch categories
$categories = [];
$items = [];
if ($pdo !== null && !$db_error) {
    try {
        $categoriesQuery = "SELECT * FROM category ORDER BY Category_ID LIMIT 6";
        $categoriesStmt = $pdo->query($categoriesQuery);
        $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
        $debug_info[] = " Loaded " . count($categories) . " categories";

        $itemsQuery = "SELECT i.*, c.Category_Name 
                       FROM item i 
                       JOIN category c ON i.Category_ID = c.Category_ID 
                       WHERE i.Stock > 0 AND i.Is_Featured = 1
                       ORDER BY i.Created_At DESC 
                       LIMIT 6";
        $itemsStmt = $pdo->query($itemsQuery);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        $debug_info[] = " Loaded " . count($items) . " featured items";
    } catch(PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        $debug_info[] = " Query failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>nyamanhippo - Order Food Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .cart-icon {
            position: relative;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -10px;
            background: white;
            color: #d70f64;
            border: 2px solid #d70f64;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 5px;
            border-radius: 50%;
            min-width: 18px;
            text-align: center;
        }

        .user-dropdown {
            position: relative;
        }

        .user-profile-btn {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 500;
            padding: 8px 14px;
            border-radius: 20px;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .user-profile-btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 120%;
            background: white;
            border-radius: 8px;
            min-width: 200px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: none;
            flex-direction: column;
            z-index: 1000;
            overflow: hidden;
        }

        .dropdown-menu a {
            padding: 12px 16px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
        }

        .dropdown-menu a:hover {
            background: #f5f5f5;
            color: #d70f64;
        }

        .dropdown-menu a i {
            width: 18px;
            text-align: center;
        }

        .dropdown-menu hr {
            border: none;
            border-top: 1px solid #eee;
            margin: 0;
        }

        /* Course Info Display */
        .nav-wrapper::after {
            content: "TMS3853 | Web Application Development";
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            padding: 0.5rem 1rem;
            font-size: 1.15rem;
            font-weight: 500;
            color: white;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        /* Location Display in Hero */
        .location-display {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            margin-top: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .location-display:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .location-display .icon {
            font-size: 1.2rem;
        }
        
        .location-display .text {
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .location-loading {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @media (max-width: 768px) {
            .nav-wrapper::after {
                display: none;
            }
        }

        /* To make discover cards clickable */
        .discover-card {
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .discover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-wrapper">
            <div class="logo">
                <img src="foodpanda-logo.jpg" alt="nyamanhippo Logo" class="logo-img" onerror="this.style.display='none';">
                <h1>nyamanhippo</h1>
            </div>
            <div class="nav-links">
                <a href="userIndex.php">Home</a>
                <a href="userBrowsing.php">Menu</a>
                <a href="cart.php" class="cart-icon">
                    <i class="fa fa-shopping-cart"></i>
                    <span class="cart-count" id="cart-count">0</span>
                </a>
                <div class="user-dropdown">
                    <button class="user-profile-btn" id="userDropdownBtn">
                        <i class="fa-regular fa-user"></i>
                        <span id="navUserName"><?php echo htmlspecialchars($currentUser['Name']); ?></span>
                        <i class="fa fa-caret-down"></i>
                    </button>
                    <div class="dropdown-menu" id="userDropdownMenu">
                        <a href="userProfile.php">
                            <i class="fa fa-user"></i> My Profile
                        </a>
                        <a href="dashboard.php">
                            <i class="fa fa-chart-line"></i> Dashboard
                        </a>
                        <hr>
                        <a href="logout.php" id="logoutBtn">
                            <i class="fa fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" style="background-image: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('hero-food.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat;">
        <div>
            <h1 class="hero-title">Hungry? Order Food Now!</h1>
            <p class="hero-subtitle">Your favourite restaurants delivered to your door</p>
            
            <!-- Location Display -->
            <div class="location-display location-loading" id="locationDisplay" onclick="requestLocationUpdate()">
                <span class="icon">📍</span>
                <span class="text" id="locationText">Detecting your location...</span>
            </div>
            
            <div class="hero-search">
                <input type="text" placeholder="Search for food or restaurants..." id="heroSearch">
                <button onclick="window.location.href='userBrowsing.php'">Find Food</button>
            </div>
        </div>
    </section>

    <!-- Categories and Discover More Section -->
    <div class="main-container">
        <div class="content-grid">
            <!-- LEFT SIDE: Categories -->
            <div class="categories-section">
                <h2>Cuisines</h2>
                <div class="category-grid">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <?php 
                            $categoryName = $category['Category_Name'];
                            $imageMap = [
                                'Beverages' => [
                                    'image' => 'category-beverages.jpg',
                                    'gradient' => 'linear-gradient(135deg, #FFA8A8 0%, #FCFF00 100%)'
                                ],
                                'Bubble Tea' => [
                                    'image' => 'category-bubble-tea.jpg',
                                    'gradient' => 'linear-gradient(135deg, #FA8BFF 0%, #2BD2FF 100%)'
                                ],
                                'Malaysian Food' => [
                                    'image' => 'category-malaysian-food.avif',
                                    'gradient' => 'linear-gradient(135deg, #F093FB 0%, #F5576C 100%)'
                                ],
                                'Pizza' => [
                                    'image' => 'category-pizza.jpg',
                                    'gradient' => 'linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%)'
                                ],
                                'Fast Food' => [
                                    'image' => 'category-fast-food.webp',
                                    'gradient' => 'linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%)'
                                ],
                                'Sushi' => [
                                    'image' => 'category-sushi.jpg',
                                    'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
                                ],
                                'Japanese Food' => [
                                    'image' => 'category-japanese-food.jpg',
                                    'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'
                                ],
                                'Burgers' => [
                                    'image' => 'category-burger.jpg',
                                    'gradient' => 'linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%)'
                                ]
                            ];
                            $categoryData = $imageMap[$categoryName] ?? [
                                'image' => 'category-default.jpg',
                                'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
                            ];
                            $imagePath = $categoryData['image'];
                            $fallback = $categoryData['gradient'];
                            ?>
                            <div class="category-card" onclick="window.location.href='userBrowsing.php'">
                                <div class="category-image">
                                    <img src="<?php echo $imagePath; ?>" 
                                         alt="<?php echo htmlspecialchars($categoryName); ?>" 
                                         onerror="this.parentElement.style.background='<?php echo $fallback; ?>'; this.style.display='none';">
                                </div>
                                <h3><?php echo htmlspecialchars($categoryName); ?></h3>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="category-card" onclick="window.location.href='userBrowsing.php'">
                            <div class="category-image">
                                <img src="category-burger.jpg" alt="Burgers" onerror="this.parentElement.style.background='linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%)'; this.style.display='none';">
                            </div>
                            <h3>Burgers</h3>
                        </div>
                        <div class="category-card" onclick="window.location.href='userBrowsing.php'">
                            <div class="category-image">
                                <img src="category-pizza.jpg" alt="Pizza" onerror="this.parentElement.style.background='linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%)'; this.style.display='none';">
                            </div>
                            <h3>Pizza</h3>
                        </div>
                        <div class="category-card" onclick="window.location.href='userBrowsing.php'">
                            <div class="category-image">
                                <img src="category-asian.jpg" alt="Asian" onerror="this.parentElement.style.background='linear-gradient(135deg, #F093FB 0%, #F5576C 100%)'; this.style.display='none';">
                            </div>
                            <h3>Asian</h3>
                        </div>
                        <div class="category-card" onclick="window.location.href='userBrowsing.php'">
                            <div class="category-image">
                                <img src="category-desserts.jpg" alt="Desserts" onerror="this.parentElement.style.background='linear-gradient(135deg, #FA8BFF 0%, #2BD2FF 100%)'; this.style.display='none';">
                            </div>
                            <h3>Desserts</h3>
                        </div>
                        <div class="category-card" onclick="window.location.href='userBrowsing.php'">
                            <div class="category-image">
                                <img src="category-beverages.jpg" alt="Beverages" onerror="this.parentElement.style.background='linear-gradient(135deg, #FFA8A8 0%, #FCFF00 100%)'; this.style.display='none';">
                            </div>
                            <h3>Beverages</h3>
                        </div>
                        <div class="category-card" onclick="window.location.href='userBrowsing.php'">
                            <div class="category-image">
                                <img src="category-chineese.jpg" alt="Sides" onerror="this.parentElement.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; this.style.display='none';">
                            </div>
                            <h3>Sides</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT SIDE: Discover More -->
            <div class="discover-section">
                <h2>Discover More</h2>
                <div class="discover-grid">
                    <div class="discover-card" onclick="window.location.href='userBrowsing.php'">
                        <img src="logo.inSum.jpg" alt="i's sum Coffee" onerror="this.parentElement.innerHTML='<div style=\'font-size: 2.5rem;\'><br><span style=\'font-size: 0.7rem;\'>i\'s sum</span></div>'">
                    </div>
                    <div class="discover-card" onclick="window.location.href='userBrowsing.php'">
                        <img src="logo.burhambk.jpg" alt="Burhambk's" onerror="this.parentElement.innerHTML='<div style=\'font-size: 2.5rem;\'><br><span style=\'font-size: 0.7rem;\'>burhambk</span></div>'">
                    </div>
                    <div class="discover-card" onclick="window.location.href='userBrowsing.php'">
                        <img src="logo.b.jpg" alt="B fresh juice" onerror="this.parentElement.innerHTML='<div style=\'font-size: 2.5rem;\'><br><span style=\'font-size: 0.7rem;\'>B</span></div>'">
                    </div>
                    <div class="discover-card" onclick="window.location.href='userBrowsing.php'">
                        <img src="logo.tShop.jpg" alt="T shop" onerror="this.parentElement.innerHTML='<div style=\'font-size: 2.5rem;\'><br><span style=\'font-size: 0.7rem;\'>TShop</span></div>'">
                    </div>
                    <div class="discover-card" onclick="window.location.href='userBrowsing.php'">
                        <img src="logo.cee.jpg" alt="C....ee" onerror="this.parentElement.innerHTML='<div style=\'font-size: 2.5rem;\'><br><span style=\'font-size: 0.7rem;\'>C....ee</span></div>'">
                    </div>
                    <div class="discover-card" onclick="window.location.href='userBrowsing.php'">
                        <img src="logo.j.jpg" alt="J" onerror="this.parentElement.innerHTML='<div style=\'font-size: 2.5rem;\'><br><span style=\'font-size: 0.7rem;\'>j</span></div>'">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Items Section -->
    <section class="featured">
        <div class="featured-container">
            <h2 class="section-title">Featured Items</h2>
            <div class="items-grid" id="featuredItems">
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                        <div class="item-card" onclick="window.location.href='userBrowsing.php'">
                            <div class="item-image">
                                <img src="<?php echo htmlspecialchars($item['Image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['Item_Name']); ?>" 
                                     onerror="console.error('Item image not found'); this.parentElement.innerHTML='<span style=\'font-size:3.5rem\'></span>'">
                            </div>
                            <div class="item-info">
                                <span class="item-category"><?php echo htmlspecialchars($item['Category_Name']); ?></span>
                                <h3 class="item-name"><?php echo htmlspecialchars($item['Item_Name']); ?></h3>
                                <p class="item-desc"><?php echo htmlspecialchars($item['Description']); ?></p>
                                <div class="item-footer">
                                    <span class="item-price">RM <?php echo number_format($item['Price'], 2); ?></span>
                                    <button class="btn-add" onclick="event.stopPropagation(); window.location.href='userBrowsing.php'">Order Now</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="item-card" onclick="window.location.href='userBrowsing.php'">
                        <div class="item-image">
                            <span style="font-size:3.5rem"></span>
                        </div>
                        <div class="item-info">
                            <span class="item-category">Burgers</span>
                            <h3 class="item-name">Classic Beef Burger</h3>
                            <p class="item-desc">Juicy beef patty with lettuce, tomato, and cheese</p>
                            <div class="item-footer">
                                <span class="item-price">RM 7.90</span>
                                <button class="btn-add" onclick="event.stopPropagation();">Order Now</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2025 nyamanhippo. All rights reserved. | CS-Erudite Group Project</p>
        <p style="font-size: 0.9rem; margin-top: 0.5rem;">
            Built with HTML, CSS, JavaScript, PHP, MySQL | TMS3853 Web Application Development
        </p>
    </footer>

    <!-- Include Location Detector -->
    <script src="location-detector.js"></script>
    <script>
        // User Dropdown Toggle
        document.getElementById('userDropdownBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = document.getElementById('userDropdownMenu');
            menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.querySelector('.user-dropdown');
            if (!dropdown.contains(e.target)) {
                document.getElementById('userDropdownMenu').style.display = 'none';
            }
        });

        // Update cart count from localStorage
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cart-count').textContent = totalItems;
        }

        // Update location display in hero
        function updateLocationDisplay(location) {
            const locationDisplay = document.getElementById('locationDisplay');
            const locationText = document.getElementById('locationText');
            
            if (locationDisplay && locationText) {
                locationDisplay.classList.remove('location-loading');
                
                if (location.isDefault) {
                    locationText.innerHTML = ` ${location.city} <span style="font-size: 0.75rem; opacity: 0.8;">(Default)</span>`;
                } else {
                    locationText.textContent = `Delivering to ${location.city}, ${location.state}`;
                }
            }
        }

        // Update location display on error
        function updateLocationDisplayError() {
            const locationDisplay = document.getElementById('locationDisplay');
            const locationText = document.getElementById('locationText');
            
            if (locationDisplay && locationText) {
                locationDisplay.classList.remove('location-loading');
                locationText.innerHTML = ` Kuala Lumpur <span style="font-size: 0.75rem; opacity: 0.8;">(Click to set location)</span>`;
            }
        }

        // Request location update
        function requestLocationUpdate() {
            const detector = window.locationDetector || locationDetector;
            if (detector) {
                const locationDisplay = document.getElementById('locationDisplay');
                const locationText = document.getElementById('locationText');
                
                locationDisplay.classList.add('location-loading');
                locationText.textContent = 'Detecting location...';
                
                detector.requestPermission(
                    (location) => updateLocationDisplay(location),
                    () => updateLocationDisplayError()
                );
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Update cart count
            updateCartCount();
            
            console.log('✓ Page loaded successfully');
            console.log('📍 Initializing location detection...');
            
            // Initialize location detector
            const detector = initLocationDetector({
                showNotification: true,
                autoDetect: true,
                onSuccess: function(location) {
                    console.log('✓ Location detected:', location);
                    updateLocationDisplay(location);
                },
                onError: function(error) {
                    console.error('✗ Location error:', error);
                    updateLocationDisplayError();
                }
            });
            
            // Check for missing images
            console.log(' Checking for missing images...');
            
            const images = document.querySelectorAll('img');
            let missingCount = 0;
            
            images.forEach(img => {
                img.addEventListener('error', function() {
                    missingCount++;
                    console.error(' Image not found:', this.src);
                });
                
                img.addEventListener('load', function() {
                    console.log(' Image loaded:', this.src);
                });
            });
            
            setTimeout(() => {
                if (missingCount > 0) {
                    console.warn(` Total missing images: ${missingCount}`);
                    console.log(' Tip: Check the Image Organization Guide for required files');
                }
            }, 2000);
        });
    </script>
</body>
</html>