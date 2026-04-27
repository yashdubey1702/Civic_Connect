console.log('[PublicMapJS] Loaded (Bhubaneswar)');

(function () {
    let map = null;
    let bmcLayer = null;
    let allMarkers = [];
    let modal = null;
    let reportForm = null;
    let loginPrompt = null;

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

        return colors[status] || '#c62828';
    }

    function createStatusIcons() {
        if (typeof L === 'undefined') return {};

        return {
            Reported: L.divIcon({
                className: 'custom-marker',
                html: '<div style="width:16px;height:16px;background-color:#c62828;border:2px solid white;border-radius:50%;box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            }),
            Acknowledged: L.divIcon({
                className: 'custom-marker',
                html: '<div style="width:16px;height:16px;background-color:#f57c00;border:2px solid white;border-radius:50%;box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            }),
            'In Progress': L.divIcon({
                className: 'custom-marker',
                html: '<div style="width:16px;height:16px;background-color:#0277bd;border:2px solid white;border-radius:50%;box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            }),
            Resolved: L.divIcon({
                className: 'custom-marker',
                html: '<div style="width:16px;height:16px;background-color:#2e7d32;border:2px solid white;border-radius:50%;box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            })
        };
    }

    const statusIcons = {};

    function selectedCheckboxValue(name) {
        const checked = document.querySelector(`input[name="${name}"]:checked`);
        return checked ? checked.value : null;
    }

    function normalizeReportsResponse(data) {
        if (Array.isArray(data)) return data;
        if (data && Array.isArray(data.reports)) return data.reports;
        return [];
    }

    function updateStats(reports) {
        const totalReports = document.getElementById('totalReports');
        const resolvedReports = document.getElementById('resolvedReports');
        const activeUsers = document.getElementById('activeUsers');

        if (totalReports) totalReports.textContent = reports.length;
        if (resolvedReports) {
            resolvedReports.textContent = reports.filter(report => report.status === 'Resolved').length;
        }
        if (activeUsers) {
            const users = new Set(
                reports
                    .map(report => (report.email || '').trim().toLowerCase())
                    .filter(Boolean)
            );
            activeUsers.textContent = users.size;
        }
    }

    function clearMarkers() {
        if (!map) return;

        allMarkers.forEach(marker => {
            if (map.hasLayer(marker)) {
                map.removeLayer(marker);
            }
        });
        allMarkers = [];
    }

    function renderReports(reports) {
        clearMarkers();
        updateStats(reports);

        reports.forEach(report => {
            if (!report || !report.latitude || !report.longitude) return;

            const icon = statusIcons[report.status] || statusIcons.Reported;
            const marker = L.marker([report.latitude, report.longitude], { icon }).addTo(map);

            marker.bindPopup(`
                <div style="min-width:200px;">
                    <h3 style="margin:0 0 10px 0;color:#0d47a1;border-bottom:2px solid #f0f0f0;padding-bottom:5px;">
                        ${escapeHtml(report.category)}
                    </h3>
                    <p style="margin:5px 0;">
                        <strong>Status:</strong>
                        <span style="color:${getStatusColor(report.status)};font-weight:bold;">
                            ${escapeHtml(report.status)}
                        </span>
                    </p>
                    <p style="margin:5px 0;">
                        <strong>Reported:</strong>
                        ${new Date(report.created_at).toLocaleDateString()}
                    </p>
                    ${report.description ? `<p style="margin:10px 0 0 0;padding-top:10px;border-top:1px solid #f0f0f0;"><em>${escapeHtml(report.description)}</em></p>` : ''}
                </div>
            `);

            allMarkers.push(marker);
        });
    }

    function loadExistingReports() {
        if (!map) return;

        const params = new URLSearchParams();
        const category = selectedCheckboxValue('category');
        const status = selectedCheckboxValue('status');

        if (category && category !== 'all') params.set('category', category);
        if (status && status !== 'all') params.set('status', status);

        const query = params.toString();
        const url = query ? `reports/get_map_reports.php?${query}` : 'reports/get_map_reports.php';

        fetch(url, { credentials: 'same-origin' })
            .then(response => response.json())
            .then(data => renderReports(normalizeReportsResponse(data)))
            .catch(error => {
                console.error('Error loading reports:', error);
            });
    }

    function showMapHelp() {
        alert(
            'Community Issues Map Help:\n\n' +
            '- Click inside Bhubaneswar city to report an issue.\n' +
            '- Use filters to view reports by category or status.\n' +
            '- Click markers to see report details.\n' +
            '- Login is required before submitting a report.'
        );
    }

    function showLoginPrompt(lat, lng) {
        const latInput = document.getElementById('lat');
        const lngInput = document.getElementById('lng');

        if (latInput) latInput.value = lat;
        if (lngInput) lngInput.value = lng;
        if (loginPrompt) loginPrompt.style.display = 'block';
        if (reportForm) reportForm.style.display = 'none';
        if (modal) modal.style.display = 'block';
    }

    function isInsideBoundary(lat, lng) {
        if (!bmcLayer || typeof isWithinBhubaneswar !== 'function') {
            return false;
        }

        return isWithinBhubaneswar(lat, lng, bmcLayer);
    }

    function closeModal() {
        if (modal) modal.style.display = 'none';
        if (reportForm) reportForm.reset();
    }

    function handleCheckboxSelection(clickedCheckbox, groupName) {
        if (clickedCheckbox.checked) {
            document.querySelectorAll(`input[name="${groupName}"]`).forEach(checkbox => {
                if (checkbox !== clickedCheckbox) {
                    checkbox.checked = false;
                }
            });
        } else {
            const checked = document.querySelectorAll(`input[name="${groupName}"]:checked`);
            if (checked.length === 0) {
                const allOption = document.querySelector(`input[name="${groupName}"][value="all"]`);
                if (allOption) allOption.checked = true;
            }
        }

        loadExistingReports();
    }

    function clearFilters() {
        document.querySelectorAll('input[name="category"]').forEach(checkbox => {
            checkbox.checked = checkbox.value === 'all';
        });
        document.querySelectorAll('input[name="status"]').forEach(checkbox => {
            checkbox.checked = checkbox.value === 'all';
        });

        loadExistingReports();
    }

    function initPublicMap() {
        const mapContainer = document.getElementById('map');

        if (!mapContainer || typeof L === 'undefined') {
            console.error('#map container or Leaflet was not found');
            return;
        }

        if (mapContainer._leaflet_id) return;

        Object.assign(statusIcons, createStatusIcons());

        map = L.map('map').setView([20.2961, 85.8245], 12);
        modal = document.getElementById('reportModal');
        reportForm = document.getElementById('reportForm');
        loginPrompt = document.getElementById('loginPrompt');

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);

        if (loginPrompt) loginPrompt.style.display = 'block';
        if (reportForm) {
            reportForm.style.display = 'none';
            reportForm.addEventListener('submit', event => {
                event.preventDefault();
                window.location.href = 'login.php';
            });
        }

        const closeButton = modal ? modal.querySelector('.close') : null;
        if (closeButton) closeButton.addEventListener('click', closeModal);

        window.addEventListener('click', event => {
            if (event.target === modal) {
                closeModal();
            }
        });

        if (typeof loadBhubaneswarBoundary === 'function') {
            loadBhubaneswarBoundary(map).then(layer => {
                bmcLayer = layer;
                loadExistingReports();
            });
        } else {
            loadExistingReports();
        }

        map.on('click', event => {
            const { lat, lng } = event.latlng;

            if (!isInsideBoundary(lat, lng)) {
                alert('Reports can only be submitted from within Bhubaneswar city.');
                return;
            }

            showLoginPrompt(lat, lng);
        });

        document.querySelectorAll('input[name="category"]').forEach(checkbox => {
            checkbox.addEventListener('click', () => handleCheckboxSelection(checkbox, 'category'));
        });
        document.querySelectorAll('input[name="status"]').forEach(checkbox => {
            checkbox.addEventListener('click', () => handleCheckboxSelection(checkbox, 'status'));
        });

        setTimeout(() => map.invalidateSize(true), 150);
    }

    window.loadExistingReports = loadExistingReports;
    window.applyFilters = loadExistingReports;
    window.clearFilters = clearFilters;
    window.showMapHelp = showMapHelp;

    document.addEventListener('DOMContentLoaded', initPublicMap);
})();
