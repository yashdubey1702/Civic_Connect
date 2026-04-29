<?php
session_start();
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Support/volunteer.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireAuth('any_admin');
$adminDashboardUrl = $auth->isSuperAdmin() ? '../admin_dashboard.php' : '../municipal_admin_dashboard.php';

$allowedStatuses = ['Assigned', 'Accepted', 'In Progress', 'Completed', 'Verified', 'Rejected', 'Cancelled'];
$status = trim($_GET['status'] ?? 'all');
$wardNo = strtoupper(trim($_GET['ward_no'] ?? 'all'));
$volunteerUserId = (int)($_GET['volunteer'] ?? 0);
$error = trim($_GET['error'] ?? '');
$success = trim($_GET['success'] ?? '');

$where = ["1=1"];
$types = "";
$values = [];

if ($status !== 'all' && in_array($status, $allowedStatuses, true)) {
    $where[] = "vt.status = ?";
    $types .= "s";
    $values[] = $status;
}

if ($auth->isWardAdmin()) {
    $wardNo = strtoupper((string)$auth->getWard());
}

if ($wardNo !== 'all' && $wardNo !== '') {
    $where[] = "UPPER(COALESCE(NULLIF(r.municipality, ''), vp.ward_no)) = ?";
    $types .= "s";
    $values[] = $wardNo;
}

if ($volunteerUserId > 0) {
    $where[] = "vt.volunteer_user_id = ?";
    $types .= "i";
    $values[] = $volunteerUserId;
}

$whereClause = "WHERE " . implode(" AND ", $where);

