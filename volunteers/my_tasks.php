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
$status = trim($_GET['status'] ?? 'all');
$error = trim($_GET['error'] ?? '');
$success = trim($_GET['success'] ?? '');
$allowedStatuses = ['Assigned', 'Accepted', 'In Progress', 'Completed', 'Verified', 'Rejected', 'Cancelled'];

$where = "WHERE vt.volunteer_user_id = ?";
$types = "i";
$values = [$userId];

if ($status !== 'all' && in_array($status, $allowedStatuses, true)) {
    $where .= " AND vt.status = ?";
    $types .= "s";
    $values[] = $status;
}

$stmt = $db->prepare("
    SELECT vt.*, r.category, r.description, r.municipality, r.status AS report_status, r.created_at AS report_created_at
    FROM volunteer_tasks vt
    INNER JOIN reports r ON r.id = vt.report_id
    $where
    ORDER BY vt.updated_at DESC
");
$stmt->bind_param($types, ...$values);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Volunteer Tasks - CivicConnect</title>
    <link rel="icon" href="/assets/images/BRP.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/user-dashboard.css">
    <link rel="stylesheet" href="/assets/css/mobile.css?v=3">
    <link rel="stylesheet" href="/assets/css/volunteer-module.css">
</head>
<body class="volunteer-shell">
<nav class="user-sidebar">
    <div class="sidebar-header"><div class="logo-container"><div class="gov-logo"><svg viewBox="0 0 24 24"><path d="M12,2L2,7L12,12L22,7L12,2M2,17L12,22L22,17V12L12,17L2,12V17Z" /></svg></div><div class="logo-text"><h2>Volunteer Portal</h2><span>CivicConnect</span></div></div></div>
    <ul class="sidebar-menu">
        <li class="menu-item"><a href="/town_issues/volunteers/dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
        <li class="menu-item active"><a href="/town_issues/volunteers/my_tasks.php"><i class="fas fa-clipboard-list"></i><span>My Tasks</span></a></li>
        <li class="menu-item"><a href="/town_issues/volunteers/profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
    </ul>
    <div class="sidebar-footer"><div class="user-info"><div class="user-avatar"><i class="fas fa-hands-helping"></i></div><div class="user-details"><span class="user-name"><?= h($_SESSION['full_name'] ?? 'Volunteer') ?></span><span class="user-role">Volunteer</span></div></div><a href="/town_issues/auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
</nav>

<main class="user-main volunteer-main">
    <header class="user-header">
        <div class="header-left">
            <button class="sidebar-toggle" aria-label="Open menu" aria-expanded="false"><i class="fas fa-bars"></i></button>
            <h1>My Tasks</h1>
        </div>
        <div class="volunteer-header-actions">
            <form method="GET" class="volunteer-form" style="display:flex; gap:8px; align-items:center;">
                <select name="status" onchange="this.form.submit()">
                    <option value="all">All Statuses</option>
                    <?php foreach ($allowedStatuses as $option): ?>
                        <option value="<?= h($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </header>

    <div class="volunteer-content">
        <?php if ($error): ?><div class="message-error"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="message-success"><?= h($success) ?></div><?php endif; ?>

        <div class="volunteer-card">
            <div class="volunteer-card-header">
                <h2><i class="fas fa-clipboard-check"></i> Assigned Work</h2>
                <span class="muted"><?= count($tasks) ?> task(s)</span>
            </div>
            <div class="volunteer-card-body">
                <?php if (empty($tasks)): ?>
                    <p class="muted">No tasks found for the selected status.</p>
                <?php else: ?>
                    <div class="volunteer-table-wrap">
                        <table class="volunteer-table">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Report</th>
                                    <th>Ward</th>
                                    <th>Task Status</th>
                                    <th>Report Status</th>
                                    <th>Assigned</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>#<?= (int)$task['id'] ?></td>
                                    <td><strong><?= h($task['category']) ?></strong><br><span class="muted"><?= h(truncateText($task['description'], 100)) ?></span></td>
                                    <td><?= h($task['municipality'] ?: '-') ?></td>
                                    <td><?= badge($task['status']) ?></td>
                                    <td><?= badge($task['report_status']) ?></td>
                                    <td><?= h(formatDateTime($task['assigned_at'])) ?></td>
                                    <td><a class="btn-volunteer" href="/town_issues/volunteers/task_view.php?id=<?= (int)$task['id'] ?>"><i class="fas fa-eye"></i> View</a></td>
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
<script src="/assets/js/theme-toggle.js"></script>
<script src="/assets/js/sidebar.js?v=3"></script>
</body>
</html>
