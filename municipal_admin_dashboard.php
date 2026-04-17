<?php
session_start();
require_once 'config/database.php';
require_once 'config/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Require WARD admin authentication
$auth->requireAuth('ward_admin');

// Session data
$email     = $_SESSION['email'];
$full_name = $_SESSION['full_name'];
$user_type = $_SESSION['user_type'];
$ward      = $auth->getWard(); 

// Human-readable ward name
$wardLabel = strtoupper($ward); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ward <?= $wardLabel ?> Admin – CivicConnect Bhubaneswar</title>

    <link rel="icon" href="assets/images/BPR.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="assets/css/admin-mobile.css">
    <link rel="stylesheet" href="assets/css/municipal-admin.css">
</head>

<body>

<!-- HEADER -->
<header class="gov-header">
    <div class="header-content">
        <div class="gov-brand">
            <div class="gov-logo">
                <svg viewBox="0 0 24 24">
                    <path d="M12,2L2,7L12,12L22,7L12,2M2,17L12,22L22,17V12L12,17L2,12V17Z"/>
                </svg>
            </div>
            <div class="gov-titles">
                <h1>CivicConnect – Bhubaneswar</h1>
                <p class="tagline">Bhubaneswar Municipal Corporation • Ward <?= $wardLabel ?></p>
            </div>
        </div>

        <div class="dashboard-controls">
            <span class="admin-welcome">
                Welcome, <?= htmlspecialchars($full_name) ?> (Ward <?= $wardLabel ?> Admin)
            </span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>

<!-- MAIN -->
<div class="dashboard-container">

    <div class="dashboard-header">
        <h1 class="dashboard-title">Ward <?= $wardLabel ?> – Reports Management</h1>
        <p class="dashboard-subtitle">
            Manage civic issues reported within Ward <?= $wardLabel ?>, Bhubaneswar
        </p>
    </div>

     <!-- Statistics Cards -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-number" id="totalReports">0</div>
                <div class="stat-label">Total Reports</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="reportedCount">0</div>
                <div class="stat-label">Reported</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="acknowledgedCount">0</div>
                <div class="stat-label">Acknowledged</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="inProgressCount">0</div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="resolvedCount">0</div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>

    <!-- FILTERS -->
    <div class="filter-controls" style="margin-top: 1rem;">
            <div class="filter-group">
                <label for="statusFilter">Status:</label>
                <select class="filter-select" id="statusFilter" onchange="applyFilters()">
                    <option value="all">All Statuses</option>
                    <option value="Reported">Reported</option>
                    <option value="Acknowledged">Acknowledged</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="categoryFilter">Category:</label>
                <select class="filter-select" id="categoryFilter" onchange="applyFilters()">
                    <option value="all">All Categories</option>
                    <option value="Pothole">Pothole</option>
                    <option value="Graffiti">Graffiti</option>
                    <option value="Broken Streetlight">Broken Streetlight</option>
                    <option value="Trash">Trash</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="searchInput">Search:</label>
                <input type="text" class="filter-select" id="searchInput" 
                       placeholder="Search by category, description, email..." onkeyup="handleSearch()">
            </div>
            <div class="filter-actions">
                <button class="refresh-btn" onclick="refreshAll()">Refresh</button>
            </div>
        </div>

    <!-- LAYOUT -->
<div class="dashboard-layout">

    <!-- MAP -->
    <div class="map-section">
        <h2>Ward <?= $wardLabel ?> Map View</h2>
        <p class="section-subtitle">
            View all reported civic issues in this ward
        </p>

        <div class="map-wrapper">
            <div id="municipalMap">
                
                <!-- Map Legend -->
                <div class="map-legend">
                    <h4>Status Legend</h4>

                    <div class="legend-item">
                        <span class="legend-color" style="background:#c62828;"></span>
                        <span>Reported</span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-color" style="background:#f57c00;"></span>
                        <span>Acknowledged</span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-color" style="background:#0277bd;"></span>
                        <span>In Progress</span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-color" style="background:#2e7d32;"></span>
                        <span>Resolved</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="reports-section">
        <h2>Reports Management</h2>

        <div class="reports-container">
            <div class="table-responsive">
                <div id="reportsTable">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <p>Loading reports...</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="pagination" class="pagination-container"></div>
    </div>

</div>
</div>

<!-- FOOTER -->
<footer class="gov-footer">
    <div class="footer-content">
        <p>Bhubaneswar Municipal Corporation – CivicConnect</p>
        <div class="footer-bottom">
            <p>© <?= date('Y') ?> Authorized Administrative Access</p>
        </div>
    </div>
</footer>

<!-- JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/@mapbox/leaflet-pip@latest/leaflet-pip.min.js"></script>
<script src="assets/js/municipal-admin-dashboard.js"></script>

</body>
</html>
