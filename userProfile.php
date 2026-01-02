<?php
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

// Get current user data and addresses
$currentUser = null;
$addresses = [];
$paymentMethods = [];

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
        
        // Get user addresses from database
        $addressQuery = "SELECT * FROM address WHERE User_ID = ? ORDER BY Is_Default DESC, Created_At DESC";
        $addressStmt = $pdo->prepare($addressQuery);
        $addressStmt->execute([$userId]);
        $addresses = $addressStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get user payment methods from database
        $paymentQuery = "SELECT * FROM payment_method WHERE User_ID = ? ORDER BY Is_Default DESC, Created_At DESC";
        $paymentStmt = $pdo->prepare($paymentQuery);
        $paymentStmt->execute([$userId]);
        $paymentMethods = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("User query failed: " . $e->getMessage());
    }
}

// Set user data
$fullName = $currentUser['Name'] ?? 'James Smith';
$nameParts = explode(' ', $fullName, 2);
$firstName = $nameParts[0] ?? 'James';
$lastName = $nameParts[1] ?? 'Smith';
$email = $currentUser['Email'] ?? 'james@email.com';
$phone = $currentUser['Phone'] ?? '+60123456789';
$profilePicture = $currentUser['Profile_Picture'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | nyamanhippo</title>
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <a href="cart.php" class="cart-icon">
                    <i class="fa fa-shopping-cart"></i>
                    <span class="cart-count" id="cart-count">0</span>
                </a>
                <a href="userProfile.php" class="user-profile active">
                    <i class="fa-regular fa-user"></i>
                    <span id="userName"><?php echo htmlspecialchars($firstName); ?></span>
                </a>
            </div>
        </div>
    </nav>

    <main class="profile-container">
        <button class="back-btn" onclick="goBack()">
            <i class="fa fa-arrow-left"></i> Back
        </button>

        <div class="profile-header">
            <div class="profile-avatar" id="profileAvatar">
                <?php if ($profilePicture): ?>
                    <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture">
                <?php else: ?>
                    <i class="fa fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h1 id="fullName"><?php echo htmlspecialchars($fullName); ?></h1>
                <p class="email" id="userEmail"><?php echo htmlspecialchars($email); ?></p>
                <button class="edit-profile-btn" onclick="window.location.href='editProfile.php'">
                    <i class="fa fa-edit"></i> Edit Profile
                </button>
            </div>
        </div>

        <div class="profile-content">
            <div class="profile-section address-section">
                <h2><i class="fa fa-map-marker-alt"></i> Delivery Addresses</h2>
                
                <?php if (empty($addresses)): ?>
                    <p style="color: #999; padding: 20px; text-align: center;">
                        No addresses added yet. Add your first delivery address!
                    </p>
                <?php else: ?>
                    <?php foreach ($addresses as $addr): ?>
                        <div class="address-card">
                            <div class="address-header">
                                <h3><?php echo htmlspecialchars($addr['Label']); ?></h3>
                                <?php if ($addr['Is_Default']): ?>
                                    <span class="default-badge">Default</span>
                                <?php endif; ?>
                            </div>
                            <p><?php echo htmlspecialchars($addr['Street']); ?></p>
                            <p><?php echo htmlspecialchars($addr['City']); ?>, <?php echo htmlspecialchars($addr['Postcode']); ?></p>
                            <?php if (!empty($addr['State'])): ?>
                                <p><?php echo htmlspecialchars($addr['State']); ?></p>
                            <?php endif; ?>
                            <p><?php echo htmlspecialchars($addr['Country']); ?></p>
                            <div class="address-actions">
                                <button class="action-btn edit-address-btn" onclick="window.location.href='editAddress.php?id=<?php echo $addr['Address_ID']; ?>'">
                                    <i class="fa fa-edit"></i> Edit
                                </button>
                                <button class="action-btn delete delete-address-btn" onclick="deleteAddress(<?php echo $addr['Address_ID']; ?>)">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <button class="add-address-btn" onclick="window.location.href='editAddress.php'">
                    <i class="fa fa-plus"></i> Add New Address
                </button>
            </div>

            <div class="profile-section payment-section">
                <h2><i class="fa fa-credit-card"></i> Payment Methods</h2>
                
                <?php if (empty($paymentMethods)): ?>
                    <p style="color: #999; padding: 20px; text-align: center;">
                        No payment methods added yet. Add your first payment card!
                    </p>
                <?php else: ?>
                    <?php foreach ($paymentMethods as $payment): ?>
                        <?php 
                            $last4 = substr(str_replace(' ', '', $payment['Card_Number']), -4);
                        ?>
                        <div class="payment-card">
                            <div class="card-icon">
                                <i class="fa fa-credit-card"></i>
                            </div>
                            <div class="card-details">
                                <h3>
                                    <?php echo htmlspecialchars($payment['Card_Type']); ?> •••• <?php echo htmlspecialchars($last4); ?>
                                    <?php if ($payment['Is_Default']): ?>
                                        <span class="default-badge" style="font-size: 10px; margin-left: 8px;">Default</span>
                                    <?php endif; ?>
                                </h3>
                                <p>Expires <?php echo htmlspecialchars($payment['Expiry_Date']); ?></p>
                                <p style="font-size: 12px; color: #999; margin-top: 4px;"><?php echo htmlspecialchars($payment['Card_Holder_Name']); ?></p>
                            </div>
                            <div class="card-actions">
                                <button class="action-btn edit-payment-btn" onclick="window.location.href='editPayment.php?id=<?php echo $payment['Payment_Method_ID']; ?>'">
                                    <i class="fa fa-edit"></i> Edit
                                </button>
                                <button class="action-btn delete delete-payment-btn" onclick="deletePayment(<?php echo $payment['Payment_Method_ID']; ?>); return false;">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <button class="add-payment-btn" onclick="window.location.href='editPayment.php'">
                    <i class="fa fa-plus"></i> Add Payment Method
                </button>
            </div>

            <div class="profile-section">
                <h2><i class="fa fa-history"></i> Recent Orders</h2>
                <button class="view-all-btn" onclick="window.location.href='dashboard.php'">View All Orders</button>
            </div>

            <div class="profile-section">
                <h2><i class="fa fa-cog"></i> Account Settings</h2>
                <div class="settings-list">
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Notifications</h3>
                            <p>Manage your notification preferences</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" class="setting-toggle" data-setting="notifications" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Email Updates</h3>
                            <p>Receive promotional emails and offers</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" class="setting-toggle" data-setting="emailUpdates" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>SMS Alerts</h3>
                            <p>Get order updates via SMS</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" class="setting-toggle" data-setting="smsAlerts">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="profile-section danger-zone">
                <h2><i class="fa fa-exclamation-triangle"></i> Danger Zone</h2>
                <button class="logout-btn" id="logoutBtn"><i class="fa fa-sign-out-alt"></i> Logout</button>
                <button class="delete-account-btn" id="deleteAccountBtn"><i class="fa fa-trash"></i> Delete Account</button>
            </div>
        </div>
    </main>

    <script>
    // Pass PHP data to JavaScript
    const userData = {
        firstName: <?php echo json_encode($firstName); ?>,
        lastName: <?php echo json_encode($lastName); ?>,
        email: <?php echo json_encode($email); ?>,
        phone: <?php echo json_encode($phone); ?>,
        profilePicture: <?php echo json_encode($profilePicture); ?>
    };

    // ============================================
    // DELETE ADDRESS FUNCTION (using AJAX)
    // ============================================
    function deleteAddress(addressId) {
        if (confirm('Are you sure you want to delete this address?')) {
            // Create form data
            const formData = new FormData();
            formData.append('delete_address', '1');
            formData.append('address_id', addressId);
            
            // Send AJAX request
            fetch('deleteAddress.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Address deleted successfully!');
                    location.reload();
                } else {
                    alert('Failed to delete address: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the address.');
            });
        }
    }

    // ============================================
    // CART AND USER PROFILE FUNCTIONS
    // ============================================

    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = totalItems;
        }
    }

    function loadUserProfile() {
        // User data is already loaded from PHP, but keep localStorage sync
        const userProfile = {
            firstName: userData.firstName,
            lastName: userData.lastName,
            email: userData.email,
            phone: userData.phone,
            profilePicture: userData.profilePicture
        };
        localStorage.setItem('userProfile', JSON.stringify(userProfile));
    }

    function loadUserSettings() {
        const settings = JSON.parse(localStorage.getItem('userSettings')) || {
            notifications: true,
            emailUpdates: true,
            smsAlerts: false
        };

        document.querySelectorAll('.setting-toggle').forEach(toggle => {
            const settingName = toggle.getAttribute('data-setting');
            if (settings[settingName] !== undefined) {
                toggle.checked = settings[settingName];
            }
        });
    }

    function saveUserSettings() {
        const settings = {};
        document.querySelectorAll('.setting-toggle').forEach(toggle => {
            const settingName = toggle.getAttribute('data-setting');
            settings[settingName] = toggle.checked;
        });
        localStorage.setItem('userSettings', JSON.stringify(settings));
    }

    // ============================================
    // PAYMENT FUNCTIONS - No longer needed (using database)
    // ============================================

    // Payment methods are now loaded from database via PHP
    // Keeping localStorage sync for backward compatibility if needed
    function syncPaymentsToLocalStorage() {
        // Optional: You can remove this function if not using localStorage anymore
    }

    // ============================================
    // OTHER FUNCTIONS
    // ============================================

    function goBack() {
        if (document.referrer) {
            window.history.back();
        } else {
            window.location.href = 'userIndex.php';
        }
    }

    // Logout
    document.getElementById('logoutBtn').addEventListener('click', () => {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    });

    // Delete Account - Simplified (no annoying popups)
    document.getElementById('deleteAccountBtn').addEventListener('click', () => {
        // Directly redirect to delete account page
        window.location.href = 'deleteAccount.php';
    });

    // Toggle Switches
    document.querySelectorAll('.setting-toggle').forEach(toggle => {
        toggle.addEventListener('change', (e) => {
            const settingName = e.target.getAttribute('data-setting');
            const isEnabled = e.target.checked;
            
            console.log(`${settingName}: ${isEnabled ? 'Enabled' : 'Disabled'}`);
            saveUserSettings();
            
            const settingInfo = e.target.closest('.setting-item').querySelector('h3').textContent;
            const message = isEnabled ? 'enabled' : 'disabled';
            console.log(`${settingInfo} ${message}`);
        });
    });

    // ============================================
    // INITIALIZE ON PAGE LOAD
    // ============================================

    document.addEventListener('DOMContentLoaded', () => {
        updateCartCount();
        loadUserProfile();
        loadUserSettings();
        console.log('User Profile page loaded');
    });
    </script>
