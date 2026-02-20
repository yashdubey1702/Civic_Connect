<?php
session_start();
require_once 'config/database.php';
require_once 'config/Auth.php';

$database = new Database();
$db = $database->getConnection(); 
$auth = new Auth($db);

// Require citizen authentication
$auth->requireAuth('citizen');

// Initialize variables
$error = '';
$success = '';
$user_details = [];
$is_editing = isset($_GET['edit']) && $_GET['edit'] === 'true';
$show_password_form = isset($_GET['action']) && $_GET['action'] === 'change-password';

//   GET USER DETAILS

$query = "SELECT full_name, email, created_at, user_type FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_details = $result->fetch_assoc();
$stmt->close();

//   UPDATE PROFILE

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_submit'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    if (empty($full_name)) {
        $error = "Full name is required.";
    } elseif (empty($email)) {
        $error = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check duplicate email
        $checkQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bind_param("si", $email, $_SESSION['user_id']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $error = "Email already exists. Please use a different email.";
        } else {
            // Update profile
            $updateQuery = "UPDATE users SET full_name = ?, email = ? WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bind_param("ssi", $full_name, $email, $_SESSION['user_id']);

            if ($updateStmt->execute()) {
                $success = "Profile updated successfully!";
                $is_editing = false;

                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;

                // Refresh user details
                $stmt = $db->prepare("SELECT full_name, email, created_at, user_type FROM users WHERE id = ?");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $user_details = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } else {
                $error = "Failed to update profile. Please try again.";
            }

            $updateStmt->close();
        }

        $checkStmt->close();
    }
}

//   CHANGE PASSWORD
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_submit'])) {
    $currentPassword = trim($_POST['current_password']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "All fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match.";
    } elseif (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Get current password hash
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = "User not found.";
        } elseif (!password_verify($currentPassword, $user['password_hash'])) {
            $error = "Current password is incorrect.";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $updateStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $updateStmt->bind_param("si", $hashedPassword, $_SESSION['user_id']);

            if ($updateStmt->execute()) {
                $success = "Password changed successfully!";
                $show_password_form = false;

                $_POST['current_password'] = '';
                $_POST['new_password'] = '';
                $_POST['confirm_password'] = '';
            } else {
                $error = "Failed to update password. Please try again.";
            }

            $updateStmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Municipal Issue Reporting System</title>
    <link rel="icon" href="assets/images/BPR.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/user-dashboard.css">
</head>
<body>
    <!-- Navigation Sidebar -->
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
                    <span>Issue Reporting System</span>
                </div>
            </div>
        </div>
        
        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="user_dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="map_reports.php">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>My Reports Map</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="report_history.php">
                    <i class="fas fa-history"></i>
                    <span>Report History</span>
                </a>
            </li>
            <li class="menu-item active">
                <a href="profile.php">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="help.php">
                    <i class="fas fa-question-circle"></i>
                    <span>Help & Support</span>
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
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="user-main">
        <!-- Header -->
        <header class="user-header">
            <div class="header-left">
                <button class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>My Profile</h1>
            </div>
            <div class="header-right">
                <div class="theme-toggle-container">
                    <div class="theme-toggle" id="themeToggle">
                        <i class="fas fa-sun"></i>
                        <i class="fas fa-moon"></i>
                        <span class="toggle-thumb"></span>
                    </div>
                </div>
                <button class="new-report-btn" onclick="location.href='map_reports.php'">
                    <i class="fas fa-plus"></i>
                    New Report
                </button>
                <div class="header-actions">
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">2</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Profile Content -->
        <div class="dashboard-content">
            <?php if (!empty($error)): ?>
                <div class="message-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="message-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-user"></i> My Profile</h2>
                    <?php if (!$is_editing && !$show_password_form): ?>
                        <button class="edit-profile-btn" onclick="enableEditMode()">
                            <i class="fas fa-edit"></i>
                            Edit Profile
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <!-- Profile Info -->
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($user_details['full_name']); ?></h2>
                            <p>Citizen User • 
                                <?php if ($show_password_form): ?>
                                    <a href="profile.php" class="change-password-link">Back to Profile</a>
                                <?php else: ?>
                                    <a href="profile.php?action=change-password" class="change-password-link">Change Password</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Profile View Mode (shown when not editing) -->
                    <div class="profile-view" id="profileView" <?php echo $is_editing ? 'style="display: none;"' : ''; ?>>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name</label>
                                <div class="profile-field-view">
                                    <?php echo htmlspecialchars($user_details['full_name']); ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <div class="profile-field-view">
                                    <?php echo htmlspecialchars($user_details['email']); ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>User Type</label>
                                <div class="profile-field-view">
                                    <?php echo htmlspecialchars($user_details['user_type']); ?>
                                </div>
                                <small class="form-text">User type cannot be changed</small>
                            </div>
                            <div class="form-group">
                                <label>Member Since</label>
                                <div class="profile-field-view">
                                    <?php echo date('F j, Y', strtotime($user_details['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile Edit Form (shown when editing) -->
                    <form method="POST" action="profile.php" class="profile-form" id="profileForm" <?php echo !$is_editing ? 'style="display: none;"' : ''; ?>>
                        <input type="hidden" name="profile_submit" value="1">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user_details['full_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user_details['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="user_type">User Type</label>
                                <div class="profile-field-view">
                                    <?php echo htmlspecialchars($user_details['user_type']); ?>
                                </div>
                                <small class="form-text">User type cannot be changed</small>
                            </div>
                            <div class="form-group">
                                <label for="member_since">Member Since</label>
                                <div class="profile-field-view">
                                    <?php echo date('F j, Y', strtotime($user_details['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-cancel" onclick="disableEditMode()">
                                <i class="fas fa-times"></i>
                                Cancel
                            </button>
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-save"></i>
                                Save Changes
                            </button>
                        </div>
                    </form>
                    
                    <!-- Password Form (shown when change password is clicked) -->
                    <form method="POST" action="profile.php" class="password-form" id="passwordForm" <?php echo !$show_password_form ? 'style="display: none;"' : ''; ?>>
                        <input type="hidden" name="password_submit" value="1">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <div class="input-with-icon password-field">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" class="form-control" id="current_password" name="current_password" 
                                           placeholder="Enter your current password" required>
                                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('current_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <div class="input-with-icon password-field">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" class="form-control" id="new_password" name="new_password" 
                                           placeholder="Enter your new password" required>
                                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('new_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <div class="input-with-icon password-field">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           placeholder="Confirm your new password" required>
                                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-cancel" onclick="showProfileForm()">
                                <i class="fas fa-times"></i>
                                Cancel
                            </button>
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-key"></i>
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="assets/js/profile.js"></script>
</body>
</html>