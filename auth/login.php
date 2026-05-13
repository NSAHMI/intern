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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - InternHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-card: rgba(255, 255, 255, 0.03);
            --bg-card-hover: rgba(255, 255, 255, 0.06);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.4);
            --accent-primary: #6366f1;
            --accent-secondary: #8b5cf6;
            --accent-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            --border-color: rgba(255, 255, 255, 0.08);
            --success: #22c55e;
            --error: #ef4444;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            overflow-x: hidden;
        }

        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(ellipse at 20% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
            z-index: -1;
        }

        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 60px 60px;
            z-index: -1;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
        }

        .logo {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo a {
            font-size: 2rem;
            font-weight: 800;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 2.5rem;
            backdrop-filter: blur(20px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        .form-input.code-input {
            text-align: center;
            font-size: 1.75rem;
            letter-spacing: 0.5rem;
            font-weight: 600;
        }

        .btn {
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background: var(--accent-gradient);
            color: white;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(99, 102, 241, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            margin-top: 0.75rem;
        }

        .btn-secondary:hover {
            background: var(--bg-card);
            color: var(--text-primary);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
            gap: 1rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .divider span {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .login-footer {
            text-align: center;
        }

        .login-footer p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .login-footer a {
            color: var(--accent-primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: var(--accent-secondary);
        }

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--text-primary);
        }

        .two-fa-icon {
            width: 80px;
            height: 80px;
            background: var(--accent-gradient);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }

        .code-hint {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
            }

            .login-card {
                padding: 1.75rem;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    <div class="grid-overlay"></div>

    <div class="login-container">
        <div class="logo">
            <a href="../index.php">InternHub</a>
        </div>

        <div class="login-card">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error, ENT_QUOTES); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success, ENT_QUOTES); ?>
                </div>
            <?php endif; ?>

            <?php if ($show_2fa): ?>
                <!-- 2FA Verification Form -->
                <div class="login-header">
                    <div class="two-fa-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h1>Verification Required</h1>
                    <p>Enter the 6-digit code sent to your email</p>
                </div>

                <form method="post" action="login.php">
                    <input type="hidden" name="verify_2fa" value="1">

                    <div class="form-group">
                        <input type="text" name="2fa_code" class="form-input code-input"
                               placeholder="000000" maxlength="6" pattern="\d{6}"
                               autocomplete="one-time-code" required autofocus>
                        <p class="code-hint">Code expires in 10 minutes</p>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Verify & Sign In
                    </button>
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </form>

            <?php else: ?>
                <!-- Regular Login Form -->
                <div class="login-header">
                    <h1>Welcome back</h1>
                    <p>Sign in to your account to continue</p>
                </div>

                <form method="post" action="login.php">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-input"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>"
                               placeholder="you@example.com" required autofocus>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-input"
                               placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Sign In
                    </button>
                </form>

                <div class="divider">
                    <span>New here?</span>
                </div>

                <div class="login-footer">
                    <p>Don't have an account? <a href="register.php">Create one</a></p>
                </div>
            <?php endif; ?>
        </div>

        <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>
</body>
</html>
