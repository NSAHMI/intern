<?php
session_start();
include "../config/db.php";
include "../config/email.php";

$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($name === '' || $email === '' || $password === '' || !in_array($role, ['student', 'company', 'admin'], true)) {
        $errors[] = 'All fields are required and role must be selected.';
    }

    if (empty($errors)) {
        // Check if email already exists
        $check_stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $check_stmt->bind_param('s', $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $errors[] = 'This email is already registered.';
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // Additional validation
            if (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters long.';
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }
            
            if (strlen($name) < 2) {
                $errors[] = 'Name must be at least 2 characters long.';
            }
            
            // Only proceed if no validation errors
            if (empty($errors)) {
                // Start transaction for data integrity
                $conn->begin_transaction();
                
                try {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $department_id = ($_POST['role'] === 'student') ? ($_POST['department_id'] ?? null) : null;
                    
                    $insert_stmt = $conn->prepare('INSERT INTO users (name, email, password, role, department_id) VALUES (?, ?, ?, ?, ?)');
                    $insert_stmt->bind_param('ssssi', $name, $email, $passwordHash, $role, $department_id);
                    
                    if ($insert_stmt->execute()) {
                        // Get user ID for email
                        $user_id = $insert_stmt->insert_id;
                        $insert_stmt->close();
                        
                        // Send welcome email (non-blocking - won't affect registration if fails)
                        try {
                            $emailNotification = new EmailNotification($conn);
                            $emailNotification->sendWelcomeEmail($email, $name, $role);
                        } catch (Exception $e) {
                            // Email failed but registration succeeded - log error but don't fail registration
                            error_log('Email sending failed for user ' . $user_id . ': ' . $e->getMessage());
                        }
                        
                        // Commit the transaction - user is successfully registered
                        $conn->commit();
                        $success = 'Registration successful! A welcome email has been sent to your email address. <a href="login.php">Login now</a>.';
                        
                    } else {
                        $insert_stmt->close();
                        throw new Exception('Database insertion failed');
                    }
                    
                } catch (Exception $e) {
                    // Rollback any changes if something went wrong
                    $conn->rollback();
                    $errors[] = 'Registration failed. Please try again later.';
                    error_log('Registration error for email ' . $email . ': ' . $e->getMessage());
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - InternHub</title>
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
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.4);
            --accent-primary: #6366f1;
            --accent-secondary: #8b5cf6;
            --accent-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            --border-color: rgba(255, 255, 255, 0.08);
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

        .register-container {
            width: 100%;
            max-width: 480px;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo a {
            font-size: 2rem;
            font-weight: 800;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }

        .register-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 2.5rem;
            backdrop-filter: blur(20px);
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
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
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .form-input,
        .form-select {
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

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        .form-select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.4)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1rem;
            padding-right: 3rem;
        }

        .form-select option {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .form-hint {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
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

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
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

        .register-footer {
            text-align: center;
        }

        .register-footer p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .register-footer a {
            color: var(--accent-primary);
            text-decoration: none;
            font-weight: 600;
        }

        .register-footer a:hover {
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

        .role-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }

        .role-card {
            padding: 1rem 0.5rem;
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .role-card:hover {
            border-color: rgba(99, 102, 241, 0.3);
        }

        .role-card.active {
            border-color: var(--accent-primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .role-card i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .role-card span {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .role-card.active span {
            color: var(--text-primary);
        }

        .role-card input {
            display: none;
        }

        #department-field {
            display: none;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
            }

            .register-card {
                padding: 1.75rem;
            }

            .role-cards {
                grid-template-columns: 1fr;
            }

            .role-card {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 1rem;
                text-align: left;
            }

            .role-card i {
                margin-bottom: 0;
            }
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    <div class="grid-overlay"></div>

    <div class="register-container">
        <div class="logo">
            <a href="../index.php">InternHub</a>
        </div>

        <div class="register-card">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="register-header">
                <h1>Create your account</h1>
                <p>Join thousands of students and companies</p>
            </div>

            <form method="post" action="register.php">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input"
                           value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES); ?>"
                           placeholder="John Doe" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>"
                           placeholder="you@example.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input"
                           placeholder="Create a strong password" required>
                    <p class="form-hint">Minimum 6 characters</p>
                </div>

                <div class="form-group">
                    <label class="form-label">I am a...</label>
                    <div class="role-cards">
                        <label class="role-card <?php echo (($_POST['role'] ?? '') === 'student') ? 'active' : ''; ?>">
                            <input type="radio" name="role" value="student" <?php echo (($_POST['role'] ?? '') === 'student') ? 'checked' : ''; ?> required>
                            <i class="fas fa-graduation-cap"></i>
                            <span>Student</span>
                        </label>
                        <label class="role-card <?php echo (($_POST['role'] ?? '') === 'company') ? 'active' : ''; ?>">
                            <input type="radio" name="role" value="company" <?php echo (($_POST['role'] ?? '') === 'company') ? 'checked' : ''; ?>>
                            <i class="fas fa-building"></i>
                            <span>Company</span>
                        </label>
                        <label class="role-card <?php echo (($_POST['role'] ?? '') === 'admin') ? 'active' : ''; ?>">
                            <input type="radio" name="role" value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'checked' : ''; ?>>
                            <i class="fas fa-shield-alt"></i>
                            <span>Admin</span>
                        </label>
                    </div>
                </div>

                <div class="form-group" id="department-field">
                    <label class="form-label">Field of Study</label>
                    <select name="department_id" class="form-select">
                        <option value="">Select your field</option>
                        <?php
                        include '../config/db.php';
                        $dept_stmt = $conn->prepare('SELECT id, name FROM departments ORDER BY name');
                        $dept_stmt->execute();
                        $dept_result = $dept_stmt->get_result();
                        while ($dept = $dept_result->fetch_assoc()):
                        ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo (($_POST['department_id'] ?? '') == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name'], ENT_QUOTES); ?>
                            </option>
                        <?php endwhile; $dept_stmt->close(); ?>
                    </select>
                    <p class="form-hint">This helps us recommend relevant internships</p>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-rocket"></i> Create Account
                </button>
            </form>

            <div class="divider">
                <span>Already registered?</span>
            </div>

            <div class="register-footer">
                <p>Have an account? <a href="login.php">Sign in</a></p>
            </div>
        </div>

        <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>

    <script>
        // Role card selection
        document.querySelectorAll('.role-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.role-card').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                toggleDepartmentField();
            });
        });

        function toggleDepartmentField() {
            const studentSelected = document.querySelector('input[name="role"][value="student"]').checked;
            const departmentField = document.getElementById('department-field');
            const departmentSelect = document.querySelector('select[name="department_id"]');

            if (studentSelected) {
                departmentField.style.display = 'block';
                departmentSelect.setAttribute('required', 'required');
            } else {
                departmentField.style.display = 'none';
                departmentSelect.removeAttribute('required');
            }
        }

        // Initialize on page load
        toggleDepartmentField();
    </script>
</body>
</html>
