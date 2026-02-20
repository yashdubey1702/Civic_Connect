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

    <!-- STATS -->
    <div class="dashboard-stats">
        <div class="stat-card"><div id="totalReports">0</div><span>Total Reports</span></div>
        <div class="stat-card"><div id="reportedCount">0</div><span>Reported</span></div>
        <div class="stat-card"><div id="acknowledgedCount">0</div><span>Acknowledged</span></div>
        <div class="stat-card"><div id="inProgressCount">0</div><span>In Progress</span></div>
        <div class="stat-card"><div id="resolvedCount">0</div><span>Resolved</span></div>
    </div>

    <!-- FILTERS -->
    <div class="filter-controls">
        <div class="filter-group">
            <label>Status</label>
            <select id="statusFilter" onchange="applyFilters()">
                <option value="all">All</option>
                <option value="Reported">Reported</option>
                <option value="Acknowledged">Acknowledged</option>
                <option value="In Progress">In Progress</option>
                <option value="Resolved">Resolved</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Category</label>
            <select id="categoryFilter" onchange="applyFilters()">
                <option value="all">All</option>
                <option value="Pothole">Pothole</option>
                <option value="Garbage">Garbage</option>
                <option value="Street Light">Street Light</option>
                <option value="Water Supply">Water Supply</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="filter-group">
            <input type="text" id="searchInput" placeholder="Search…" onkeyup="handleSearch()">
        </div>

        <button class="refresh-btn" onclick="refreshAll()">Refresh</button>
    </div>

    <!-- LAYOUT -->
    <div class="dashboard-layout">

        <!-- MAP -->
        <div class="map-section">
            <h2>Ward <?= $wardLabel ?> Map View</h2>
            <div id="municipalMap"></div>
        </div>

        <!-- TABLE -->
        <div class="reports-section">
            <h2>Reports</h2>
            <div id="reportsTable"></div>
            <div id="pagination"></div>
        </div>

    </div>
</div>

<!-- FOOTER -->
<footer class="gov-footer">
    <p>© <?= date('Y') ?> Bhubaneswar Municipal Corporation – CivicConnect</p>
</footer>

<!-- JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/@mapbox/leaflet-pip@latest/leaflet-pip.min.js"></script>
<script src="assets/js/municipal-admin-dashboard.js"></script>

</body>
</html>
