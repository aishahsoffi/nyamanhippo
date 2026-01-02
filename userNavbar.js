// ===============================
// SHARED NAVBAR LOGIC
// ===============================

// Load username + profile picture
function loadNavbarUser() {
    const nameSpan = document.getElementById('navUserName');
    const avatarIcon = document.getElementById('navUserAvatar');

    // Try userProfile first (most accurate)
    const userProfile = JSON.parse(localStorage.getItem('userProfile'));

    if (userProfile) {
        if (nameSpan && userProfile.firstName) {
            nameSpan.textContent = userProfile.firstName;
        }

        if (avatarIcon && userProfile.profilePicture) {
            avatarIcon.innerHTML = `
                <img src="${userProfile.profilePicture}" 
                     style="width:28px;height:28px;border-radius:50%;object-fit:cover;">
            `;
        }
        return;
    }

    // Fallback: session user
    const currentUser = JSON.parse(sessionStorage.getItem('currentUser'));
    if (currentUser && nameSpan) {
        nameSpan.textContent =
            currentUser.firstName ||
            currentUser.fullName ||
            currentUser.username ||
            'User';
    }
}

// Dropdown toggle
function initUserDropdown() {
    const btn = document.getElementById('userDropdownBtn');
    const menu = document.getElementById('userDropdownMenu');

    if (!btn || !menu) return;

    btn.addEventListener('click', () => {
        menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
    });

    document.addEventListener('click', (e) => {
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
            menu.style.display = 'none';
        }
    });
}


// Logout
function initLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (!logoutBtn) return;

    logoutBtn.addEventListener('click', () => {
        if (confirm('Logout now?')) {
            sessionStorage.clear();
            window.location.href = 'index.html';
        }
    });
}

// Init navbar
document.addEventListener('DOMContentLoaded', () => {
    loadNavbarUser();
    initUserDropdown();
    initLogout();
});


