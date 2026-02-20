console.log('[MunicipalDashboardJS] Bhubaneswar loaded');

let map;
let reports = [];
let currentPage = 1;
const perPage = 10;
let mapMarkers = [];
let bmcLayer;

//INIT
  
document.addEventListener('DOMContentLoaded', function () {
    initializeMap();
    loadStats();
    loadReports(1);
    loadMapReports();
});

// Utility: escape HTML to avoid injection
function escapeHTML(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

//MAP INITIALIZATION (Bhubaneswar Admin)
  
function initializeMap() {

    // Destroy old map safely
    if (map && map.remove) {
        try { map.remove(); } catch (e) {}
    }

    const container = document.getElementById('municipalMap');
    if (container && container._leaflet_id) {
        container._leaflet_id = null;
    }

    map = L.map('municipalMap', {
        center: [20.2961, 85.8245], // Bhubaneswar city center
        zoom: 12,
        zoomControl: true
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18
    }).addTo(map);

    loadMapReports();

    fetch('./data/bmc_boundary.geojson')
        .then(res => res.json())
        .then(data => {

            // Remove old layer if exists
            if (typeof bmcLayer !== 'undefined' && bmcLayer) {
                map.removeLayer(bmcLayer);
            }

            bmcLayer = L.geoJSON(data, {
                style: {
                    color: '#ff7800',
                    weight: 3,
                    fillOpacity: 0,
                    fillColor: 'transparent',
                    className: 'bmc-boundary'
                }
            }).addTo(map);

            bmcLayer.bindTooltip(
                "Bhubaneswar Municipal Corporation Boundary",
                {
                    direction: 'center',
                    className: 'boundary-tooltip'
                }
            );

            // Fit map AFTER boundary loads
            map.fitBounds(bmcLayer.getBounds());
        })
        .catch(err => {
            console.warn('BMC boundary not loaded (map still functional)', err);
        });
}



// Load statistics
function loadStats() {
    fetch('reports/get_stats.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('totalReports').textContent = data.total || 0;
            document.getElementById('reportedCount').textContent = data.reported || 0;
            document.getElementById('acknowledgedCount').textContent = data.acknowledged || 0;
            document.getElementById('inProgressCount').textContent = data.in_progress || 0;
            document.getElementById('resolvedCount').textContent = data.resolved || 0;
        })
        .catch(error => {
            console.error('Error loading stats:', error);
        });
}

// Load reports for the table
function loadReports(page = 1) {
    currentPage = page;
    
    const statusFilter = document.getElementById('statusFilter').value;
    const categoryFilter = document.getElementById('categoryFilter').value;
    const searchInput = document.getElementById('searchInput').value;
    
    const params = new URLSearchParams({
        page: page,
        per_page: perPage,
        status: statusFilter,
        category: categoryFilter,
        search: searchInput
    });
    
    console.log('Loading reports with params:', params.toString());
    
    fetch(`reports/get_reports.php?${params}`)
        .then(response => response.json())
        .then(data => {
            console.log('Reports API response:', data);
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            reports = data.reports || [];
            console.log('Reports loaded:', reports.length);
            displayReports(reports);
            displayPagination(data.pagination);
        })
        .catch(error => {
            console.error('Error loading reports:', error);
            document.getElementById('reportsTable').innerHTML = 
                '<div class="error-state"><p>Error loading reports: ' + error.message + '</p></div>';
        });
}

// Display reports in the table
function displayReports(reports) {
    const tableContainer = document.getElementById('reportsTable');
    
    if (reports.length === 0) {
        tableContainer.innerHTML = '<div class="empty-state"><p>No reports found for your municipality.</p></div>';
        return;
    }
    
    let html = `
        <table class="reports-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Email</th>
                    <th>Image</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    reports.forEach(report => {
        const statusClass = report.status.toLowerCase().replace(' ', '-');
        const date = new Date(report.created_at).toLocaleDateString();
        
        html += `
            <tr>
                <td>${report.id}</td>
                <td>${report.category}</td>
                <td>${report.description ? report.description : 'No description'}</td>
                <td>${report.email || 'N/A'}</td>
                <td>
                    ${report.image_filename ? 
                        `<img src="reports/uploads/${report.image_filename}" alt="Report Image" class="report-thumbnail" onclick="showImageModal('reports/uploads/${report.image_filename}')" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; cursor: pointer;">` : 
                        '<span class="no-image">No Image</span>'
                    }
                </td>
                <td><span class="status-badge status-${statusClass}">${report.status}</span></td>
                <td>${date}</td>
                <td>
                    <select class="status-select" onchange="updateStatus(${report.id}, this.value)">
                        <option value="Reported" ${report.status === 'Reported' ? 'selected' : ''}>Reported</option>
                        <option value="Acknowledged" ${report.status === 'Acknowledged' ? 'selected' : ''}>Acknowledged</option>
                        <option value="In Progress" ${report.status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                        <option value="Resolved" ${report.status === 'Resolved' ? 'selected' : ''}>Resolved</option>
                    </select>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    tableContainer.innerHTML = html;
}

// Display pagination
function displayPagination(pagination) {
    const paginationContainer = document.getElementById('pagination');
    
    if (pagination.total_pages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    let html = '<div class="pagination">';
    
    // Previous button
    if (pagination.current_page > 1) {
        html += `<button onclick="loadReports(${pagination.current_page - 1})" class="page-btn">Previous</button>`;
    }
    
    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.current_page) {
            html += `<button class="page-btn active">${i}</button>`;
        } else {
            html += `<button onclick="loadReports(${i})" class="page-btn">${i}</button>`;
        }
    }
    
    // Next button
    if (pagination.current_page < pagination.total_pages) {
        html += `<button onclick="loadReports(${pagination.current_page + 1})" class="page-btn">Next</button>`;
    }
    
    html += '</div>';
    paginationContainer.innerHTML = html;
}

