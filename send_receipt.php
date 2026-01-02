<?php
// send_receipt.php - Email Receipt Functionality
// This file handles sending order receipts to users after successful payment

function sendOrderReceipt($orderId, $userId) {
    // Database configuration
    $host = 'localhost';
    $dbname = 'foodpanda_db';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Fetch order details from database
        $orderQuery = "SELECT o.Order_ID, o.Total_Price, o.Status, o.Order_Date,
                              u.Name, u.Email, u.PhoneNo,
                              p.Payment_Method, p.Payment_Date, p.Amount
                       FROM `order` o
                       JOIN user u ON o.User_ID = u.User_ID
                       LEFT JOIN payment p ON o.Payment_ID = p.Payment_ID
                       WHERE o.Order_ID = ? AND o.User_ID = ?";
        
        $stmt = $pdo->prepare($orderQuery);
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            error_log("Order not found for receipt: Order ID $orderId, User ID $userId");
            return false;
        }
        
        // Build email content
        $to = $order['Email'];
        $subject = "Order Receipt #ORD-" . str_pad($orderId, 4, '0', STR_PAD_LEFT) . " - FoodPanda";
        
        // Email message
        $message = "Dear " . $order['Name'] . ",\n\n";
        $message .= "Thank you for your order with FoodPanda!\n\n";
        $message .= "==============================================\n";
        $message .= "ORDER RECEIPT\n";
        $message .= "==============================================\n\n";
        
        $message .= "Order ID: #ORD-" . str_pad($order['Order_ID'], 4, '0', STR_PAD_LEFT) . "\n";
        $message .= "Order Date: " . date('d M Y, h:i A', strtotime($order['Order_Date'])) . "\n";
        $message .= "Status: " . $order['Status'] . "\n\n";
        
        $message .= "----------------------------------------------\n";
        $message .= "CUSTOMER DETAILS\n";
        $message .= "----------------------------------------------\n";
        $message .= "Name: " . $order['Name'] . "\n";
        $message .= "Email: " . $order['Email'] . "\n";
        $message .= "Phone: " . $order['PhoneNo'] . "\n\n";
        
        $message .= "----------------------------------------------\n";
        $message .= "PAYMENT INFORMATION\n";
        $message .= "----------------------------------------------\n";
        $message .= "Payment Method: " . ($order['Payment_Method'] ?? 'N/A') . "\n";
        $message .= "Payment Date: " . ($order['Payment_Date'] ? date('d M Y', strtotime($order['Payment_Date'])) : 'N/A') . "\n";
        $message .= "Amount Paid: RM " . number_format($order['Amount'] ?? $order['Total_Price'], 2) . "\n\n";
        
        $message .= "----------------------------------------------\n";
        $message .= "ORDER SUMMARY\n";
        $message .= "----------------------------------------------\n";
        $message .= "Total Amount: RM " . number_format($order['Total_Price'], 2) . "\n\n";
        
        $message .= "==============================================\n\n";
        $message .= "Thank you for choosing FoodPanda!\n";
        $message .= "We hope you enjoy your meal.\n\n";
        $message .= "If you have any questions or concerns, please contact our customer support.\n\n";
        $message .= "Best regards,\n";
        $message .= "FoodPanda Team\n";
        $message .= "www.foodpanda.com\n";
        
        // Email headers
        $headers = "From: FoodPanda <noreply@foodpanda.com>\r\n";
        $headers .= "Reply-To: support@foodpanda.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        // Send email
        $emailSent = mail($to, $subject, $message, $headers);
        
        if ($emailSent) {
            error_log("Receipt email sent successfully to: " . $to . " for Order ID: " . $orderId);
            return true;
        } else {
            error_log("Failed to send receipt email to: " . $to . " for Order ID: " . $orderId);
            return false;
        }
        
    } catch(PDOException $e) {
        error_log("Database error in sendOrderReceipt: " . $e->getMessage());
        return false;
    }
}

// Function to send multiple order receipts (for multi-restaurant orders)
function sendMultipleOrderReceipts($orderIds, $userId) {
    $allSent = true;
    
    foreach ($orderIds as $orderId) {
        $sent = sendOrderReceipt($orderId, $userId);
        if (!$sent) {
            $allSent = false;
        }
    }
    
    return $allSent;
}
?>
