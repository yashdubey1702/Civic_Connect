<?php
session_start();
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Support/volunteer.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireAuth('any_admin');

$taskId = (int)($_GET['id'] ?? ($_POST['task_id'] ?? 0));
if ($taskId <= 0 || !adminCanAccessTask($db, $auth, $taskId)) {
    header("Location: /town_issues/admin/volunteer_tasks.php?error=" . rawurlencode("Task not found or access denied"));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    $reviewNote = trim($_POST['admin_review_note'] ?? '');

    $stmt = $db->prepare("SELECT * FROM volunteer_tasks WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $task = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$task) {
        header("Location: /town_issues/admin/volunteer_tasks.php?error=" . rawurlencode("Task not found"));
        exit;
    }

    $newStatus = null;
    $timeSql = "";

    if ($action === 'verify' && $task && $task['status'] === 'Completed') {
        $newStatus = 'Verified';
        $timeSql = ", verified_at = NOW()";
    } elseif ($action === 'reject' && $task && $task['status'] === 'Completed') {
        $newStatus = 'Rejected';
    }

    if ($newStatus === null) {
        header("Location: /town_issues/admin/review_volunteer_task.php?id={$taskId}&error=" . rawurlencode("Invalid review action for this task status"));
        exit;
    } else {
        $db->begin_transaction();

        try {
            $stmt = $db->prepare("
                UPDATE volunteer_tasks
                SET status = ?, admin_review_note = ? $timeSql
                WHERE id = ?
            ");
            $stmt->bind_param("ssi", $newStatus, $reviewNote, $taskId);
            if (!$stmt->execute()) {
                throw new RuntimeException("Review update failed.");
            }
            $stmt->close();

            addVolunteerTaskUpdate($db, $taskId, (int)$_SESSION['user_id'], $task['status'], $newStatus, $reviewNote ?: null, null);

            if ($newStatus === 'Verified') {
                $reportId = (int)$task['report_id'];
                $stmt = $db->prepare("UPDATE reports SET status = 'Resolved' WHERE id = ?");
                $stmt->bind_param("i", $reportId);
                if (!$stmt->execute()) {
                    throw new RuntimeException("Report resolution failed.");
                }
                $stmt->close();
            }

            $db->commit();
            header("Location: /town_issues/admin/review_volunteer_task.php?id={$taskId}&success=" . rawurlencode("Task review saved"));
            exit;
        } catch (Throwable $e) {
            $db->rollback();
            header("Location: /town_issues/admin/review_volunteer_task.php?id={$taskId}&error=" . rawurlencode("Unable to save review"));
            exit;
        }
    }
}

$stmt = $db->prepare("
    SELECT vt.*, r.category, r.description, r.latitude, r.longitude, r.municipality, r.status AS report_status,
           r.image_filename, r.created_at AS report_created_at,
           vp.full_name AS volunteer_name, vp.phone AS volunteer_phone, vp.ward_no AS volunteer_ward_no,
           vp.skills AS volunteer_skills, vp.availability AS volunteer_availability, u.email AS volunteer_email,
           admin.full_name AS assigned_by_name
    FROM volunteer_tasks vt
    INNER JOIN reports r ON r.id = vt.report_id
    INNER JOIN volunteer_profiles vp ON vp.user_id = vt.volunteer_user_id
    INNER JOIN users u ON u.id = vt.volunteer_user_id
    INNER JOIN users admin ON admin.id = vt.assigned_by
    WHERE vt.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $taskId);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$task) {
    header("Location: /town_issues/admin/volunteer_tasks.php?error=" . rawurlencode("Task not found"));
    exit;
}

