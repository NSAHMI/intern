<?php
/**
 * User Login
 * Internship Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('/' . get_user_role() . '/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        $user = db_fetch("SELECT * FROM users WHERE email = ?", [$email]);

        if ($user && password_verify($password, $user['password'])) {
            if (!$user['is_active']) {
                $error = 'Your account has been deactivated. Please contact support.';
            } else {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                set_flash('success', 'Welcome back, ' . $user['name'] . '!');
                redirect('/' . $user['role'] . '/dashboard.php');
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}

$page_title = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - InternHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --danger: #ef4444;
            --success: #22c55e;
            --gray: #6b7280;
            --light: #f3f4f6;
            --border: #e5e7eb;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo a {
            color: white;
            text-decoration: none;
            font-size: 2rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .auth-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h1 {
            font-size: 1.75rem;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: var(--gray);
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-danger {
            background: rgba(239,68,68,0.1);
            color: #b91c1c;
            border: 1px solid rgba(239,68,68,0.2);
        }

        .alert-success {
            background: rgba(34,197,94,0.1);
            color: #15803d;
            border: 1px solid rgba(34,197,94,0.2);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            color: #374151;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .auth-footer p {
            color: var(--gray);
        }

        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link a:hover {
            color: white;
        }

        .demo-login {
            margin-top: 1.5rem;
            padding: 1rem;
            background: var(--light);
            border-radius: 10px;
        }

        .demo-login h4 {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        .demo-login p {
            font-size: 0.8rem;
            color: #6b7280;
            margin: 0.25rem 0;
        }

        .demo-login code {
            background: white;
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
            font-size: 0.75rem;
        }

        @media (max-width: 480px) {
            body { padding: 1rem; }
            .auth-card { padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <a href="/index.php"><i class="fas fa-briefcase"></i> InternHub</a>
        </div>

        <div class="auth-card">
            <div class="auth-header">
                <h1>Welcome Back</h1>
                <p>Sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($flash = get_flash('success')): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $flash; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control"
                           value="<?php echo clean($_POST['email'] ?? ''); ?>"
                           placeholder="you@example.com" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="/auth/register.php">Create Account</a></p>
                </div>
            </form>

            <div class="demo-login">
                <h4>Demo Credentials</h4>
                <p><strong>Admin:</strong> <code>admin@internhub.com</code> / <code>password</code></p>
                <p><strong>Company:</strong> <code>hr@techcorp.com</code> / <code>password</code></p>
                <p><strong>Student:</strong> <code>john@university.edu</code> / <code>password</code></p>
            </div>
        </div>

        <div class="back-link">
            <a href="/index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>
</body>
</html>
