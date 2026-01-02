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

// Create database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    $db_error = true;
}

// Get current user data
$currentUser = null;
if ($pdo && !$db_error) {
    try {
        $userId = $_SESSION['user_id'];
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
        error_log("User query failed: " . $e->getMessage());
    }
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $firstName = trim($_POST['firstName']);
        $lastName = trim($_POST['lastName']);
        $fullName = $firstName . ' ' . $lastName;
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $dateOfBirth = !empty($_POST['dateOfBirth']) ? $_POST['dateOfBirth'] : null;
        $gender = !empty($_POST['gender']) ? $_POST['gender'] : null;
        
        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
            $error_message = "Please fill in all required fields!";
        } 
        // Validate email format
        else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address!";
        }
        else {
            // Handle profile picture upload
            $profilePicturePath = $currentUser['Profile_Picture'];
            
            // Check if user wants to remove picture
            if (isset($_POST['removePicture']) && $_POST['removePicture'] == '1') {
                // Delete old picture file if exists
                if ($profilePicturePath && file_exists($profilePicturePath)) {
                    unlink($profilePicturePath);
                }
                $profilePicturePath = null;
            }
            // Check if new picture uploaded
            else if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profilePicture'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                // Validate file type
                if (!in_array($file['type'], $allowedTypes)) {
                    $error_message = "Only JPG, PNG, and GIF files are allowed!";
                }
                // Validate file size
                else if ($file['size'] > $maxSize) {
                    $error_message = "File size must be less than 5MB!";
                }
                else {
                    // Create uploads directory if it doesn't exist
                    $uploadDir = 'uploads/profile_pictures/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
                    $uploadPath = $uploadDir . $filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        // Delete old picture if exists
                        if ($profilePicturePath && file_exists($profilePicturePath)) {
                            unlink($profilePicturePath);
                        }
                        $profilePicturePath = $uploadPath;
                    } else {
                        $error_message = "Failed to upload profile picture!";
                    }
                }
            }
            
            // Handle password change
            if (!empty($_POST['newPassword'])) {
                $currentPassword = $_POST['currentPassword'];
                $newPassword = $_POST['newPassword'];
                $confirmPassword = $_POST['confirmPassword'];
                
                // Verify current password
                if (!password_verify($currentPassword, $currentUser['Password'])) {
                    $error_message = "Current password is incorrect!";
                } else if ($newPassword !== $confirmPassword) {
                    $error_message = "New passwords do not match!";
                } else if (strlen($newPassword) < 6) {
                    $error_message = "New password must be at least 6 characters long!";
                } else {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $passwordQuery = "UPDATE user SET Password = ? WHERE User_ID = ?";
                    $passwordStmt = $pdo->prepare($passwordQuery);
                    $passwordStmt->execute([$hashedPassword, $userId]);
                }
            }
            
            if (empty($error_message)) {
                // Update user profile with all fields
                $updateQuery = "UPDATE user SET Name = ?, Email = ?, PhoneNo = ?, Date_Of_Birth = ?, Gender = ?, Profile_Picture = ? WHERE User_ID = ?";
                $updateStmt = $pdo->prepare($updateQuery);
                $updateStmt->execute([$fullName, $email, $phone, $dateOfBirth, $gender, $profilePicturePath, $userId]);
                
                $success_message = "Profile updated successfully!";
                
                // Refresh user data
                $userStmt->execute([$userId]);
                $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    } catch(PDOException $e) {
        error_log("Update failed: " . $e->getMessage());
        $error_message = "Failed to update profile. Please try again.";
    }
}

// Set user data for form
$fullName = $currentUser['Name'] ?? 'James Smith';
$nameParts = explode(' ', $fullName, 2);
$firstName = $nameParts[0] ?? 'James';
$lastName = $nameParts[1] ?? 'Smith';
$email = $currentUser['Email'] ?? 'james@email.com';
$phone = $currentUser['PhoneNo'] ?? '+60123456789';
$dateOfBirth = $currentUser['Date_Of_Birth'] ?? '';
$gender = $currentUser['Gender'] ?? '';
$profilePicture = $currentUser['Profile_Picture'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | nyamanhippo</title>
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
            </div>
        </div>
    </nav>

    <main class="edit-profile-container">
        <div class="back-link-wrapper">
            <a href="userProfile.php" class="back-link">
                <i class="fa fa-arrow-left"></i> Back to Profile
            </a>
        </div>

        <div class="edit-profile-card">
            <h1>Edit Profile</h1>
            <p class="subtitle">Update your personal information and profile picture</p>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form id="editProfileForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                <input type="hidden" name="removePicture" id="removePictureInput" value="0">
                
                <!-- Profile Picture Section -->
                <div class="profile-picture-section">
                    <div class="current-avatar" id="currentAvatar">
                        <?php if ($profilePicture && file_exists($profilePicture)): ?>
                            <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <i class="fa fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="picture-controls">
                        <input type="file" id="profilePictureInput" name="profilePicture" accept="image/*" style="display: none;">
                        <button type="button" class="upload-btn" onclick="document.getElementById('profilePictureInput').click()">
                            <i class="fa fa-camera"></i> Change Picture
                        </button>
                        <button type="button" class="remove-btn" id="removePictureBtn">
                            <i class="fa fa-trash"></i> Remove Picture
                        </button>
                        <p class="upload-hint">JPG, PNG or GIF (Max 5MB)</p>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="form-section">
                    <h2><i class="fa fa-user"></i> Personal Information</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name *</label>
                            <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($firstName); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name *</label>
                            <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($lastName); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="dateOfBirth">Date of Birth</label>
                        <input type="date" id="dateOfBirth" name="dateOfBirth" value="<?php echo htmlspecialchars($dateOfBirth); ?>">
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Prefer not to say</option>
                            <option value="male" <?php echo $gender === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo $gender === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo $gender === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <!-- Change Password Section -->
                <div class="form-section">
                    <h2><i class="fa fa-lock"></i> Change Password</h2>
                    <p class="section-subtitle">Leave blank if you don't want to change your password</p>
                    
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" id="currentPassword" name="currentPassword" placeholder="Enter current password">
                    </div>

                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <input type="password" id="newPassword" name="newPassword" placeholder="Enter new password">
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm new password">
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="window.location.href='userProfile.php'">
                        Cancel
                    </button>
                    <button type="submit" class="btn-save">
                        <i class="fa fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
// Handle profile picture upload preview
document.getElementById('profilePictureInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Check file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB!');
            this.value = '';
            return;
        }

        // Check file type
        if (!file.type.match('image.*')) {
            alert('Please upload an image file (JPG, PNG, or GIF)');
            this.value = '';
            return;
        }

        // Read and display the image
        const reader = new FileReader();
        reader.onload = function(event) {
            const avatarDiv = document.getElementById('currentAvatar');
            avatarDiv.innerHTML = `<img src="${event.target.result}" alt="Profile Picture">`;
            document.getElementById('removePictureInput').value = '0';
        };
        reader.readAsDataURL(file);
    }
});

