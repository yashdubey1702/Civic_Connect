<?php
session_start();
require_once 'config/database.php';
require_once 'config/Auth.php';

$database = new Database();
$db = $database->getConnection(); // mysqli
$auth = new Auth($db);

// Require citizen authentication
$auth->requireAuth('citizen');

// Get user's reports
$user_id = $_SESSION['user_id'];
$reports = [];
$error = '';

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
    <title>Report History - Municipal Issue Reporting System</title>
    <link rel="icon" href="assets/images/BPR.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/user-dashboard.css">
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
            <li class="menu-item active">
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
                <button class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1></h1>
            </div>
            <div class="header-right">
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

        <!-- Reports Content -->
        <div class="dashboard-content">
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> My Reports History</h2>
                    <div class="report-filter">
                        <select id="reportStatusFilter" onchange="filterReports()">
                            <option value="all">All Statuses</option>
                            <option value="Reported">Reported</option>
                            <option value="Acknowledged">Acknowledged</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="reports-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Date Reported</th>
                                    <th>Image</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($reports) > 0): ?>
                                    <?php foreach ($reports as $report): ?>
                                        <tr id="report-<?php echo $report['id']; ?>" class="report-row" data-status="<?php echo strtolower(str_replace(' ', '-', $report['status'])); ?>">
                                            <td><?php echo htmlspecialchars($report['category']); ?></td>
                                            <td><?php echo htmlspecialchars($report['description']); ?></td>
                                            
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $report['status'])); ?>">
                                                    <?php echo $report['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($report['created_at'])); ?></td>
                                            <td>
                                                <?php if (!empty($report['image_filename'])): ?>
                                                    <img src="reports/uploads/<?php echo htmlspecialchars($report['image_filename']); ?>" 
                                                         alt="Report Image" class="report-image" 
                                                         onclick="openImageModal('reports/uploads/<?php echo htmlspecialchars($report['image_filename']); ?>')">
                                                <?php else: ?>
                                                    <span class="no-image">No image</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-update" onclick="openUpdateModal(<?php echo $report['id']; ?>, '<?php echo addslashes($report['category']); ?>', '<?php echo addslashes($report['description']); ?>', <?php echo $report['latitude']; ?>, <?php echo $report['longitude']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                        Edit
                                                    </button>
                                                    <button class="btn-delete" onclick="confirmDelete(<?php echo $report['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="no-reports">
                                            <div class="empty-state">
                                                <i class="fas fa-inbox"></i>
                                                <h3>No Reports Yet</h3>
                                                <p>You haven't submitted any reports yet.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Update Report Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Update Report</h2>
                <span class="close" onclick="closeUpdateModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="updateForm">
                    <input type="hidden" id="update_report_id">
                    <input type="hidden" id="update_lat">
                    <input type="hidden" id="update_lng">
                    
                    <div class="form-group">
                        <label for="update_category">Issue Category</label>
                        <select class="form-control" id="update_category" name="category" required>
                            <option value="">Select a category</option>
                            <option value="Pothole">Pothole</option>
                            <option value="Graffiti">Graffiti</option>
                            <option value="Broken Streetlight">Broken Streetlight</option>
                            <option value="Trash">Trash Debris</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="update_description">Description</label>
                        <textarea class="form-control" id="update_description" name="description" rows="3" placeholder="Please describe the issue in detail..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="update_image">Update Photo (Optional)</label>
                        <input type="file" class="form-control" id="update_image" name="image" accept="image/*">
                        <small>Leave empty to keep current image</small>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="closeUpdateModal()">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-check"></i>
                            Update Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content image-modal">
            <div class="modal-header">
                <h2>Report Image</h2>
                <span class="close" onclick="closeImageModal()">&times;</span>
            </div>
            <div class="modal-body">
                <img id="modalImage" src="" alt="Report Image">
            </div>
        </div>
    </div>

    <script src="assets/js/report_history.js"></script>
</body>
</html>