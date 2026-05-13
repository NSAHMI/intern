<?php
session_start();
include "../config/db.php";
include "../config/security.php";
include "../config/email.php";

$security = new SecurityManager($conn);
$email = new EmailNotification($conn);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_address = trim($_POST['email'] ?? '');
    
    if (empty($email_address)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check rate limiting
        if (!$security->checkRateLimit($email_address, 'password_reset')) {
            $error = 'Too many password reset attempts. Please try again later.';
        } else {
            // Create password reset token
            $token = $security->createPasswordReset($email_address);
            
            if ($token) {
                // Send reset email
                $reset_link = "http://{$_SERVER['HTTP_HOST']}/intern/auth/reset_password.php?token=" . urlencode($token);
                
                $subject = "Password Reset - Internship Hub";
                $html_content = "
                    <h3>Password Reset Request</h3>
                    <p>Hello,</p>
                    <p>We received a request to reset your password for your Internship Hub account.</p>
                    <p>Click the link below to reset your password:</p>
                    <p><a href='{$reset_link}' style='background: #6366f1; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>Reset Password</a></p>
                    <p>Or copy and paste this link in your browser:</p>
                    <p><small>{$reset_link}</small></p>
                    <p>This link will expire in 1 hour for security reasons.</p>
                    <p>If you didn't request this password reset, please ignore this email.</p>
                    <p>Best regards,<br>The Internship Hub Team</p>
                ";
                
                $text_content = "Password Reset Request\n\nHello,\n\nWe received a request to reset your password for your Internship Hub account.\n\nClick the link below to reset your password:\n{$reset_link}\n\nThis link will expire in 1 hour for security reasons.\n\nIf you didn't request this password reset, please ignore this email.\n\nBest regards,\nThe Internship Hub Team";
                
                if ($email->sendEmail($email_address, $subject, $html_content, $text_content)) {
                    $security->logSecurityEvent($email_address, 'password_reset_requested', 'Password reset email sent', true);
                    $success = 'Password reset link has been sent to your email address. Please check your inbox.';
                } else {
                    $security->logSecurityEvent($email_address, 'password_reset_requested', 'Failed to send reset email', false);
                    $error = 'Failed to send reset email. Please try again later.';
                }
            } else {
                $security->logSecurityEvent($email_address, 'password_reset_requested', 'Email not found', false);
                // Don't reveal if email exists or not for security
                $success = 'If an account with this email exists, a password reset link has been sent.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Internship Hub</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --success-color: #10b981;
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
        
        .forgot-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            max-width: 450px;
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
        
        .security-note {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1.5rem;
        }
        
        .security-note i {
            color: #0284c7;
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
<div class="forgot-container">
    <div class="header-section">
        <i class="fas fa-key icon-large"></i>
        <h2 class="mb-2">Forgot Password?</h2>
        <p class="mb-3">No worries, we'll help you reset it</p>
        <a href="login.php" class="back-link">
            <i class="fas fa-arrow-left me-1"></i>Back to Login
        </a>
    </div>

    <div class="content-section">
        <?php if ($success): ?>
            <div class="alert alert-success border-0 mb-4" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
            
            <div class="text-center">
                <h5 class="mb-3">Check Your Email</h5>
                <p class="text-muted mb-4">
                    We've sent you an email with instructions to reset your password. 
                    The link will expire in 1 hour for security reasons.
                </p>
                
                <div class="security-note">
                    <i class="fas fa-shield-alt me-2"></i>
                    <strong>Security Tip:</strong> If you don't receive the email within a few minutes, 
                    check your spam folder or try again.
                </div>
            </div>
            
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 mb-4" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-envelope me-2"></i>Email Address
                    </label>
                    <input type="email" name="email" class="form-control" 
                           placeholder="Enter your registered email" required>
                    <div class="form-text">Enter the email address associated with your account</div>
                </div>
                
                <button type="submit" class="btn-reset">
                    <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                </button>
            </form>
            
            <div class="security-note">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> For security reasons, we won't confirm if the email exists 
                in our system. You'll receive an email only if the address is registered.
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <hr class="my-3">
            <p class="mb-0 text-muted">
                Remember your password? 
                <a href="login.php" class="text-decoration-none fw-semibold">Login Here</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
