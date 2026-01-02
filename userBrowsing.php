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

// Create database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    $db_error = true;
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
    } catch(PDOException $e) {
        error_log("User query failed: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>nyamanhippo | Browsing Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
/* ============================================
   GLOBAL STYLES & RESET
   ============================================ */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary-color: #d70f64;
    --secondary-color: #ff2b85;
    --dark-color: #2e2e2e;
    --light-color: #f8f9fa;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: #333;
    background-color: #fff;
}

/* ============================================
   NAVIGATION - EXACT COPY FROM userIndex.php
   ============================================ */

.navbar {
    background: var(--primary-color);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.nav-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 40px;
    height: 60px;
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.logo-img {
    height: 40px;
    width: auto;
}

.logo h1 {
    color: white;
    font-size: 1.5rem;
    font-weight: 400;
    margin: 0;
}

.nav-links {
    display: flex;
    gap: 2rem;
    align-items: center;
}

.nav-links a {
    text-decoration: none;
    color: white;
    font-weight: 500;
    font-size: 0.95rem;
    transition: opacity 0.3s;
}

.nav-links a:hover {
    opacity: 0.8;
}

/* Cart Icon Styling */
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

/* USER DROPDOWN - EXACT COPY FROM userIndex.php */
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

/* ============================================
   PAGE SPECIFIC STYLES
   ============================================ */

/* Hero Section */
.hero {
    height: 250px;
    width: 100%;
    overflow: hidden;
}

.hero img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Main Layout */
.main-layout {
    display: flex;
    padding: 20px 40px;
    gap: 40px;
}

/* Sidebar */
.sidebar {
    width: 200px;
    flex-shrink: 0;
}

.sidebar h3 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
}

.filter-group {
    margin-bottom: 25px;
}

.filter-label {
    font-size: 12px;
    color: #777;
    margin-bottom: 10px;
    letter-spacing: 1px;
}

.sidebar label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    color: #333;
    cursor: pointer;
}

.price-inputs {
    display: flex;
    align-items: center;
    gap: 5px;
}

.price-inputs input {
    width: 60px;
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.apply-filters-btn {
    width: 100%;
    background: #d70f64;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 10px;
}

.apply-filters-btn:hover {
    background: #b00c50;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(215, 15, 100, 0.3);
}

/* Content Area */
.content {
    flex-grow: 1;
}

.section-header h2 {
    font-size: 20px;
    margin-bottom: 20px;
    color: #333;
}

.brand-grid {
    display: flex;
    gap: 15px;
    margin-bottom: 40px;
    align-items: center;
    flex-wrap: wrap;
}

.brand-card {
    width: 90px;
    height: 90px;
    border: 1px solid #eee;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.2s;
}

.brand-card:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.brand-card img {
    width: 80%;
    height: auto;
}

.scroll-arrow {
    width: 40px;
    height: 40px;
    border: 1px solid #ccc;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    margin-left: 10px;
}

/* All Restaurants Grid */
.all-restaurants-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
}

.restaurant-item { 
    cursor: pointer; 
    transition: 0.2s; 
}

.restaurant-item:hover { 
    transform: translateY(-5px); 
}

.res-image-wrapper { 
    position: relative; 
    border-radius: 12px; 
    overflow: hidden; 
    height: 180px; 
}

.res-image-wrapper img { 
    width: 100%; 
    height: 100%; 
    object-fit: cover; 
}

.promo-tag {
    position: absolute; 
    top: 10px; 
    left: 10px;
    background: #d70f64; 
    color: white; 
    padding: 4px 8px;
    font-size: 11px; 
    font-weight: bold; 
    border-radius: 4px;
}

.res-details { 
    padding: 12px 5px; 
}

.res-details h3 { 
    font-size: 18px; 
    margin-bottom: 4px; 
}

.res-info { 
    font-size: 14px; 
    color: #707070; 
}

.res-rating { 
    font-size: 13px; 
    font-weight: bold; 
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1000px) { 
    .all-restaurants-grid { 
        grid-template-columns: repeat(2, 1fr); 
    } 
}

@media (max-width: 768px) {
    .nav-wrapper {
        padding: 0 20px;
    }

    .nav-links {
        gap: 1rem;
        font-size: 0.85rem;
    }

    .main-layout {
        flex-direction: column;
    }
    
    .sidebar {
        width: 100%;
    }
}

@media (max-width: 600px) { 
    .all-restaurants-grid { 
        grid-template-columns: 1fr; 
    }
}

