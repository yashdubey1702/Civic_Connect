const userMap = L.map('userMap').setView([20.2961, 85.8245], 12);

// CartoDB tiles
L.tileLayer(
    'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
    {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }
).addTo(userMap);

// Modal + State

const reportModal = document.getElementById("reportModal");
const reportForm  = document.getElementById("reportForm");

let clickedLatLng;
let userMarkers = [];
let bmcLayer;

//Load Bhubaneswar (BMC) Boundary
  
fetch('./data/bmc_boundary.geojson')
    .then(res => {
        if (!res.ok) throw new Error('GeoJSON not found');
        return res.json();
    })
    .then(data => {
        bmcLayer = L.geoJSON(data, {
            style: {
            color: "#ff7800",
            weight: 3,
            fillOpacity: 0,
            fillColor: "transparent",
            className: 'bmc-boundary'
      }
        }).addTo(userMap);

        bmcLayer.bindTooltip(
            "Bhubaneswar Municipal Corporation Boundary",
            {
                permanent: false, 
                direction: 'center',
                className: 'boundary-tooltip'
            }
        );

        userMap.fitBounds(bmcLayer.getBounds());
        loadUserReports();
    })
    .catch(err => {
        console.error('BMC boundary load failed:', err);
        loadUserReports(); // fail-safe
    });

//Boundary Validation

function isWithinBhubaneswar(lat, lng) {
    if (!bmcLayer) return true; // fail-safe
    const point = L.latLng(lat, lng);
    const results = leafletPip.pointInLayer(point, bmcLayer, true);
    return results.length > 0;
}

// Marker icons
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
// Load user's reports on the map
function loadUserReports() {
    fetch('reports/get_user_reports.php')
        .then(response => response.json())
        .then(data => {
             if (!data.success) return;

              const reports = data.reports;

           userMarkers.forEach(marker => userMap.removeLayer(marker));
           userMarkers = [];

            reports.forEach(report => {
                const icon = statusIcons[report.status] || statusIcons['Reported'];
                let marker = L.marker([report.latitude, report.longitude], { icon }).addTo(userMap);
                
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
                userMarkers.push(marker);
            });

            if (reports.length > 0) {
                const group = new L.featureGroup(userMarkers);
                userMap.fitBounds(group.getBounds().pad(0.1));
            }
        })
        .catch(error => console.error('Error loading user reports:', error));
}


//Map Click → Open Report Modal
   
userMap.on('click', function (e) {
    const { lat, lng } = e.latlng;

    if (!isWithinBhubaneswar(lat, lng)) {
        showNotification(
            'Please select a location within Bhubaneswar city.',
            'warning'
        );
        return;
    }

    clickedLatLng = e.latlng;
    document.getElementById("lat").value = lat;
    document.getElementById("lng").value = lng;
    reportModal.style.display = "block";
});

//Submit Report
 
reportForm.addEventListener('submit', function (e) {
    e.preventDefault();

    const lat = parseFloat(document.getElementById("lat").value);
    const lng = parseFloat(document.getElementById("lng").value);

    if (!isWithinBhubaneswar(lat, lng)) {
        showNotification(
            'Reports must be within Bhubaneswar city.',
            'error'
        );
        return;
    }

    const formData = new FormData(reportForm);
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
        credentials: 'include'

    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('Report submitted successfully!', 'success');
                reportModal.style.display = "none";
                reportForm.reset();
                 closeModal();
                loadUserReports();
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(() =>
            showNotification('Submission failed.', 'error')
        );
});

//Helpers
 
function getStatusColor(status) {
    const colors = {
        'Reported': '#c62828',
        'Acknowledged': '#f57c00',
        'In Progress': '#0277bd',
        'Resolved': '#2e7d32'
    };
    return colors[status] || '#212529';
}

function showNotification(message, type = 'success') {
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

// Load user's reports when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadUserReports();
    
    // Sidebar toggle functionality
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.user-sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
});

// Add notification styles
const style = document.createElement('style');
style.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease-out;
    }
    
    .notification.success {
        background: var(--success);
    }
    
    .notification.error {
        background: var(--danger);
    }
    
    .notification.warning {
        background: var(--warning);
        color: var(--dark);
    }
    
    .notification.fade-out {
        animation: slideOut 0.3s ease-in forwards;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;

document.head.appendChild(style);
document.addEventListener('DOMContentLoaded', loadUserReports);
