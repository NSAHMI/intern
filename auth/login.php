<?php
session_start();
include "../config/db.php";
include "../config/sms_2fa.php";

$error = '';
$success = '';
$show_2fa = false;
$pending_user = null;

// Handle 2FA verification
if (isset($_POST['verify_2fa']) && isset($_SESSION['pending_login_user'])) {
    $code = $_POST['2fa_code'] ?? '';
    $pending_user = $_SESSION['pending_login_user'];
    
    if (empty($code)) {
        $error = 'Please enter the verification code.';
    } else {
        $two_factor = new TwoFactorAuth($conn);
        
        if ($two_factor->verify2FACode($pending_user['id'], $code, 'login')) {
            // 2FA successful - complete login
            $_SESSION['user_id'] = $pending_user['id'];
            $_SESSION['user_name'] = $pending_user['name'];
            $_SESSION['role'] = $pending_user['role'];
            
            // Update last login
            $stmt = $conn->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
            $stmt->bind_param('i', $pending_user['id']);
            $stmt->execute();
            $stmt->close();
            
            // Log successful login
            $two_factor->log2FAAttempt($pending_user['id'], '2fa_login_success', true);
            
            unset($_SESSION['pending_login_user']);
            
            // Redirect based on role
            if ($pending_user['role'] === 'student') {
                header('Location: ../student/dashboard.php');
                exit;
            }
            if ($pending_user['role'] === 'company') {
                header('Location: ../company/dashboard.php');
                exit;
            }
            header('Location: ../admin/dashboard.php');
            exit;
        } else {
            $error = 'Invalid or expired verification code.';
            $two_factor->log2FAAttempt($pending_user['id'], '2fa_login_failed', false, 'Invalid code: ' . $code);
        }
    }
}

// Handle regular login
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        // Check if sms_2fa_enabled column exists
        $column_check = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'sms_2fa_enabled'");
        $column_check->execute();
        $column_exists = $column_check->get_result()->num_rows > 0;
        $column_check->close();
        
        if ($column_exists) {
            $stmt = $conn->prepare('SELECT id, name, password, role, sms_2fa_enabled FROM users WHERE email = ?');
        } else {
            $stmt = $conn->prepare('SELECT id, name, password, role FROM users WHERE email = ?');
        }
        
        if ($stmt === false) {
            $error = 'Database error. Please try again later.';
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            // Add default value for sms_2fa_enabled if column doesn't exist
            if ($user && !$column_exists) {
                $user['sms_2fa_enabled'] = false;
            }
        }

        if ($user && password_verify($password, $user['password'])) {
            // Check if 2FA is enabled
            if ($user['sms_2fa_enabled']) {
                // Send 2FA code
                $two_factor = new TwoFactorAuth($conn);
                
                if ($two_factor->send2FACode($user['id'], 'login')) {
                    $_SESSION['pending_login_user'] = $user;
                    $show_2fa = true;
                    $success = 'A verification code has been sent to your email.';
                    $two_factor->log2FAAttempt($user['id'], '2fa_code_sent', true);
                } else {
                    $error = 'Failed to send verification code. Please try again.';
                    $two_factor->log2FAAttempt($user['id'], '2fa_code_failed', false);
                }
            } else {
                // No 2FA - direct login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                
                // Update last login
                $stmt = $conn->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
                $stmt->bind_param('i', $user['id']);
                $stmt->execute();
                $stmt->close();

                if ($user['role'] === 'student') {
                    header('Location: ../student/dashboard.php');
                    exit;
                }
                if ($user['role'] === 'company') {
                    header('Location: ../company/dashboard.php');
                    exit;
                }
                header('Location: ../admin/dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-bg: #f9fafb;
            --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --card-hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            margin: 20px auto;
            max-width: 1200px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .header-section {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
            position: relative;
            overflow: hidden;
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .nav-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }
        
        .btn-custom {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary-custom {
            background: white;
            color: var(--primary-color);
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
            color: var(--primary-dark);
        }
        
        .btn-secondary-custom {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn-secondary-custom:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .content-section {
            padding: 2rem;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .btn-apply {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">Welcome Back! 👋</h1>
            <p class="header-subtitle">Sign in to access your internship dashboard</p>
            
            <div class="nav-buttons">
                <a href="../index.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="register.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            </div>
        </div>
    </div>

    <div class="content-section">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error, ENT_QUOTES); ?>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="text-primary mb-3">
                                <i class="fas fa-sign-in-alt fa-3x"></i>
                            </div>
                            <h4>Sign In</h4>
                            <p class="text-muted">Enter your credentials to continue</p>
                        </div>

                        <?php if ($show_2fa): ?>
                        <!-- 2FA Verification Form -->
                        <form method="post" action="login.php">
                            <input type="hidden" name="verify_2fa" value="1">
                            <div class="text-center mb-4">
                                <div class="mb-3">
                                    <i class="fas fa-shield-alt fa-3x text-primary"></i>
                                </div>
                                <h5>Two-Factor Authentication</h5>
                                <p class="text-muted">Enter the 6-digit code sent to your email</p>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-key me-2"></i>Verification Code
                                </label>
                                <input type="text" name="2fa_code" class="form-control text-center" 
                                       placeholder="000000" maxlength="6" pattern="\d{6}" 
                                       style="font-size: 24px; letter-spacing: 8px;" required>
                                <small class="text-muted">Code expires in 10 minutes</small>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn-apply">
                                    <i class="fas fa-check me-2"></i>Verify & Login
                                </button>
                                <a href="login.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                                </a>
                            </div>
                        </form>
                        
                        <?php else: ?>
                        <!-- Regular Login Form -->
                        <form method="post" action="login.php">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-envelope me-2"></i>Email Address
                                </label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>" 
                                       placeholder="Enter your email" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <input type="password" name="password" class="form-control" 
                                       placeholder="Enter your password" required>
                            </div>
                            <button type="submit" class="btn-apply w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </form>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            <p class="text-muted mb-0">Don't have an account?</p>
                            <a href="register.php" class="btn-custom btn-primary-custom mt-2">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
