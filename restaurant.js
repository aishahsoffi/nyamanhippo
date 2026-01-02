// Check if user is logged in by checking PHP session
function isLoggedIn() {
    // We'll check this via an API call to PHP
    return checkSessionStatus();
}

// Check session status from PHP
async function checkSessionStatus() {
    try {
        const response = await fetch('check_session.php');
        const data = await response.json();
        return data.loggedIn;
    } catch (error) {
        console.error('Error checking session:', error);
        return false;
    }
}

// Show custom login modal
function showLoginModal() {
    const modalHTML = `
        <div id="loginModal" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        ">
            <div style="
                background: white;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
                text-align: center;
                max-width: 400px;
                animation: slideIn 0.3s ease-out;
            ">
                <div style="
                    width: 60px;
                    height: 60px;
                    background: #ffe5f0;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                ">
                    <i class="fa fa-user" style="font-size: 28px; color: #d70f64;"></i>
                </div>
                <h2 style="
                    color: #2e2e2e;
                    font-size: 24px;
                    margin-bottom: 10px;
                    font-weight: 700;
                ">Login Required</h2>
                <p style="
                    color: #666;
                    font-size: 15px;
                    margin-bottom: 30px;
                    line-height: 1.5;
                ">Please login or create an account to add items to your cart</p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button onclick="closeLoginModal()" style="
                        background: #f0f0f0;
                        color: #666;
                        border: none;
                        padding: 12px 28px;
                        border-radius: 8px;
                        font-size: 15px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.2s;
                    " onmouseover="this.style.background='#e0e0e0'" onmouseout="this.style.background='#f0f0f0'">Cancel</button>
                    <button onclick="goToLogin()" style="
                        background: #d70f64;
                        color: white;
                        border: none;
                        padding: 12px 28px;
                        border-radius: 8px;
                        font-size: 15px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.2s;
                        box-shadow: 0 4px 12px rgba(215, 15, 100, 0.3);
                    " onmouseover="this.style.background='#b00c50'" onmouseout="this.style.background='#d70f64'">Login</button>
                </div>
            </div>
        </div>
        <style>
            @keyframes slideIn {
                from {
                    transform: translateY(-20px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
        </style>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Close login modal
function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) {
        modal.remove();
    }
}

// Go to login page
function goToLogin() {
    window.location.href = 'login.php';
}

// Show success notification
function showSuccessNotification(message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: #4CAF50;
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        animation: slideInRight 0.3s ease-out;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    `;
    notification.innerHTML = `
        <i class="fa fa-check-circle" style="font-size: 20px;"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 2000);
}

// Show error notification
function showErrorNotification(message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: #f44336;
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        animation: slideInRight 0.3s ease-out;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    `;
    notification.innerHTML = `
        <i class="fa fa-exclamation-circle" style="font-size: 20px;"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    .cart-icon {
        transition: transform 0.2s ease;
    }
`;
document.head.appendChild(style);

// Add to cart function with database integration
async function addToCart(itemName, price, itemId = null) {
    // Check if user is logged in
    const loggedIn = await checkSessionStatus();
    
    if (!loggedIn) {
        showLoginModal();
        return;
    }
    
    // If itemId is not provided, try to find it from the button's data attribute
    if (!itemId) {
        console.error('Item ID is required');
        showErrorNotification('Unable to add item to cart');
        return;
    }
    
    // Get the button that was clicked for visual feedback
    const clickedButton = event ? event.target : null;
    const originalButtonContent = clickedButton ? clickedButton.innerHTML : null;
    
    // Show loading state on button
    if (clickedButton) {
        clickedButton.disabled = true;
        clickedButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
        clickedButton.style.pointerEvents = 'none';
    }
    
    try {
        // Add item to database via PHP
        const formData = new FormData();
        formData.append('item_id', itemId);
        formData.append('quantity', 1);
        
        const response = await fetch('add_to_cart.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update cart count
            await updateCartCount();
            
            // Visual feedback on button
            if (clickedButton) {
                clickedButton.innerHTML = '<i class="fa fa-check"></i>';
                clickedButton.style.background = '#4CAF50';
                
                // Reset button after delay
                setTimeout(() => {
                    clickedButton.innerHTML = originalButtonContent;
                    clickedButton.style.background = '';
                    clickedButton.disabled = false;
                    clickedButton.style.pointerEvents = '';
                }, 1500);
            }
            
            // Visual feedback - flash cart icon
            const cartIcon = document.querySelector('.cart-icon');
            if (cartIcon) {
                cartIcon.style.transform = "scale(1.3)";
                setTimeout(() => {
                    cartIcon.style.transform = "scale(1)";
                }, 300);
            }
            
            // Show success notification
            showSuccessNotification(`${itemName} added to cart!`);
        } else {
            // Reset button on error
            if (clickedButton) {
                clickedButton.innerHTML = originalButtonContent;
                clickedButton.disabled = false;
                clickedButton.style.pointerEvents = '';
            }
            showErrorNotification(data.message || 'Failed to add item to cart');
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        
        // Reset button on error
        if (clickedButton) {
            clickedButton.innerHTML = originalButtonContent;
            clickedButton.disabled = false;
            clickedButton.style.pointerEvents = '';
        }
        
        showErrorNotification('An error occurred. Please try again.');
    }
}

// Update cart count from database
async function updateCartCount() {
    try {
        const response = await fetch('get_cart_count.php');
        const data = await response.json();
        
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
            const newCount = data.count || 0;
            
            // Animate count change
            cartCountElement.style.transform = 'scale(1.4)';
            cartCountElement.textContent = newCount;
            
            setTimeout(() => {
                cartCountElement.style.transform = 'scale(1)';
            }, 300);
        }
    } catch (error) {
        console.error('Error updating cart count:', error);
    }
}

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', async function() {
    const loggedIn = await checkSessionStatus();
    
    if (loggedIn) {
        updateCartCount();
    }
});