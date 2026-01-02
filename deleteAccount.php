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

// Initialize variables
$error_message = '';
$success = false;
$currentUser = null;

// Create database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get current user data
$userId = $_SESSION['user_id'];
try {
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
    die("User query failed: " . $e->getMessage());
}

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $password_input = $_POST['password'] ?? '';
    
    // Verify password
    if (empty($password_input)) {
        $error_message = "Please enter your password to confirm deletion.";
    } else {
        // Check if password is correct (plain text comparison since passwords are not hashed)
        if ($password_input === $currentUser['Password']) {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Delete profile picture if exists
                if (!empty($currentUser['Profile_Picture']) && file_exists($currentUser['Profile_Picture'])) {
                    unlink($currentUser['Profile_Picture']);
                }
                
                // Temporarily disable foreign key checks
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                // First, manually delete related records in correct order
                // 1. Delete cart items
                try {
                    $deleteCart = "DELETE FROM cart_item WHERE User_ID = ?";
                    $stmt1 = $pdo->prepare($deleteCart);
                    $stmt1->execute([$userId]);
                } catch(Exception $e) {
                    // Table might not exist, continue
                }
                
                // 2. Delete addresses
                try {
                    $deleteAddresses = "DELETE FROM address WHERE User_ID = ?";
                    $stmt2 = $pdo->prepare($deleteAddresses);
                    $stmt2->execute([$userId]);
                } catch(Exception $e) {
                    // Table might not exist, continue
                }
                
                // 3. Delete payment methods
                try {
                    $deletePayments = "DELETE FROM payment_method WHERE User_ID = ?";
                    $stmt3 = $pdo->prepare($deletePayments);
                    $stmt3->execute([$userId]);
                } catch(Exception $e) {
                    // Table might not exist, continue
                }
                
                // 4. Delete orders (if order table exists)
                try {
                    $deleteOrders = "DELETE FROM orders WHERE User_ID = ?";
                    $stmt4 = $pdo->prepare($deleteOrders);
                    $stmt4->execute([$userId]);
                } catch(Exception $e) {
                    // Table might not exist, continue
                }
                
                // 5. Delete reviews (if review table exists)
                try {
                    $deleteReviews = "DELETE FROM review WHERE User_ID = ?";
                    $stmt5 = $pdo->prepare($deleteReviews);
                    $stmt5->execute([$userId]);
                } catch(Exception $e) {
                    // Table might not exist, continue
                }
                
                // 6. Finally delete the user
                $deleteUser = "DELETE FROM user WHERE User_ID = ?";
                $stmt6 = $pdo->prepare($deleteUser);
                $stmt6->execute([$userId]);
                
                // Re-enable foreign key checks
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                // Check if user was actually deleted
                if ($stmt6->rowCount() > 0) {
                    // Commit transaction
                    $pdo->commit();
                    
                    // Destroy session
                    session_unset();
                    session_destroy();
                    
                    $success = true;
                } else {
                    $pdo->rollBack();
                    $error_message = "Failed to delete account. User not found or already deleted.";
                }
                
            } catch(PDOException $e) {
                // Rollback on error and re-enable foreign key checks
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                $pdo->rollBack();
                $error_message = "Failed to delete account. Error: " . $e->getMessage();
            }
        } else {
            $error_message = "Incorrect password. Please try again.";
        }
    }
}

$fullName = $currentUser['Name'] ?? 'User';
$nameParts = explode(' ', $fullName, 2);
$firstName = $nameParts[0] ?? 'User';
$email = $currentUser['Email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account | foodpanda</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 500px;
        }

        .delete-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            border: 3px solid #dc3545;
        }

        .warning-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .warning-header i {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 15px;
        }

        .warning-header h1 {
            font-size: 28px;
            color: #dc3545;
            margin-bottom: 10px;
        }



        .alert-error {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error i {
            font-size: 18px;
        }

        .account-info {
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .account-info h3 {
            color: #2e2e2e;
            font-size: 16px;
            margin-bottom: 12px;
        }

        .account-info p {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #2e2e2e;
            margin-bottom: 8px;
        }

        .required {
            color: #dc3545;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }

        input[type="password"]:focus {
            outline: none;
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        small {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }

        .checkbox-group {
            background: #fee;
            border: 2px solid #dc3545;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #dc3545;
        }

        .checkbox-label input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            gap: 15px;
        }

        .btn-secondary, .btn-danger {
            flex: 1;
            padding: 14px 20px;
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

        .btn-secondary {
            background: #f8f9fa;
            color: #666;
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            border-color: #ccc;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover:not(:disabled) {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-danger:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .success-card {
            background: white;
            border-radius: 16px;
            padding: 60px 40px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .success-icon {
            margin-bottom: 30px;
        }

        .success-icon i {
            font-size: 80px;
            color: #28a745;
        }

        .success-card h1 {
            font-size: 28px;
            color: #2e2e2e;
            margin-bottom: 20px;
        }

        .success-card p {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .success-actions {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            justify-content: center;
        }

        .btn-primary {
            background: #d70f64;
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: #b00c50;
            transform: translateY(-2px);
        }

        @media (max-width: 576px) {
            .delete-card, .success-card {
                padding: 25px;
            }
            
            .form-actions, .success-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <!-- Success Message -->
            <div class="success-card">
                <div class="success-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <h1>Account Deleted Successfully</h1>
                <p>Your account and all associated data have been permanently deleted.</p>
                <p>We're sorry to see you go. If you change your mind, you can always create a new account.</p>
                
                <div class="success-actions">
                    <a href="index.php" class="btn-primary">
                        <i class="fa fa-home"></i> Go to Homepage
                    </a>
                    <a href="registration.php" class="btn-primary">
                        <i class="fa fa-user-plus"></i> Create New Account
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Delete Confirmation Form -->
            <div class="delete-card">
                <div class="warning-header">
                    <i class="fa fa-exclamation-triangle"></i>
                    <h1>Delete Account</h1>
                </div>



                <?php if (!empty($error_message)): ?>
                    <div class="alert-error">
                        <i class="fa fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="deleteForm">
                    <div class="account-info">
                        <h3>Account to be deleted:</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($fullName); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                    </div>

                    <div class="form-group">
                        <label for="password">Enter your password to confirm <span class="required">*</span></label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password"
                            required
                            autofocus
                        >
                        <small>You must enter your password to delete your account</small>
                    </div>

                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="confirmCheck" required>
                            I understand that this action is permanent and cannot be undone
                        </label>
                    </div>

                    <input type="hidden" name="confirm_delete" value="1">

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="window.location.href='userProfile.php'">
                            <i class="fa fa-arrow-left"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn-danger" id="deleteBtn" disabled>
                            <i class="fa fa-trash"></i>
                            Delete My Account
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Enable/disable delete button based on checkbox
    const confirmCheck = document.getElementById('confirmCheck');
    const deleteBtn = document.getElementById('deleteBtn');
    const deleteForm = document.getElementById('deleteForm');
    
    if (confirmCheck && deleteBtn) {
        confirmCheck.addEventListener('change', function() {
            deleteBtn.disabled = !this.checked;
        });

        // Form submission - simplified without extra popup
        deleteForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            
            if (!password) {
                e.preventDefault();
                alert('Please enter your password to confirm deletion.');
                return false;
            }
            
            if (!confirmCheck.checked) {
                e.preventDefault();
                alert('Please confirm that you understand this action is permanent.');
                return false;
            }
            
            // Show loading state
            deleteBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Deleting...';
            deleteBtn.disabled = true;
        });
    }
    </script>
</body>
</html>