<?php
session_start();
require_once 'config/database.php';
require_once 'config/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Require citizen authentication
$auth->requireAuth('citizen');

// Get user's reports
$query = "SELECT id, latitude, longitude, category, description, status, created_at, image_filename
          FROM reports
          WHERE email = ?
          ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$result = $stmt->get_result();
$reports = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard – CivicConnect Bhubaneswar</title>
    <link rel="icon" href="assets/images/BPR.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/user-dashboard.css">
    <link rel="stylesheet" href="assets/css/mobile.css">

<style>
.leaflet-container {
    outline: none !important;
}

.leaflet-container svg {
    outline: none !important;
}

.leaflet-container path {
    outline: none !important;
}

.leaflet-container:focus {
    outline: none !important;
}

.leaflet-pane,
.leaflet-map-pane,
.leaflet-overlay-pane {
    outline: none !important;
    border: none !important;
}
</style>
</head>
<body>

<!-- Sidebar -->
<nav class="user-sidebar sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <div class="gov-logo">
                <svg viewBox="0 0 24 24">
                    <path d="M12,2L2,7L12,12L22,7L12,2M2,17L12,22L22,17V12L12,17L2,12V17Z" />
                </svg>
            </div>
            <div class="logo-text">
                <h2>Citizen Portal</h2>
                <span>CivicConnect – Bhubaneswar</span>
            </div>
        </div>
    </div>

     <ul class="sidebar-menu">
            <li class="menu-item active">
                <a href="user_dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="map_reports.php">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>My Reports Map</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="report_history.php">
                    <i class="fas fa-history"></i>
                    <span>Report History</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="profile.php">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="help.php">
                    <i class="fas fa-question-circle"></i>
                    <span>Help & Support</span>
                </a>
            </li>
        </ul>

    <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <span class="user-role">Citizen</span>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>

 <!-- Main Content -->
    <main class="user-main">
        <!-- Header -->
        <header class="user-header">
            <div class="header-left">
                <button class="sidebar-toggle hamburger-btn">☰</button>
                <h1>My Reports Dashboard</h1>
            </div>
            <div class="header-right">
            <div class="theme-toggle-container">
                <div class="theme-toggle" id="themeToggle">
                    <i class="fas fa-sun"></i>
                    <i class="fas fa-moon"></i>
                    <span class="toggle-thumb"></span>
                </div>
            </div>
            <button class="new-report-btn" onclick="location.href='map_reports.php'">
                <i class="fas fa-plus"></i>
                New Report
            </button>
            <div class="header-actions">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">2</span>
                </button>
            </div>
        </div>
        </header>

    <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <div class="welcome-content">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                    <p>Track and manage your civic issue reports within Bhubaneswar city.</p>
                    <div class="welcome-stats">
                        <div class="welcome-stat">
                            <span class="stat-number"><?php echo count($reports); ?></span>
                            <span class="stat-label">Total Reports</span>
                        </div>
                        <div class="welcome-stat">
                            <span class="stat-number"><?php echo count(array_filter($reports, function($r) { return $r['status'] === 'Resolved'; })); ?></span>
                            <span class="stat-label">Resolved Issues</span>
                        </div>
                    </div>
                </div>
                <div class="welcome-image">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
            </div>

       
            <!-- Quick Stats -->
            <div class="content-grid">
                <!-- Recent Reports -->
                <div class="content-card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> Recent Reports</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($reports) > 0): ?>
                            <div class="recent-reports">
                                <?php $recentReports = array_slice($reports, 0, 5); ?>
                                <?php foreach ($recentReports as $report): ?>
                                    <div class="report-item">
                                        <div class="report-info">
                                            <h4><?php echo htmlspecialchars($report['category']); ?></h4>
                                            <p><?php echo date('M j, Y', strtotime($report['created_at'])); ?></p>
                                        </div>
                                        <div class="report-status">
                                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $report['status'])); ?>">
                                                <?php echo $report['status']; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="view-all">
                                <a href="report_history.php">View All Reports →</a>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No Reports Yet</h3>
                                <p>You haven't submitted any reports yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <!-- Quick Actions -->
                <div class="content-card">
                    <div class="card-header">
                        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="map_reports.php" class="action-btn">
                                <i class="fas fa-map-marked-alt"></i>
                                <span>View Reports Map</span>
                            </a>
                            <a href="map_reports.php" class="action-btn">
                                <i class="fas fa-plus"></i>
                                <span>Submit New Report</span>
                            </a>
                            <a href="report_history.php" class="action-btn">
                                <i class="fas fa-history"></i>
                                <span>View Report History</span>
                            </a>
                            <a href="profile.php" class="action-btn">
                                <i class="fas fa-user"></i>
                                <span>Update Profile</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/js/map-common.js"></script>
    <script src="assets/js/user-dashboard.js"></script>
    <script src="assets/js/sidebar.js"></script>
</body>
</html>
