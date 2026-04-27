<?php

session_start();
require_once 'config/database.php';
require_once 'config/mail.php';
require_once 'config/password_reset.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email !== '') {
        $_SESSION['reset_email'] = $email;

        $otp = issuePasswordResetOtp($db, $email);
        if ($otp !== null) {
            sendOTP($email, $otp);
        }

        header("Location: verify_account.php");
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CivicConnect – Forgot Password</title>
<link href="./assets/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="./assets/css/forget_password.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">   
<link rel="stylesheet" href="assets/css/auth.css">
</head>

<body>
<!-- HEADER -->
<header class="app-header py-3 px-4 px-md-5 d-flex justify-content-between align-items-center">
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

<!-- MAIN -->
<main class="d-flex flex-grow-1 align-items-center justify-content-center px-3 px-sm-4 py-5">
  <div class="reset-card bg-white w-100" style="max-width:420px;">
    <div class="p-4 p-md-5">

      <!-- ICON -->
      <div class="d-flex justify-content-center mb-4">
        <div class="icon-circle">
          <i class="bi bi-lock fs-3"></i>
        </div>
      </div>

      <!-- TEXT -->
      <div class="text-center mb-4">
        <h1 class="fw-bold mb-2">Forgot Password?</h1>
        <p class="text-muted mb-0">
          No worries, we'll send you reset instructions.<br>
          Please enter the email associated with your CivicConnect account.
        </p>
      </div>

      <!-- FORM -->
      <form method="POST">
        <div class="mb-4 position-relative">
          <label class="form-label fw-semibold">Email Address</label>
          <i class="bi bi-envelope input-icon"></i>
          <input
            type="email"
            name="email"
            class="form-control"
            placeholder="e.g., citizen@example.com"
            required
          >
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
          Send Reset Instructions
        </button>
      </form>

      <!-- BACK -->
      <div class="text-center mt-4">
        <a href="login.php" class="text-muted fw-semibold text-decoration-none d-inline-flex align-items-center gap-1">
          <i class="bi bi-arrow-left"></i>
          Back to log in
        </a>
      </div>

    </div>
  </div>
</main>

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
