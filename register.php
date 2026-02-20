<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection(); // mysqli

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $full_name = trim($_POST['full_name']);

    // Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already exists. Please use a different email.";
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $insert_query = "INSERT INTO users (email, password_hash, full_name, user_type)
                             VALUES (?, ?, ?, 'citizen')";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bind_param("sss", $email, $hashed_password, $full_name);

            if ($insert_stmt->execute()) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }

            $insert_stmt->close();
        }

        $check_stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Municipal Issue Reporting System</title>
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
                <div class="gov-logo-large">
                    <div class="gov-logo"> <img src="./assets/images/BPR.png" alt="Government Logo"class="gov-logo-image"/> </div>
                </div>
                <h2>Join Our Community</h2>
                <p>Help make Bhubaneswar a better place for everyone</p>
                <div class="illustration-features">
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure and private account</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-bell"></i>
                        <span>Get updates on your reports</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-chart-line"></i>
                        <span>Track community progress</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Registration Form -->
        <div class="auth-form-panel">
            <div class="auth-form-container">
                <div class="auth-header">
                    <a href="index.html" class="back-button">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1>Create Account</h1>
                    <p>Join us in improving our community</p>
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

                <form method="POST" action="register.php" class="auth-form">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   placeholder="Enter your full name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        </div>
                    </div>

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
                                   placeholder="Create a password (min. 6 characters)" required>
                            <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-with-icon password-field">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm your password" required>
                            <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Sign in</a></p>
                </div>
            </div>

            <div class="auth-copyright">
                <p>© 2024 Municipal Government of Bhubaneswar. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const eyeIcon = passwordInput.parentNode.querySelector('.toggle-password i');
            
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

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('password-strength');
            
            if (!strengthIndicator) {
                const strengthDiv = document.createElement('div');
                strengthDiv.id = 'password-strength';
                strengthDiv.className = 'password-strength';
                this.parentNode.parentNode.appendChild(strengthDiv);
            }
            
            const strengthText = document.getElementById('password-strength-text') || document.createElement('div');
            strengthText.id = 'password-strength-text';
            
            let strength = 0;
            let message = '';
            let strengthClass = '';
            
            if (password.length > 0) {
                if (password.length < 6) {
                    message = 'Too short';
                    strengthClass = 'weak';
                } else {
                    // Check for character variety
                    if (/[a-z]/.test(password)) strength++;
                    if (/[A-Z]/.test(password)) strength++;
                    if (/[0-9]/.test(password)) strength++;
                    if (/[^a-zA-Z0-9]/.test(password)) strength++;
                    
                    if (password.length > 10) strength++;
                    
                    switch(strength) {
                        case 1:
                        case 2:
                            message = 'Weak';
                            strengthClass = 'weak';
                            break;
                        case 3:
                            message = 'Medium';
                            strengthClass = 'medium';
                            break;
                        case 4:
                        case 5:
                            message = 'Strong';
                            strengthClass = 'strong';
                            break;
                    }
                }
                
                strengthText.innerHTML = `<span class="strength-${strengthClass}">${message}</span>`;
                strengthText.className = `strength-text ${strengthClass}`;
                
                if (!document.getElementById('password-strength-text')) {
                    this.parentNode.parentNode.appendChild(strengthText);
                }
            } else {
                if (strengthText.parentNode) {
                    strengthText.parentNode.removeChild(strengthText);
                }
                if (strengthIndicator) {
                    strengthIndicator.parentNode.removeChild(strengthIndicator);
                }
            }
        });
    </script>
</body>
</html>