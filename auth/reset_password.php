<?php
session_start();
include "../config/db.php";
include "../config/security.php";

$security = new SecurityManager($conn);
$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Validate token first
if (empty($token)) {
    $error = 'Invalid reset link.';
    $valid_token = false;
} else {
    $user_id = $security->verifyPasswordReset($token);
    if (!$user_id) {
        $error = 'Invalid or expired reset link. Please request a new password reset.';
        $valid_token = false;
    } else {
        $valid_token = true;
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password)) {
        $error = 'Please enter a new password.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $error = 'Password must contain at least one uppercase letter, one lowercase letter, and one number.';
    } else {
        if ($security->resetPassword($token, $password)) {
            $success = 'Your password has been reset successfully! You can now login with your new password.';
            $valid_token = false; // Prevent form from showing again
        } else {
            $error = 'Failed to reset password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Internship Hub</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --success-color: #10b981;
            --danger-color: #ef4444;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .reset-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .content-section {
            padding: 2rem;
        }
        
        .icon-large {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        .form-control {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .btn-reset {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        
        .password-strength {
            margin-top: 0.5rem;
        }
        
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e5e7eb;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .strength-weak { background: #ef4444; width: 33%; }
        .strength-medium { background: #f59e0b; width: 66%; }
        .strength-strong { background: #10b981; width: 100%; }
        
        .requirements {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .requirements h6 {
            color: #475569;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .requirements ul {
            margin: 0;
            padding-left: 1.25rem;
            font-size: 0.813rem;
            color: #64748b;
        }
        
        .requirement-met {
            color: var(--success-color);
            text-decoration: line-through;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            opacity: 0.9;
            transition: opacity 0.3s ease;
        }
        
        .back-link:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
<div class="reset-container">
    <?php if ($success): ?>
        <div class="header-section" style="background: linear-gradient(135deg, var(--success-color), #059669);">
            <i class="fas fa-check-circle icon-large"></i>
            <h2 class="mb-2">Password Reset!</h2>
            <p class="mb-3">Your password has been changed successfully</p>
            <a href="login.php" class="back-link">
                <i class="fas fa-sign-in-alt me-1"></i>Login to Account
            </a>
        </div>
        
        <div class="content-section">
            <div class="text-center">
                <div class="mb-4">
                    <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                    <h5 class="mb-2">Security Updated</h5>
                    <p class="text-muted">
                        Your password has been reset and your account is now secure. 
                        You can login with your new password.
                    </p>
                </div>
                
                <div class="alert alert-light">
                    <h6 class="mb-2"><i class="fas fa-lightbulb me-2"></i>Security Tips:</h6>
                    <ul class="text-start mb-0">
                        <li>Use a unique password for this account</li>
                        <li>Don't share your password with anyone</li>
                        <li>Consider enabling two-factor authentication</li>
                        <li>Update your password regularly</li>
                    </ul>
                </div>
                
                <a href="login.php" class="btn-reset">
                    <i class="fas fa-sign-in-alt me-2"></i>Login Now
                </a>
            </div>
        </div>
        
    <?php elseif (!$valid_token): ?>
        <div class="header-section" style="background: linear-gradient(135deg, var(--danger-color), #dc2626);">
            <i class="fas fa-exclamation-triangle icon-large"></i>
            <h2 class="mb-2">Invalid Link</h2>
            <p class="mb-3">This reset link is no longer valid</p>
            <a href="forgot_password.php" class="back-link">
                <i class="fas fa-key me-1"></i>Request New Link
            </a>
        </div>
        
        <div class="content-section">
            <div class="text-center">
                <p class="mb-4"><?php echo htmlspecialchars($error); ?></p>
                
                <div class="alert alert-light">
                    <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Why this happened:</h6>
                    <ul class="text-start mb-0">
                        <li>The reset link has expired (valid for 1 hour)</li>
                        <li>The link has already been used</li>
                        <li>The link is invalid or corrupted</li>
                    </ul>
                </div>
                
                <a href="forgot_password.php" class="btn-reset">
                    <i class="fas fa-key me-2"></i>Request New Reset
                </a>
            </div>
        </div>
        
    <?php else: ?>
        <div class="header-section">
            <i class="fas fa-lock icon-large"></i>
            <h2 class="mb-2">Reset Password</h2>
            <p class="mb-3">Create a new password for your account</p>
            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left me-1"></i>Back to Login
            </a>
        </div>

        <div class="content-section">
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 mb-4" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" id="resetForm">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-lock me-2"></i>New Password
                    </label>
                    <input type="password" name="password" class="form-control" 
                           id="password" placeholder="Enter new password" required>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthBar"></div>
                        </div>
                        <small class="text-muted" id="strengthText">Enter a password</small>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-lock me-2"></i>Confirm Password
                    </label>
                    <input type="password" name="confirm_password" class="form-control" 
                           id="confirmPassword" placeholder="Confirm new password" required>
                    <div class="form-text" id="matchText">Enter the same password as above</div>
                </div>
                
                <button type="submit" class="btn-reset">
                    <i class="fas fa-check me-2"></i>Reset Password
                </button>
                
                <div class="requirements">
                    <h6><i class="fas fa-shield-alt me-1"></i>Password Requirements:</h6>
                    <ul>
                        <li id="req-length">At least 8 characters long</li>
                        <li id="req-uppercase">Contains uppercase letter (A-Z)</li>
                        <li id="req-lowercase">Contains lowercase letter (a-z)</li>
                        <li id="req-number">Contains number (0-9)</li>
                    </ul>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php if ($valid_token): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    const matchText = document.getElementById('matchText');
    
    // Requirements elements
    const reqLength = document.getElementById('req-length');
    const reqUppercase = document.getElementById('req-uppercase');
    const reqLowercase = document.getElementById('req-lowercase');
    const reqNumber = document.getElementById('req-number');
    
    function checkPasswordStrength(pass) {
        let strength = 0;
        let strengthClass = '';
        let strengthLabel = '';
        
        // Check requirements
        const hasLength = pass.length >= 8;
        const hasUppercase = /[A-Z]/.test(pass);
        const hasLowercase = /[a-z]/.test(pass);
        const hasNumber = /\d/.test(pass);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(pass);
        
        // Update requirements
        reqLength.className = hasLength ? 'requirement-met' : '';
        reqUppercase.className = hasUppercase ? 'requirement-met' : '';
        reqLowercase.className = hasLowercase ? 'requirement-met' : '';
        reqNumber.className = hasNumber ? 'requirement-met' : '';
        
        // Calculate strength
        if (hasLength) strength++;
        if (hasUppercase) strength++;
        if (hasLowercase) strength++;
        if (hasNumber) strength++;
        if (hasSpecial) strength++;
        
        if (strength <= 2) {
            strengthClass = 'strength-weak';
            strengthLabel = 'Weak password';
        } else if (strength <= 4) {
            strengthClass = 'strength-medium';
            strengthLabel = 'Medium strength';
        } else {
            strengthClass = 'strength-strong';
            strengthLabel = 'Strong password';
        }
        
        strengthBar.className = 'strength-fill ' + strengthClass;
        strengthText.textContent = strengthLabel;
    }
    
    function checkPasswordMatch() {
        if (confirmPassword.value === '') {
            matchText.textContent = 'Enter the same password as above';
            matchText.className = 'form-text';
        } else if (password.value === confirmPassword.value) {
            matchText.textContent = 'Passwords match!';
            matchText.className = 'form-text text-success';
        } else {
            matchText.textContent = 'Passwords do not match';
            matchText.className = 'form-text text-danger';
        }
    }
    
    password.addEventListener('input', function() {
        checkPasswordStrength(this.value);
        checkPasswordMatch();
    });
    
    confirmPassword.addEventListener('input', checkPasswordMatch);
    
    // Form validation
    document.getElementById('resetForm').addEventListener('submit', function(e) {
        const pass = password.value;
        
        if (pass.length < 8 || !/[A-Z]/.test(pass) || !/[a-z]/.test(pass) || !/\d/.test(pass)) {
            e.preventDefault();
            alert('Please meet all password requirements.');
        } else if (pass !== confirmPassword.value) {
            e.preventDefault();
            alert('Passwords do not match.');
        }
    });
});
</script>
<?php endif; ?>
</body>
</html>
