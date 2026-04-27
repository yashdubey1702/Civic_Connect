console.log('[AdminDashboardJS] Loaded');

function esc(value) {
    return (value == null ? '' : String(value))
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

let adminMap = null;
let bmcLayer = null;
let mapMarkers = [];
let currentPage = 1;
let perPage = 10;
let totalPages = 1;
let totalRecords = 0;
let searchTimer = null;

const statusIcons = {};
const statuses = ['Reported', 'Acknowledged', 'In Progress', 'Resolved'];

function createStatusIcons() {
    if (typeof L === 'undefined') return;

    const baseUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/';
    const shadowUrl = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png';

    Object.assign(statusIcons, {
        Reported: L.icon({
            iconUrl: `${baseUrl}marker-icon-2x-red.png`,
            shadowUrl,
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        }),
        Acknowledged: L.icon({
            iconUrl: `${baseUrl}marker-icon-2x-orange.png`,
            shadowUrl,
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        }),
        'In Progress': L.icon({
            iconUrl: `${baseUrl}marker-icon-2x-blue.png`,
            shadowUrl,
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        }),
        Resolved: L.icon({
            iconUrl: `${baseUrl}marker-icon-2x-green.png`,
            shadowUrl,
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        })
    });
}

function getStatusColor(status) {
    return {
        Reported: '#c62828',
        Acknowledged: '#f57c00',
        'In Progress': '#0277bd',
        Resolved: '#2e7d32'
    }[status] || '#212529';
}

function getFilters() {
    return {
        status: document.getElementById('statusFilter')?.value || 'all',
        category: document.getElementById('categoryFilter')?.value || 'all',
        ward: document.getElementById('municipalityFilter')?.value || 'all',
        search: document.getElementById('searchInput')?.value.trim() || ''
    };
}

function buildQuery(params) {
    const query = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '' && value !== 'all') {
            query.set(key, value);
        }
    });

    return query.toString();
}

function initMap() {
    const container = document.getElementById('adminMap');
    if (!container || typeof L === 'undefined') return;

    if (container._leaflet_id) return;

    createStatusIcons();

    adminMap = L.map('adminMap', {
        center: [20.2961, 85.8245],
        zoom: 12,
        zoomControl: true
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 18
    }).addTo(adminMap);

    fetch('data/bmc_boundary.geojson')
        .then(response => response.json())
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
        .catch(error => {
            console.error('Boundary load failed:', error);
            loadMapReports();
        });

    setTimeout(() => adminMap.invalidateSize(), 150);
}

