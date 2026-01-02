<?php
// Start session
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'foodpanda_db';
$username = 'root';
$password = '';

$errorMessage = '';
$successMessage = '';

// Create database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $phoneNo = trim($_POST['phoneNo']);
    $address = trim($_POST['address']);
    $terms = isset($_POST['terms']);
    
    // Validation
    if (!$terms) {
        $errorMessage = 'Please accept the Terms of Service and Privacy Policy to continue.';
    }
    elseif (empty($username) || empty($fullName) || empty($email) || empty($password) || empty($confirmPassword) || empty($phoneNo) || empty($address)) {
        $errorMessage = 'Please fill in all fields';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address';
    }
    elseif (strlen($password) < 6 || strlen($password) > 14) {
        $errorMessage = 'Password must be 6-14 characters long';
    }
    elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-14]/', $password) || !preg_match('/[!@#$%^&*]/', $password) || preg_match('/\s/', $password)) {
        $errorMessage = 'Password must contain: One uppercase letter, One number, One special character (!@#$%^&*), No spaces';
    }
    elseif ($password !== $confirmPassword) {
        $errorMessage = 'Passwords do not match';
    }
    elseif (!preg_match('/^[0-9]{10,11}$/', $phoneNo)) {
        $errorMessage = 'Please enter a valid phone number (10-11 digits)';
    }
    else {
        // Check if email already exists
        $checkEmailQuery = "SELECT User_ID FROM user WHERE Email = ?";
        $checkEmailStmt = $pdo->prepare($checkEmailQuery);
        $checkEmailStmt->execute([$email]);
        
        if ($checkEmailStmt->rowCount() > 0) {
            $errorMessage = 'The email already registered. Please use a different email or login.';
        }
        else {
            // Check if username already exists
            $checkUsernameQuery = "SELECT User_ID FROM user WHERE Username = ?";
            $checkUsernameStmt = $pdo->prepare($checkUsernameQuery);
            $checkUsernameStmt->execute([$username]);
            
            if ($checkUsernameStmt->rowCount() > 0) {
                $errorMessage = 'Username already taken. Please choose a different username.';
            }
                
                // Insert new user into database
                $insertQuery = "INSERT INTO user (Username, Name, Email, Password, PhoneNo, Address) VALUES (?, ?, ?, ?, ?, ?)";
                $insertStmt = $pdo->prepare($insertQuery);
                
                try {
                    $insertStmt->execute([$username, $fullName, $email, $password, $phoneNo, $address]);
                    $successMessage = 'Registration successful! Redirecting to login...';
                    
                    // Redirect to login page after 2 seconds
                    header("refresh:2;url=login.php");
                } catch(PDOException $e) {
                    $errorMessage = 'Registration failed. Please try again.';
                }
            }
        }
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - nyamanhippo</title>
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-wrapper">
            <div class="logo">
                <img src="foodpanda-logo.jpg" alt="FoodPanda Logo" class="logo-img" onerror="this.style.display='none';">
                <h1>nyamanhippo</h1>
            </div>
            <div class="nav-links">
                <a href="index.php">Home</a>
            </div>
        </div>
    </nav>

    <!-- Registration Section -->
    <section class="register-section">
        <div class="register-container">
            <!-- LEFT SIDE: Registration Form -->
            <div class="register-left">
                <div class="register-content">
                    <h1>Create Your Account</h1>
                    <p class="subtitle">Join nyamanhippo and start ordering delicious food</p>
                    
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($successMessage); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form id="registerForm" class="register-form" method="POST" action="registration.php">
                        <div class="form-group">
                            <input type="text" id="username" name="username" placeholder="Username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <input type="text" id="fullName" name="fullName" placeholder="Full name" 
                                   value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <input type="email" id="email" name="email" placeholder="Email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>

                        <div class="form-group password-group">
                            <input type="password" id="password" name="password" placeholder="Password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fa fa-eye" id="password-icon"></i>
                            </button>
                        </div>

                        <div class="form-group password-group">
                            <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword')">
                                <i class="fa fa-eye" id="confirmPassword-icon"></i>
                            </button>
                        </div>

                        <div class="form-group">
                            <input type="tel" id="phoneNo" name="phoneNo" placeholder="Phone Number" 
                                   value="<?php echo isset($_POST['phoneNo']) ? htmlspecialchars($_POST['phoneNo']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <textarea id="address" name="address" placeholder="Address" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>

                        <div class="checkbox-group">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">I agree to nyamanhippo's <a href="termsService.php" target="_blank">Terms and Service</a> and <a href="privacyPolicy.php" target="_blank">Privacy Policy</a></label>
                        </div>

                        <button type="submit" class="btn-register">Create Account</button>

                        <p class="login-link">Already have an account? <a href="login.php">Login</a></p>
                    </form>
                </div>
            </div>

            <!-- RIGHT SIDE: Image -->
            <div class="register-right">
                <img src="register-food-image.jpg" alt="Delicious Food" class="register-image">
            </div>
        </div>
    </section>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Real-time password match indicator
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.style.borderColor = '#dc3545';
            } else if (confirmPassword && password === confirmPassword) {
                this.style.borderColor = '#28a745';
            } else {
                this.style.borderColor = '#ffc4d6';
            }
        });

        // Client-side validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const terms = document.getElementById('terms').checked;
            
            if (!terms) {
                e.preventDefault();
                alert('Please accept the Terms of Service and Privacy Policy to continue.');
                return;
            }
            
            if (password.length < 6 || password.length > 12) {
                e.preventDefault();
                alert('Password must be 6-12 characters long');
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
    line-height: 1.6;
    color: #333;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.register-section {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    background: white;
}

