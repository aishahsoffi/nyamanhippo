<?php
// Start session
session_start();

// Enable error reporting for debugging (REMOVE IN PRODUCTION!)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$dbname = 'foodpanda_db';
$username = 'root';
$password = '';

$pdo = null;
$db_error = false;
$debug_info = []; // Store debug information

// Create database connection with error handling
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $debug_info[] = " Database connected successfully";
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    $db_error = true;
    $pdo = null;
    $debug_info[] = " Database connection failed: " . $e->getMessage();
}

// Initialize arrays for categories and items
$categories = [];
$items = [];

// Fetch data only if connection is successful
if ($pdo !== null && !$db_error) {
    try {
        // Fetch categories from database
        $categoriesQuery = "SELECT * FROM category ORDER BY Category_ID LIMIT 6";
        $categoriesStmt = $pdo->query($categoriesQuery);
        $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
        $debug_info[] = " Loaded " . count($categories) . " categories";
        
        // Debug: Show category names and expected image files
        foreach ($categories as $cat) {
            $slug = strtolower(str_replace(' ', '-', $cat['Category_Name']));
            $debug_info[] = "Category: " . $cat['Category_Name'] . " → Expected image: category-{$slug}.jpg";
        }

        // Fetch featured items from database (join with category)
        $itemsQuery = "SELECT i.*, c.Category_Name 
                       FROM item i 
                       JOIN category c ON i.Category_ID = c.Category_ID 
                       WHERE i.Stock > 0 AND i.Is_Featured = 1
                       ORDER BY i.Created_At DESC 
                       LIMIT 6";
        $itemsStmt = $pdo->query($itemsQuery);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        $debug_info[] = " Loaded " . count($items) . " featured items";
        
        // Debug: Show image paths
        foreach ($items as $item) {
            $debug_info[] = "Item: " . $item['Item_Name'] . " → Image: " . $item['Image'];
        }
    } catch(PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        $debug_info[] = " Query failed: " . $e->getMessage();
        // Continue with empty arrays, will show default content
    }
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$currentUser = null;

if ($isLoggedIn && $pdo !== null && !$db_error) {
    try {
        $userId = $_SESSION['user_id'];
        $userQuery = "SELECT * FROM user WHERE User_ID = ?";
        $userStmt = $pdo->prepare($userQuery);
        $userStmt->execute([$userId]);
        $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        // If user not found, clear session
        if (!$currentUser) {
            $isLoggedIn = false;
            session_destroy();
        } else {
            $debug_info[] = " User logged in: " . $currentUser['Name'];
        }
    } catch(PDOException $e) {
        error_log("User query failed: " . $e->getMessage());
        $debug_info[] = " User query failed: " . $e->getMessage();
    }
}

// Check if debug mode is enabled (add ?debug=1 to URL)
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>nyamanhippo - Order Food Online</title>
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Debug panel styles */
        .debug-panel {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #1a1a1a;
            color: #00ff00;
            padding: 10px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 9999;
            border-top: 2px solid #00ff00;
        }
        .debug-panel h4 {
            margin: 0 0 10px 0;
            color: #ffff00;
        }
        .debug-panel ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .debug-panel li {
            margin: 3px 0;
        }
        .debug-toggle {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: #ff2e63;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            z-index: 10000;
            font-weight: bold;
        }
        .image-not-found {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            font-size: 3rem;
        }
        /* discover cards  */
        .discover-card {
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .discover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
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
    </style>
</head>
<body>
    <?php if ($db_error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 12px; text-align: center; border-bottom: 2px solid #f5c6cb;">
            <strong>Notice:</strong> Database connection unavailable. Some features may be limited.
        </div>
    <?php endif; ?>

    <!-- Debug Toggle Button -->
    <?php if ($debug_mode): ?>
    <button class="debug-toggle" onclick="document.querySelector('.debug-panel').style.display = document.querySelector('.debug-panel').style.display === 'none' ? 'block' : 'none'">
        Toggle Debug
    </button>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-wrapper">
            <div class="logo">
                <img src="foodpanda-logo.jpg" alt="nyamanhippo Logo" class="logo-img" 
                     onerror="this.style.display='none'; console.error('Logo image not found: foodpanda-logo.jpg');">
                <h1>nyamanhippo</h1>
            </div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="browsing.html">Menu</a>
                <a href="login.php">Login</a>
                <a href="registration.php">Register</a>
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
                <button onclick="window.location.href='browsing.html'">Find Food</button>
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

                            // Map database categories to image files and fallback gradients
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
                            <div class="category-card" onclick="window.location.href='browsing.html'">
                                <div class="category-image">
                                    <img src="<?php echo $imagePath; ?>" 
                                         alt="<?php echo htmlspecialchars($categoryName); ?>" 
                                         onerror="this.parentElement.style.background='<?php echo $fallback; ?>'; this.style.display='none';">
                                </div>
                                <h3><?php echo htmlspecialchars($categoryName); ?></h3>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Default categories if database is empty -->
                        <div class="category-card" onclick="window.location.href='browsing.html'">
                            <div class="category-image">
                                <img src="category-burger.jpg" alt="Burgers" onerror="this.parentElement.style.background='linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%)'; this.style.display='none';">
                            </div>
                            <h3>Burgers</h3>
                        </div>
                        <div class="category-card" onclick="window.location.href='browsing.html'">
                            <div class="category-image">
                                <img src="category-pizza.jpg" alt="Pizza" onerror="this.parentElement.style.background='linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%)'; this.style.display='none';">
                            </div>
                            <h3>Pizza</h3>
                        </div>
                        <div class="category-card" onclick="window.location.href='browsing.html'">
                            <div class="category-image">
                                <img src="category-asian.jpg" alt="Asian" onerror="this.parentElement.style.background='linear-gradient(135deg, #F093FB 0%, #F5576C 100%)'; this.style.display='none';">
                            </div>
                            <h3>Asian</h3>
                        </div>
                        <div class="category-card" onclick="window.location.href='browsing.html'">
                            <div class="category-image">
                                <img src="category-desserts.jpg" alt="Desserts" onerror="this.parentElement.style.background='linear-gradient(135deg, #FA8BFF 0%, #2BD2FF 100%)'; this.style.display='none';">
                            </div>
                            <h3>Desserts</h3>
                        </div>
                        <div class="category-card" onclick="window.location.href='browsing.html'">
                            <div class="category-image">
                                <img src="category-beverages.jpg" alt="Beverages" onerror="this.parentElement.style.background='linear-gradient(135deg, #FFA8A8 0%, #FCFF00 100%)'; this.style.display='none';">
                            </div>
                            <h3>Beverages</h3>
                        </div>
                        <div class="category-card" onclick="window.location.href='browsing.html'">
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
                    <div class="discover-card" onclick="window.location.href='browsing.html'">
                        <img src="logo.inSum.jpg" alt="i's sum Coffee" onerror="this.parentElement.innerHTML='<div style=\'font-size: 2.5rem;\'><br><span style=\'font-size: 0.7rem;\'>i\'s sum</span></div>'">
                    </div>
                    <div class="discover-card" onclick="window.location.href='browsing.html'">
                        <img src="logo.burhambk.jpg" alt="Burhambk's" onerror="this.parentElement.innerHTML='<div style=\'font-size: 2.5rem;\'><br><span style=\'font-size: 0.7rem;\'>burhambk</span></div>'">
                    </div>
                    <div class="discover-card" onclick="window.location.href='browsing.html'">
                        <img src="logo.b.jpg" alt="B fresh juice" onerror="this.parentElement.innerHTML='<div style=\'font-size: 2.5rem;\'><br><span style=\'font-size: 0.7rem;\'>B</span></div>'">
                    </div>
                    <div class="discover-card" onclick="window.location.href='browsing.html'">
                        <img src="logo.tShop.jpg" alt="T shop" onerror="this.parentElement.innerHTML='<div style=\'font-size: 2.5rem;\'><br><span style=\'font-size: 0.7rem;\'>TShop</span></div>'">
                    </div>
                    <div class="discover-card" onclick="window.location.href='browsing.html'">
                        <img src="logo.cee.jpg" alt="C....ee" onerror="this.parentElement.innerHTML='<div style=\'font-size: 2.5rem;\'><br><span style=\'font-size: 0.7rem;\'>C....ee</span></div>'">
                    </div>
                    <div class="discover-card" onclick="window.location.href='browsing.html'">
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
                        <div class="item-card" onclick="window.location.href='browsing.html'">
                            <div class="item-image">
                                <img src="<?php echo htmlspecialchars($item['Image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['Item_Name']); ?>" 
                                     onerror="console.error('Item image not found: <?php echo htmlspecialchars($item['Image']); ?>'); this.parentElement.innerHTML='<span style=\'font-size:3.5rem\'>🍽️</span>'">
                            </div>
                            <div class="item-info">
                                <span class="item-category"><?php echo htmlspecialchars($item['Category_Name']); ?></span>
                                <h3 class="item-name"><?php echo htmlspecialchars($item['Item_Name']); ?></h3>
                                <p class="item-desc"><?php echo htmlspecialchars($item['Description']); ?></p>
                                <div class="item-footer">
                                    <span class="item-price">RM <?php echo number_format($item['Price'], 2); ?></span>
                                    <button class="btn-add" onclick="event.stopPropagation(); window.location.href='browsing.html'">Order Now</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default items if database is empty -->
                    <div class="item-card" onclick="window.location.href='browsing.html'">
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

    <!-- Debug Panel -->
    <?php if ($debug_mode): ?>
    <div class="debug-panel">
        <h4> Debug Information (Add ?debug=1 to URL to show this)</h4>
        <ul>
            <?php foreach ($debug_info as $info): ?>
                <li><?php echo htmlspecialchars($info); ?></li>
            <?php endforeach; ?>
            <li> Categories count: <?php echo count($categories); ?></li>
            <li> Items count: <?php echo count($items); ?></li>
            <li>Database errors: <?php echo $db_error ? 'YES' : 'NO'; ?></li>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Include Location Detector -->
    <script src="location-detector.js"></script>
    <script>
        // Initialize location detection on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log(' Page loaded successfully');
            console.log('📍 Initializing location detection...');
            
            // Initialize location detector
            const detector = initLocationDetector({
                showNotification: true,
                autoDetect: true,
                onSuccess: function(location) {
                    console.log(' Location detected:', location);
                    updateLocationDisplay(location);
                },
                onError: function(error) {
                    console.error(' Location error:', error);
                    updateLocationDisplayError();
                }
            });
            
            // Check for missing images
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

        // Update location display in hero
        function updateLocationDisplay(location) {
            const locationDisplay = document.getElementById('locationDisplay');
            const locationText = document.getElementById('locationText');
            
            if (locationDisplay && locationText) {
                locationDisplay.classList.remove('location-loading');
                
                if (location.isDefault) {
                    locationText.innerHTML = `📍 ${location.city} <span style="font-size: 0.75rem; opacity: 0.8;">(Default)</span>`;
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
                locationText.innerHTML = `📍 Kuala Lumpur <span style="font-size: 0.75rem; opacity: 0.8;">(Click to set location)</span>`;
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
    </script>
</body>
</html>