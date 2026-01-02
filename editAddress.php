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
$editMode = false;
$address = null;

// Create database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    $db_error = true;
}

// Check if editing existing address
if (isset($_GET['id']) && $pdo && !$db_error) {
    $editMode = true;
    $addressId = $_GET['id'];
    $userId = $_SESSION['user_id'];
    
    try {
        $query = "SELECT * FROM address WHERE Address_ID = ? AND User_ID = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$addressId, $userId]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$address) {
            header("Location: userProfile.php");
            exit();
        }
    } catch(PDOException $e) {
        error_log("Address fetch failed: " . $e->getMessage());
        header("Location: userProfile.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo && !$db_error) {
    $userId = $_SESSION['user_id'];
    $label = trim($_POST['label']);
    $street = trim($_POST['street']);
    $city = trim($_POST['city']);
    $postcode = trim($_POST['postcode']);
    $state = trim($_POST['state']);
    $country = trim($_POST['country']);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    
    try {
        // If setting as default, remove default from other addresses
        if ($isDefault) {
            $updateQuery = "UPDATE address SET Is_Default = 0 WHERE User_ID = ?";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$userId]);
        }
        
        if ($editMode && isset($_POST['address_id'])) {
            // Update existing address
            $addressId = $_POST['address_id'];
            $query = "UPDATE address SET Label = ?, Street = ?, City = ?, Postcode = ?, State = ?, Country = ?, Is_Default = ? WHERE Address_ID = ? AND User_ID = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$label, $street, $city, $postcode, $state, $country, $isDefault, $addressId, $userId]);
            
            $_SESSION['success_message'] = 'Address updated successfully!';
        } else {
            // Insert new address
            $query = "INSERT INTO address (User_ID, Label, Street, City, Postcode, State, Country, Is_Default, Created_At) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$userId, $label, $street, $city, $postcode, $state, $country, $isDefault]);
            
            $_SESSION['success_message'] = 'Address added successfully!';
        }
        
        header("Location: userProfile.php");
        exit();
        
    } catch(PDOException $e) {
        error_log("Address save failed: " . $e->getMessage());
        $error_message = "Failed to save address. Please try again.";
    }
}

// Get user data for navbar
$currentUser = null;
if ($pdo && !$db_error) {
    try {
        $userId = $_SESSION['user_id'];
        $userQuery = "SELECT * FROM user WHERE User_ID = ?";
        $userStmt = $pdo->prepare($userQuery);
        $userStmt->execute([$userId]);
        $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("User query failed: " . $e->getMessage());
    }
}

