// Status color helper
function getStatusColor(status) {
    const colors = {
        'Reported': '#c62828',
        'Acknowledged': '#f57c00',
        'In Progress': '#0277bd',
        'Resolved': '#2e7d32'
    };
    return colors[status] || '#212529';
}

//Point-in-Boundary Validation (BMC)

function isWithinBhubaneswar(lat, lng, bmcLayer) {
    // Fail-safe: allow interaction if boundary not loaded
    if (!bmcLayer) return true;

    const point = L.latLng(lat, lng);
    const results = leafletPip.pointInLayer(point, bmcLayer, true);
    return results.length > 0;
}

//Load Bhubaneswar Municipal Boundary

function loadBhubaneswarBoundary(map) {
    return fetch('./data/bmc_boundary.geojson')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            const layer = L.geoJSON(data, {
                style: {
                    color: "#ff7800",
                    weight: 3,
                    fillOpacity: 0,
                    fillColor: "transparent",
                    className: 'bmc-boundary'
                }
            }).addTo(map);

            // Tooltip for clarity & debugging
            layer.bindTooltip(
                'Bhubaneswar Municipal Corporation Boundary',
                {
                    permanent: false,
                    direction: 'center',
                    className: 'boundary-tooltip'
                }
            );

            // Zoom map to city bounds
            map.fitBounds(layer.getBounds());

            console.log('[MapCommon] BMC boundary loaded');
            return layer;
        })
        .catch(error => {
            console.error('[MapCommon] Failed to load BMC boundary:', error);
            return null;
        });
}
