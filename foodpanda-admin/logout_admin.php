<?php
session_start();

// Store admin name before destroying session (for display)
$adminName = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
$adminEmail = isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : '';

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Delete remember me cookie
if (isset($_COOKIE['admin_remember'])) {
    setcookie('admin_remember', '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Store logout time for display
$logoutTime = date('d M Y, h:i:s A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logout - Admin Panel</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #1a1f2e 0%, #263447 100%);
      color: #e4e7eb;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 2rem;
    }

    .logout-container {
      background: #263447;
      padding: 3rem;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
      text-align: center;
      max-width: 500px;
      width: 100%;
      animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .panda-logo {
      width: 80px;
      height: 80px;
      object-fit: contain;
      margin-bottom: 1.5rem;
      display: block;
      margin-left: auto;
      margin-right: auto;
      animation: bounce 2s infinite;
    }

    @keyframes bounce {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-10px);
      }
    }

    h1 {
      font-size: 2rem;
      margin-bottom: 1rem;
      color: #e91e63;
    }

    .logout-message {
      font-size: 1.1rem;
      color: #b8c1d3;
      margin-bottom: 2rem;
      line-height: 1.6;
    }

    .logout-info {
      background: #1a1f2e;
      padding: 1.5rem;
      border-radius: 8px;
      margin-bottom: 2rem;
      border-left: 4px solid #e91e63;
    }

    .logout-info p {
      margin-bottom: 0.5rem;
      color: #9ca3af;
      font-size: 0.95rem;
    }

    .logout-info p:last-child {
      margin-bottom: 0;
    }

    .logout-info strong {
      color: #e4e7eb;
    }

    .button-group {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
    }

    .btn {
      padding: 0.9rem 2rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-block;
    }

    .btn-primary {
      background: #e91e63;
      color: white;
    }

    .btn-primary:hover {
      background: #c2185b;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(233, 30, 99, 0.4);
    }

    .btn-secondary {
      background: #3d4756;
      color: #e4e7eb;
    }

    .btn-secondary:hover {
      background: #4a5568;
      transform: translateY(-2px);
    }

    .countdown {
      margin-top: 2rem;
      color: #9ca3af;
      font-size: 0.9rem;
    }

    .countdown span {
      color: #e91e63;
      font-weight: bold;
    }

    @media (max-width: 768px) {
      .logout-container {
        padding: 2rem;
      }

      h1 {
        font-size: 1.6rem;
      }

      .logout-message {
        font-size: 1rem;
      }

      .button-group {
        flex-direction: column;
      }

      .btn {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="logout-container">
    <img src="foodpanda-logo.jpg" alt="Foodpanda Logo" class="panda-logo">
    <h1>Successfully Logged Out</h1>
    <p class="logout-message">You have been safely logged out from the Admin Panel. Thank you for using our system!</p>
    
    <div class="logout-info">
      <p><strong>Session ended:</strong> <?php echo $logoutTime; ?></p>
      <p><strong>User:</strong> <?php echo htmlspecialchars($adminName); ?></p>
      <?php if ($adminEmail): ?>
      <p><strong>Email:</strong> <?php echo htmlspecialchars($adminEmail); ?></p>
      <?php endif; ?>
      <p>All your data has been saved and secured.</p>
    </div>

    <div class="button-group">
      <a href="login_admin.html" class="btn btn-primary">Login Again</a>
      <a href="index.html" class="btn btn-secondary">Go to Homepage</a>
    </div>

    <div class="countdown">
      Redirecting to login page in <span id="countdown">10</span> seconds...
    </div>
  </div>

  <script>
    // Countdown timer
    let seconds = 10;
    const countdownElement = document.getElementById('countdown');

    function updateCountdown() {
      seconds--;
      countdownElement.textContent = seconds;

      if (seconds <= 0) {
        window.location.href = 'login_admin.html';
      }
    }

    const countdownInterval = setInterval(updateCountdown, 1000);

    // Clear countdown if user clicks login button
    document.querySelector('.btn-primary').addEventListener('click', () => {
      clearInterval(countdownInterval);
    });
  </script>
</body>
</html>