$stmt = $db->prepare("
    SELECT vtu.*, u.full_name
    FROM volunteer_task_updates vtu
    INNER JOIN users u ON u.id = vtu.user_id
    WHERE vtu.task_id = ?
    ORDER BY vtu.created_at DESC
");
$stmt->bind_param("i", $taskId);
$stmt->execute();
$updates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$error = trim($_GET['error'] ?? '');
$success = trim($_GET['success'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Volunteer Task - CivicConnect Admin</title>
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
        <div class="gov-brand"><div class="gov-logo"><svg viewBox="0 0 24 24"><path d="M12,2L2,7L12,12L22,7L12,2M2,17L12,22L22,17V12L12,17L2,12V17Z" /></svg></div><div class="gov-titles"><h1>CivicConnect</h1><p class="tagline">Volunteer Task Review</p></div></div>
        <div class="dashboard-controls"><a href="/town_issues/admin/volunteers.php" class="logout-btn">Volunteers</a><a href="/town_issues/admin/volunteer_tasks.php" class="logout-btn">Volunteer Tasks</a><a href="/town_issues/auth/logout.php" class="logout-btn">Logout</a></div>
    </div>
</header>

<div class="dashboard-container">
    <?php if ($error): ?><div class="message-error"><?= h($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="message-success"><?= h($success) ?></div><?php endif; ?>

    <div class="dashboard-header">
        <h1 class="dashboard-title">Task #<?= (int)$task['id'] ?></h1>
        <p class="dashboard-subtitle"><?= h($task['category']) ?> &middot; Task <?= badge($task['status']) ?> &middot; Report <?= badge($task['report_status']) ?></p>
    </div>

    <div class="dashboard-layout">
        <div class="reports-section">
            <h2>Report and Volunteer</h2>
            <div class="detail-list">
                <div class="detail-item"><span>Report ID</span>#<?= (int)$task['report_id'] ?></div>
                <div class="detail-item"><span>Report Category</span><?= h($task['category']) ?></div>
                <div class="detail-item"><span>Report Status</span><?= badge($task['report_status']) ?></div>
                <div class="detail-item"><span>Ward</span><?= h($task['municipality'] ?: '-') ?></div>
                <div class="detail-item"><span>Location</span><?= h($task['latitude']) ?>, <?= h($task['longitude']) ?></div>
                <div class="detail-item"><span>Volunteer</span><?= h($task['volunteer_name']) ?></div>
                <div class="detail-item"><span>Contact</span><?= h($task['volunteer_phone'] ?: $task['volunteer_email']) ?></div>
                <div class="detail-item"><span>Volunteer Ward</span><?= h($task['volunteer_ward_no'] ?: '-') ?></div>
                <div class="detail-item"><span>Availability</span><?= h($task['volunteer_availability'] ?: '-') ?></div>
                <div class="detail-item"><span>Assigned At</span><?= h(formatDateTime($task['assigned_at'])) ?></div>
                <div class="detail-item"><span>Completed At</span><?= h(formatDateTime($task['completed_at'])) ?></div>
                <div class="detail-item full-width"><span>Volunteer Skills</span><?= nl2br(h($task['volunteer_skills'] ?: '-')) ?></div>
                <div class="detail-item full-width"><span>Description</span><?= nl2br(h($task['description'])) ?></div>
                <div class="detail-item full-width"><span>Assignment Note</span><?= nl2br(h($task['assigned_note'] ?: '-')) ?></div>
            </div>
        </div>

        <div class="reports-section">
            <h2>Review Completion</h2>
            <?php if (!empty($task['completion_note'])): ?>
                <p><?= nl2br(h($task['completion_note'])) ?></p>
            <?php else: ?>
                <p class="muted">No completion note submitted yet.</p>
            <?php endif; ?>

            <?php if (!empty($task['proof_image'])): ?>
                <p><img class="proof-image" src="/<?= h($task['proof_image']) ?>" alt="Volunteer proof image"></p>
            <?php else: ?>
                <p class="muted">No proof image submitted.</p>
            <?php endif; ?>

            <?php if ($task['status'] === 'Completed'): ?>
                <form method="POST" class="volunteer-form">
                    <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                    <label for="admin_review_note">Review Note</label>
                    <textarea id="admin_review_note" name="admin_review_note"><?= h($task['admin_review_note']) ?></textarea>
                    <div class="volunteer-actions" style="margin-top: 16px;">
                        <button class="btn-volunteer-success" name="action" value="verify" type="submit">Verify Completion</button>
                        <button class="btn-volunteer-danger" name="action" value="reject" type="submit">Reject Proof</button>
                    </div>
                </form>
            <?php elseif (!empty($task['admin_review_note'])): ?>
                <div class="detail-item full-width"><span>Admin Review Note</span><?= nl2br(h($task['admin_review_note'])) ?></div>
            <?php else: ?>
                <p class="muted">Review actions are available after the volunteer marks the task Completed.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="reports-section" style="margin-top: 24px;">
        <h2>Task Updates</h2>
        <?php if (empty($updates)): ?>
            <div class="empty-state">No updates recorded.</div>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($updates as $update): ?>
                    <div class="timeline-item">
                        <strong><?= h($update['old_status'] ?: 'Created') ?> &rarr; <?= h($update['new_status']) ?></strong>
                        <span class="muted"><?= h($update['full_name']) ?> on <?= h(formatDateTime($update['created_at'])) ?></span>
                        <?php if (!empty($update['note'])): ?><p><?= nl2br(h($update['note'])) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="/assets/js/theme-toggle.js"></script>
</body>
</html>
