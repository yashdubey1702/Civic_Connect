<?php
session_start();
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Support/volunteer.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireAuth('any_admin');

// Open this page from an admin report row with:
// admin/assign_volunteer.php?report_id=REPORT_ID
$reportId = (int)($_GET['report_id'] ?? ($_POST['report_id'] ?? 0));
$error = '';

if ($reportId <= 0) {
    $error = "Report ID is required.";
}

$report = null;
if ($error === '') {
    if (!adminCanAccessReport($db, $auth, $reportId)) {
        $error = "You are not allowed to assign this report.";
    } else {
        $stmt = $db->prepare("
            SELECT id, category, description, latitude, longitude, municipality, status, email, created_at
            FROM reports
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $reportId);
        $stmt->execute();
        $report = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$report) {
            $error = "Report not found.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $report) {
    $volunteerUserId = (int)($_POST['volunteer_user_id'] ?? 0);
    $assignedNote = trim($_POST['assigned_note'] ?? '');

    if ($volunteerUserId <= 0) {
        $error = "Please select an approved volunteer.";
    } else {
        $volunteerSql = "
            SELECT vp.user_id
            FROM volunteer_profiles vp
            WHERE vp.user_id = ? AND vp.status = 'Approved'
        ";
        $volunteerTypes = "i";
        $volunteerValues = [$volunteerUserId];

        if (!empty($report['municipality'])) {
            $volunteerSql .= " AND UPPER(vp.ward_no) = ?";
            $volunteerTypes .= "s";
            $volunteerValues[] = strtoupper((string)$report['municipality']);
        }

        $volunteerSql .= " LIMIT 1";
        $stmt = $db->prepare($volunteerSql);
        $stmt->bind_param($volunteerTypes, ...$volunteerValues);
        $stmt->execute();
        $stmt->store_result();
        $volunteerOk = $stmt->num_rows === 1;
        $stmt->close();

        if (!$volunteerOk) {
            $error = "Selected volunteer is not approved for this report ward.";
        } else {
            $stmt = $db->prepare("
                SELECT id
                FROM volunteer_tasks
                WHERE report_id = ? AND status IN ('Assigned', 'Accepted', 'In Progress', 'Completed')
                LIMIT 1
            ");
            $stmt->bind_param("i", $reportId);
            $stmt->execute();
            $stmt->store_result();
            $hasActiveTask = $stmt->num_rows > 0;
            $stmt->close();

            if ($hasActiveTask) {
                $error = "This report already has an active volunteer assignment.";
            } else {
                $db->begin_transaction();

                try {
                    $adminId = (int)$_SESSION['user_id'];
                    $stmt = $db->prepare("
                        INSERT INTO volunteer_tasks
                            (report_id, volunteer_user_id, assigned_by, status, assigned_note, assigned_at)
                        VALUES (?, ?, ?, 'Assigned', ?, NOW())
                    ");
                    $stmt->bind_param("iiis", $reportId, $volunteerUserId, $adminId, $assignedNote);
                    if (!$stmt->execute()) {
                        throw new RuntimeException("Volunteer assignment failed.");
                    }

                    $taskId = $stmt->insert_id;
                    $stmt->close();

                    $stmt = $db->prepare("
                        INSERT INTO volunteer_task_updates
                            (task_id, user_id, old_status, new_status, note)
                        VALUES (?, ?, NULL, 'Assigned', ?)
                    ");
                    $noteForHistory = $assignedNote !== '' ? $assignedNote : null;
                    $stmt->bind_param("iis", $taskId, $adminId, $noteForHistory);
                    if (!$stmt->execute()) {
                        throw new RuntimeException("Volunteer update history failed.");
                    }
                    $stmt->close();

                    if ($report['status'] === 'Reported') {
                        $stmt = $db->prepare("UPDATE reports SET status = 'Acknowledged' WHERE id = ?");
                        $stmt->bind_param("i", $reportId);
                        if (!$stmt->execute()) {
                            throw new RuntimeException("Report acknowledgement failed.");
                        }
                        $stmt->close();
                    }

                    $db->commit();
                    header("Location: /town_issues/admin/volunteer_tasks.php");
                    exit;
                } catch (Throwable $e) {
                    $db->rollback();
                    $error = "Unable to assign volunteer. Please try again.";
                }
            }
        }
    }
}

$volunteers = [];
if ($report) {
    $volunteerSql = "
        SELECT vp.user_id, vp.full_name, vp.ward_no, vp.skills, u.email
        FROM volunteer_profiles vp
        INNER JOIN users u ON u.id = vp.user_id
        WHERE vp.status = 'Approved'
    ";
    $volunteerTypes = "";
    $volunteerValues = [];

    if (!empty($report['municipality'])) {
        $volunteerSql .= " AND UPPER(vp.ward_no) = ?";
        $volunteerTypes = "s";
        $volunteerValues[] = strtoupper((string)$report['municipality']);
    }

    $volunteerSql .= " ORDER BY vp.full_name ASC";
    $stmt = $db->prepare($volunteerSql);
    if ($volunteerTypes !== "") {
        $stmt->bind_param($volunteerTypes, ...$volunteerValues);
    }
    $stmt->execute();
    $volunteers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Volunteer - CivicConnect Admin</title>
    <link rel="icon" href="/town_issues/assets/images/BRP.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/town_issues/assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="/town_issues/assets/css/admin-mobile.css">
    <link rel="stylesheet" href="/town_issues/assets/css/volunteer-module.css">
</head>
<body>
<header class="gov-header">
    <div class="header-content">
        <div class="gov-brand"><div class="gov-logo"><svg viewBox="0 0 24 24"><path d="M12,2L2,7L12,12L22,7L12,2M2,17L12,22L22,17V12L12,17L2,12V17Z" /></svg></div><div class="gov-titles"><h1>CivicConnect</h1><p class="tagline">Assign Volunteer</p></div></div>
        <div class="dashboard-controls"><a href="/town_issues/admin/volunteers.php" class="logout-btn">Volunteers</a><a href="/town_issues/admin/volunteer_tasks.php" class="logout-btn">Volunteer Tasks</a><a href="/town_issues/auth/logout.php" class="logout-btn">Logout</a></div>
    </div>
</header>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Assign Volunteer</h1>
        <p class="dashboard-subtitle">Assign an approved volunteer to an existing civic issue report.</p>
    </div>

    <?php if ($error): ?><div class="message-error"><?= h($error) ?></div><?php endif; ?>

    <?php if ($report): ?>
        <div class="dashboard-layout">
            <div class="reports-section">
                <h2>Report Details</h2>
                <div class="detail-list">
                    <div class="detail-item"><span>Report ID</span>#<?= (int)$report['id'] ?></div>
                    <div class="detail-item"><span>Category</span><?= h($report['category']) ?></div>
                    <div class="detail-item"><span>Ward</span><?= h($report['municipality'] ?: '-') ?></div>
                    <div class="detail-item"><span>Status</span><?= badge($report['status']) ?></div>
                    <div class="detail-item"><span>Reported By</span><?= h($report['email'] ?: 'Anonymous') ?></div>
                    <div class="detail-item"><span>Reported On</span><?= h(formatDateTime($report['created_at'])) ?></div>
                    <div class="detail-item"><span>Location</span><?= h($report['latitude']) ?>, <?= h($report['longitude']) ?></div>
                    <div class="detail-item full-width"><span>Description</span><?= nl2br(h($report['description'])) ?></div>
                </div>
            </div>

            <div class="reports-section">
                <h2>Assignment</h2>
                <form method="POST" class="volunteer-form">
                    <input type="hidden" name="report_id" value="<?= (int)$report['id'] ?>">
                    <div class="volunteer-form-grid">
                        <div class="full-width">
                            <label for="volunteer_user_id">Approved Volunteer</label>
                            <select id="volunteer_user_id" name="volunteer_user_id" required>
                                <option value="">Select volunteer</option>
                                <?php foreach ($volunteers as $volunteer): ?>
                                    <option value="<?= (int)$volunteer['user_id'] ?>">
                                        <?= h($volunteer['full_name']) ?> - <?= h($volunteer['ward_no'] ?: 'No ward') ?> - <?= h(truncateText($volunteer['skills'], 60)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($volunteers)): ?>
                                <p class="muted" style="margin-top: 8px;">No approved volunteers are available.</p>
                            <?php endif; ?>
                        </div>
                        <div class="full-width">
                            <label for="assigned_note">Assignment Note</label>
                            <textarea id="assigned_note" name="assigned_note" placeholder="Add instructions for the volunteer"></textarea>
                        </div>
                    </div>
                    <div class="volunteer-actions" style="margin-top: 16px;">
                        <button class="btn-volunteer" type="submit" <?= empty($volunteers) ? 'disabled' : '' ?>>Assign Volunteer</button>
                        <a class="btn-volunteer-secondary" href="/town_issues/admin/volunteer_tasks.php">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<script src="/town_issues/assets/js/theme-toggle.js"></script>
</body>
</html>
