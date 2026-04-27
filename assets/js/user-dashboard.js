console.log('[UserDashboardJS] Loaded (Bhubaneswar)');

function escapeHtml(value) {
    return (value == null ? '' : String(value))
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function getStatusColor(status) {
    const colors = {
        Reported: '#c62828',
        Acknowledged: '#f57c00',
        'In Progress': '#0277bd',
        Resolved: '#2e7d32'
    };

    return colors[status] || '#212529';
}

function createStatusIcons() {
    if (typeof L === 'undefined') return {};

    const shadowUrl = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png';
    const baseUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/';

    return {
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
    };
}

function normalizeReportsResponse(data) {
    if (Array.isArray(data)) return data;
    if (data && data.success && Array.isArray(data.reports)) return data.reports;
    return [];
}

function initUserMap() {
    const mapContainer = document.getElementById('userMap');

    if (!mapContainer || typeof L === 'undefined') {
        console.log('User map not present. Skipping map init.');
        return;
    }

    if (mapContainer._leaflet_id) return;

    const userMap = L.map('userMap').setView([20.2961, 85.8245], 12);
    const statusIcons = createStatusIcons();
    const userMarkers = [];
    let bmcLayer = null;

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(userMap);

    function clearMarkers() {
        while (userMarkers.length) {
            const marker = userMarkers.pop();
            if (userMap.hasLayer(marker)) {
                userMap.removeLayer(marker);
            }
        }
    }

    function renderReports(reports) {
        clearMarkers();

        reports.forEach(report => {
            if (!report || !report.latitude || !report.longitude) return;

            const icon = statusIcons[report.status] || statusIcons.Reported;
            const marker = L.marker([report.latitude, report.longitude], { icon }).addTo(userMap);

            marker.bindPopup(`
                <div style="min-width:200px">
                    <h4 style="margin-bottom:6px">${escapeHtml(report.category)}</h4>
                    <p style="margin:4px 0">
                        <strong>Status:</strong>
                        <span style="color:${getStatusColor(report.status)}; font-weight:600">
                            ${escapeHtml(report.status)}
                        </span>
                    </p>
                    <p style="margin:4px 0">
                        <strong>Reported:</strong>
                        ${new Date(report.created_at).toLocaleDateString()}
                    </p>
                    ${report.description ? `<p style="margin-top:8px"><em>${escapeHtml(report.description)}</em></p>` : ''}
                </div>
            `);

            userMarkers.push(marker);
        });

        if (userMarkers.length > 1) {
            userMap.fitBounds(L.featureGroup(userMarkers).getBounds().pad(0.15));
        }
    }

    function loadUserReports() {
        fetch('reports/get_user_reports.php', { credentials: 'same-origin' })
            .then(response => response.json())
            .then(data => {
                renderReports(normalizeReportsResponse(data));
            })
            .catch(error => {
                console.error('User reports load error:', error);
            });
    }

    window.loadUserReports = loadUserReports;

    if (typeof loadBhubaneswarBoundary === 'function') {
        loadBhubaneswarBoundary(userMap).then(layer => {
            bmcLayer = layer;
            loadUserReports();
        });
    } else {
        loadUserReports();
    }

    userMap.on('click', event => {
        if (
            typeof isWithinBhubaneswar === 'function' &&
            !isWithinBhubaneswar(event.latlng.lat, event.latlng.lng, bmcLayer)
        ) {
            showNotification('Please select a location within Bhubaneswar city.', 'warning');
            return;
        }

        const latInput = document.getElementById('lat');
        const lngInput = document.getElementById('lng');
        const reportModal = document.getElementById('reportModal');

        if (latInput) latInput.value = event.latlng.lat;
        if (lngInput) lngInput.value = event.latlng.lng;
        if (reportModal) reportModal.style.display = 'flex';
    });

    setTimeout(() => userMap.invalidateSize(), 150);
}

function initSidebar() {
    const hamburgerBtn = document.querySelector('.hamburger-btn');
    const sidebar = document.querySelector('.user-sidebar.sidebar');
    const mainContent = document.querySelector('.user-main');

    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    if (!hamburgerBtn || !sidebar) return;

    function toggleSidebar() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');

        if (mainContent) {
            mainContent.classList.toggle('sidebar-open');
        }

        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }

    hamburgerBtn.addEventListener('click', event => {
        event.stopPropagation();
        toggleSidebar();
    });

    overlay.addEventListener('click', () => {
        if (sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    });

    document.addEventListener('click', event => {
        if (
            sidebar.classList.contains('active') &&
            !sidebar.contains(event.target) &&
            event.target !== hamburgerBtn
        ) {
            toggleSidebar();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    });
}

function filterReports() {
    const statusFilter = document.getElementById('reportStatusFilter');
    if (!statusFilter) return;

    const selectedStatus = statusFilter.value.toLowerCase().replace(/\s+/g, '-');
    document.querySelectorAll('.report-row').forEach(row => {
        row.style.display =
            statusFilter.value === 'all' || row.getAttribute('data-status') === selectedStatus
                ? ''
                : 'none';
    });
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${escapeHtml(message)}</span>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', () => {
    initUserMap();
    initSidebar();

    const statusFilter = document.getElementById('reportStatusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', filterReports);
    }

    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.user-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }
});
