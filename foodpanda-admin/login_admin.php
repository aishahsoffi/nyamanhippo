<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'foodpanda_db';
$username = 'root';
$password = '';

$errorMessage = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get form data
        $email = trim($_POST['email']);
        $inputPassword = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;

        // Validate inputs
        if (empty($email) || empty($inputPassword)) {
            $errorMessage = 'Please fill in all fields';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Please enter a valid email address';
        } else {
            // Check credentials in admin table
            $stmt = $conn->prepare("SELECT * FROM admin WHERE Email = :email LIMIT 1");
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Direct password comparison (no hashing in your DB)
                if ($inputPassword === $admin['Password']) {
                    // Login successful
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['Admin_ID'];
                    $_SESSION['admin_email'] = $admin['Email'];
                    $_SESSION['admin_name'] = $admin['Name'];

                    // Remember me functionality
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('admin_remember', $token, time() + (86400 * 10), "/");
                        
                        // Store token in session or database if needed
                        $_SESSION['remember_token'] = $token;
                    }

                    // Redirect to dashboard
                    header("Location: adminDashboard.html");
                    exit();
                } else {
                    $errorMessage = 'Incorrect password. Please try again.';
                }
            } else {
                $errorMessage = 'Account does not exist. Please check your email.';
            }
        }
    } catch(PDOException $e) {
        $errorMessage = 'Database connection error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Foodpanda</title>
    <link rel="stylesheet" href="login_admin.css">
</head>
<body>

<div class="login-wrapper">

    <!-- LEFT PANEL -->
    <div class="login-left">
        <div class="logo-container">
            <img src="foodpanda-logo.jpg" alt="Foodpanda Logo" class="logo">
        </div>
        <h2>Admin Control Center</h2>
    </div>

    <!-- RIGHT PANEL -->
    <div class="login-right">
        <span class="badge">🔒 ADMIN ACCESS ONLY</span>

        <h1>Welcome Back</h1>
        <p class="subtitle">Sign in to access the admin dashboard</p>

        <?php if (!empty($errorMessage)): ?>
            <div class="error-box" style="display: block;">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <form action="login_admin.php" method="POST">
            <div class="input-group">
                <input type="email" name="email" placeholder="Admin Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <div class="options">
                <label class="remember">
                    <input type="checkbox" name="remember">
                    Remember me for 10 days
                </label>
                <a href="forgotpassAdmin.php" class="forgot">Forgot password?</a>
            </div>

            <button type="submit" class="login-btn">
                Sign in to Dashboard
            </button>
        </form>
    </div>

</div>

</body>
</html>