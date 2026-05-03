<?php
session_start();
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Support/volunteer.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireAuth('any_admin');

$profileId = (int)($_GET['id'] ?? 0);
if ($profileId <= 0 || !adminCanAccessVolunteer($db, $auth, $profileId)) {
    header("Location: /town_issues/admin/volunteers.php?error=" . rawurlencode("Volunteer profile not found or access denied"));
    exit;
}

$stmt = $db->prepare("
    SELECT vp.*, u.email, u.is_active, approver.full_name AS approved_by_name
    FROM volunteer_profiles vp
    INNER JOIN users u ON u.id = vp.user_id
    LEFT JOIN users approver ON approver.id = vp.approved_by
    WHERE vp.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $profileId);
$stmt->execute();
$volunteer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$volunteer) {
    header("Location: /town_issues/admin/volunteers.php?error=" . rawurlencode("Volunteer profile not found"));
    exit;
}

$stmt = $db->prepare("
    SELECT vt.id, vt.status, vt.assigned_at, vt.completed_at, vt.verified_at, r.category, r.municipality
    FROM volunteer_tasks vt
    INNER JOIN reports r ON r.id = vt.report_id
    WHERE vt.volunteer_user_id = ?
    ORDER BY vt.updated_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $volunteer['user_id']);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$success = trim($_GET['success'] ?? '');
$error = trim($_GET['error'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Review - CivicConnect Admin</title>
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
        <div class="dashboard-controls"><a href="/town_issues/admin/volunteers.php" class="logout-btn">Volunteers</a><a href="/town_issues/admin/volunteer_tasks.php" class="logout-btn">Volunteer Tasks</a><a href="/town_issues/auth/logout.php" class="logout-btn">Logout</a></div>
    </div>
</header>

<div class="dashboard-container">
    <?php if ($error): ?><div class="message-error"><?= h($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="message-success"><?= h($success) ?></div><?php endif; ?>

    <div class="dashboard-header">
        <h1 class="dashboard-title"><?= h($volunteer['full_name']) ?></h1>
        <p class="dashboard-subtitle"><?= h($volunteer['email']) ?> &middot; <?= badge($volunteer['status']) ?></p>
    </div>

    <div class="dashboard-layout">
        <div class="reports-section">
            <h2>Profile</h2>
            <div class="detail-list">
                <div class="detail-item"><span>Phone</span><?= h($volunteer['phone'] ?: '-') ?></div>
                <div class="detail-item"><span>Ward</span><?= h($volunteer['ward_no'] ?: '-') ?></div>
                <div class="detail-item"><span>Availability</span><?= h($volunteer['availability'] ?: '-') ?></div>
                <div class="detail-item"><span>Approved By</span><?= h($volunteer['approved_by_name'] ?: '-') ?></div>
                <div class="detail-item full-width"><span>Skills</span><?= nl2br(h($volunteer['skills'] ?: '-')) ?></div>
                <div class="detail-item full-width"><span>Address</span><?= nl2br(h($volunteer['address'] ?: '-')) ?></div>
                <div class="detail-item full-width"><span>Admin Note</span><?= nl2br(h($volunteer['admin_note'] ?: '-')) ?></div>
            </div>
        </div>

        <div class="reports-section">
            <h2>Review Action</h2>
            <form method="POST" action="/town_issues/admin/volunteer_action.php" class="volunteer-form">
                <input type="hidden" name="profile_id" value="<?= (int)$volunteer['id'] ?>">
                <label for="admin_note">Admin Note</label>
                <textarea id="admin_note" name="admin_note"><?= h($volunteer['admin_note']) ?></textarea>
                <div class="volunteer-actions" style="margin-top: 16px;">
                    <button class="btn-volunteer-success" name="action" value="approve" type="submit">Approve</button>
                    <button class="btn-volunteer-danger" name="action" value="reject" type="submit">Reject</button>
                    <button class="btn-volunteer-secondary" name="action" value="suspend" type="submit">Suspend</button>
                    <button class="btn-volunteer" name="action" value="restore" type="submit">Restore</button>
                </div>
            </form>
        </div>
    </div>

    <div class="reports-section" style="margin-top: 24px;">
        <h2>Recent Tasks</h2>
        <?php if (empty($tasks)): ?>
            <div class="empty-state">No tasks assigned yet.</div>
        <?php else: ?>
            <div class="volunteer-table-wrap">
                <table class="volunteer-table">
                    <thead><tr><th>Task</th><th>Report</th><th>Ward</th><th>Status</th><th>Assigned</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td>#<?= (int)$task['id'] ?></td>
                            <td><?= h($task['category']) ?></td>
                            <td><?= h($task['municipality'] ?: '-') ?></td>
                            <td><?= badge($task['status']) ?></td>
                            <td><?= h(formatDateTime($task['assigned_at'])) ?></td>
                            <td><a class="btn-volunteer-secondary" href="/town_issues/admin/review_volunteer_task.php?id=<?= (int)$task['id'] ?>">Open</a></td>
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
