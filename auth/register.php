<?php
/**
 * User Registration
 * Internship Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('/' . get_user_role() . '/dashboard.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }

    if (!in_array($role, ['student', 'company'])) {
        $errors[] = 'Please select a valid role';
    }

    // Check if email exists
    if (empty($errors)) {
        $existing = db_fetch("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            $errors[] = 'Email is already registered';
        }
    }

    // Create user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        db_query(
            "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)",
            [$name, $email, $hashed_password, $role]
        );

        $user_id = db_last_id();

        // Create profile based on role
        if ($role === 'student') {
            db_query("INSERT INTO student_profiles (user_id) VALUES (?)", [$user_id]);
        } elseif ($role === 'company') {
            $company_name = $_POST['company_name'] ?? $name;
            db_query(
                "INSERT INTO company_profiles (user_id, company_name) VALUES (?, ?)",
                [$user_id, $company_name]
            );
        }

        set_flash('success', 'Registration successful! Please sign in.');
        redirect('/auth/login.php');
    }
}

$page_title = 'Create Account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - InternHub</title>
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
            max-width: 480px;
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
        }

        .alert-danger {
            background: rgba(239,68,68,0.1);
            color: #b91c1c;
            border: 1px solid rgba(239,68,68,0.2);
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

        .role-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .role-option {
            position: relative;
        }

        .role-option input {
            position: absolute;
            opacity: 0;
        }

        .role-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.25rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .role-option label i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--gray);
        }

        .role-option label span {
            font-weight: 600;
            color: #374151;
        }

        .role-option input:checked + label {
            border-color: var(--primary);
            background: rgba(99,102,241,0.05);
        }

        .role-option input:checked + label i {
            color: var(--primary);
        }

        .company-field {
            display: none;
            animation: slideDown 0.3s ease;
        }

        .company-field.show {
            display: block;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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

        @media (max-width: 480px) {
            body { padding: 1rem; }
            .auth-card { padding: 1.5rem; }
            .role-options { grid-template-columns: 1fr; }
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
                <h1>Create Account</h1>
                <p>Join our internship platform</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo implode('<br>', $errors); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control"
                           value="<?php echo clean($_POST['name'] ?? ''); ?>"
                           placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control"
                           value="<?php echo clean($_POST['email'] ?? ''); ?>"
                           placeholder="you@example.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Minimum 6 characters" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control"
                           placeholder="Confirm your password" required>
                </div>

                <div class="form-group">
                    <label class="form-label">I am a...</label>
                    <div class="role-options">
                        <div class="role-option">
                            <input type="radio" name="role" value="student" id="role_student"
                                   <?php echo ($_POST['role'] ?? '') === 'student' ? 'checked' : ''; ?> required>
                            <label for="role_student">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Student</span>
                            </label>
                        </div>
                        <div class="role-option">
                            <input type="radio" name="role" value="company" id="role_company"
                                   <?php echo ($_POST['role'] ?? '') === 'company' ? 'checked' : ''; ?>>
                            <label for="role_company">
                                <i class="fas fa-building"></i>
                                <span>Company</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group company-field" id="companyField">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-control"
                           value="<?php echo clean($_POST['company_name'] ?? ''); ?>"
                           placeholder="Enter company name">
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>

                <div class="auth-footer">
                    <p>Already have an account? <a href="/auth/login.php">Sign In</a></p>
                </div>
            </form>
        </div>

        <div class="back-link">
            <a href="/index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>

    <script>
        // Show/hide company name field
        const roleInputs = document.querySelectorAll('input[name="role"]');
        const companyField = document.getElementById('companyField');

        roleInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value === 'company') {
                    companyField.classList.add('show');
                    companyField.querySelector('input').required = true;
                } else {
                    companyField.classList.remove('show');
                    companyField.querySelector('input').required = false;
                }
            });
        });

        // Check initial state
        if (document.querySelector('input[name="role"][value="company"]:checked')) {
            companyField.classList.add('show');
        }
    </script>
</body>
</html>
