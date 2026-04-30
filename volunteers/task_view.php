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
$taskId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT vt.*, r.category, r.description, r.latitude, r.longitude, r.municipality, r.status AS report_status,
           r.image_filename, r.created_at AS report_created_at
    FROM volunteer_tasks vt
    INNER JOIN reports r ON r.id = vt.report_id
    WHERE vt.id = ? AND vt.volunteer_user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $taskId, $userId);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$task) {
    header("Location: /town_issues/volunteers/my_tasks.php?error=" . rawurlencode("Task not found or no longer assigned to you"));
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
    <title>Volunteer Task #<?= (int)$taskId ?> - CivicConnect</title>
    <link rel="icon" href="/town_issues/assets/images/BRP.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/town_issues/assets/css/user-dashboard.css">
    <link rel="stylesheet" href="/town_issues/assets/css/mobile.css?v=3">
    <link rel="stylesheet" href="/town_issues/assets/css/volunteer-module.css">
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
            <h1>Task #<?= (int)$taskId ?></h1>
        </div>
        <div class="volunteer-header-actions">
            <a class="btn-volunteer-secondary" href="/town_issues/volunteers/my_tasks.php"><i class="fas fa-arrow-left"></i> My Tasks</a>
            <?= badge($task['status']) ?>
        </div>
    </header>

    <div class="volunteer-content">
        <?php if ($error): ?><div class="message-error"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="message-success"><?= h($success) ?></div><?php endif; ?>

        <div class="volunteer-card">
            <div class="volunteer-card-header">
                <h2><i class="fas fa-file-alt"></i> Report Details</h2>
                <span>Report <?= badge($task['report_status']) ?></span>
            </div>
            <div class="volunteer-card-body">
                <div class="detail-list">
                    <div class="detail-item"><span>Category</span><?= h($task['category']) ?></div>
                    <div class="detail-item"><span>Ward</span><?= h($task['municipality'] ?: '-') ?></div>
                    <div class="detail-item"><span>Location</span><?= h($task['latitude']) ?>, <?= h($task['longitude']) ?></div>
                    <div class="detail-item"><span>Reported</span><?= h(formatDateTime($task['report_created_at'])) ?></div>
                    <div class="detail-item full-width"><span>Description</span><?= nl2br(h($task['description'])) ?></div>
                    <?php if (!empty($task['assigned_note'])): ?>
                        <div class="detail-item full-width"><span>Assignment Note</span><?= nl2br(h($task['assigned_note'])) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($task['admin_review_note'])): ?>
                        <div class="detail-item full-width"><span>Admin Review Note</span><?= nl2br(h($task['admin_review_note'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!in_array($task['status'], ['Completed', 'Verified', 'Cancelled'], true)): ?>
            <div class="volunteer-card">
                <div class="volunteer-card-header"><h2><i class="fas fa-play-circle"></i> Update Task</h2></div>
                <div class="volunteer-card-body">
                    <?php if ($task['status'] === 'Assigned'): ?>
                        <form method="POST" action="/town_issues/volunteers/task_action.php" class="volunteer-form">
                            <input type="hidden" name="task_id" value="<?= (int)$taskId ?>">
                            <input type="hidden" name="action" value="accept">
                            <button class="btn-volunteer" type="submit"><i class="fas fa-check"></i> Accept Task</button>
                        </form>
                    <?php elseif (in_array($task['status'], ['Accepted', 'Rejected'], true)): ?>
                        <form method="POST" action="/town_issues/volunteers/task_action.php" class="volunteer-form">
                            <input type="hidden" name="task_id" value="<?= (int)$taskId ?>">
                            <input type="hidden" name="action" value="start">
                            <button class="btn-volunteer" type="submit"><i class="fas fa-spinner"></i> Mark In Progress</button>
                        </form>
                    <?php elseif ($task['status'] === 'In Progress'): ?>
                        <form method="POST" action="/town_issues/volunteers/task_action.php" enctype="multipart/form-data" class="volunteer-form">
                            <input type="hidden" name="task_id" value="<?= (int)$taskId ?>">
                            <input type="hidden" name="action" value="complete">
                            <div class="volunteer-form-grid">
                                <div class="full-width">
                                    <label for="note">Completion Notes</label>
                                    <textarea name="note" id="note" required placeholder="Describe what was completed"></textarea>
                                </div>
                                <div class="full-width">
                                    <label for="proof_image">Proof Image</label>
                                    <input type="file" name="proof_image" id="proof_image" accept="image/jpeg,image/png,image/webp" <?= empty($task['proof_image']) ? 'required' : '' ?>>
                                </div>
                            </div>
                            <div class="volunteer-actions" style="margin-top: 16px;">
                                <button class="btn-volunteer-success" type="submit"><i class="fas fa-upload"></i> Submit Completion</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($task['completion_note']) || !empty($task['proof_image'])): ?>
            <div class="volunteer-card">
                <div class="volunteer-card-header"><h2><i class="fas fa-check-circle"></i> Completion Submission</h2></div>
                <div class="volunteer-card-body">
                    <?php if (!empty($task['completion_note'])): ?><p><?= nl2br(h($task['completion_note'])) ?></p><?php endif; ?>
                    <?php if (!empty($task['proof_image'])): ?><img class="proof-image" src="/town_issues/<?= h($task['proof_image']) ?>" alt="Volunteer proof image"><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="volunteer-card">
            <div class="volunteer-card-header"><h2><i class="fas fa-history"></i> Task History</h2></div>
            <div class="volunteer-card-body">
                <?php if (empty($updates)): ?>
                    <p class="muted">No updates yet.</p>
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
    </div>
</main>
<script src="/town_issues/assets/js/theme-toggle.js"></script>
<script src="/town_issues/assets/js/sidebar.js?v=3"></script>
</body>
</html>