function populateWardFilter() {
    const select = document.getElementById('municipalityFilter');
    if (!select) return Promise.resolve();

    return fetch('data/Wards.geojson')
        .then(response => response.json())
        .then(data => {
            const wards = (data.features || [])
                .map(feature => feature.properties?.wardno)
                .filter(Boolean)
                .map(ward => String(ward).toUpperCase())
                .filter((ward, index, list) => list.indexOf(ward) === index)
                .sort((a, b) => {
                    const aNum = parseInt(a.replace(/\D/g, ''), 10);
                    const bNum = parseInt(b.replace(/\D/g, ''), 10);
                    return aNum - bNum;
                });

            select.innerHTML = '<option value="all">All Wards</option>';
            wards.forEach(ward => {
                const option = document.createElement('option');
                option.value = ward;
                option.textContent = ward;
                select.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Ward list load failed:', error);
        });
}

function clearMapMarkers() {
    if (!adminMap) return;

    mapMarkers.forEach(marker => {
        if (adminMap.hasLayer(marker)) {
            adminMap.removeLayer(marker);
        }
    });
    mapMarkers = [];
}

function loadMapReports() {
    if (!adminMap) return;

    const filters = getFilters();
    const query = buildQuery({
        status: filters.status,
        category: filters.category,
        ward: filters.ward
    });
    const url = query ? `reports/get_map_reports.php?${query}` : 'reports/get_map_reports.php';

    fetch(url, { credentials: 'same-origin' })
        .then(response => response.json())
        .then(reports => {
            if (!Array.isArray(reports)) throw new Error('Invalid map data');

            clearMapMarkers();

            reports.forEach(report => {
                if (!report || !report.latitude || !report.longitude) return;

                const icon = statusIcons[report.status] || statusIcons.Reported;
                const marker = L.marker([report.latitude, report.longitude], { icon }).addTo(adminMap);

                marker.bindPopup(`
                    <div style="min-width:200px">
                        <h3 style="color:#0d47a1">${esc(report.category)}</h3>
                        <p><strong>Status:</strong>
                            <span style="color:${getStatusColor(report.status)}">${esc(report.status)}</span>
                        </p>
                        <p><strong>Ward:</strong> ${esc(report.municipality || '-')}</p>
                        <p><strong>Date:</strong> ${new Date(report.created_at).toLocaleDateString()}</p>
                        ${report.description ? `<p><em>${esc(report.description)}</em></p>` : ''}
                    </div>
                `);

                mapMarkers.push(marker);
            });

            if (mapMarkers.length) {
                adminMap.fitBounds(L.featureGroup(mapMarkers).getBounds().pad(0.1));
            } else if (bmcLayer) {
                adminMap.fitBounds(bmcLayer.getBounds());
            }
        })
        .catch(error => {
            console.error('Map reports load error:', error);
        });
}

function loadReports(page = 1) {
    currentPage = page;

    const filters = getFilters();
    const query = buildQuery({
        page,
        per_page: perPage,
        status: filters.status,
        category: filters.category,
        ward: filters.ward,
        search: filters.search
    });

    fetch(`reports/get_reports.php?${query}`, { credentials: 'same-origin' })
        .then(response => response.json())
        .then(data => {
            if (!data || !Array.isArray(data.reports) || !data.pagination) {
                throw new Error('Invalid API response');
            }

            totalRecords = data.pagination.total_records;
            totalPages = data.pagination.total_pages;

            renderReportsTable(data.reports);
            renderPagination();
            updateStatistics(data.status_counts || {});
        })
        .catch(error => {
            console.error('Reports load error:', error);
            const table = document.getElementById('reportsTable');
            if (table) table.innerHTML = '<p class="error">Failed to load reports</p>';
        });
}

function renderReportsTable(reports) {
    const table = document.getElementById('reportsTable');
    if (!table) return;

    if (!reports.length) {
        table.innerHTML = '<div class="empty-state">No reports found</div>';
        return;
    }

    const rows = reports.map(report => `
        <tr>
            <td>${esc(report.id)}</td>
            <td>${esc(report.category)}</td>
            <td>${esc(report.description || '-')}</td>
            <td>${esc(report.municipality || '-')}</td>
            <td>${esc(report.email || 'Anonymous')}</td>
            <td>
                <span class="status-badge status-${String(report.status).toLowerCase().replace(/\s+/g, '-')}">
                    ${esc(report.status)}
                </span>
            </td>
            <td>${new Date(report.created_at).toLocaleDateString()}</td>
            <td>
                <select onchange="updateStatus(${Number(report.id)}, this.value)">
                    ${statuses.map(status => `
                        <option value="${esc(status)}" ${status === report.status ? 'selected' : ''}>
                            ${esc(status)}
                        </option>
                    `).join('')}
                </select>
            </td>
        </tr>
    `).join('');

    table.innerHTML = `
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
            <tbody>${rows}</tbody>
        </table>
    `;
}

function renderPagination() {
    const el = document.getElementById('pagination');
    if (!el) return;

    let html = `<button ${currentPage === 1 ? 'disabled' : ''} onclick="loadReports(${currentPage - 1})">Prev</button>`;

    for (let i = 1; i <= totalPages; i++) {
        html += `<button class="${i === currentPage ? 'active' : ''}" onclick="loadReports(${i})">${i}</button>`;
    }

    html += `<button ${currentPage >= totalPages ? 'disabled' : ''} onclick="loadReports(${currentPage + 1})">Next</button>`;
    el.innerHTML = html;
}

function updateStatistics(counts) {
    const values = {
        totalReports: totalRecords,
        reportedCount: counts.Reported || 0,
        acknowledgedCount: counts.Acknowledged || 0,
        inProgressCount: counts['In Progress'] || 0,
        resolvedCount: counts.Resolved || 0
    };

    Object.entries(values).forEach(([id, value]) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    });
}

function applyFilters() {
    loadReports(1);
    loadMapReports();
}

function handleSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        loadReports(1);
    }, 250);
}

function refreshAll() {
    loadReports(currentPage);
    loadMapReports();
}

function updateStatus(id, status) {
    fetch('reports/update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ id, status })
    })
        .then(response => response.json())
        .then(data => {
            if (data && data.success) {
                loadReports(currentPage);
                loadMapReports();
            } else {
                alert(data.message || 'Failed to update status');
            }
        })
        .catch(error => {
            console.error('Update error:', error);
            alert('Something went wrong while updating status');
        });
}

window.applyFilters = applyFilters;
window.handleSearch = handleSearch;
window.refreshAll = refreshAll;
window.loadReports = loadReports;
window.updateStatus = updateStatus;

document.addEventListener('DOMContentLoaded', () => {
    populateWardFilter().finally(() => {
        initMap();
        loadReports(1);
    });

    window.addEventListener('resize', () => adminMap?.invalidateSize());
});
