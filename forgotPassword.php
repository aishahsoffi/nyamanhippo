<?php
session_start();

// Clear any existing reset session data when page first loads
if (!isset($_POST['verify_user']) && !isset($_POST['reset_password'])) {
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_user_email']);
    unset($_SESSION['reset_user_name']);
    unset($_SESSION['reset_username']);
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "foodpanda_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";
$success_message = "";
$show_reset_form = false;
$user_data = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['verify_user'])) {
        // Step 1: Verify user exists
        $email_or_username = trim($_POST['email_or_username']);
        
        if (empty($email_or_username)) {
            $error_message = "Please enter your email or username";
        } else {
            // Check if email/username exists in database
            $stmt = $conn->prepare("SELECT User_ID, Username, Name, Email FROM user WHERE Email = ? OR Username = ?");
            $stmt->bind_param("ss", $email_or_username, $email_or_username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user_data = $result->fetch_assoc();
                $_SESSION['reset_user_id'] = $user_data['User_ID'];
                $_SESSION['reset_user_email'] = $user_data['Email'];
                $_SESSION['reset_user_name'] = $user_data['Name'];
                $_SESSION['reset_username'] = $user_data['Username'];
                $show_reset_form = true;
            } else {
                $error_message = "No account found with that email or username";
            }
            
            $stmt->close();
        }
    } elseif (isset($_POST['reset_password']) && isset($_SESSION['reset_user_id'])) {
        // Step 2: Reset password
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $user_id = $_SESSION['reset_user_id'];
        $user_email = $_SESSION['reset_user_email'];
        
        if (empty($new_password) || empty($confirm_password)) {
            $error_message = "Please fill in all fields";
            $show_reset_form = true;
            $user_data = [
                'User_ID' => $_SESSION['reset_user_id'],
                'Email' => $_SESSION['reset_user_email'],
                'Name' => $_SESSION['reset_user_name'],
                'Username' => $_SESSION['reset_username']
            ];
        } elseif (strlen($new_password) < 6 || strlen($new_password) > 14) {
            $error_message = "Password must be 6-14 characters long";
            $show_reset_form = true;
            $user_data = [
                'User_ID' => $_SESSION['reset_user_id'],
                'Email' => $_SESSION['reset_user_email'],
                'Name' => $_SESSION['reset_user_name'],
                'Username' => $_SESSION['reset_username']
            ];
        } elseif (!preg_match('/[A-Z]/', $new_password)) {
            $error_message = "Password must contain at least one uppercase letter";
            $show_reset_form = true;
            $user_data = [
                'User_ID' => $_SESSION['reset_user_id'],
                'Email' => $_SESSION['reset_user_email'],
                'Name' => $_SESSION['reset_user_name'],
                'Username' => $_SESSION['reset_username']
            ];
        } elseif (!preg_match('/[0-9]/', $new_password)) {
            $error_message = "Password must contain at least one number";
            $show_reset_form = true;
            $user_data = [
                'User_ID' => $_SESSION['reset_user_id'],
                'Email' => $_SESSION['reset_user_email'],
                'Name' => $_SESSION['reset_user_name'],
                'Username' => $_SESSION['reset_username']
            ];
        } elseif (!preg_match('/[!@#$%^&*]/', $new_password)) {
            $error_message = "Password must contain at least one special character (!@#$%^&*)";
            $show_reset_form = true;
            $user_data = [
                'User_ID' => $_SESSION['reset_user_id'],
                'Email' => $_SESSION['reset_user_email'],
                'Name' => $_SESSION['reset_user_name'],
                'Username' => $_SESSION['reset_username']
            ];
        } elseif (preg_match('/\s/', $new_password)) {
            $error_message = "Password cannot contain spaces";
            $show_reset_form = true;
            $user_data = [
                'User_ID' => $_SESSION['reset_user_id'],
                'Email' => $_SESSION['reset_user_email'],
                'Name' => $_SESSION['reset_user_name'],
                'Username' => $_SESSION['reset_username']
            ];
        } elseif ($new_password !== $confirm_password) {
            $error_message = "Passwords do not match";
            $show_reset_form = true;
            $user_data = [
                'User_ID' => $_SESSION['reset_user_id'],
                'Email' => $_SESSION['reset_user_email'],
                'Name' => $_SESSION['reset_user_name'],
                'Username' => $_SESSION['reset_username']
            ];
        } else {
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
            
            // Insert into password_resets table
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expiry, created_at) VALUES (?, ?, ?, NOW())");
            
            if ($stmt === false) {
                $error_message = "Database error: " . $conn->error;
                $show_reset_form = true;
                $user_data = [
                    'User_ID' => $_SESSION['reset_user_id'],
                    'Email' => $_SESSION['reset_user_email'],
                    'Name' => $_SESSION['reset_user_name'],
                    'Username' => $_SESSION['reset_username']
                ];
            } else {
                $stmt->bind_param("sss", $user_email, $token, $expiry);
                
                if ($stmt->execute()) {
                    // Now update the actual password in user table
                    $update_stmt = $conn->prepare("UPDATE user SET Password = ? WHERE User_ID = ?");
                    $update_stmt->bind_param("si", $new_password, $user_id);
                    
                    if ($update_stmt->execute()) {
                        $success_message = "Password reset successful! Reset token stored and password updated. You can now log in with your new password.";
                        $show_reset_form = false;
                        
                        // Clear session
                        unset($_SESSION['reset_user_id']);
                        unset($_SESSION['reset_user_email']);
                        unset($_SESSION['reset_user_name']);
                        unset($_SESSION['reset_username']);
                        
                        // Redirect to login after 3 seconds
                        echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 3000);</script>";
                    } else {
                        $error_message = "Password reset token stored, but failed to update password: " . $update_stmt->error;
                        $show_reset_form = true;
                        $user_data = [
                            'User_ID' => $_SESSION['reset_user_id'],
                            'Email' => $_SESSION['reset_user_email'],
                            'Name' => $_SESSION['reset_user_name'],
                            'Username' => $_SESSION['reset_username']
                        ];
                    }
                    
                    $update_stmt->close();
                } else {
                    $error_message = "Failed to store password reset token: " . $stmt->error;
                    $show_reset_form = true;
                    $user_data = [
                        'User_ID' => $_SESSION['reset_user_id'],
                        'Email' => $_SESSION['reset_user_email'],
                        'Name' => $_SESSION['reset_user_name'],
                        'Username' => $_SESSION['reset_username']
                    ];
                }
                
                $stmt->close();
            }
        }
    }
}

