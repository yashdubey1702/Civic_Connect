<?php
session_start();
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Support/volunteer.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireAuth('volunteer');

$userId = (int)$_SESSION['user_id'];
$profile = ensureVolunteerProfile($db, $userId, $_SESSION['full_name'] ?? 'Volunteer');

$stats = [
    'Assigned' => 0,
    'Accepted' => 0,
    'In Progress' => 0,
    'Completed' => 0,
    'Verified' => 0,
    'Rejected' => 0
];

$stmt = $db->prepare("SELECT status, COUNT(*) total FROM volunteer_tasks WHERE volunteer_user_id = ? GROUP BY status");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (array_key_exists($row['status'], $stats)) {
        $stats[$row['status']] = (int)$row['total'];
    }
}
$stmt->close();

$stmt = $db->prepare("
    SELECT vt.id, vt.status, vt.assigned_at, r.category, r.description, r.municipality
    FROM volunteer_tasks vt
    INNER JOIN reports r ON r.id = vt.report_id
    WHERE vt.volunteer_user_id = ?
    ORDER BY vt.updated_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Dashboard - CivicConnect</title>
    <link rel="icon" href="../assets/images/BRP.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/user-dashboard.css">
    <link rel="stylesheet" href="../assets/css/mobile.css?v=3">
    <link rel="stylesheet" href="../assets/css/volunteer-module.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
</head>
<body class="volunteer-shell">
<nav class="user-sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <div class="gov-logo"><svg viewBox="0 0 24 24"><path d="M12,2L2,7L12,12L22,7L12,2M2,17L12,22L22,17V12L12,17L2,12V17Z" /></svg></div>
            <div class="logo-text"><h2>Volunteer Portal</h2><span>CivicConnect</span></div>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li class="menu-item active"><a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
        <li class="menu-item"><a href="my_tasks.php"><i class="fas fa-clipboard-list"></i><span>My Tasks</span></a></li>
        <li class="menu-item"><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
    </ul>
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><i class="fas fa-hands-helping"></i></div>
            <div class="user-details"><span class="user-name"><?= h($_SESSION['full_name'] ?? 'Volunteer') ?></span><span class="user-role">Volunteer</span></div>
        </div>
        <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</nav>

<main class="user-main volunteer-main">
    <header class="user-header">
        <div class="header-left">
            <button class="sidebar-toggle" aria-label="Open menu" aria-expanded="false"><i class="fas fa-bars"></i></button>
            <h1>Volunteer Dashboard</h1>
        </div>
        <div class="volunteer-header-actions">
            <div class="theme-toggle-container">
                <div class="theme-toggle" id="themeToggle">
                    <i class="fas fa-sun"></i>
                    <i class="fas fa-moon"></i>
                    <span class="toggle-thumb"></span>
                </div>
            </div>
            <button class="notification-btn" type="button" aria-label="Open notifications">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" hidden>0</span>
            </button>
            <?= badge($profile['status']) ?>
            <a class="btn-volunteer-secondary" href="profile.php"><i class="fas fa-user-edit"></i> Profile</a>
        </div>
    </header>

    <div class="volunteer-content">
        <div class="welcome-card">
            <div class="welcome-content">
                <h2>Welcome, <?= h($_SESSION['full_name'] ?? 'Volunteer') ?>!</h2>
                <p>Your volunteer account is <?= h($profile['status']) ?>. Approved volunteers can accept, update, and complete assigned civic tasks.</p>
                <div class="welcome-stats">
                    <div class="welcome-stat"><span class="stat-number"><?= array_sum($stats) ?></span><span class="stat-label">Total Tasks</span></div>
                    <div class="welcome-stat"><span class="stat-number"><?= (int)$stats['Verified'] ?></span><span class="stat-label">Verified</span></div>
                </div>
            </div>
            <div class="welcome-image"><i class="fas fa-hands-helping"></i></div>
        </div>

        <div class="volunteer-grid">
            <?php foreach ($stats as $label => $count): ?>
                <div class="volunteer-stat">
                    <strong><?= (int)$count ?></strong>
                    <span><?= h($label) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="volunteer-card" style="margin-top: 24px;">
            <div class="volunteer-card-header">
                <h2><i class="fas fa-clock"></i> Recent Tasks</h2>
                <a class="btn-volunteer" href="my_tasks.php"><i class="fas fa-list"></i> View All</a>
            </div>
            <div class="volunteer-card-body">
                <?php if (empty($recentTasks)): ?>
                    <p class="muted">No tasks have been assigned yet.</p>
                <?php else: ?>
                    <div class="volunteer-table-wrap">
                        <table class="volunteer-table">
                            <thead><tr><th>Task</th><th>Report</th><th>Ward</th><th>Status</th><th>Assigned</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($recentTasks as $task): ?>
                                <tr>
                                    <td>#<?= (int)$task['id'] ?></td>
                                    <td><strong><?= h($task['category']) ?></strong><br><span class="muted"><?= h(truncateText($task['description'], 80)) ?></span></td>
                                    <td><?= h($task['municipality'] ?: '-') ?></td>
                                    <td><?= badge($task['status']) ?></td>
                                    <td><?= h(formatDateTime($task['assigned_at'])) ?></td>
                                    <td><a class="btn-volunteer-secondary" href="task_view.php?id=<?= (int)$task['id'] ?>">Open</a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<script src="../assets/js/theme-toggle.js"></script>
<script src="../assets/js/notifications.js?v=1"></script>
<script src="../assets/js/sidebar.js?v=3"></script>
</body>
</html>