.register-container {
    width: 100%;
    height: 100%;
    min-height: calc(100vh - 60px);
    display: grid;
    grid-template-columns: 1.5fr 1fr;
}

.register-left {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 6% 10% 3rem 12%;
    background: white;
}

.register-content {
    width: 100%;
    max-width: 500px;
}

.register-content h1 {
    color: var(--dark);
    font-size: 2rem;
    margin-bottom: 0.5rem;
    font-weight: 700;
    text-align: center;
}

.subtitle {
    color: #666;
    font-size: 0.95rem;
    margin-bottom: 1.8rem;
    text-align: center;
    line-height: 1.4;
}

/* Alert Messages */
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    text-align: center;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.register-right {
    background: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

.register-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

.register-right::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    z-index: -1;
}

.register-form {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    position: relative;
}

.form-group input,
.form-group textarea {
    padding: 0.85rem 1rem;
    border: 1px solid #ffc4d6;
    border-radius: 6px;
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.3s;
    width: 100%;
    background: #ffe8f0;
}

/* Password field with toggle button */
.password-group input {
    padding-right: 45px;
}

.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #666;
    font-size: 18px;
    padding: 5px 10px;
    transition: color 0.3s;
}

.toggle-password:hover {
    color: var(--primary);
}

.toggle-password i {
    pointer-events: none;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 2px rgba(215, 15, 100, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 70px;
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: #999;
    font-size: 0.9rem;
}

.checkbox-group {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    margin-top: 0.3rem;
}

.checkbox-group input[type="checkbox"] {
    width: 16px;
    height: 16px;
    margin-top: 0.2rem;
    cursor: pointer;
    flex-shrink: 0;
    accent-color: var(--primary);
}

.checkbox-group label {
    font-size: 0.8rem;
    color: #333;
    line-height: 1.3;
    cursor: pointer;
}

.checkbox-group label a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
}

.checkbox-group label a:hover {
    text-decoration: underline;
}

.btn-register {
    background: var(--primary);
    color: white;
    padding: 0.85rem 2rem;
    border: none;
    border-radius: 25px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 0.8rem;
    width: 100%;
}

.btn-register:hover {
    background: var(--secondary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(215, 15, 100, 0.3);
}

.login-link {
    text-align: center;
    margin-top: 0.8rem;
    font-size: 0.85rem;
    color: #333;
}

.login-link a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
}

.login-link a:hover {
    text-decoration: underline;
}

@media (max-width: 1024px) {
    .register-container {
        grid-template-columns: 1fr;
    }

    .register-right {
        display: none;
    }

    .register-left {
        min-height: calc(100vh - 60px);
    }
}

@media (max-width: 768px) {
    .register-left {
        padding: 2rem 1.5rem;
    }

    .register-content h1 {
        font-size: 1.6rem;
    }
}

@media (max-width: 480px) {
    .register-left {
        padding: 1.5rem 1rem;
    }

    .register-content {
        max-width: 100%;
    }

    .register-content h1 {
        font-size: 1.4rem;
    }

    .subtitle {
        font-size: 0.85rem;
    }

    .form-group input,
    .form-group textarea {
        padding: 0.7rem 0.9rem;
        font-size: 0.9rem;
    }

    .password-group input {
        padding-right: 40px;
    }

    .btn-register {
        padding: 0.8rem 1.5rem;
        font-size: 0.95rem;
    }
}
</style>