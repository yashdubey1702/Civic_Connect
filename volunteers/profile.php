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
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $wardNo = trim($_POST['ward_no'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $availability = trim($_POST['availability'] ?? '');

    if ($fullName === '') {
        $error = "Full name is required.";
    } else {
        $db->begin_transaction();

        try {
            $stmt = $db->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $stmt->bind_param("si", $fullName, $userId);
            $stmt->execute();
            $stmt->close();

            $stmt = $db->prepare("
                UPDATE volunteer_profiles
                SET full_name = ?, phone = ?, address = ?, ward_no = ?, skills = ?, availability = ?
                WHERE user_id = ?
            ");
            $stmt->bind_param("ssssssi", $fullName, $phone, $address, $wardNo, $skills, $availability, $userId);
            $stmt->execute();
            $stmt->close();

            $db->commit();
            $_SESSION['full_name'] = $fullName;
            $success = "Volunteer profile updated.";
            $profile = getVolunteerProfile($db, $userId);
        } catch (Throwable $e) {
            $db->rollback();
            $error = "Unable to update volunteer profile.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Profile - CivicConnect</title>
    <link rel="icon" href="../assets/images/BRP.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/user-dashboard.css">
    <link rel="stylesheet" href="../assets/css/mobile.css?v=3">
    <link rel="stylesheet" href="../assets/css/volunteer-module.css">
</head>
<body class="volunteer-shell">
<nav class="user-sidebar">
    <div class="sidebar-header"><div class="logo-container"><div class="gov-logo"><svg viewBox="0 0 24 24"><path d="M12,2L2,7L12,12L22,7L12,2M2,17L12,22L22,17V12L12,17L2,12V17Z" /></svg></div><div class="logo-text"><h2>Volunteer Portal</h2><span>CivicConnect</span></div></div></div>
    <ul class="sidebar-menu">
        <li class="menu-item"><a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
        <li class="menu-item"><a href="my_tasks.php"><i class="fas fa-clipboard-list"></i><span>My Tasks</span></a></li>
        <li class="menu-item active"><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
    </ul>
    <div class="sidebar-footer"><div class="user-info"><div class="user-avatar"><i class="fas fa-hands-helping"></i></div><div class="user-details"><span class="user-name"><?= h($_SESSION['full_name'] ?? 'Volunteer') ?></span><span class="user-role">Volunteer</span></div></div><a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
</nav>

<main class="user-main volunteer-main">
    <header class="user-header">
        <div class="header-left">
            <button class="sidebar-toggle" aria-label="Open menu" aria-expanded="false"><i class="fas fa-bars"></i></button>
            <h1>Volunteer Profile</h1>
        </div>
        <div class="volunteer-header-actions">
            <?= badge($profile['status']) ?>
        </div>
    </header>

    <div class="volunteer-content">
        <?php if ($error): ?><div class="message-error"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="message-success"><?= h($success) ?></div><?php endif; ?>

        <div class="volunteer-card">
            <div class="volunteer-card-header">
                <h2><i class="fas fa-id-card"></i> Profile Details</h2>
                <span class="muted">Admin approval controls task assignment.</span>
            </div>
            <div class="volunteer-card-body">
                <form method="POST" class="volunteer-form">
                    <div class="volunteer-form-grid">
                        <div>
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?= h($profile['full_name']) ?>" required>
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" value="<?= h($profile['email']) ?>" disabled>
                        </div>
                        <div>
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" value="<?= h($profile['phone']) ?>">
                        </div>
                        <div>
                            <label for="ward_no">Preferred Ward</label>
                            <input type="text" id="ward_no" name="ward_no" value="<?= h($profile['ward_no']) ?>" placeholder="Example: W8">
                        </div>
                        <div>
                            <label for="skills">Skills</label>
                            <input type="text" id="skills" name="skills" value="<?= h($profile['skills']) ?>">
                        </div>
                        <div>
                            <label for="availability">Availability</label>
                            <input type="text" id="availability" name="availability" value="<?= h($profile['availability']) ?>">
                        </div>
                        <div class="full-width">
                            <label for="address">Address</label>
                            <textarea id="address" name="address"><?= h($profile['address']) ?></textarea>
                        </div>
                    </div>
                    <div class="volunteer-actions" style="margin-top: 18px;">
                        <button class="btn-volunteer" type="submit"><i class="fas fa-save"></i> Save Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<script src="../assets/js/theme-toggle.js"></script>
<script src="../assets/js/sidebar.js?v=3"></script>
</body>
</html>
