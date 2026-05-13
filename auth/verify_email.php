<?php
session_start();

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    $error = 'Invalid verification link.';
} else {
    include "../config/db.php";
    include "../config/security.php";
    
    $security = new SecurityManager($conn);
    $user_id = $security->verifyEmail($token);
    
    if ($user_id) {
        // Get user info for logging
        $stmt = $conn->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Log successful verification
        $security->logSecurityEvent($user['email'], 'email_verified', 'Email verification successful', true);
        
        $success = 'Your email has been successfully verified! You can now log in to your account.';
        
        // Start session for auto-login if not logged in
        if (empty($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $user['name'] ?? 'User';
            $_SESSION['email_verified'] = true;
        }
    } else {
        $error = 'Invalid or expired verification link. Please request a new verification email.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
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
        
        .verification-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(135deg, var(--primary-color), #4f46e5);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        
        .content-section {
            padding: 2rem;
        }
        
        .icon-large {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .btn-action {
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #4f46e5);
            color: white;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .feature-list i {
            color: var(--success-color);
            width: 20px;
        }
    </style>
</head>
<body>
<div class="verification-container">
    <?php if ($success): ?>
        <div class="header-section">
            <i class="fas fa-check-circle icon-large"></i>
            <h2 class="mb-2">Email Verified!</h2>
            <p class="mb-0">Your account is now fully activated</p>
        </div>
        
        <div class="content-section">
            <div class="text-center">
                <p class="mb-4">Thank you for verifying your email address. Your account is now ready to use!</p>
                
                <h5 class="mb-3">What's Next?</h5>
                <ul class="feature-list">
                    <li><i class="fas fa-check"></i> Browse available internships</li>
                    <li><i class="fas fa-check"></i> Complete your profile</li>
                    <li><i class="fas fa-check"></i> Apply for opportunities</li>
                    <li><i class="fas fa-check"></i> Connect with companies</li>
                </ul>
                
                <div class="d-grid gap-2">
                    <a href="../student/dashboard.php" class="btn-action btn-success">
                        <i class="fas fa-rocket me-2"></i>Go to Dashboard
                    </a>
                    <a href="login.php" class="btn-action btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Account
                    </a>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <div class="header-section" style="background: linear-gradient(135deg, var(--danger-color), #dc2626);">
            <i class="fas fa-exclamation-triangle icon-large"></i>
            <h2 class="mb-2">Verification Failed</h2>
            <p class="mb-0">We couldn't verify your email</p>
        </div>
        
        <div class="content-section">
            <div class="text-center">
                <p class="mb-4"><?php echo htmlspecialchars($error); ?></p>
                
                <div class="alert alert-light">
                    <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Possible Reasons:</h6>
                    <ul class="text-start mb-0">
                        <li>The verification link has expired</li>
                        <li>The link was already used</li>
                        <li>The link is invalid or corrupted</li>
                        <li>You already verified your email</li>
                    </ul>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="register.php" class="btn-action btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Create New Account
                    </a>
                    <a href="login.php" class="btn-action btn-secondary">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Account
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
