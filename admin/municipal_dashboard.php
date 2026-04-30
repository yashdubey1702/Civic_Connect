<?php
session_start();
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Require ward or municipal admin authentication
$auth->requireAuth();
if (!$auth->isWardAdmin() && !$auth->isMunicipalAdmin()) {
    header("Location: /town_issues/public/unauthorized.php");
    exit;
}

// Session data
$email     = $_SESSION['email'];
$full_name = $_SESSION['full_name'];
$user_type = $_SESSION['user_type'];
$ward      = $auth->getWard();

// Formats a ward value into a safe display label.
function formatWardLabel($ward)
{
    $ward = trim((string)$ward);

    if ($ward === '') {
        return 'Unassigned';
    }

    if (preg_match('/^w(\d+)$/i', $ward, $matches)) {
        return 'W' . $matches[1];
    }

    if (preg_match('/^[A-Za-z0-9 _-]{1,30}$/', $ward)) {
        return $ward;
    }

    return 'Unknown';
}

// Human-readable ward name
$wardLabel = formatWardLabel($ward);
$safeWardLabel = htmlspecialchars($wardLabel, ENT_QUOTES, 'UTF-8');
$adminScopeLabel = $auth->isWardAdmin() ? "Ward {$safeWardLabel}" : "Municipal";
$adminRoleLabel = $auth->isWardAdmin() ? "Ward {$safeWardLabel} Admin" : "Municipal Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $adminScopeLabel ?> Admin – CivicConnect Bhubaneswar</title>

    <link rel="icon" href="/town_issues/assets/images/BRP.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/town_issues/assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="/town_issues/assets/css/admin-mobile.css">
    <link rel="stylesheet" href="/town_issues/assets/css/municipal-admin.css">
    <link rel="stylesheet" href="/town_issues/assets/css/notifications.css">
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
                <p class="tagline">Bhubaneswar Municipal Corporation • <?= $adminScopeLabel ?></p>
            </div>
        </div>

        <div class="dashboard-controls">
            <div class="theme-toggle-container">
                <div class="theme-toggle" id="themeToggle">
                    <i class="fas fa-sun"></i>
                    <i class="fas fa-moon"></i>
                    <span class="toggle-thumb"></span>
                </div>
            </div>
            <span class="admin-welcome">
                Welcome, <?= htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') ?> (<?= $adminRoleLabel ?>)
            </span>
            <button class="notification-btn" type="button" aria-label="Open notifications">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" hidden>0</span>
            </button>
            <a href="/town_issues/admin/volunteers.php" class="logout-btn">Volunteers</a>
            <a href="/town_issues/admin/volunteer_tasks.php" class="logout-btn">Volunteer Tasks</a>
            <a href="/town_issues/auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>

<!-- MAIN -->
<div class="dashboard-container">

    <div class="dashboard-header">
        <h1 class="dashboard-title"><?= $adminScopeLabel ?> – Reports Management</h1>
        <p class="dashboard-subtitle">
            Manage civic issues reported <?= $auth->isWardAdmin() ? "within Ward {$safeWardLabel}," : "across" ?> Bhubaneswar
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
        <h2><?= $adminScopeLabel ?> Map View</h2>
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
<script src="/town_issues/assets/js/theme-toggle.js"></script>
<script src="/town_issues/assets/js/notifications.js?v=2"></script>
<script src="/town_issues/assets/js/municipal-admin-dashboard.js?v=2"></script>

</body>
</html>
