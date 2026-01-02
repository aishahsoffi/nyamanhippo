<?php
session_start();

// Database connection 
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "foodpanda_db";

$conn = null;
$db_error = false;

// Try to connect with error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        $db_error = true;
        $conn = null;
    }
} catch (mysqli_sql_exception $e) {
    error_log("MySQL Connection Error: " . $e->getMessage());
    $db_error = true;
    $conn = null;
}

$error_message = "";
$success_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if database connection is available
    if ($conn === null || $db_error) {
        $error_message = "Database connection error. Please try again later.";
    } else {
        $emailOrUsername = trim($_POST['emailOrUsername']);
        $password = $_POST['password'];
        $rememberMe = isset($_POST['rememberMe']);
        
        // Validation
        if (empty($emailOrUsername) || empty($password)) {
            $error_message = "Please fill in all fields";
        } else {
            // Prepare SQL statement to prevent SQL injection
            $stmt = $conn->prepare("SELECT User_ID, Username, Name, Email, Password, PhoneNo, Address FROM user WHERE Email = ? OR Username = ?");
            
            if ($stmt === false) {
                $error_message = "Error preparing query. Please try again.";
                error_log("Prepare failed: " . $conn->error);
            } else {
                $stmt->bind_param("ss", $emailOrUsername, $emailOrUsername);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    // Verify password (assuming passwords are hashed with password_hash())
                    // If passwords are plain text in your current setup, use: if ($password === $user['Password'])
                    if (password_verify($password, $user['Password']) || $password === $user['Password']) {
                        // Login successful
                        $_SESSION['user_id'] = $user['User_ID'];
                        $_SESSION['username'] = $user['Username'];
                        $_SESSION['name'] = $user['Name'];
                        $_SESSION['email'] = $user['Email'];
                        
                        // Store user data in session for JavaScript access
                        $_SESSION['currentUser'] = json_encode([
                            'userId' => $user['User_ID'],
                            'username' => $user['Username'],
                            'fullName' => $user['Name'],
                            'email' => $user['Email'],
                            'phone' => $user['PhoneNo'],
                            'address' => $user['Address']
                        ]);
                        
                        // Remember me functionality
                        if ($rememberMe) {
                            setcookie('remember_user', $user['Email'], time() + (86400 * 30), "/"); // 30 days
                        }
                        
                        $success_message = "Login successful! Welcome back, " . htmlspecialchars($user['Name']) . "!";
                        
                        // Redirect to user index page
                        echo "<script>
                            sessionStorage.setItem('currentUser', '" . addslashes($_SESSION['currentUser']) . "');
                            setTimeout(function() {
                                window.location.href = 'userIndex.php';
                            }, 1500);
                        </script>";
                    } else {
                        $error_message = "Incorrect password. Please try again.";
                    }
                } else {
                    $error_message = "User not found. Please check your email/username or create an account.";
                }
                
                $stmt->close();
            }
        }
    }
}

// Check for remembered user
$remembered_email = "";
if (isset($_COOKIE['remember_user'])) {
    $remembered_email = $_COOKIE['remember_user'];
}

// Close connection if it exists
if ($conn !== null) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - nyamanhippo</title>
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
            </div>
        </div>
    </nav>

    <!-- Login Section -->
    <section class="login-section">
        <div class="login-container">
            <!-- LEFT SIDE: Login Form -->
            <div class="login-left">
                <div class="login-content">
                    <h1>Welcome Back</h1>
                    <p class="subtitle">Log in to continue ordering</p>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($db_error): ?>
                        <div class="alert alert-error">
                            <strong>Connection Error:</strong> Unable to connect to database. Please check if MySQL is running.
                        </div>
                    <?php endif; ?>
                    
                    <form id="loginForm" class="login-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-group">
                            <input type="text" id="emailOrUsername" name="emailOrUsername" 
                                   placeholder="Email or username" 
                                   value="<?php echo htmlspecialchars($remembered_email); ?>" 
                                   required>
                        </div>

                        <div class="form-group password-group">
                            <input type="password" id="password" name="password" placeholder="Password" required>
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fa fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>

                        <div class="form-options">
                            <div class="remember-group">
                                <input type="checkbox" id="rememberMe" name="rememberMe" 
                                       <?php echo $remembered_email ? 'checked' : ''; ?>>
                                <label for="rememberMe">Remember Me</label>
                            </div>
                            <a href="forgotPassword.php" class="forgot-link">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn-login">Login</button>

                        <p class="register-link">Don't have an account? <a href="registration.php">Create Account</a></p>
                        
                        <!-- Admin Login Link -->
                        <p class="admin-link">Are you an admin? <a href="login_admin.html">Admin Login</a></p>
                    </form>
                </div>
            </div>

            <!-- RIGHT SIDE: Image -->
            <div class="login-right">
                <img src="login-food-image.jpg" alt="Delicious Food" class="login-image">
            </div>
        </div>
    </section>

    <script>
        // Toggle Password Visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>

