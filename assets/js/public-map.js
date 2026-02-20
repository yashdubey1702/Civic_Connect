console.log("Map JS loaded");

document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM ready");

    const mapContainer = document.getElementById('map');

    if (!mapContainer) {
        console.error("❌ #map container not found");
        return;
    }

    //Prevent Leaflet double-initialization
    if (mapContainer._leaflet_id) {
        mapContainer._leaflet_id = null;
    }

    //Initialize map (Bhubaneswar)
    const map = L.map('map').setView([20.2961, 85.8245], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);



// Status icons for markers
const statusIcons = {
    'Reported': L.divIcon({
        className: 'custom-marker',
        html: '<div style="width: 16px; height: 16px; background-color: #c62828; border: 2px solid white; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
        iconSize: [16, 16],
        iconAnchor: [8, 8]
    }),
    'Acknowledged': L.divIcon({
        className: 'custom-marker',
        html: '<div style="width: 16px; height: 16px; background-color: #f57c00; border: 2px solid white; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
        iconSize: [16, 16],
        iconAnchor: [8, 8]
    }),
    'In Progress': L.divIcon({
        className: 'custom-marker',
        html: '<div style="width: 16px; height: 16px; background-color: #0277bd; border: 2px solid white; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
        iconSize: [16, 16],
        iconAnchor: [8, 8]
    }),
    'Resolved': L.divIcon({
        className: 'custom-marker',
        html: '<div style="width: 16px; height: 16px; background-color: #2e7d32; border: 2px solid white; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
        iconSize: [16, 16],
        iconAnchor: [8, 8]
    })
};

// Get status color for styling
function getStatusColor(status) {
    const colors = {
        'Reported': '#c62828',
        'Acknowledged': '#f57c00',
        'In Progress': '#0277bd',
        'Resolved': '#2e7d32'
    };
    return colors[status] || '#c62828';
}

// Get modal elements
const modal = document.getElementById("reportModal");
const span = document.getElementsByClassName("close")[0];
const form = document.getElementById("reportForm");
const loginPrompt = document.getElementById("loginPrompt");

// Initially hide both forms
loginPrompt.style.display = 'block';
form.style.display = 'none';


// Close modal function
function closeModal() {
    modal.style.display = "none";
    form.reset();
}

// Close modal when clicking on X
span.onclick = closeModal;

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target == modal) {
        closeModal();
    }
}

   // Load BMC Boundary
    loadBhubaneswarBoundary(map)
        .then(layer => {
            if (layer) {
                console.log("✅ BMC boundary loaded");
            } else {
                console.warn("⚠️ BMC boundary NOT loaded — fallback active");
            }
        })
        .catch(err => {
            console.error("❌ Boundary load error:", err);
        });

    //Fix blank-map resize bug

    setTimeout(() => {
        map.invalidateSize(true);
    }, 150);

    console.log("Map initialized");
});

// Show map help
function showMapHelp() {
    alert("Community Issues Map Help:\n\n• Click anywhere on the map to report a new issue\n• Use the filters to view specific types of reports\n• Click on markers to see report details\n• Different colors represent different statuses\n\nYou need to be logged in to submit reports.");
}


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

    // Check if user is logged in
    const isLoggedIn = localStorage.getItem('isLoggedIn') || false;
    
    if (isLoggedIn) {
        // User is logged in - show report form
        loginPrompt.style.display = 'none';
        form.style.display = 'block';
    } else {
        // User is not logged in - show login prompt
        loginPrompt.style.display = 'block';
        form.style.display = 'none';
    }
    
    modal.style.display = "block";
    });


// Form submission

form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const lat = parseFloat(document.getElementById("lat").value);
    const lng = parseFloat(document.getElementById("lng").value);

    if (!isWithinBhubaneswar(lat, lng, bmcLayer)) {
        alert("❌ Reports can only be submitted from within Biliran Province.");
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
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("✅ Report submitted successfully!");
            closeModal();
            loadExistingReports();
        } else {
            alert("❌ Error: " + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("❌ An error occurred while submitting the report.");
    });
});

// Load reports

