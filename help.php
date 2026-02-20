<?php
session_start();
require_once 'config/database.php';
require_once 'config/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Require citizen authentication
$auth->requireAuth('citizen');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support – CivicConnect Bhubaneswar</title>
    <link rel="icon" href="assets/images/BPR.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/user-dashboard.css">
</head>
<body>

<!-- Sidebar -->
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
                <span>CivicConnect – Bhubaneswar</span>
            </div>
        </div>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-item">
            <a href="user_dashboard.php">
                <i class="fas fa-home"></i><span>Dashboard</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="map_reports.php">
                <i class="fas fa-map-marked-alt"></i><span>My Reports Map</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="report_history.php">
                <i class="fas fa-history"></i><span>Report History</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="profile.php">
                <i class="fas fa-user"></i><span>My Profile</span>
            </a>
        </li>
        <li class="menu-item active">
            <a href="help.php">
                <i class="fas fa-question-circle"></i><span>Help & Support</span>
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
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>
</nav>

<!-- Main -->
<main class="user-main">
    <header class="user-header">
        <div class="header-left">
            <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            <h1>Help & Support</h1>
        </div>
        <div class="header-right">
            <button class="new-report-btn" onclick="location.href='map_reports.php'">
                <i class="fas fa-plus"></i> New Report
            </button>
        </div>
    </header>

    <div class="dashboard-content">

        <div class="help-cards">
            <div class="help-card">
                <i class="fas fa-book"></i>
                <h3>User Guide</h3>
                <p>Learn how to report civic issues in Bhubaneswar using CivicConnect.</p>
                <button class="submit-btn">View Guide</button>
            </div>

            <div class="help-card">
                <i class="fas fa-envelope"></i>
                <h3>Contact Support</h3>
                <p>Reach Bhubaneswar Municipal Corporation support team.</p>
                <button class="submit-btn">Contact Us</button>
            </div>

            <div class="help-card">
                <i class="fas fa-info-circle"></i>
                <h3>About CivicConnect</h3>
                <p>Know more about the Bhubaneswar civic issue reporting platform.</p>
                <button class="submit-btn">Learn More</button>
            </div>
        </div>

        <div class="faq-section">
            <h3>Frequently Asked Questions</h3>

            <div class="faq-item">
                <div class="faq-question">
                    How do I submit a new report?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Click on “New Report” or select a location directly on the Bhubaneswar map.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    How long does it take to resolve issues?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Resolution time depends on issue type. Most civic issues are addressed within 7–21 days.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    Can I edit or delete my reports?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Yes. Reports can be edited or deleted before they are processed by authorities.
                </div>
            </div>
        </div>

    </div>
</main>

<script src="assets/js/help.js"></script>
</body>
</html>