<style>
/* ============================================
   RESET & VARIABLES
   ============================================ */
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

/* ============================================
   ALERT MESSAGES
   ============================================ */
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 0.9rem;
    font-weight: 500;
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

/* ============================================
   LOGIN SECTION
   ============================================ */
.login-section {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    background: white;
}

.login-container {
    width: 100%;
    height: 100%;
    min-height: calc(100vh - 60px);
    display: grid;
    grid-template-columns: 1.5fr 1fr;
}

/* LEFT SIDE: Form */
.login-left {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 10% 3rem 12%;
    background: white;
}

.login-content {
    width: 100%;
    max-width: 450px;
}

.login-content h1 {
    color: var(--dark);
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    font-weight: 700;
    text-align: center;
}

.subtitle {
    color: #666;
    font-size: 1rem;
    margin-bottom: 2.5rem;
    text-align: center;
    line-height: 1.4;
}

/* RIGHT SIDE: Image */
.login-right {
    background: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

.login-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

/* If image doesn't load, show gradient */
.login-right::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    z-index: -1;
}

/* ============================================
   FORM STYLES
   ============================================ */
.login-form {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    position: relative;
}

.password-group {
    position: relative;
}

.form-group input {
    padding: 1rem 1.2rem;
    border: 1px solid #ffc4d6;
    border-radius: 8px;
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.3s;
    width: 100%;
    background: #ffe8f0;
}

.password-group input {
    padding-right: 45px; /* Make room for the eye icon */
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
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.3s;
    font-size: 1.1rem;
}

.toggle-password:hover {
    color: var(--primary);
}

.form-group input:focus {
    outline: none;
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 3px rgba(215, 15, 100, 0.1);
}

.form-group input::placeholder {
    color: #999;
    font-size: 0.95rem;
}

/* ============================================
   FORM OPTIONS (Remember Me + Forgot Password)
   ============================================ */
.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: -0.3rem;
}

.remember-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.remember-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--primary);
}

.remember-group label {
    font-size: 0.9rem;
    color: #333;
    cursor: pointer;
}

.forgot-link {
    color: var(--primary);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
}

.forgot-link:hover {
    text-decoration: underline;
}

/* ============================================
   BUTTON
   ============================================ */
.btn-login {
    background: var(--primary);
    color: white;
    padding: 1rem 2rem;
    border: none;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 0.5rem;
    width: 100%;
}

.btn-login:hover {
    background: var(--secondary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(215, 15, 100, 0.3);
}

/* ============================================
   REGISTER LINK
   ============================================ */
.register-link {
    text-align: center;
    margin-top: 0.5rem;
    font-size: 0.9rem;
    color: #333;
}

.register-link a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
}

.register-link a:hover {
    text-decoration: underline;
}

/* ============================================
   ADMIN LINK
   ============================================ */
.admin-link {
    text-align: center;
    margin-top: 0.8rem;
    font-size: 0.85rem;
    color: #666;
    padding-top: 0.8rem;
    border-top: 1px solid #f0f0f0;
}

.admin-link a {
    color: #555;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s;
}

.admin-link a:hover {
    color: var(--primary);
    text-decoration: underline;
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 1024px) {
    .login-container {
        grid-template-columns: 1fr;
    }

    .login-right {
        display: none;
    }

    .login-left {
        min-height: calc(100vh - 60px);
    }
}

@media (max-width: 768px) {
    .login-left {
        padding: 2rem 1.5rem;
    }

    .login-content h1 {
        font-size: 2rem;
    }
}

@media (max-width: 480px) {
    .login-left {
        padding: 1.5rem 1rem;
    }

    .login-content {
        max-width: 100%;
    }

    .login-content h1 {
        font-size: 1.8rem;
    }

    .subtitle {
        font-size: 0.9rem;
    }

    .form-group input {
        padding: 0.85rem 1rem;
        font-size: 0.95rem;
    }

    .btn-login {
        padding: 0.9rem 1.5rem;
    }

    .form-options {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>