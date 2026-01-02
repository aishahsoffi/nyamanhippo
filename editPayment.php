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
$payment = null;

// Create database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    $db_error = true;
}

// Check if editing existing payment method
if (isset($_GET['id']) && $pdo && !$db_error) {
    $editMode = true;
    $paymentId = $_GET['id'];
    $userId = $_SESSION['user_id'];
    
    try {
        $query = "SELECT * FROM payment_method WHERE Payment_Method_ID = ? AND User_ID = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$paymentId, $userId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            header("Location: userProfile.php");
            exit();
        }
    } catch(PDOException $e) {
        error_log("Payment fetch failed: " . $e->getMessage());
        header("Location: userProfile.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo && !$db_error) {
    $userId = $_SESSION['user_id'];
    $cardName = trim($_POST['card_name']);
    $cardType = trim($_POST['card_type']);
    $cardNumber = trim($_POST['card_number']);
    $expiryDate = trim($_POST['expiry_date']);
    $cvv = trim($_POST['cvv']);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    
    // Basic validation
    if (empty($cardName) || empty($cardType) || empty($cardNumber) || empty($expiryDate) || empty($cvv)) {
        $error_message = "All fields are required.";
    } else {
        try {
            // If setting as default, remove default from other payment methods
            if ($isDefault) {
                $updateQuery = "UPDATE payment_method SET Is_Default = 0 WHERE User_ID = ?";
                $updateStmt = $pdo->prepare($updateQuery);
                $updateStmt->execute([$userId]);
            }
            
            if ($editMode && isset($_POST['payment_id'])) {
                // Update existing payment method
                $paymentId = $_POST['payment_id'];
                $query = "UPDATE payment_method SET Card_Holder_Name = ?, Card_Type = ?, Card_Number = ?, Expiry_Date = ?, CVV = ?, Is_Default = ? WHERE Payment_Method_ID = ? AND User_ID = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$cardName, $cardType, $cardNumber, $expiryDate, $cvv, $isDefault, $paymentId, $userId]);
                
                $_SESSION['success_message'] = 'Payment method updated successfully!';
            } else {
                // Insert new payment method
                $query = "INSERT INTO payment_method (User_ID, Card_Holder_Name, Card_Type, Card_Number, Expiry_Date, CVV, Is_Default, Created_At) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$userId, $cardName, $cardType, $cardNumber, $expiryDate, $cvv, $isDefault]);
                
                $_SESSION['success_message'] = 'Payment method added successfully!';
            }
            
            header("Location: userProfile.php");
            exit();
            
        } catch(PDOException $e) {
            error_log("Payment save failed: " . $e->getMessage());
            $error_message = "Failed to save payment method. Please try again.";
        }
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
    <title><?php echo $editMode ? 'Edit Payment' : 'Add Payment'; ?> | nyamanhippo</title>
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
                <i class="fa fa-credit-card"></i>
                <h1><?php echo $editMode ? 'Edit Payment Method' : 'Add Payment Method'; ?></h1>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fa fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="paymentForm">
                <?php if ($editMode): ?>
                    <input type="hidden" name="payment_id" value="<?php echo htmlspecialchars($payment['Payment_Method_ID']); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="card_name">Card Holder Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="card_name" 
                        name="card_name" 
                        placeholder="e.g., John Doe" 
                        value="<?php echo $editMode ? htmlspecialchars($payment['Card_Holder_Name']) : ''; ?>"
                        required
                    >
                    <small>Enter the name as it appears on your card</small>
                </div>

                <div class="form-group">
                    <label for="card_type">Card Type <span class="required">*</span></label>
                    <select id="card_type" name="card_type" required>
                        <option value="">Select Card Type</option>
                        <option value="Visa" <?php echo ($editMode && $payment['Card_Type'] === 'Visa') ? 'selected' : ''; ?>>Visa</option>
                        <option value="Mastercard" <?php echo ($editMode && $payment['Card_Type'] === 'Mastercard') ? 'selected' : ''; ?>>Mastercard</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="card_number">Card Number <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="card_number" 
                        name="card_number" 
                        placeholder="1234 5678 9012 3456" 
                        maxlength="19"
                        value="<?php echo $editMode ? htmlspecialchars($payment['Card_Number']) : ''; ?>"
                        required
                    >
                    <small>Enter your 15 or 16-digit card number</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date <span class="required">*</span></label>
                        <input 
                            type="month" 
                            id="expiry_date" 
                            name="expiry_date" 
                            value="<?php echo $editMode ? htmlspecialchars($payment['Expiry_Date']) : ''; ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="cvv">CVV <span class="required">*</span></label>
                        <input 
                            type="password" 
                            id="cvv" 
                            name="cvv" 
                            placeholder="123" 
                            maxlength="4"
                            value="<?php echo $editMode ? htmlspecialchars($payment['CVV']) : ''; ?>"
                            required
                        >
                        <small>3 or 4-digit security code</small>
                    </div>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            name="is_default" 
                            id="is_default"
                            <?php echo ($editMode && $payment['Is_Default']) ? 'checked' : ''; ?>
                        >
                        <span class="checkmark"></span>
                        Set as default payment method
                    </label>
                    <small>This payment method will be used by default for your orders</small>
                </div>

                <div class="security-notice">
                    <i class="fa fa-lock"></i>
                    <div>
                        <strong>Your payment information is secure</strong>
                        <p>We use industry-standard encryption to protect your card details</p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="window.location.href='userProfile.php'">
                        <i class="fa fa-times"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fa fa-save"></i>
                        <?php echo $editMode ? 'Update Payment' : 'Save Payment'; ?>
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

    // Format card number as user types
    document.getElementById('card_number').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        e.target.value = formattedValue;
    });

    // Only allow numbers for card number
    document.getElementById('card_number').addEventListener('keypress', function(e) {
        if (!/[0-9]/.test(e.key) && e.key !== 'Backspace') {
            e.preventDefault();
        }
    });

    // Only allow numbers for CVV
    document.getElementById('cvv').addEventListener('keypress', function(e) {
        if (!/[0-9]/.test(e.key) && e.key !== 'Backspace') {
            e.preventDefault();
        }
    });

    // Form validation
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        const cardName = document.getElementById('card_name').value.trim();
        const cardType = document.getElementById('card_type').value;
        const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
        const expiryDate = document.getElementById('expiry_date').value;
        const cvv = document.getElementById('cvv').value;

        if (!cardName || !cardType || !cardNumber || !expiryDate || !cvv) {
            e.preventDefault();
            alert('Please fill in all required fields marked with *');
            return false;
        }

        // Validate card number length (15 or 16 digits)
        if (cardNumber.length < 15 || cardNumber.length > 16) {
            e.preventDefault();
            alert('Please enter a valid card number (15 or 16 digits)');
            document.getElementById('card_number').focus();
            return false;
        }

        // Validate CVV length (3 or 4 digits)
        if (cvv.length < 3 || cvv.length > 4) {
            e.preventDefault();
            alert('Please enter a valid CVV (3 or 4 digits)');
            document.getElementById('cvv').focus();
            return false;
        }

        // Validate expiry date is not in the past
        const today = new Date();
        const currentMonth = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0');
        if (expiryDate < currentMonth) {
            e.preventDefault();
            alert('Card expiry date cannot be in the past');
            document.getElementById('expiry_date').focus();
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
    max-width: 700px;
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
input[type="password"],
input[type="month"],
select {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}

input[type="text"]:focus,
input[type="password"]:focus,
input[type="month"]:focus,
select:focus {
    outline: none;
    border-color: #d70f64;
    box-shadow: 0 0 0 3px rgba(215, 15, 100, 0.1);
}

select {
    cursor: pointer;
    background-color: white;
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

/* Security Notice */
.security-notice {
    background: #e8f5e9;
    border: 1px solid #a5d6a7;
    border-radius: 8px;
    padding: 15px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-top: 25px;
}

.security-notice i {
    color: #4caf50;
    font-size: 20px;
    margin-top: 2px;
}

.security-notice strong {
    display: block;
    color: #2e7d32;
    font-size: 14px;
    margin-bottom: 4px;
}

.security-notice p {
    color: #388e3c;
    font-size: 13px;
    margin: 0;
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