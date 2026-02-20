console.log('[UserDashboardJS] Loaded (Bhubaneswar)');

//MAP INITIALIZATION (Only if map exists)
 
function initUserMap() {
    const mapContainer = document.getElementById('userMap');
    if (!mapContainer || typeof L === 'undefined') {
        console.log('User map not present. Skipping map init.');
        return;
    }

    // Prevent double init
    if (mapContainer._leaflet_id) return;

    const userMap = L.map('userMap').setView([20.2961, 85.8245], 12);

   // Use CartoDB tiles instead of OpenStreetMap
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(userMap);

    // Get modal elements
    const reportModal = document.getElementById("reportModal");
    const updateModal = document.getElementById("updateModal");
    const imageModal = document.getElementById("imageModal");
    // const reportForm = document.getElementById("reportForm");
    const updateForm = document.getElementById("updateForm");

    let bmcLayer = null;
    let userMarkers = [];

    // Load Bhubaneswar boundary (COMMON UTILITY)
    loadBhubaneswarBoundary(userMap).then(layer => {
        bmcLayer = layer;
        loadUserReports();
    });

// Load User Reports (FIXED)

function loadUserReports() {
    fetch('reports/get_user_reports.php')
        .then(res => res.json())
        .then(reports => {

            // Clear existing markers safely
            userMarkers.forEach(m => {
                if (userMap.hasLayer(m)) {
                    userMap.removeLayer(m);
                }
            });
            userMarkers = [];

            if (!Array.isArray(reports) || reports.length === 0) {
                return; // nothing to plot
            }

            reports.forEach(r => {

                // Defensive coordinate check
                if (!r.latitude || !r.longitude) return;
                const icon =
                    statusIcons[r.status] ||
                    statusIcons['Reported'];

                const marker = L.marker(
                    [r.latitude, r.longitude],
                    { icon }
                ).addTo(userMap);

                marker.bindPopup(`
                    <div style="min-width:200px">
                        <h4 style="margin-bottom:6px">${r.category}</h4>

                        <p style="margin:4px 0">
                            <strong>Status:</strong>
                            <span style="color:${getStatusColor(r.status)}; font-weight:600">
                                ${r.status}
                            </span>
                        </p>

                        <p style="margin:4px 0">
                            <strong>Reported:</strong>
                            ${new Date(r.created_at).toLocaleDateString()}
                        </p>

                        ${r.description
                            ? `<p style="margin-top:8px"><em>${r.description}</em></p>`
                            : ''
                        }
                    </div>
                `);

                userMarkers.push(marker);
            });

            if (userMarkers.length > 1) {
                userMap.fitBounds(
                    L.featureGroup(userMarkers)
                        .getBounds()
                        .pad(0.15)
                );
            }

        })
        .catch(err => {
            console.error('User reports load error:', err);
        });
}



//Click to Report (Boundary check)

    userMap.on('click', function (e) {
        if (!isWithinBhubaneswar(e.latlng.lat, e.latlng.lng, bmcLayer)) {
            showNotification(
                'Please select a location within Bhubaneswar city.',
                'warning'
            );
            return;
        }

        document.getElementById('lat').value = e.latlng.lat;
        document.getElementById('lng').value = e.latlng.lng;

        document.getElementById('reportModal').style.display = 'block';
    });

      // Click on user map to report (with boundary validation)
    userMap.on('click', function(e) {
        const latlng = e.latlng;
        
        if (!isWithinBhubaneswar(lat, lng, bmcLayer)) {
                showNotification(
                    'Reports must be inside Bhubaneswar city.',
                    'error'
                );
                return;
            }
        
        clickedLatLng = latlng;
        document.getElementById("lat").value = clickedLatLng.lat;
        document.getElementById("lng").value = clickedLatLng.lng;
        openReportModal();
    });

//Report Submission
     
    // Form submission with boundary validation
    reportForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const lat = parseFloat(document.getElementById("lat").value);
        const lng = parseFloat(document.getElementById("lng").value);

        if (!isWithinBiliran(lat, lng)) {
            showNotification('Reports can only be submitted from within Biliran Province.', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('lat', lat);
        formData.append('lng', lng);
        formData.append('category', document.getElementById("category").value);
        formData.append('description', document.getElementById("description").value);
        formData.append('email', document.getElementById("email").value);
        
        const imageInput = document.getElementById("image");
        if (imageInput.files[0]) {
            formData.append('image', imageInput.files[0]);
        }

        fetch('reports/submit_report.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Report submitted successfully!', 'success');
                closeModal();
                loadUserReports();
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while submitting the report.', 'error');
        });
    });
}

//SIDEBAR FUNCTIONS

function initSidebar() {
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
            overlay.classList.toggle('active');
            
            if (mainContent) {
                mainContent.classList.toggle('sidebar-open');
            }
            
            // Prevent body scroll when sidebar is open
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
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
        
        console.log('Mobile sidebar initialized successfully');
    } else {
        console.log('Sidebar elements not found');
    }
}

//UTILITY FUNCTIONS
function getStatusColor(status) {
    const colors = {
        'Reported': '#c62828',
        'Acknowledged': '#f57c00',
        'In Progress': '#0277bd',
        'Resolved': '#2e7d32'
    };
    return colors[status] || '#212529';
}

//INITIALIZE EVERYTHING
document.addEventListener('DOMContentLoaded', function() {
    initMap();      // Initialize map (only if container exists)
    initSidebar();  // Initialize sidebar (always try to initialize)
    
    // Other initialization code that should always run...
    const statusFilter = document.getElementById('reportStatusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', filterReports);
    }
    
    // Desktop sidebar toggle (if needed)
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.user-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
});

// Marker icons (should be available for both map and other pages)
const statusIcons = {
    'Reported': L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    }),
    'Acknowledged': L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    }),
    'In Progress': L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    }),
    'Resolved': L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    })
};


// Filter reports by status
function filterReports() {
    const statusFilter = document.getElementById('reportStatusFilter').value;
    const rows = document.querySelectorAll('.report-row');
    
    rows.forEach(row => {
        if (statusFilter === 'all' || row.getAttribute('data-status') === statusFilter.toLowerCase()) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

//NOTIFICATIONS
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

//INIT

document.addEventListener('DOMContentLoaded', initUserMap);


