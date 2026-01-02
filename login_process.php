<?php
session_start();

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Dummy admin credentials (for prototype)
$admin_email = "admin@gmail.com";
$admin_password = "admin123";

if ($email === $admin_email && $password === $admin_password) {

    $_SESSION['admin_logged_in'] = true;

    // Redirect to dashboard (teammate's page)
    header("Location: dashboard.php");
    exit();

} else {
    echo "<script>
            alert('Invalid admin email or password');
            window.location.href = 'login_admin.html';
          </script>";
}
?>
