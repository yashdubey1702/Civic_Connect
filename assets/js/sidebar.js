// assets/js/sidebar.js

function initMobileSidebar() {
    const hamburgerBtn = document.querySelector('.hamburger-btn');
    const sidebar = document.querySelector('.user-sidebar.sidebar');
    const mainContent = document.querySelector('.user-main');
    
    // Create overlay if it doesn't exist
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }
    
    if (hamburgerBtn && sidebar) {
        // Toggle sidebar function
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            
            if (mainContent) {
                mainContent.classList.toggle('sidebar-open');
            }
            
            // Prevent body scroll when sidebar is open
            document.body.classList.toggle('sidebar-open');
            // Toggle overlay visibility
            if (overlay) {
                overlay.classList.toggle('active');
            }
        }
        
        // Add click event to hamburger button
        hamburgerBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
        
        // Close sidebar when clicking overlay
        overlay.addEventListener('click', function() {
            if (sidebar.classList.contains('active')) {
                toggleSidebar();
            }
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (sidebar.classList.contains('active') && 
                !sidebar.contains(e.target) && 
                e.target !== hamburgerBtn) {
                toggleSidebar();
            }
        });
        
        // Close sidebar when pressing Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                toggleSidebar();
            }
        });
    }
}

// Initialize on all pages
document.addEventListener('DOMContentLoaded', initMobileSidebar);
window.addEventListener('load', initMobileSidebar);