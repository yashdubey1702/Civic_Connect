<?php
session_start();
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Support/volunteer.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireAuth('any_admin');
$adminDashboardUrl = $auth->isSuperAdmin() ? '/town_issues/admin/dashboard.php' : '/town_issues/admin/municipal_dashboard.php';

$status = trim($_GET['status'] ?? 'all');
$error = trim($_GET['error'] ?? '');
$success = trim($_GET['success'] ?? '');
$allowedStatuses = ['Pending', 'Approved', 'Rejected', 'Suspended'];
$where = ["1=1"];
$types = "";
$values = [];

if ($status !== 'all' && in_array($status, $allowedStatuses, true)) {
    $where[] = "vp.status = ?";
    $types .= "s";
    $values[] = $status;
}

if ($auth->isWardAdmin()) {
    $where[] = "UPPER(vp.ward_no) = ?";
    $types .= "s";
    $values[] = strtoupper((string)$auth->getWard());
}

$whereClause = "WHERE " . implode(" AND ", $where);

$stmt = $db->prepare("
    SELECT vp.*, u.email, u.created_at AS user_created_at
    FROM volunteer_profiles vp
    INNER JOIN users u ON u.id = vp.user_id
    $whereClause
    ORDER BY FIELD(vp.status, 'Pending', 'Approved', 'Rejected', 'Suspended'), vp.created_at DESC
");
if ($types !== "") {
    $stmt->bind_param($types, ...$values);
}
$stmt->execute();
$volunteers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$counts = array_fill_keys($allowedStatuses, 0);
$countSql = "SELECT status, COUNT(*) total FROM volunteer_profiles";
if ($auth->isWardAdmin()) {
    $countSql .= " WHERE UPPER(ward_no) = ?";
    $stmt = $db->prepare($countSql . " GROUP BY status");
    $ward = strtoupper((string)$auth->getWard());
    $stmt->bind_param("s", $ward);
} else {
    $stmt = $db->prepare($countSql . " GROUP BY status");
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $counts[$row['status']] = (int)$row['total'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteers - CivicConnect Admin</title>
    <link rel="icon" href="/assets/images/BRP.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="/assets/css/admin-mobile.css">
    <link rel="stylesheet" href="/assets/css/volunteer-module.css">
</head>
<body>
<header class="gov-header">
    <div class="header-content">
        <div class="gov-brand"><div class="gov-logo"><svg viewBox="0 0 24 24"><path d="M12,2L2,7L12,12L22,7L12,2M2,17L12,22L22,17V12L12,17L2,12V17Z" /></svg></div><div class="gov-titles"><h1>CivicConnect</h1><p class="tagline">Volunteer Administration</p></div></div>
        <div class="dashboard-controls"><span class="admin-welcome"><?= h($_SESSION['full_name'] ?? 'Admin') ?></span><a href="<?= h($adminDashboardUrl) ?>" class="logout-btn">Dashboard</a><a href="/town_issues/admin/volunteers.php" class="logout-btn">Volunteers</a><a href="/town_issues/admin/volunteer_tasks.php" class="logout-btn">Volunteer Tasks</a><a href="/town_issues/auth/logout.php" class="logout-btn">Logout</a></div>
    </div>
</header>

<div class="dashboard-container">
    <?php if ($error): ?><div class="message-error"><?= h($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="message-success"><?= h($success) ?></div><?php endif; ?>

    <div class="dashboard-header">
        <h1 class="dashboard-title">Volunteer Applications</h1>
        <p class="dashboard-subtitle">Approve volunteers, review profiles, and assign civic reports.</p>
    </div>

    <div class="dashboard-stats">
        <?php foreach ($counts as $label => $count): ?>
            <div class="stat-card"><div class="stat-number"><?= (int)$count ?></div><div class="stat-label"><?= h($label) ?></div></div>
        <?php endforeach; ?>
    </div>

    <div class="filter-controls">
        <form method="GET" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
            <div class="filter-group">
                <label for="status">Status</label>
                <select class="filter-select" id="status" name="status">
                    <option value="all">All Statuses</option>
                    <?php foreach ($allowedStatuses as $option): ?>
                        <option value="<?= h($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="refresh-btn" type="submit">Filter</button>
            <a class="refresh-btn" href="/town_issues/admin/volunteer_tasks.php" style="text-align:center; text-decoration:none;">Tasks</a>
        </form>
    </div>

    <div class="reports-section">
        <h2>Volunteer List</h2>
        <?php if (empty($volunteers)): ?>
            <div class="empty-state">No volunteers found.</div>
        <?php else: ?>
            <div class="volunteer-table-wrap">
                <table class="volunteer-table">
                    <thead><tr><th>Name</th><th>Email</th><th>Ward</th><th>Phone</th><th>Skills</th><th>Status</th><th>Applied</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($volunteers as $volunteer): ?>
                        <tr>
                            <td><?= h($volunteer['full_name']) ?></td>
                            <td><?= h($volunteer['email']) ?></td>
                            <td><?= h($volunteer['ward_no'] ?: '-') ?></td>
                            <td><?= h($volunteer['phone'] ?: '-') ?></td>
                            <td><?= h(truncateText($volunteer['skills'], 70)) ?></td>
                            <td><?= badge($volunteer['status']) ?></td>
                            <td><?= h(formatDateTime($volunteer['created_at'])) ?></td>
                            <td><a class="btn-volunteer" href="/town_issues/admin/volunteer_view.php?id=<?= (int)$volunteer['id'] ?>">Review</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="/assets/js/theme-toggle.js"></script>
</body>
</html>