// Handle remove picture button
document.getElementById('removePictureBtn').addEventListener('click', function() {
    if (confirm('Are you sure you want to remove your profile picture?')) {
        const avatarDiv = document.getElementById('currentAvatar');
        avatarDiv.innerHTML = '<i class="fa fa-user"></i>';
        document.getElementById('profilePictureInput').value = '';
        document.getElementById('removePictureInput').value = '1';
    }
});

// Client-side form validation
document.getElementById('editProfileForm').addEventListener('submit', function(e) {
    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const email = document.getElementById('email').value.trim();
    const phone = document.getElementById('phone').value.trim();

    // Validate required fields
    if (!firstName || !lastName || !email || !phone) {
        e.preventDefault();
        alert('Please fill in all required fields!');
        return false;
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        alert('Please enter a valid email address!');
        return false;
    }

    // Handle password change validation
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const currentPassword = document.getElementById('currentPassword').value;

    if (newPassword || confirmPassword) {
        if (!currentPassword) {
            e.preventDefault();
            alert('Please enter your current password to change your password!');
            return false;
        }

        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('New passwords do not match!');
            return false;
        }

        if (newPassword.length < 6) {
            e.preventDefault();
            alert('New password must be at least 6 characters long!');
            return false;
        }
    }

    return true;
});

// Auto-hide success message after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0';
            setTimeout(() => successAlert.remove(), 300);
        }, 3000);
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
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background-color: #f8f9fa;
}

/* Alert Messages */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    transition: opacity 0.3s;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert i {
    font-size: 18px;
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

/* Edit Profile Container */
.edit-profile-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 20px;
}

.back-link-wrapper {
    margin-bottom: 20px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #d70f64;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    transition: color 0.2s;
}

.back-link:hover {
    color: #b00c50;
}

/* Edit Profile Card */
.edit-profile-card {
    background: white;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.edit-profile-card h1 {
    font-size: 28px;
    color: #2e2e2e;
    margin-bottom: 8px;
}

.subtitle {
    color: #666;
    font-size: 15px;
    margin-bottom: 30px;
}

/* Profile Picture Section */
.profile-picture-section {
    display: flex;
    align-items: center;
    gap: 30px;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 12px;
    margin-bottom: 30px;
}

.current-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, #d70f64 0%, #e91e63 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: white;
    flex-shrink: 0;
    overflow: hidden;
}

.current-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.picture-controls {
    flex: 1;
}

.upload-btn, .remove-btn {
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    margin-right: 10px;
    margin-bottom: 10px;
}

.upload-btn {
    background: #d70f64;
    color: white;
}

.upload-btn:hover {
    background: #b00c50;
}

.remove-btn {
    background: white;
    border: 1px solid #dc3545;
    color: #dc3545;
}

.remove-btn:hover {
    background: #dc3545;
    color: white;
}

.upload-hint {
    color: #999;
    font-size: 13px;
    margin-top: 5px;
}

/* Form Sections */
.form-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e5e5e5;
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section h2 {
    font-size: 18px;
    color: #2e2e2e;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section h2 i {
    color: #d70f64;
}

.section-subtitle {
    color: #666;
    font-size: 14px;
    margin-bottom: 20px;
}

/* Form Groups */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #2e2e2e;
    margin-bottom: 8px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #d70f64;
}

.form-group input::placeholder {
    color: #999;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 30px;
}

.btn-cancel, .btn-save {
    padding: 12px 30px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-cancel {
    background: white;
    border: 1px solid #e5e5e5;
    color: #666;
}

.btn-cancel:hover {
    background: #f8f9fa;
    border-color: #d70f64;
    color: #d70f64;
}

.btn-save {
    background: #d70f64;
    border: none;
    color: white;
}

.btn-save:hover {
    background: #b00c50;
    transform: translateY(-2px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .edit-profile-card {
        padding: 25px;
    }

    .profile-picture-section {
        flex-direction: column;
        text-align: center;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
    }

    .btn-cancel, .btn-save {
        width: 100%;
    }

    .upload-btn, .remove-btn {
        width: 100%;
        margin-right: 0;
    }
}
</style>