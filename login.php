
<?php
session_start();
require_once 'config/database.php';
require_once 'config/Auth.php';

$database = new Database();
$db = $database->getConnection(); // mysqli
$auth = new Auth($db);

$error = '';
$success = '';

// Check if user was redirected from successful registration
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = "Registration successful! Please login with your credentials.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        if ($auth->login($email, $password)) {
            if ($_SESSION['user_type'] === 'admin') {
                header("Location: admin_dashboard.php");
            } elseif (strpos($_SESSION['user_type'], '_admin') !== false) {
                header("Location: municipal_admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Municipal Issue Reporting System</title>
    <link rel="icon" href="assets/images/BPR.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <!-- Left Panel - Illustration -->
        <div class="auth-illustration">
            <div class="illustration-content">
                <div class="gov-logo">
                    <img src="assets/images/BPR.png" alt="Government Logo"class="gov-logo-image"/>
                </div>
                <h2>Municipal Issue Reporting System</h2>
                <p>Report and track community issues in Bhubaneswar</p>
                <div class="illustration-features">
                    <div class="feature">
                        <i class="fas fa-map-marked-alt"></i>
                        <span>Pinpoint issues on our interactive map</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-tasks"></i>
                        <span>Track report progress in real-time</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-users"></i>
                        <span>Join a community of active citizens</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="auth-form-panel">
            <div class="auth-form-container">
                <div class="auth-header">
                    <a href="index.html" class="back-button">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1>Welcome Back</h1>
                    <p>Sign in to your account to continue</p>
                </div>

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

                <form method="POST" action="login.php" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon password-field">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter your password" required>
                            <button type="button" class="toggle-password" onclick="togglePasswordVisibility()">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" id="rememberMe">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="register.php">Create account</a></p>
                </div>
            </div>

            <div class="auth-copyright">
                <p>© 2024 Municipal Government of Bhubaneswar. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