$fullName = $currentUser['Name'] ?? 'User';
$nameParts = explode(' ', $fullName, 2);
$firstName = $nameParts[0] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editMode ? 'Edit Address' : 'Add Address'; ?> | nyamanhippo</title>
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
                <a href="userProfile.php" class="user-profile">
                    <i class="fa-regular fa-user"></i>
                    <span id="userName"><?php echo htmlspecialchars($firstName); ?></span>
                </a>
            </div>
        </div>
    </nav>

    <main class="form-container">
        <button class="back-btn" onclick="window.location.href='userProfile.php'">
            <i class="fa fa-arrow-left"></i> Back to Profile
        </button>

        <div class="form-card">
            <div class="form-header">
                <i class="fa fa-map-marker-alt"></i>
                <h1><?php echo $editMode ? 'Edit Address' : 'Add New Address'; ?></h1>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fa fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="addressForm">
                <?php if ($editMode): ?>
                    <input type="hidden" name="address_id" value="<?php echo htmlspecialchars($address['Address_ID']); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="label">Address Label <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="label" 
                        name="label" 
                        placeholder="e.g., Home, Office, Apartment" 
                        value="<?php echo $editMode ? htmlspecialchars($address['Label']) : ''; ?>"
                        required
                    >
                    <small>Give this address a memorable name</small>
                </div>

                <div class="form-group">
                    <label for="street">Street Address <span class="required">*</span></label>
                    <textarea 
                        id="street" 
                        name="street" 
                        rows="3" 
                        placeholder="Enter your street address, building name, floor, unit number"
                        required
                    ><?php echo $editMode ? htmlspecialchars($address['Street']) : ''; ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="city" 
                            name="city" 
                            placeholder="Enter city"
                            value="<?php echo $editMode ? htmlspecialchars($address['City']) : ''; ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="postcode">Postcode <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="postcode" 
                            name="postcode" 
                            placeholder="Enter postcode"
                            value="<?php echo $editMode ? htmlspecialchars($address['Postcode']) : ''; ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="state">State/Province</label>
                        <input 
                            type="text" 
                            id="state" 
                            name="state" 
                            placeholder="Enter state/province"
                            value="<?php echo $editMode ? htmlspecialchars($address['State']) : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="country">Country <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="country" 
                            name="country" 
                            placeholder="Enter country"
                            value="<?php echo $editMode ? htmlspecialchars($address['Country']) : 'Malaysia'; ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            name="is_default" 
                            id="is_default"
                            <?php echo ($editMode && $address['Is_Default']) ? 'checked' : ''; ?>
                        >
                        <span class="checkmark"></span>
                        Set as default address
                    </label>
                    <small>This address will be used by default for your orders</small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="window.location.href='userProfile.php'">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fa fa-save"></i>
                        <?php echo $editMode ? 'Update Address' : 'Save Address'; ?>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
    // Update cart count
    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = totalItems;
        }
    }

    // Form validation
    document.getElementById('addressForm').addEventListener('submit', function(e) {
        const label = document.getElementById('label').value.trim();
        const street = document.getElementById('street').value.trim();
        const city = document.getElementById('city').value.trim();
        const postcode = document.getElementById('postcode').value.trim();
        const country = document.getElementById('country').value.trim();

        if (!label || !street || !city || !postcode || !country) {
            e.preventDefault();
            alert('Please fill in all required fields marked with *');
            return false;
        }

        // Validate postcode format (basic validation)
        if (postcode.length < 4) {
            e.preventDefault();
            alert('Please enter a valid postcode');
            document.getElementById('postcode').focus();
            return false;
        }
    });

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        updateCartCount();
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

/* Form Container */
.form-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 20px;
}

.form-card {
    background: white;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.form-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.form-header i {
    font-size: 32px;
    color: #d70f64;
}

.form-header h1 {
    font-size: 28px;
    color: #2e2e2e;
}

/* Alert */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-error {
    background-color: #fee;
    border: 1px solid #fcc;
    color: #c33;
}

.alert i {
    font-size: 18px;
}

/* Form Styles */
.form-group {
    margin-bottom: 25px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #2e2e2e;
    margin-bottom: 8px;
}

.required {
    color: #d70f64;
}

input[type="text"],
textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}

input[type="text"]:focus,
textarea:focus {
    outline: none;
    border-color: #d70f64;
    box-shadow: 0 0 0 3px rgba(215, 15, 100, 0.1);
}

textarea {
    resize: vertical;
    font-family: inherit;
}

small {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #666;
}

/* Checkbox Group */
.checkbox-group {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    color: #2e2e2e;
    position: relative;
    padding-left: 35px;
}

.checkbox-label input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    width: 0;
    height: 0;
}

.checkmark {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    height: 22px;
    width: 22px;
    background-color: white;
    border: 2px solid #ddd;
    border-radius: 4px;
    transition: all 0.2s;
}

.checkbox-label:hover .checkmark {
    border-color: #d70f64;
}

.checkbox-label input:checked ~ .checkmark {
    background-color: #d70f64;
    border-color: #d70f64;
}

.checkmark:after {
    content: "";
    position: absolute;
    display: none;
    left: 7px;
    top: 3px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.checkbox-label input:checked ~ .checkmark:after {
    display: block;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 35px;
    padding-top: 25px;
    border-top: 2px solid #f0f0f0;
}

.btn-primary,
.btn-secondary {
    flex: 1;
    padding: 14px 25px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary {
    background: #d70f64;
    color: white;
}

.btn-primary:hover {
    background: #b00c50;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(215, 15, 100, 0.3);
}

.btn-secondary {
    background: #f8f9fa;
    color: #666;
    border: 2px solid #e0e0e0;
}

.btn-secondary:hover {
    background: #e9ecef;
    border-color: #ccc;
}

/* Responsive Design */
@media (max-width: 768px) {
    .form-card {
        padding: 25px;
    }

    .form-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .form-header h1 {
        font-size: 24px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
    }
}
</style>