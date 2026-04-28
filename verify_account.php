<?php

session_start();
require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/app/Support/password_reset.php';

if(!isset($_SESSION['reset_email'])){
    header("Location: forget_password.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$maskedEmail = maskResetEmail($_SESSION['reset_email']);
$error = "";
$notice = $_SESSION['reset_notice'] ?? "";
$noticeType = $_SESSION['reset_notice_type'] ?? "success";
unset($_SESSION['reset_notice']);
unset($_SESSION['reset_notice_type']);
$remainingSeconds = RESET_OTP_TTL_MINUTES * 60;

function getLatestPasswordReset($db, $email) {
    $query = "SELECT otp, expires_at,
              GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), expires_at)) AS remaining_seconds
              FROM password_resets
              WHERE email = ?
              ORDER BY created_at DESC
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

$latestReset = getLatestPasswordReset($db, $_SESSION['reset_email']);
if ($latestReset && !empty($latestReset['expires_at'])) {
    $remainingSeconds = (int)$latestReset['remaining_seconds'];
}

if($_SERVER['REQUEST_METHOD']=='POST'){

$email = $_SESSION['reset_email'];
$otpParts = $_POST['otp'] ?? [];
$otp = preg_replace('/\D/', '', is_array($otpParts) ? implode("", $otpParts) : (string)$otpParts);
$row = getLatestPasswordReset($db, $email);

if(strlen($otp) !== 6){

    $error = "Enter the 6-digit verification code.";

} elseif($row){

    if(hash_equals((string)$row['otp'], $otp) && (int)$row['remaining_seconds'] > 0){

        $_SESSION['otp_verified'] = true;

        header("Location: reset_password.php");
        exit;

    } else {

        $error = "Invalid or expired verification code.";

    }

} else {

    $error = "Invalid or expired verification code.";

}

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CivicConnect – Verify Account</title>

  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="./assets/css/verify_account.css">
  <link href="./assets/bootstrap.min.css" rel="stylesheet">
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
  <main class="d-flex align-items-center justify-content-center py-5 px-3 position-relative">

    <!-- Decorative blobs -->
    <div class="position-absolute top-0 start-0 translate-middle bg-primary bg-opacity-10 rounded-circle"
      style="width:420px;height:420px;filter:blur(120px);"></div>
    <div class="position-absolute top-50 end-0 translate-middle bg-primary bg-opacity-10 rounded-circle"
      style="width:360px;height:360px;filter:blur(120px);"></div>

    <div class="verify-card bg-white w-100 position-relative" style="max-width:420px; z-index:1;">

      <!-- Progress -->
      <div class="progress-bar-top">
        <div class="progress-bar-fill"></div>
      </div>

      <div class="p-4 p-md-5">

  <!-- Icon -->
  <div class="text-center mb-3">
    <div class="icon-circle mx-auto mb-2">
      <i class="bi bi-envelope-check fs-3"></i>
    </div>
    <h2 class="fw-bold mb-1">Check your email</h2>
    <p class="text-muted mb-1">
      If an account exists, a 6-digit verification code was sent to<br>
      <strong class="text-dark"><?php echo htmlspecialchars($maskedEmail); ?></strong>
    </p>
    <a href="forget_password.php" class="text-primary small fw-semibold text-decoration-none">
      Change email address
    </a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger text-center py-2">
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($notice)): ?>
    <div class="alert alert-<?php echo $noticeType === 'danger' ? 'danger' : 'success'; ?> text-center py-2">
      <?php echo htmlspecialchars($notice); ?>
    </div>
  <?php endif; ?>

  <!-- OTP -->
  <form method="POST" id="otp-form" novalidate>

    <div class="d-flex justify-content-center gap-2 my-4 otp-group" aria-label="6-digit verification code">
      <input class="otp-input" name="otp[]" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" maxlength="1" aria-label="Digit 1" required autofocus>
      <input class="otp-input" name="otp[]" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" maxlength="1" aria-label="Digit 2" required>
      <input class="otp-input" name="otp[]" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" maxlength="1" aria-label="Digit 3" required>
      <input class="otp-input" name="otp[]" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" maxlength="1" aria-label="Digit 4" required>
      <input class="otp-input" name="otp[]" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" maxlength="1" aria-label="Digit 5" required>
      <input class="otp-input" name="otp[]" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" maxlength="1" aria-label="Digit 6" required>
    </div>

    <!-- Timer -->
    <div class="text-center small text-muted mb-2">
      <i class="bi bi-clock me-1"></i>
      Code expires in 
      <span id="otp-timer" class="fw-semibold text-dark">--:--</span>
    </div>

    <div class="text-center small text-muted mb-4">
      Didn't receive the code?
      <a href="resend_otp.php" id="resend-btn" class="fw-bold text-primary text-decoration-none" style="pointer-events:none; opacity:0.5;">
      Resend
      </a>
    </div>

    <!-- Button -->
    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 d-flex align-items-center justify-content-center gap-2">
      Verify Account
      <i class="bi bi-arrow-right"></i>
    </button>

  </form>

