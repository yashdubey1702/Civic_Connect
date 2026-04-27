<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
    header("Location: forget_password.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if (empty($password) || empty($confirmPassword)) {
        $error = "All fields are required.";
    }

    elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    }

    elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    }

    else {

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $email = $_SESSION['reset_email'];

        $query = "UPDATE users SET password_hash = ? WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ss", $hash, $email);

        if ($stmt->execute()) {

            // delete used OTP
            $delete = "DELETE FROM password_resets WHERE email=?";
            $delStmt = $db->prepare($delete);
            $delStmt->bind_param("s", $email);
            $delStmt->execute();

            session_destroy();

            $success = "Password updated successfully! You can now login.";

        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CivicConnect – Reset Password</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="./assets/css/verify_account.css">
  <link href="./assets/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">   
  <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body>

 <!-- HEADER -->
<header class="app-header py-3  px-4 px-md-5 d-flex justify-content-between align-items-center">
    
      <div class="fs-3 d-flex align-items-center gap-2">
         <div class="gov-logo">
              <svg viewBox="0 0 24 24">
                <path d="M12,2L2,7L12,12L22,7L12,2M2,17L12,22L22,17V12L12,17L2,12V17Z" />
              </svg>
            </div>
        <strong class="text-white">CivicConnect</strong>
      </div>
      <div class="d-none d-sm-flex gap-3 small">
        <a href="login.php" class="fs-4 text-white fw-semibold text-decoration-none">Log In</a>
        <a href="index.html" class="fs-4 text-white text-decoration-none">Home Page</a>
      </div>
</header>

    <div class="container w-50 py-5  border">
    <div class="wrapper">
        <div class="otp">

        <h2>Reset Password</h2>
        <hr>

        <?php if(!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <a href="login.php" class="btn btn-primary mt-2">Go to Login</a>
        <?php endif; ?>
        
        <form method="POST">
        
        <div class="form-group">
        <label>New Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter new password" required>
        </div>
        
        <div class="form-group mt-3">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" required>
        </div>
        
        <div class="form-group mt-4">
        <input type="submit" name="resetPassword" class="btn btn-primary w-100" value="Reset Password">
        </div>
        
        </form>
        
        </div>
        </div>
    </div>
        


<!-- FOOTER -->
<footer class="app-footer text-center py-4">
  <div>© 2026 CivicConnect. All rights reserved.</div>
  <div class="mt-1">
    <a href="#" class="text-muted text-decoration-none me-3">Privacy Policy</a>
    <a href="#" class="text-muted text-decoration-none">Terms of Service</a>
  </div>
</footer>

</body>
</html>