// Check if we should show reset form from session (only if coming from a POST request)
if (!$show_reset_form && isset($_SESSION['reset_user_id']) && empty($error_message) && empty($success_message) && ($_SERVER["REQUEST_METHOD"] == "POST")) {
    $show_reset_form = true;
    $user_data = [
        'User_ID' => $_SESSION['reset_user_id'],
        'Email' => isset($_SESSION['reset_user_email']) ? $_SESSION['reset_user_email'] : '',
        'Name' => isset($_SESSION['reset_user_name']) ? $_SESSION['reset_user_name'] : '',
        'Username' => isset($_SESSION['reset_username']) ? $_SESSION['reset_username'] : ''
    ];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - foodpanda</title>
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #d70f64;
            --secondary: #ff2b85;
            --dark: #2e2e2e;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .forgot-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .forgot-container {
            width: 100%;
            max-width: 480px;
        }

        .forgot-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .icon-wrapper {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }

        .icon-wrapper i {
            font-size: 2.5rem;
            color: white;
        }

        .forgot-card h1 {
            color: var(--dark);
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .user-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
        }

        .user-info p {
            margin: 5px 0;
            color: var(--dark);
        }

        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }

        .alert i {
            font-size: 1.2rem;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .forgot-form, .reset-form {
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper > i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.1rem;
            z-index: 1;
        }

        .password-group input {
            padding-right: 45px !important;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(215, 15, 100, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 8px;
            font-size: 1.1rem;
            z-index: 2;
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .password-requirements {
            margin-top: 8px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 0.8rem;
            color: #666;
            text-align: left;
        }

        .password-requirements ul {
            margin: 5px 0 0 20px;
            padding: 0;
        }

        .password-requirements li {
            margin: 3px 0;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(215, 15, 100, 0.4);
        }

        .btn-secondary {
            background: #666;
        }

        .btn-secondary:hover {
            background: #555;
        }

        .back-to-login {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .back-to-login a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .back-to-login a:hover {
            gap: 12px;
        }

        @media (max-width: 480px) {
            .forgot-card {
                padding: 30px 20px;
            }

            .forgot-card h1 {
                font-size: 1.6rem;
            }

            .icon-wrapper {
                width: 70px;
                height: 70px;
            }

            .icon-wrapper i {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-wrapper">
            <div class="logo">
                <img src="foodpanda-logo.jpg" alt="FoodPanda Logo" class="logo-img" onerror="this.style.display='none';">
                <h1>foodpanda</h1>
            </div>
            <div class="nav-links">
                <a href="index.html">Home</a>
                <a href="login.php">Login</a>
            </div>
        </div>
    </nav>

    <section class="forgot-section">
        <div class="forgot-container">
            <div class="forgot-card">
                <div class="icon-wrapper">
                    <i class="fas fa-lock"></i>
                </div>
                
                <h1>Forgot Password?</h1>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <?php echo htmlspecialchars($success_message); ?>
                            <p style="margin-top: 10px; font-size: 0.85rem;">Redirecting to login page in 3 seconds...</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!$show_reset_form && !$success_message): ?>
                    <p class="subtitle">Enter your email or username to reset your password</p>
                    
                    <form method="POST" action="" class="forgot-form">
                        <input type="hidden" name="verify_user" value="1">
                        <div class="form-group">
                            <label for="email_or_username">Email or Username</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" id="email_or_username" name="email_or_username" 
                                       placeholder="Enter your email or username" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-search"></i>
                            Verify Account
                        </button>
                    </form>
                <?php endif; ?>
                
                <?php if ($show_reset_form && $user_data): ?>
                    <p class="subtitle">Enter your new password for:</p>
                    
                    <div class="user-info">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user_data['Name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['Email']); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user_data['Username']); ?></p>
                    </div>
                    
                    <form method="POST" action="" class="reset-form" id="resetForm">
                        <input type="hidden" name="reset_password" value="1">
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="input-wrapper password-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="new_password" name="new_password" 
                                       placeholder="Enter new password" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('new_password', 'eye1')">
                                    <i class="fas fa-eye" id="eye1"></i>
                                </button>
                            </div>
                            <div class="password-requirements">
                                <strong>Password must contain:</strong>
                                <ul>
                                    <li>6-14 characters</li>
                                    <li>One uppercase letter</li>
                                    <li>One number</li>
                                    <li>One special character (!@#$%^&*)</li>
                                    <li>No spaces</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="input-wrapper password-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm new password" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', 'eye2')">
                                    <i class="fas fa-eye" id="eye2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-check"></i>
                            Reset Password
                        </button>
                    </form>
                    
                    <div style="margin-top: 15px;">
                        <a href="forgotPassword.php" class="btn-submit btn-secondary" style="text-decoration: none;">
                            <i class="fas fa-redo"></i>
                            Try Different Account
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="back-to-login">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i>
                        Back to Login
                    </a>
                </div>
            </div>
        </div>
    </section>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Client-side validation
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password.length < 6 || password.length > 14) {
                e.preventDefault();
                alert('Password must be 6-14 characters long');
                return;
            }
            
            const hasUppercase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*]/.test(password);
            const hasSpace = /\s/.test(password);
            
            if (!hasUppercase || !hasNumber || !hasSpecial || hasSpace) {
                e.preventDefault();
                alert('Password must contain:\n- One uppercase letter\n- One number\n- One special character (!@#$%^&*)\n- No spaces');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return;
            }
        });
    </script>
</body>
</html>