</body>
</html>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background-color: #f8f9fa;
}

/* Back Button */
.back-btn {
    background: transparent;
    border: none;
    color: #d70f64;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    padding: 0;
}

.back-btn:hover {
    text-decoration: underline;
}

/* Cart Count Styling */
.cart-count {
    position: absolute;
    top: -8px;
    right: -10px;
    background: #d70f64;
    border: 2px solid white;
    font-size: 10px;
    padding: 2px 5px;
    border-radius: 50%;
    min-width: 18px;
    text-align: center;
}

.cart-icon {
    position: relative;
    display: flex;
    align-items: center;
    gap: 5px;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 6px;
}

.user-profile.active {
    opacity: 0.8;
}

/* Profile Container */
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* Profile Header */
.profile-header {
    background: white;
    border-radius: 16px;
    padding: 40px;
    display: flex;
    align-items: center;
    gap: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, #d70f64 0%, #e91e63 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: white;
    overflow: hidden;
    flex-shrink: 0;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-info h1 {
    font-size: 32px;
    color: #2e2e2e;
    margin-bottom: 8px;
}

.email {
    color: #666;
    font-size: 16px;
    margin-bottom: 15px;
}

.edit-profile-btn {
    background: #d70f64;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.edit-profile-btn:hover {
    background: #b00c50;
    transform: translateY(-2px);
}

/* Profile Content */
.profile-content {
    display: grid;
    gap: 25px;
}

.profile-section {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.profile-section h2 {
    font-size: 20px;
    color: #2e2e2e;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile-section h2 i {
    color: #d70f64;
}

/* Address Card */
.address-card {
    background: #f8f9fa;
    border: 1px solid #e5e5e5;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
}

.address-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.address-header h3 {
    font-size: 16px;
    color: #2e2e2e;
}

.default-badge {
    background: #d70f64;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.address-card p {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
}

.address-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.action-btn {
    background: transparent;
    border: 1px solid #d70f64;
    color: #d70f64;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.action-btn:hover {
    background: #d70f64;
    color: white;
}

.action-btn.delete {
    border-color: #dc3545;
    color: #dc3545;
}

.action-btn.delete:hover {
    background: #dc3545;
    color: white;
}

.add-address-btn, .add-payment-btn {
    width: 100%;
    background: #f8f9fa;
    border: 2px dashed #d70f64;
    color: #d70f64;
    padding: 15px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.add-address-btn:hover, .add-payment-btn:hover {
    background: #fff0f6;
    border-color: #b00c50;
}

/* Payment Card */
.payment-card {
    background: #f8f9fa;
    border: 1px solid #e5e5e5;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
}

.card-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #d70f64 0%, #e91e63 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    flex-shrink: 0;
}

.card-details {
    flex: 1;
}

.card-details h3 {
    font-size: 16px;
    color: #2e2e2e;
    margin-bottom: 5px;
}

.card-details p {
    font-size: 14px;
    color: #666;
}

.card-actions {
    display: flex;
    gap: 10px;
}

.view-all-btn {
    width: 100%;
    background: transparent;
    border: 1px solid #d70f64;
    color: #d70f64;
    padding: 12px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 10px;
}

.view-all-btn:hover {
    background: #d70f64;
    color: white;
}

/* Settings List */
.settings-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 12px;
}

.setting-info h3 {
    font-size: 15px;
    color: #2e2e2e;
    margin-bottom: 5px;
}

.setting-info p {
    font-size: 13px;
    color: #666;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 26px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #d70f64;
}

input:checked + .slider:before {
    transform: translateX(24px);
}

/* Danger Zone */
.danger-zone {
    border: 2px solid #dc3545;
}

.danger-zone h2 {
    color: #dc3545;
}

.danger-zone h2 i {
    color: #dc3545;
}

.logout-btn, .delete-account-btn {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.logout-btn {
    background: #ffc107;
    border: none;
    color: #000;
}

.logout-btn:hover {
    background: #e0a800;
}

.delete-account-btn {
    background: transparent;
    border: 2px solid #dc3545;
    color: #dc3545;
}

.delete-account-btn:hover {
    background: #dc3545;
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        font-size: 40px;
    }

    .payment-card {
        flex-direction: column;
        text-align: center;
    }

    .card-actions {
        width: 100%;
        flex-direction: column;
    }

    .action-btn {
        width: 100%;
        justify-content: center;
    }

    .address-actions {
        flex-direction: column;
    }
}
</style>