</div>

      <!-- Secure footer -->
      <div class="card-footer-secure py-3 text-center border-top">
        <i class="bi bi-lock me-1"></i>
        Secure 256-bit encrypted verification
      </div>

    </div>
  </main>

  <!-- FOOTER -->
  <footer class="text-center small text-muted py-4">
    © 2026 CivicConnect. All rights reserved.
  </footer>
  <script>
    const otpForm = document.getElementById('otp-form');
    const otpInputs = Array.from(document.querySelectorAll('.otp-input'));
    const timer = document.getElementById('otp-timer');
    const resendBtn = document.getElementById('resend-btn');
    let timeLeft = <?php echo (int)$remainingSeconds; ?>;

    function setOtpValue(code) {
      const digits = code.replace(/\D/g, '').slice(0, otpInputs.length);
      otpInputs.forEach((input, index) => {
        input.value = digits[index] || '';
        input.classList.toggle('has-value', Boolean(input.value));
      });

      const nextIndex = Math.min(digits.length, otpInputs.length - 1);
      otpInputs[nextIndex].focus();

      if (digits.length === otpInputs.length) {
        otpInputs[otpInputs.length - 1].focus();
      }
    }

    otpInputs.forEach((input, index) => {
      input.addEventListener('input', event => {
        const digits = event.target.value.replace(/\D/g, '');

        if (digits.length > 1) {
          setOtpValue(digits);
          return;
        }

        event.target.value = digits;
        event.target.classList.toggle('has-value', Boolean(digits));
        if (digits && otpInputs[index + 1]) {
          otpInputs[index + 1].focus();
        }
      });

      input.addEventListener('keydown', event => {
        if (event.key === 'Backspace' && !input.value && otpInputs[index - 1]) {
          otpInputs[index - 1].focus();
          otpInputs[index - 1].value = '';
          otpInputs[index - 1].classList.remove('has-value');
        }

        if (event.key === 'ArrowLeft' && otpInputs[index - 1]) {
          event.preventDefault();
          otpInputs[index - 1].focus();
        }

        if (event.key === 'ArrowRight' && otpInputs[index + 1]) {
          event.preventDefault();
          otpInputs[index + 1].focus();
        }
      });

      input.addEventListener('paste', event => {
        event.preventDefault();
        setOtpValue(event.clipboardData.getData('text'));
      });
    });

    if (otpForm) {
      otpForm.addEventListener('submit', event => {
        const code = otpInputs.map(input => input.value).join('');
        if (!/^\d{6}$/.test(code)) {
          event.preventDefault();
          otpInputs[0].focus();
        }
      });
    }

    function updateTimer() {
      if (timeLeft <= 0) {
        timer.innerText = '0:00';
        resendBtn.style.pointerEvents = 'auto';
        resendBtn.style.opacity = '1';
        return;
      }

      const minutes = Math.floor(timeLeft / 60);
      const seconds = String(timeLeft % 60).padStart(2, '0');
      timer.innerText = `${minutes}:${seconds}`;
      timeLeft -= 1;
      setTimeout(updateTimer, 1000);
    }

    updateTimer();
  </script>

</body>

</html>
