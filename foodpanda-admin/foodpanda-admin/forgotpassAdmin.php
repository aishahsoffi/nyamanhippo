<?php
$successMessage = '';
$errorMessage = '';

// Database connection
$host = 'localhost';
$dbname = 'foodpanda_db';
$username = 'root';
$password = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get email
        $email = trim($_POST['email']);

        // Validate input
        if (empty($email)) {
            $errorMessage = 'Please enter your email address';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Please enter a valid email address';
        } else {
            // Check if email exists in admin table
            $stmt = $conn->prepare("SELECT * FROM admin WHERE Email = :email LIMIT 1");
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Store token in password_resets table
                $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expiry) VALUES (:email, :token, :expiry)");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':token', $token);
                $stmt->bindParam(':expiry', $expires);
                $stmt->execute();

                // Create reset link
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password_admin.php?token=" . $token;

                // Email content
                $to = $email;
                $subject = "Password Reset Request - Foodpanda Admin";
                $message = "
                <html>
                <head>
                    <title>Password Reset</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #e91e63; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f9f9f9; }
                        .button { display: inline-block; padding: 12px 30px; background: #e91e63; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Password Reset Request</h2>
                        </div>
                        <div class='content'>
                            <p>Hello " . htmlspecialchars($admin['Name']) . ",</p>
                            <p>We received a request to reset your admin password. Click the button below to reset it:</p>
                            <p style='text-align: center;'>
                                <a href='" . $resetLink . "' class='button'>Reset Password</a>
                            </p>
                            <p>Or copy and paste this link into your browser:</p>
                            <p style='word-break: break-all; background: #fff; padding: 10px; border-left: 3px solid #e91e63;'>" . $resetLink . "</p>
                            <p><strong>This link will expire in 1 hour.</strong></p>
                            <p>If you didn't request this, please ignore this email. Your password will remain unchanged.</p>
                        </div>
                        <div class='footer'>
                            <p>© 2025 Foodpanda Admin Panel. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";

                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8\r\n";
                $headers .= "From: Foodpanda Admin <noreply@foodpanda.com>\r\n";

                // Send email (in production, use proper mail service)
                // mail($to, $subject, $message, $headers);
                
                // For development: Log the reset link
                error_log("Password reset link for $email: $resetLink");
            }
            
            // Always show success message (security best practice - don't reveal if email exists)
            $successMessage = 'If your email exists in our system, you will receive a password reset link within 5 minutes.';
            
            // Auto redirect after 3 seconds
            echo '<script>setTimeout(function(){ window.location.href = "login_admin.php"; }, 3000);</script>';
        }
    } catch(PDOException $e) {
        $errorMessage = 'An error occurred. Please try again later.';
        error_log("Forgot password error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Admin</title>
    <link rel="stylesheet" href="forgotpassAdmin.css">
</head>
<body>

<div class="forgot-wrapper">
    
    <!-- LEFT PANEL -->
    <div class="forgot-left">
        <div class="logo-container">
            <img src="foodpanda-logo.jpg" alt="Foodpanda Logo" class="logo">
        </div>
        <h2>Admin Control Center</h2>
    </div>

    <!-- RIGHT PANEL -->
    <div class="forgot-right">
        <span class="badge">🔒 ADMIN ACCESS ONLY</span>

        <h1>Forgot Password?</h1>
        <p class="subtitle">Enter your email address and we'll send you a reset link</p>

        <?php if (!empty($successMessage)): ?>
            <div class="message-box success-box" style="display: block;">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="message-box error-box" style="display: block;">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <form action="forgotpassAdmin.php" method="POST">
            <div class="input-group">
                <input type="email" name="email" placeholder="Admin Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <button type="submit" class="reset-btn">
                Send Reset Link
            </button>
        </form>

        <div class="back-to-login">
            <a href="login_admin.php">← Back to Login</a>
        </div>

        <div class="info-box">
            <p><strong>📧 Note:</strong></p>
            <p>If the email exists in our system, you will receive a password reset link within 5 minutes.</p>
            <p>Check your spam folder if you don't see the email.</p>
        </div>
    </div>

</div>

</body>
</html>