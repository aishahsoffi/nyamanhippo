// Load admin info on page load
document.addEventListener('DOMContentLoaded', function() {
    checkAuthentication();
});

// Check if admin is authenticated
async function checkAuthentication() {
    try {
        const response = await fetch('check_session.php');
        const data = await response.json();
        
        if (!data.success) {
            window.location.href = 'login_admin.html';
            return;
        }
        
        // Load admin information
        loadAdminInfo(data.admin);
    } catch (error) {
        console.error('Authentication check failed:', error);
        window.location.href = 'login_admin.html';
    }
}

// Load admin information into form
function loadAdminInfo(admin) {
    // Display current admin info
    document.getElementById('currentAdminName').textContent = admin.name;
    document.getElementById('currentAdminEmail').textContent = admin.email;
    
    // Pre-fill form fields
    document.getElementById('adminName').value = admin.name;
    document.getElementById('adminEmail').value = admin.email;
}

// Save all settings
async function saveAllSettings() {
    const name = document.getElementById('adminName').value.trim();
    const email = document.getElementById('adminEmail').value.trim();
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    // Validate email
    if (!validateEmail(email)) {
        showMessage('Please enter a valid email address', 'error');
        return;
    }
    
    // Check if password is being changed
    const isChangingPassword = currentPassword || newPassword || confirmPassword;
    
    if (isChangingPassword) {
        // Validate password change
        if (!currentPassword) {
            showMessage('Please enter your current password', 'error');
            return;
        }
        
        if (!newPassword || newPassword.length < 6) {
            showMessage('New password must be at least 6 characters', 'error');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            showMessage('New passwords do not match', 'error');
            return;
        }
    }
    
    // Prepare data to send
    const formData = new FormData();
    formData.append('action', 'updateSettings');
    formData.append('name', name);
    formData.append('email', email);
    
    if (isChangingPassword) {
        formData.append('currentPassword', currentPassword);
        formData.append('newPassword', newPassword);
    }
    
    // Get notification preferences
    formData.append('emailOrders', document.getElementById('emailOrders').checked ? 1 : 0);
    formData.append('smsUrgent', document.getElementById('smsUrgent').checked ? 1 : 0);
    formData.append('weeklyReport', document.getElementById('weeklyReport').checked ? 1 : 0);
    formData.append('lowStock', document.getElementById('lowStock').checked ? 1 : 0);
    
    try {
        const response = await fetch('settings_admin.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message || 'Settings saved successfully!', 'success');
            
            // Clear password fields
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            
            // Update displayed info
            document.getElementById('currentAdminName').textContent = name;
            document.getElementById('currentAdminEmail').textContent = email;
            
            // Reload page after 2 seconds if password was changed
            if (isChangingPassword) {
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } else {
            showMessage(data.message || 'Failed to save settings', 'error');
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        showMessage('An error occurred while saving settings', 'error');
    }
}

// Cancel changes
function cancelChanges() {
    if (confirm('Are you sure you want to discard changes?')) {
        window.location.reload();
    }
}

// Navigation function
function navigateTo(page) {
    if (page === 'logout_admin') {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout_admin.php';
        }
        return;
    }
    
    if (page === 'settings_admin') {
        window.location.reload();
        return;
    }
    
    window.location.href = page + '.html';
}

// Show message to user
function showMessage(message, type) {
    const messageBox = document.getElementById('messageBox');
    messageBox.textContent = message;
    messageBox.className = type === 'success' ? 'message-success' : 'message-error';
    messageBox.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageBox.style.display = 'none';
    }, 5000);
}

// Validate email format
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}