//MAP REPORTS

function loadMapReports() {
    fetch('reports/get_map_reports.php')
        .then(r => r.json())
        .then(data => {
            mapMarkers.forEach(m => map.removeLayer(m));
            mapMarkers = [];

            data.forEach(r => {
                const marker = L.marker([r.latitude, r.longitude]).addTo(map);
                marker.bindPopup(`
                    <strong>${r.category}</strong><br>
                    Status: ${r.status}<br>
                    ${new Date(r.created_at).toLocaleDateString()}
                `);
                mapMarkers.push(marker);
            });
        })
        .catch(err => console.error('Map reports error:', err));
}

// Add marker to map
function addMapMarker(report) {
    const statusColors = {
        'Reported': '#c62828',
        'Acknowledged': '#f57c00',
        'In Progress': '#0277bd',
        'Resolved': '#2e7d32'
    };
    
    const color = statusColors[report.status] || '#c62828';
    
    // Create custom icon
    const customIcon = L.divIcon({
        className: 'custom-marker',
        html: `<div style="
            width: 16px;
            height: 16px;
            background-color: ${color};
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        "></div>`,
        iconSize: [16, 16],
        iconAnchor: [8, 8]
    });
    
    const marker = L.marker([report.latitude, report.longitude], {
        icon: customIcon
    }).addTo(map);
    
    // Create popup content
    let popupContent = `
        <div style="min-width: 200px; font-family: Inter, sans-serif;">
            <h4 style="margin: 0 0 8px 0; color: #1f2937; font-size: 14px;">${report.category}</h4>
            <p style="margin: 4px 0; font-size: 12px; color: #6b7280;">
                <strong>Status:</strong> <span style="color: ${color}; font-weight: 600;">${report.status}</span>
            </p>
            <p style="margin: 4px 0; font-size: 12px; color: #6b7280;">
                <strong>Description:</strong> ${report.description.substring(0, 100)}${report.description.length > 100 ? '...' : ''}
            </p>
            <p style="margin: 4px 0; font-size: 12px; color: #6b7280;">
                <strong>Date:</strong> ${new Date(report.created_at).toLocaleDateString()}
            </p>
    `;
    
    if (report.image_filename) {
        popupContent += `
            <div style="margin-top: 8px;">
                <img src="reports/uploads/${report.image_filename}" 
                     style="max-width: 180px; max-height: 120px; border-radius: 4px; object-fit: cover;">
            </div>
        `;
    }
    
    popupContent += `</div>`;
    
    marker.bindPopup(popupContent, {
        maxWidth: 250,
        className: 'custom-popup'
    });
}

// Update report status
function updateStatus(reportId, newStatus) {
    fetch('reports/update_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: reportId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload reports and stats
            loadReports(currentPage);
            loadStats();
            loadMapReports();
            
            // Show success message
            showNotification('Status updated successfully', 'success');
        } else {
            showNotification('Error updating status: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error updating status:', error);
        showNotification('Error updating status', 'error');
    });
}

// Apply filters
function applyFilters() {
    // Update both table and map when filters change
    loadReports(1);
    loadMapReports();
}

// Handle search
function handleSearch() {
    const searchInput = document.getElementById('searchInput');
    clearTimeout(searchInput.searchTimeout);
    
    searchInput.searchTimeout = setTimeout(() => {
        // Debounced search updates both table and map
        loadReports(1);
        loadMapReports();
    }, 500);
}

// Combined Refresh (matches provincial behavior)
function refreshAll() {
    loadReports(1);
    loadMapReports();
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Image Modal Functions
function showImageModal(imageSrc) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('imageModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'imageModal';
        modal.className = 'image-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <span class="close" onclick="closeImageModal()">&times;</span>
                <img id="modalImage" src="" alt="Report Image">
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Set image source and show modal
    document.getElementById('modalImage').src = imageSrc;
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scrolling
    }
}

// Close modal when clicking outside the image
document.addEventListener('click', function(event) {
    const modal = document.getElementById('imageModal');
    if (modal && event.target === modal) {
        closeImageModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});

//Description Modal (for long text)

function showDescriptionModal(text) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('descModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'descModal';
        modal.className = 'image-modal'; // reuse existing modal overlay styles
        modal.innerHTML = `
            <div class="modal-content">
                <span class="close" onclick="closeDescriptionModal()">&times;</span>
                <div id="descModalBody" style="padding: 20px; max-height: 80vh; overflow: auto; white-space: pre-wrap; line-height: 1.5; font-size: 14px;"></div>
            </div>
        `;
        document.body.appendChild(modal);
        modal.addEventListener('click', function(ev) {
            if (ev.target === modal) closeDescriptionModal();
        });
        document.addEventListener('keydown', function(ev) {
            if (ev.key === 'Escape') closeDescriptionModal();
        });
    }

    const body = document.getElementById('descModalBody');
    body.textContent = text || 'No description';
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeDescriptionModal() {
    const modal = document.getElementById('descModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}



