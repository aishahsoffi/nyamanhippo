<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Database configuration
$host = 'localhost';
$dbname = 'foodpanda_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'updateSettings') {
        $adminId = $_SESSION['admin_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $currentPassword = isset($_POST['currentPassword']) ? $_POST['currentPassword'] : '';
        $newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';
        
        // Validate inputs
        if (empty($name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Name and email are required']);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit;
        }
        
        try {
            // Check if email is already taken by another admin
            $checkEmailSql = "SELECT Admin_ID FROM admin WHERE Email = :email AND Admin_ID != :adminId";
            $checkEmailStmt = $pdo->prepare($checkEmailSql);
            $checkEmailStmt->execute([':email' => $email, ':adminId' => $adminId]);
            
            if ($checkEmailStmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Email is already in use by another admin']);
                exit;
            }
            
            // If changing password, verify current password
            if (!empty($currentPassword) && !empty($newPassword)) {
                // Get current password from database
                $getPasswordSql = "SELECT Password FROM admin WHERE Admin_ID = :adminId";
                $getPasswordStmt = $pdo->prepare($getPasswordSql);
                $getPasswordStmt->execute([':adminId' => $adminId]);
                $admin = $getPasswordStmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify current password (direct comparison since not hashed)
                if ($currentPassword !== $admin['Password']) {
                    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                    exit;
                }
                
                // Validate new password
                if (strlen($newPassword) < 6) {
                    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
                    exit;
                }
                
                // Update with new password
                $updateSql = "UPDATE admin SET Name = :name, Email = :email, Password = :password WHERE Admin_ID = :adminId";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $newPassword, // Store directly (not hashed)
                    ':adminId' => $adminId
                ]);
                
                // Update session
                $_SESSION['admin_name'] = $name;
                $_SESSION['admin_email'] = $email;
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Settings and password updated successfully!'
                ]);
            } else {
                // Update without password change
                $updateSql = "UPDATE admin SET Name = :name, Email = :email WHERE Admin_ID = :adminId";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':adminId' => $adminId
                ]);
                
                // Update session
                $_SESSION['admin_name'] = $name;
                $_SESSION['admin_email'] = $email;
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Settings updated successfully!'
                ]);
            }
            
        } catch(PDOException $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>