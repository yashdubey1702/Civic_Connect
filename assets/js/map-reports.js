console.log('[MapReportsJS] Loaded (Bhubaneswar)');

(function () {
    let userMap = null;
    let bmcLayer = null;
    let reportModal = null;
    let reportForm = null;
    let tempMarker = null;
    let userMarkers = [];
    let statusIcons = {};

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

    function isWithinLoadedBoundary(lat, lng) {
        if (!bmcLayer) {
            showNotification('Map boundary is still loading. Please try again in a moment.', 'warning');
            return false;
        }

        if (typeof leafletPip === 'undefined') {
            showNotification('Map boundary checker is not available.', 'error');
            return false;
        }

        const point = L.latLng(lat, lng);
        return leafletPip.pointInLayer(point, bmcLayer, true).length > 0;
    }

    function setSelectedLocation(lat, lng) {
        const latInput = document.getElementById('lat');
        const lngInput = document.getElementById('lng');

        if (latInput) latInput.value = lat;
        if (lngInput) lngInput.value = lng;
    }

    function openReportModal() {
        if (!reportModal) return;

        reportModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeReportModal() {
        if (reportModal) reportModal.style.display = 'none';
        if (reportForm) reportForm.reset();
        document.body.style.overflow = 'auto';
    }

    function placeTempMarker(lat, lng, label) {
        if (!userMap) return;

        if (tempMarker && userMap.hasLayer(tempMarker)) {
            userMap.removeLayer(tempMarker);
        }

        tempMarker = L.marker([lat, lng])
            .addTo(userMap)
            .bindPopup(label)
            .openPopup();
    }

    function clearUserMarkers() {
        if (!userMap) return;

        userMarkers.forEach(marker => {
            if (userMap.hasLayer(marker)) {
                userMap.removeLayer(marker);
            }
        });
        userMarkers = [];
    }

    function renderUserReports(reports) {
        clearUserMarkers();

        reports.forEach(report => {
            if (!report || !report.latitude || !report.longitude) return;

            const icon = statusIcons[report.status] || statusIcons.Reported;
            const marker = L.marker([report.latitude, report.longitude], { icon }).addTo(userMap);

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

            userMarkers.push(marker);
        });

        if (userMarkers.length > 0) {
            userMap.fitBounds(L.featureGroup(userMarkers).getBounds().pad(0.1));
        } else if (bmcLayer) {
            userMap.fitBounds(bmcLayer.getBounds());
        }
    }

    function loadUserReports() {
        if (!userMap) return;

        fetch('reports/get_user_reports.php', { credentials: 'same-origin' })
            .then(response => response.json())
            .then(data => renderUserReports(normalizeReportsResponse(data)))
            .catch(error => {
                console.error('Error loading user reports:', error);
                showNotification('Unable to load your reports.', 'error');
            });
    }

    function loadBoundary() {
        return fetch('./data/bmc_boundary.geojson')
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                bmcLayer = L.geoJSON(data, {
                    style: {
                        color: '#ff7800',
                        weight: 3,
                        fillOpacity: 0,
                        fillColor: 'transparent',
                        className: 'bmc-boundary'
                    }
                }).addTo(userMap);

                bmcLayer.bindTooltip('Bhubaneswar Municipal Corporation Boundary', {
                    permanent: false,
                    direction: 'center',
                    className: 'boundary-tooltip'
                });

                userMap.fitBounds(bmcLayer.getBounds());
            })
            .catch(error => {
                console.error('BMC boundary load failed:', error);
                showNotification('Unable to load the Bhubaneswar boundary.', 'error');
            })
            .finally(loadUserReports);
    }

    function searchLocation() {
        if (!userMap) {
            showNotification('Map is still loading.', 'warning');
            return;
        }

        const searchInput = document.getElementById('locationSearch');
        const query = searchInput ? searchInput.value.trim() : '';

        if (!query) {
            showNotification('Please enter a location name.', 'warning');
            return;
        }

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) {
                    showNotification('Location not found.', 'error');
                    return;
                }

                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);

                if (!isWithinLoadedBoundary(lat, lng)) {
                    showNotification('Location is outside Bhubaneswar city.', 'warning');
                    return;
                }

                userMap.setView([lat, lng], 15);
                placeTempMarker(lat, lng, 'Searched Location');
                setSelectedLocation(lat, lng);
                openReportModal();
            })
            .catch(error => {
                console.error('Search failed:', error);
                showNotification('Search failed.', 'error');
            });
    }

    function getCurrentLocation() {
        if (!userMap) {
            showNotification('Map is still loading.', 'warning');
            return;
        }

        if (!navigator.geolocation) {
            showNotification('Geolocation is not supported by your browser.', 'error');
            return;
        }

        showNotification('Fetching your location...', 'success');

        navigator.geolocation.getCurrentPosition(
            position => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                if (!isWithinLoadedBoundary(lat, lng)) {
                    showNotification('Your current location is outside Bhubaneswar city.', 'warning');
                    return;
                }

                userMap.setView([lat, lng], 15);
                placeTempMarker(lat, lng, 'You are here');
                setSelectedLocation(lat, lng);
                openReportModal();
            },
            error => {
                console.error('Geolocation failed:', error);
                showNotification('Unable to retrieve your location.', 'error');
            },
            { enableHighAccuracy: true }
        );
    }

    function submitReport(event) {
        event.preventDefault();

        const lat = parseFloat(document.getElementById('lat')?.value || '');
        const lng = parseFloat(document.getElementById('lng')?.value || '');

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            showNotification('Please select a valid location on the map.', 'error');
            return;
        }

        if (!isWithinLoadedBoundary(lat, lng)) {
            showNotification('Reports must be within Bhubaneswar city.', 'error');
            return;
        }

        const formData = new FormData(reportForm);
        formData.set('lat', String(lat));
        formData.set('lng', String(lng));

        fetch('reports/submit_report.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Report submitted successfully.', 'success');
                    closeReportModal();
                    loadUserReports();
                } else {
                    showNotification(data.message || 'Report submission failed.', 'error');
                }
            })
            .catch(error => {
                console.error('Submission failed:', error);
                showNotification('Submission failed.', 'error');
            });
    }

    function initMapReports() {
        const mapContainer = document.getElementById('userMap');

        if (!mapContainer || typeof L === 'undefined') {
            console.error('#userMap container or Leaflet was not found');
            return;
        }

        if (mapContainer._leaflet_id) return;

        reportModal = document.getElementById('reportModal');
        reportForm = document.getElementById('reportForm');
        statusIcons = createStatusIcons();

        userMap = L.map('userMap').setView([20.2961, 85.8245], 12);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(userMap);

        userMap.on('click', event => {
            const { lat, lng } = event.latlng;

            if (!isWithinLoadedBoundary(lat, lng)) {
                showNotification('Please select a location within Bhubaneswar city.', 'warning');
                return;
            }

            setSelectedLocation(lat, lng);
            openReportModal();
        });

        if (reportForm) {
            reportForm.addEventListener('submit', submitReport);
        }

        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.user-sidebar');
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }

        loadBoundary();
        setTimeout(() => userMap.invalidateSize(true), 150);
    }

    window.searchLocation = searchLocation;
    window.getCurrentLocation = getCurrentLocation;
    window.loadUserReports = loadUserReports;
    window.closeMapReportModal = closeReportModal;

    document.addEventListener('DOMContentLoaded', initMapReports);

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

        .notification.success { background: #28a745; }
        .notification.error { background: #dc3545; }
        .notification.warning { background: #ffc107; color: #000; }
        .notification.fade-out { animation: slideOut 0.3s ease-in forwards; }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
})();
