// Profile page functionality

function togglePasswordVisibility(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const eyeIcon = passwordInput.parentNode.querySelector('.toggle-password i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}

function enableEditMode() {
    // Redirect to edit mode
    window.location.href = 'profile.php?edit=true';
}

function disableEditMode() {
    // Redirect to view mode
    window.location.href = 'profile.php';
}

function showPasswordForm() {
    window.location.href = 'profile.php?action=change-password';
}

function showProfileForm() {
    window.location.href = 'profile.php';
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    console.log("Profile page loaded");
    
    // Check if Edit Profile button exists and add event listener
    const editProfileBtn = document.querySelector('.edit-profile-btn');
    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', function() {
            console.log("Edit Profile button clicked");
            enableEditMode();
        });
        
        // Make sure the button is clickable
        editProfileBtn.style.cursor = 'pointer';
        editProfileBtn.disabled = false;
    }
    
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value;
            const email = document.getElementById('email').value;
            
            if (!fullName.trim()) {
                e.preventDefault();
                showNotification('Full name is required', 'error');
                return;
            }
            
            if (!email.trim()) {
                e.preventDefault();
                showNotification('Email is required', 'error');
                return;
            }
            
            if (!validateEmail(email)) {
                e.preventDefault();
                showNotification('Please enter a valid email address', 'error');
                return;
            }
        });
    }
    
    // Password form validation
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (!currentPassword || !newPassword || !confirmPassword) {
                e.preventDefault();
                showNotification('Please fill in all password fields', 'error');
                return;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                showNotification('New password must be at least 6 characters long', 'error');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                showNotification('New passwords do not match', 'error');
                return;
            }
        });
    }
    
    // Initialize theme toggle if it exists
    if (typeof initThemeToggle === 'function') {
        initThemeToggle();
    }
    
    // Debug: Log all buttons and their event listeners
    const allButtons = document.querySelectorAll('button');
    console.log("Found buttons:", allButtons.length);
    allButtons.forEach((btn, index) => {
        console.log(`Button ${index}:`, btn.textContent, btn.className);
    });
});

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Notification function
function showNotification(message, type = 'success') {
    // Remove any existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}