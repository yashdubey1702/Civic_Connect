console.log('[AdminDashboardJS] Ward-based version loaded');

// GLOBAL STATE

let adminMap;
let mapMarkers = [];
let bmcLayer;

let allReports = [];
let currentPage = 1;
let perPage = 10;
let totalPages = 1;
let totalRecords = 0;

// STATUS ICONS

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

// MAP INITIALIZATION (Bhubaneswar)

function initMap() {
    if (adminMap && adminMap.remove) {
        adminMap.remove();
    }

    const container = document.getElementById('adminMap');
    if (container && container._leaflet_id) {
        container._leaflet_id = null;
    }

    adminMap = L.map('adminMap', {
        center: [20.2961, 85.8245],
        zoom: 12,
        zoomControl: true
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18
    }).addTo(adminMap);

// Load BMC boundary (visual reference)
    fetch('data/bmc_boundary.geojson')
        .then(res => res.json())
        .then(data => {
            bmcLayer = L.geoJSON(data, {
                style: {
                    color: '#ff7800',
                    weight: 3,
                    fillOpacity: 0,
                    fillColor: 'transparent'
                }
            }).addTo(adminMap);

            adminMap.fitBounds(bmcLayer.getBounds());
            loadMapReports();
        })
        .catch(() => loadMapReports());

    setTimeout(() => adminMap.invalidateSize(), 150);
}

// LOAD MAP REPORTS (WARD BASED)

function loadMapReports() {
    const status = document.getElementById('statusFilter')?.value || 'all';
    const category = document.getElementById('categoryFilter')?.value || 'all';
    const ward = document.getElementById('municipalityFilter')?.value || 'all';

    let url = 'reports/get_map_reports.php?';
    if (status !== 'all') url += `status=${encodeURIComponent(status)}&`;
    if (category !== 'all') url += `category=${encodeURIComponent(category)}&`;
    if (ward !== 'all') url += `ward=${encodeURIComponent(ward)}`;

    fetch(url)
        .then(res => res.json())
        .then(reports => {
            mapMarkers.forEach(m => adminMap.removeLayer(m));
            mapMarkers = [];

            reports.forEach(r => {
                const icon = statusIcons[r.status] || statusIcons.Reported;
                const marker = L.marker([r.latitude, r.longitude], { icon }).addTo(adminMap);

                marker.bindPopup(`
                    <div style="min-width:200px">
                        <h3 style="color:#0d47a1">${r.category}</h3>
                        <p><strong>Status:</strong>
                            <span style="color:${getStatusColor(r.status)}">${r.status}</span>
                        </p>
                        <p><strong>Ward:</strong> ${r.municipality?.toUpperCase()}</p>
                        <p><strong>Date:</strong> ${new Date(r.created_at).toLocaleDateString()}</p>
                        ${r.description ? `<p><em>${r.description}</em></p>` : ''}
                    </div>
                `);

                mapMarkers.push(marker);
            });

            if (mapMarkers.length) {
                adminMap.fitBounds(
                    L.featureGroup(mapMarkers).getBounds().pad(0.1)
                );
            }
        })
        .catch(err => console.error('Map load error:', err));
}


// REPORTS TABLE (WARD BASED)

function loadReports(page = 1) {
    currentPage = page;

    const status = document.getElementById('statusFilter').value;
    const category = document.getElementById('categoryFilter').value;
    const ward = document.getElementById('municipalityFilter')?.value || 'all';
    const search = document.getElementById('searchInput')?.value.trim() || '';

    let url = `reports/get_reports.php?page=${page}&per_page=${perPage}`;
    if (status !== 'all') url += `&status=${encodeURIComponent(status)}`;
    if (category !== 'all') url += `&category=${encodeURIComponent(category)}`;
    if (ward !== 'all') url += `&ward=${encodeURIComponent(ward)}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            allReports = data.reports;
            totalRecords = data.pagination.total_records;
            totalPages = data.pagination.total_pages;

            renderReportsTable(data.reports);
            renderPagination();
            updateStatistics(data.reports);
        })
        .catch(() => {
            document.getElementById('reportsTable').innerHTML =
                `<p class="error">Failed to load reports</p>`;
        });
}


// RENDER TABLE

function renderReportsTable(reports) {
    if (!reports.length) {
        document.getElementById('reportsTable').innerHTML =
            `<div class="empty-state">No reports found</div>`;
        return;
    }

    let html = `
        <table class="reports-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Ward</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
    `;

    reports.forEach(r => {
        html += `
            <tr>
                <td>${r.id}</td>
                <td>${r.category}</td>
                <td>${r.description || '-'}</td>
                <td>${r.municipality?.toUpperCase()}</td>
                <td>${r.email || 'Anonymous'}</td>
                <td>
                    <span class="status-badge status-${r.status.toLowerCase().replace(' ', '-')}">
                        ${r.status}
                    </span>
                </td>
                <td>${new Date(r.created_at).toLocaleDateString()}</td>
                <td>
                    <select onchange="updateStatus(${r.id}, this.value)">
                        ${['Reported','Acknowledged','In Progress','Resolved']
                            .map(s => `<option ${s===r.status?'selected':''}>${s}</option>`).join('')}
                    </select>
                </td>
            </tr>
        `;
    });

    html += `</tbody></table>`;
    document.getElementById('reportsTable').innerHTML = html;
}

// UTILITIES

function renderPagination() {
    const el = document.getElementById('pagination');
    if (!el) return;

    let html = `<button ${currentPage===1?'disabled':''}
                onclick="loadReports(${currentPage-1})">Prev</button>`;

    for (let i = 1; i <= totalPages; i++) {
        html += `<button class="${i===currentPage?'active':''}"
                onclick="loadReports(${i})">${i}</button>`;
    }

    html += `<button ${currentPage===totalPages?'disabled':''}
                onclick="loadReports(${currentPage+1})">Next</button>`;

    el.innerHTML = html;
}

function updateStatistics(reports) {
    document.getElementById('totalReports').textContent = totalRecords;
    document.getElementById('reportedCount').textContent = reports.filter(r => r.status === 'Reported').length;
    document.getElementById('acknowledgedCount').textContent = reports.filter(r => r.status === 'Acknowledged').length;
    document.getElementById('inProgressCount').textContent = reports.filter(r => r.status === 'In Progress').length;
    document.getElementById('resolvedCount').textContent = reports.filter(r => r.status === 'Resolved').length;
}

function getStatusColor(status) {
    return {
        'Reported': '#c62828',
        'Acknowledged': '#f57c00',
        'In Progress': '#0277bd',
        'Resolved': '#2e7d32'
    }[status] || '#212529';
}


// EVENTS

function applyFilters() {
    loadReports(1);
    loadMapReports();
}

function updateStatus(id, status) {
    fetch('reports/update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
    })
    .then(res => res.json())
    .then(() => {
        loadReports(currentPage);
        loadMapReports();
    });
}


// INIT

document.addEventListener('DOMContentLoaded', () => {
    initMap();
    loadReports(1);
    window.addEventListener('resize', () => adminMap?.invalidateSize());
});
