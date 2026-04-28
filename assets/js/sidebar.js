// assets/js/sidebar.js

function initMobileSidebar() {
    if (document.body.dataset.mobileSidebarInitialized === 'true') {
        return;
    }

    const hamburgerBtn = document.querySelector('.sidebar-toggle, .hamburger-btn');
    const sidebar = document.querySelector('.user-sidebar');
    const mainContent = document.querySelector('.user-main');
    
    // Create overlay if it doesn't exist
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }
    
    if (hamburgerBtn && sidebar) {
        document.body.dataset.mobileSidebarInitialized = 'true';

        // Toggle sidebar function
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            
            const isOpen = sidebar.classList.contains('active');

            if (mainContent) {
                mainContent.classList.toggle('sidebar-open', isOpen);
            }

            document.body.classList.toggle('sidebar-open', isOpen);
            overlay.classList.toggle('active', isOpen);
            hamburgerBtn.setAttribute('aria-expanded', String(isOpen));
        }
        
        // Add click event to hamburger button
        hamburgerBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            e.stopImmediatePropagation();
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
