<?php
session_start();

// Database connection 
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "foodpanda_db";

// Try to connect with error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        // Log error but don't show details to users
        error_log("Connection failed: " . $conn->connect_error);
        $conn = null; // Set connection to null
    }
} catch (mysqli_sql_exception $e) {
    // Catch connection exceptions
    error_log("MySQL Connection Error: " . $e->getMessage());
    $conn = null; // Set connection to null
    // Continue without database for static pages
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - nyamanhippo</title>
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
            line-height: 1.6;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #fafafa;
        }

        .terms-section {
            flex: 1;
            padding: 3rem 2rem;
        }

        .terms-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .terms-container h1 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .last-updated {
            text-align: center;
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 2rem;
        }

        .terms-content h2 {
            color: var(--dark);
            font-size: 1.2rem;
            margin-top: 1.5rem;
            margin-bottom: 0.8rem;
        }

        .terms-content p {
            margin-bottom: 0.8rem;
            line-height: 1.7;
            color: #555;
            font-size: 0.95rem;
        }

        .terms-content ul {
            margin: 0.8rem 0 0.8rem 2rem;
            line-height: 1.7;
        }

        .terms-content ul li {
            margin-bottom: 0.4rem;
            color: #555;
            font-size: 0.95rem;
        }

        .footer {
            background: var(--dark);
            color: white;
            text-align: center;
            padding: 2rem 0;
            margin-top: 2rem;
        }

        .footer p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .terms-container {
                padding: 2rem 1.5rem;
            }

            .terms-container h1 {
                font-size: 2rem;
            }

            .terms-content h2 {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 480px) {
            .terms-section {
                padding: 2rem 1rem;
            }

            .terms-container {
                padding: 1.5rem 1rem;
            }

            .terms-container h1 {
                font-size: 1.6rem;
            }

            .terms-content h2 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-wrapper">
            <div class="logo">
                <img src="foodpanda-logo.jpg" alt="FoodPanda Logo" class="logo-img" onerror="this.style.display='none'">
                <h1>nyamanhippo</h1>
            </div>
            <div class="nav-links">
            </div>
        </div>
    </nav>

    <!-- Terms Content -->
    <section class="terms-section">
        <div class="terms-container">
            <h1>Terms of Service</h1>
            <p class="last-updated">Last Updated: <?php echo date('F j, Y'); ?></p>

            <div class="terms-content">
                <h2>1. Acceptance of Terms</h2>
                <p>By accessing and using nyamanhippo ("the Service"), you accept and agree to be bound by the terms and provisions of this agreement. If you do not agree to these terms, please do not use our Service.</p>

                <h2>2. Use of Service</h2>
                <p>You agree to use the Service only for lawful purposes and in accordance with these Terms. You agree not to use the Service:</p>
                <ul>
                    <li>In any way that violates any applicable national or international law or regulation</li>
                    <li>To transmit, or procure the sending of, any advertising or promotional material without our prior written consent</li>
                    <li>To impersonate or attempt to impersonate nyamanhippo, a nyamanhippo employee, another user, or any other person or entity</li>
                </ul>

                <h2>3. User Account</h2>
                <p>When you create an account with us, you guarantee that the information you provide is accurate, complete, and current at all times. You are responsible for maintaining the confidentiality of your account and password.</p>

                <h2>4. Orders and Payment</h2>
                <p>All orders placed through our Service are subject to acceptance and availability. Prices for our products are subject to change without notice. We reserve the right to refuse or cancel any order for any reason at any time.</p>

                <h2>5. Delivery</h2>
                <p>We will make every effort to deliver your order within the estimated delivery time. However, delivery times are estimates and we are not liable for any delays in delivery.</p>

                <h2>6. Cancellation and Refunds</h2>
                <p>Orders can be cancelled within 5 minutes of placement for a full refund. After this period, cancellations may not be possible as preparation may have already begun.</p>

                <h2>7. User Conduct</h2>
                <p>You agree to use the Service in a respectful manner. Abusive behavior towards delivery personnel or restaurant staff will result in account suspension or termination.</p>

                <h2>8. Intellectual Property</h2>
                <p>The Service and its original content, features, and functionality are owned by nyamanhippo and are protected by international copyright, trademark, patent, trade secret, and other intellectual property laws.</p>

                <h2>9. Limitation of Liability</h2>
                <p>In no event shall nyamanhippo, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential, or punitive damages arising out of your use of the Service.</p>

                <h2>10. Changes to Terms</h2>
                <p>We reserve the right to modify or replace these Terms at any time. We will provide notice of any changes by posting the new Terms on this page and updating the "Last Updated" date.</p>

                <h2>11. Contact Us</h2>
                <p>If you have any questions about these Terms, please contact us at:</p>
                <ul>
                    <li>Email: support@nyamanhippo.com</li>
                    <li>Phone: +60 3-1234 5678</li>
                    <li>Address: Kuching, Sarawak, Malaysia</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> nyamanhippo. All rights reserved. | CS-Erudite Group Project</p>
        <?php
        // Display current date and time in footer
        date_default_timezone_set('Asia/Kuala_Lumpur');
        echo '<p style="font-size: 0.8rem; margin-top: 5px; opacity: 0.7;">';
        echo 'Current time: ' . date('g:i A, F j, Y');
        echo '</p>';
        
        // Close database connection if it exists
        if ($conn) {
            $conn->close();
        }
        ?>
    </footer>
</body>
</html>