function loadExistingReports() {
    const selectedCategory = getSingleSelectedCheckbox('category');
    const selectedStatus = getSingleSelectedCheckbox('status');
    let url = 'reports/get_map_reports.php?';
    
    // Handle category - only if not "all"
    if (selectedCategory && selectedCategory !== 'all') {
        url += 'category=' + encodeURIComponent(selectedCategory);
    }
    
    // Handle status - only if not "all"
    if (selectedStatus && selectedStatus !== 'all') {
        if (url.includes('?')) {
            url += '&status=' + encodeURIComponent(selectedStatus);
        } else {
            url += 'status=' + encodeURIComponent(selectedStatus);
        }
    }

    // If no specific filters are selected, show ALL reports
    if (url === 'reports/get_map_reports.php?') {
        url = 'reports/get_map_reports.php';
    }

    fetch(url)
        .then(response => response.json())
        .then(reports => {
            allMarkers.forEach(marker => map.removeLayer(marker));
            allMarkers = [];
            
            // Update stats
            document.getElementById('totalReports').textContent = reports.length;
            document.getElementById('resolvedReports').textContent = reports.filter(r => r.status === 'Resolved').length;
            
            reports.forEach(report => {
                const icon = statusIcons[report.status] || statusIcons['Reported'];
                let marker = L.marker([report.latitude, report.longitude], { icon }).addTo(map);
                marker.reportData = report;
                
                let popupContent = `
                    <div style="min-width: 200px;">
                        <h3 style="margin: 0 0 10px 0; color: #0d47a1; border-bottom: 2px solid #f0f0f0; padding-bottom: 5px;">
                            ${report.category}
                        </h3>
                        <p style="margin: 5px 0;"><strong>Status:</strong> <span style="color: ${getStatusColor(report.status)}; font-weight: bold;">${report.status}</span></p>
                        <p style="margin: 5px 0;"><strong>Reported:</strong> ${new Date(report.created_at).toLocaleDateString()}</p>
                `;
                
                if (report.description) {
                    popupContent += `<p style="margin: 10px 0 0 0; padding-top: 10px; border-top: 1px solid #f0f0f0;"><em>${report.description}</em></p>`;
                }
                
                popupContent += `</div>`;
                marker.bindPopup(popupContent);
                allMarkers.push(marker);
            });
        })
        .catch(error => console.error('Error loading reports:', error));
}

// Helper function to get single selected checkbox value
function getSingleSelectedCheckbox(name) {
    const checkboxes = document.querySelectorAll(`input[name="${name}"]:checked`);
    if (checkboxes.length === 0) return null;
    return checkboxes[0].value;
}

// Function to handle checkbox clicks
function handleCheckboxSelection(clickedCheckbox, groupName) {
    if (clickedCheckbox.checked) {
        // Uncheck all other checkboxes in the same group
        document.querySelectorAll(`input[name="${groupName}"]`).forEach(checkbox => {
            if (checkbox !== clickedCheckbox) {
                checkbox.checked = false;
            }
        });
    } else {
        // If unchecking the last checkbox, re-check "All"
        const checkedCheckboxes = document.querySelectorAll(`input[name="${groupName}"]:checked`);
        if (checkedCheckboxes.length === 0) {
            document.querySelector(`input[name="${groupName}"][value="all"]`).checked = true;
        }
    }
    applyFilters();
}

// Clear all filters
function clearFilters() {
    // Check "All" for both category and status
    document.querySelectorAll('input[name="category"][value="all"]').forEach(cb => cb.checked = true);
    document.querySelectorAll('input[name="status"][value="all"]').forEach(cb => cb.checked = true);
    
    // Uncheck all others
    document.querySelectorAll('input[name="category"]:not([value="all"])').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[name="status"]:not([value="all"])').forEach(cb => cb.checked = false);
    
    loadExistingReports();
}

function applyFilters() {
    loadExistingReports();
}

// Add event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for category checkboxes
    document.querySelectorAll('input[name="category"]').forEach(checkbox => {
        checkbox.addEventListener('click', function() {
            handleCheckboxSelection(this, 'category');
        });
    });
    
    // Add click handlers for status checkboxes
    document.querySelectorAll('input[name="status"]').forEach(checkbox => {
        checkbox.addEventListener('click', function() {
            handleCheckboxSelection(this, 'status');
        });
    });
    
    // Load all reports initially
    loadExistingReports();
    
    // Set active users (random number for demo)
    document.getElementById('activeUsers').textContent = Math.floor(Math.random() * 500) + 100;
});