$stmt = $db->prepare("
    SELECT
        vt.id,
        vt.report_id,
        vt.status,
        vt.assigned_at,
        vt.completed_at,
        r.category,
        r.description,
        r.municipality,
        r.latitude,
        r.longitude,
        r.status AS report_status,
        vp.full_name AS volunteer_name,
        vp.ward_no AS volunteer_ward_no,
        u.email AS volunteer_email,
        COALESCE(NULLIF(r.municipality, ''), vp.ward_no) AS task_ward
    FROM volunteer_tasks vt
    INNER JOIN reports r ON r.id = vt.report_id
    INNER JOIN volunteer_profiles vp ON vp.user_id = vt.volunteer_user_id
    INNER JOIN users u ON u.id = vt.volunteer_user_id
    $whereClause
    ORDER BY vt.updated_at DESC
");
if ($types !== "") {
    $stmt->bind_param($types, ...$values);
}
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$optionWhere = [];
$optionTypes = "";
$optionValues = [];

if ($auth->isWardAdmin()) {
    $optionWhere[] = "UPPER(COALESCE(NULLIF(r.municipality, ''), vp.ward_no)) = ?";
    $optionTypes .= "s";
    $optionValues[] = strtoupper((string)$auth->getWard());
}

$optionWhereClause = $optionWhere ? "WHERE " . implode(" AND ", $optionWhere) : "";

$stmt = $db->prepare("
    SELECT DISTINCT vt.volunteer_user_id, vp.full_name
    FROM volunteer_tasks vt
    INNER JOIN reports r ON r.id = vt.report_id
    INNER JOIN volunteer_profiles vp ON vp.user_id = vt.volunteer_user_id
    $optionWhereClause
    ORDER BY vp.full_name ASC
");
if ($optionTypes !== "") {
    $stmt->bind_param($optionTypes, ...$optionValues);
}
$stmt->execute();
$volunteerOptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$wardOptions = [];
if (!$auth->isWardAdmin()) {
    $stmt = $db->prepare("
        SELECT DISTINCT COALESCE(NULLIF(r.municipality, ''), vp.ward_no) AS ward_no
        FROM volunteer_tasks vt
        INNER JOIN reports r ON r.id = vt.report_id
        INNER JOIN volunteer_profiles vp ON vp.user_id = vt.volunteer_user_id
        WHERE COALESCE(NULLIF(r.municipality, ''), vp.ward_no) IS NOT NULL
          AND COALESCE(NULLIF(r.municipality, ''), vp.ward_no) <> ''
        ORDER BY ward_no ASC
    ");
    $stmt->execute();
    $wardOptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Tasks - CivicConnect Admin</title>
    <link rel="icon" href="../assets/images/BRP.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin-mobile.css">
    <link rel="stylesheet" href="../assets/css/volunteer-module.css">
</head>
<body>
<header class="gov-header">
    <div class="header-content">
        <div class="gov-brand"><div class="gov-logo"><svg viewBox="0 0 24 24"><path d="M12,2L2,7L12,12L22,7L12,2M2,17L12,22L22,17V12L12,17L2,12V17Z" /></svg></div><div class="gov-titles"><h1>CivicConnect</h1><p class="tagline">Volunteer Task Tracking</p></div></div>
        <div class="dashboard-controls"><a href="<?= h($adminDashboardUrl) ?>" class="logout-btn">Dashboard</a><a href="volunteers.php" class="logout-btn">Volunteers</a><a href="volunteer_tasks.php" class="logout-btn">Volunteer Tasks</a><a href="../logout.php" class="logout-btn">Logout</a></div>
    </div>
</header>

<div class="dashboard-container">
    <?php if ($error): ?><div class="message-error"><?= h($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="message-success"><?= h($success) ?></div><?php endif; ?>

    <div class="dashboard-header">
        <h1 class="dashboard-title">Volunteer Tasks</h1>
        <p class="dashboard-subtitle">Review completed work before resolving civic issue reports.</p>
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

            <div class="filter-group">
                <label for="ward_no">Ward</label>
                <?php if ($auth->isWardAdmin()): ?>
                    <input class="filter-select" id="ward_no" value="<?= h($wardNo) ?>" disabled>
                    <input type="hidden" name="ward_no" value="<?= h($wardNo) ?>">
                <?php else: ?>
                    <select class="filter-select" id="ward_no" name="ward_no">
                        <option value="all">All Wards</option>
                        <?php foreach ($wardOptions as $option): ?>
                            <?php $optionWard = strtoupper((string)$option['ward_no']); ?>
                            <option value="<?= h($optionWard) ?>" <?= $wardNo === $optionWard ? 'selected' : '' ?>><?= h($optionWard) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="filter-group">
                <label for="volunteer">Volunteer</label>
                <select class="filter-select" id="volunteer" name="volunteer">
                    <option value="0">All Volunteers</option>
                    <?php foreach ($volunteerOptions as $option): ?>
                        <option value="<?= (int)$option['volunteer_user_id'] ?>" <?= $volunteerUserId === (int)$option['volunteer_user_id'] ? 'selected' : '' ?>>
                            <?= h($option['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="refresh-btn" type="submit">Filter</button>
            <a class="btn-volunteer-secondary" href="volunteer_tasks.php">Reset</a>
        </form>
    </div>

    <div class="reports-section">
        <h2>Tasks</h2>
        <?php if (empty($tasks)): ?>
            <div class="empty-state">No volunteer tasks found.</div>
        <?php else: ?>
            <div class="volunteer-table-wrap">
                <table class="volunteer-table">
                    <thead>
                        <tr>
                            <th>Task ID</th>
                            <th>Report ID</th>
                            <th>Volunteer</th>
                            <th>Report Category</th>
                            <th>Ward / Location</th>
                            <th>Task Status</th>
                            <th>Assigned At</th>
                            <th>Completed At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td>#<?= (int)$task['id'] ?></td>
                            <td>#<?= (int)$task['report_id'] ?></td>
                            <td><?= h($task['volunteer_name']) ?><br><span class="muted"><?= h($task['volunteer_email']) ?></span></td>
                            <td><?= h($task['category']) ?></td>
                            <td>
                                <?= h($task['task_ward'] ?: '-') ?><br>
                                <span class="muted"><?= h($task['latitude']) ?>, <?= h($task['longitude']) ?></span>
                            </td>
                            <td><?= badge($task['status']) ?></td>
                            <td><?= h(formatDateTime($task['assigned_at'])) ?></td>
                            <td><?= h(formatDateTime($task['completed_at'])) ?></td>
                            <td><a class="btn-volunteer" href="review_volunteer_task.php?id=<?= (int)$task['id'] ?>">Review/View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="../assets/js/theme-toggle.js"></script>
</body>
</html>