@media (max-width: 480px) {
    .nav-links {
        gap: 0.7rem;
        flex-wrap: wrap;
        font-size: 0.8rem;
    }
}
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-wrapper">
            <div class="logo">
                <img src="foodpanda-logo.jpg" alt="foodpanda" class="logo-img">
                <h1>nyamanhippo</h1>
            </div>
            <div class="nav-links">
                <a href="userIndex.php">Home</a>
                <a href="userBrowsing.php">Menu</a>
                <a href="cart.php" class="cart-icon">
                    <i class="fa fa-shopping-cart"></i>
                    <span class="cart-count">0</span>
                </a>
                
                <!-- User Dropdown -->
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

    <header class="hero">
        <img src="https://images.unsplash.com/photo-1579871494447-9811cf80d66c?auto=format&fit=crop&q=80&w=2070" alt="Sushi Banner">
    </header>

    <main class="main-layout">
        <aside class="sidebar">
            <h3>Filters</h3>
            
            <div class="filter-group">
                <p class="filter-label">SORT BY</p>
                <label><input type="radio" name="sort" checked> Relevance</label>
                <label><input type="radio" name="sort"> Popularity</label>
                <label><input type="radio" name="sort"> Distance</label>
            </div>

            <div class="filter-group">
                <p class="filter-label">PRICE RANGE</p>
                <div class="price-inputs">
                    <input type="number" placeholder="0">
                    <span>-</span>
                    <input type="number" placeholder="100">
                </div>
            </div>

            <div class="filter-group">
                <p class="filter-label">CATEGORIES</p>
                <div id="categoryContainer">
                    </div>
            </div>

            <button class="apply-filters-btn">Apply Filters</button>
        </aside>

        <section class="content">
            <div class="section-header">
                <h2>Your Favourites</h2>
            </div>
            <div class="brand-grid">
                <div class="brand-card sushiking-card" onclick="window.location.href='sushiking.html'">
                    <img src="logo.sshissn.jpg" alt="Sshissns">
                </div>
                <div class="brand-card unclebob-card" onclick="window.location.href='unclebob.html'">
                    <img src="logo.chikiido.jpg" alt="Chikiido ">
                </div>
                <div class="brand-card tealive-card" onclick="window.location.href='tealive.html'">
                    <img src="logo.tShop.jpg" alt="tShop">
                </div>
                <div class="brand-card topglobal-card" onclick="window.location.href='topglobal.html'">
                    <img src="logo.gepukz.jpg" alt="Gepukz">
                </div>
                <div class="brand-card chagee-card" onclick="window.location.href='chagee.html'">
                    <img src="logo.cee.jpg" alt="C...ee">
                </div>
            </div>

            <div class="section-header">
                <h2>Top Brands</h2>
            </div>
            <div class="brand-grid">
                <div class="brand-card burhambk" onclick="window.location.href='comingsoon.html'">
        <img src="logo.burhambk.jpg" alt="Burhambk">
    </div>
    <div class="brand-card k" onclick="window.location.href='comingsoon.html'">
        <img src="logo.k.jpg" alt="K">
    </div>
    <div class="brand-card i'ssum coffee" onclick="window.location.href='comingsoon.html'">
        <img src="logo.insum.jpg" alt="i'ssum coffee">
    </div>
    <div class="brand-card j" onclick="window.location.href='comingsoon.html'">
        <img src="logo.j.jpg" alt="J">
    </div>
    <div class="brand-card B" onclick="window.location.href='comingsoon.html'">
        <img src="logo.b.jpg" alt="B">
    </div>
            </div>

            <div class="section-header"><h2>All Restaurants</h2></div>
            <div class="all-restaurants-grid" id="allRestaurants">
                <!-- Restaurants will be dynamically rendered here -->
            </div>
        </section>
    </main>

    <script src="userBrowsing.js"></script>
    
    <script>
        // ===============================
        // USER DROPDOWN LOGIC
        // ===============================

        // Toggle dropdown
        document.getElementById('userDropdownBtn')
            .addEventListener('click', function (e) {
                e.stopPropagation();
                const menu = document.getElementById('userDropdownMenu');
                menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
            });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            const dropdown = document.querySelector('.user-dropdown');
            if (!dropdown.contains(e.target)) {
                document.getElementById('userDropdownMenu').style.display = 'none';
            }
        });

        // Logout
        document.getElementById('logoutBtn').addEventListener('click', function (e) {
            e.preventDefault();
            if (confirm('Logout now?')) {
                window.location.href = 'logout.php';
            }
        });
    </script>
</body>
</html>