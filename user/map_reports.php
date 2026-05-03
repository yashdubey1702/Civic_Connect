<?php
session_start();
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';

$database = new Database();
$db = $database->getConnection(); // mysqli
$auth = new Auth($db);

// Require citizen authentication
$auth->requireAuth('citizen');

// Get user's reports
$user_id = (int)$_SESSION['user_id'];
$reports = [];
$error = '';

$query = "SELECT id, latitude, longitude, category, description, status, created_at, image_filename
          FROM reports
          WHERE user_id = ?
          ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
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
    <title>My Reports Map - Municipal Issue Reporting System</title>
    <link rel="icon" href="/assets/images/BRP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/user-dashboard.css">
    <link rel="stylesheet" href="/assets/css/mobile.css?v=3">
    <link rel="stylesheet" href="/assets/css/notifications.css">
</head>
<body>
    <!-- Navigation Sidebar -->
    <nav class="user-sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <div class="gov-logo">
                    <svg viewBox="0 0 24 24">
                        <path d="M12,2L2,7L12,12L22,7L12,2M2,17L12,22L22,17V12L12,17L2,12V17Z" />
                    </svg>
                </div>
                <div class="logo-text">
                    <h2>Citizen Portal</h2>
                    <span>Issue Reporting System</span>
                </div>
            </div>
        </div>
        
        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="/town_issues/user/dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item active">
                <a href="/town_issues/user/map_reports.php">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>My Reports Map</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/town_issues/user/report_history.php">
                    <i class="fas fa-history"></i>
                    <span>Report History</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/town_issues/user/profile.php">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/town_issues/user/help.php">
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
            <a href="/town_issues/auth/logout.php" class="logout-btn">
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
                <button class="sidebar-toggle" aria-label="Open menu" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>
                <h1></h1>
            </div>
<div class="header-right">
    <button class="new-report-btn" onclick="location.href='/town_issues/user/map_reports.php'">
        <i class="fas fa-plus"></i>
        New Report
    </button>
    <div class="header-actions">
        <button class="notification-btn" type="button" aria-label="Open notifications">
            <i class="fas fa-bell"></i>
            <span class="notification-badge" hidden>0</span>
        </button>
    </div>
</div>
        </header>

        <!-- Map Content -->
        <div class="dashboard-content">
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-map-marked-alt"></i> My Reports Map</h2>
                    <button class="refresh-btn" onclick="loadUserReports()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body">
                    <p>View your submitted reports within Bhubaneswar City</p>
                    <div class="map-search-panel" role="search" aria-label="Search report location">
                        <div class="map-search-box">
                            <i class="fas fa-location-dot" aria-hidden="true"></i>
                            <input
                                type="search"
                                id="locationSearch"
                                placeholder="Search a location in Bhubaneswar"
                                autocomplete="off"
                            />
                        </div>
                        <button type="button" id="locationSearchBtn" class="map-icon-btn" onclick="searchLocation()" title="Search location" aria-label="Search location">
                            <i class="fas fa-search"></i>
                        </button>
                        <button type="button" id="currentLocationBtn" class="map-icon-btn" onclick="getCurrentLocation()" title="Use current location" aria-label="Use current location">
                            <i class="fas fa-location-arrow"></i>
                        </button>
                        <span id="locationSearchStatus" class="map-search-status" aria-live="polite"></span>
                    </div>
                    <div id="userMap">
                        <!-- Map Legend -->
                        <div class="map-legend">
                            <h4>Status Legend</h4>
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: #c62828;"></span>
                                <span>Reported</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: #f57c00;"></span>
                                <span>Acknowledged</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: #0277bd;"></span>
                                <span>In Progress</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: #2e7d32;"></span>
                                <span>Resolved</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Report Modal -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Submit New Report</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="reportForm">
                    <input type="hidden" id="lat">
                    <input type="hidden" id="lng">
                    
                    <div class="form-group">
                        <label for="category">Issue Category</label>
                        <select class="form-control" id="category" name="category" required>
                            <option value="">Select a category</option>
                            <option value="Pothole">Pothole</option>
                            <option value="Graffiti">Graffiti</option>
                            <option value="Broken Streetlight">Broken Streetlight</option>
                            <option value="Trash">Trash Debris</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="email">Email (Optional - for updates)</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_SESSION['email']); ?>" 
                               placeholder="Your email for updates">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Please describe the issue in detail..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image">Upload Photo (Optional)</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Submit Report
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@mapbox/leaflet-pip@latest/leaflet-pip.min.js"></script>
    <script src="/assets/js/map-reports.js?v=6"></script>
    <script src="/assets/js/theme-toggle.js"></script>
    <script src="/assets/js/notifications.js?v=2"></script>
    <script src="/assets/js/sidebar.js?v=3"></script>
    <script>
        // Opens the report submission modal.
        function openModal() {
            const modal = document.getElementById('reportModal');
            if (!modal) return;

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // prevent background scroll
        }

        // Closes the report submission modal.
        function closeModal() {
            const modal = document.getElementById('reportModal');
            if (!modal) return;

            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // restore scroll
        }

        /* Close modal when clicking outside */
        window.addEventListener('click', function (e) {
            const modal = document.getElementById('reportModal');
            if (modal && e.target === modal) {
                closeModal();
            }
        });

        /* Close modal on ESC key */
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
