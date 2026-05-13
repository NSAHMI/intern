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
    <title>Register - Internship Management System</title>
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
    <script>
        function toggleDepartmentField() {
            const roleSelect = document.querySelector('select[name="role"]');
            const departmentField = document.getElementById('department-field');
            
            if (roleSelect.value === 'student') {
                departmentField.style.display = 'block';
                document.querySelector('select[name="department_id"]').setAttribute('required', 'required');
            } else {
                departmentField.style.display = 'none';
                document.querySelector('select[name="department_id"]').removeAttribute('required');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.querySelector('select[name="role"]');
            roleSelect.addEventListener('change', toggleDepartmentField);
            toggleDepartmentField(); // Initialize on page load
        });
    </script>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">Join Us Today! 🎓</h1>
            <p class="header-subtitle">Create your account and start your internship journey</p>
            
            <div class="nav-buttons">
                <a href="../index.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="login.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        </div>
    </div>

    <div class="content-section">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 mb-4" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo implode('<br>', $errors); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success border-0 mb-4" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="text-primary mb-3">
                                <i class="fas fa-user-plus fa-3x"></i>
                            </div>
                            <h4>Create Account</h4>
                            <p class="text-muted">Fill in your details to get started</p>
                        </div>

                        <form method="post" action="register.php">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-user me-2"></i>Full Name
                                </label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES); ?>" 
                                       placeholder="Enter your full name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-envelope me-2"></i>Email Address
                                </label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>" 
                                       placeholder="Enter your email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <input type="password" name="password" class="form-control" 
                                       placeholder="Create a strong password" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-user-tag me-2"></i>Account Type
                                </label>
                                <select name="role" class="form-select" required>
                                    <option value="">Select your role</option>
                                    <option value="student" <?php echo (($_POST['role'] ?? '') === 'student') ? 'selected' : ''; ?>>
                                        <i class="fas fa-graduation-cap"></i> Student
                                    </option>
                                    <option value="company" <?php echo (($_POST['role'] ?? '') === 'company') ? 'selected' : ''; ?>>
                                        <i class="fas fa-building"></i> Company
                                    </option>
                                    <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>
                                        <i class="fas fa-shield-alt"></i> Administrator
                                    </option>
                                </select>
                            </div>
                            <div class="mb-4" id="department-field" style="display: none;">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-graduation-cap me-2"></i>Field of Study/Department
                                </label>
                                <select name="department_id" class="form-select">
                                    <option value="">Select your field of study</option>
                                    <?php
                                    include '../config/db.php';
                                    $dept_stmt = $conn->prepare('SELECT id, name, icon FROM departments ORDER BY name');
                                    $dept_stmt->execute();
                                    $dept_result = $dept_stmt->get_result();
                                    while ($dept = $dept_result->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo (($_POST['department_id'] ?? '') == $dept['id']) ? 'selected' : ''; ?>>
                                            <i class="fas <?php echo $dept['icon']; ?>"></i> <?php echo htmlspecialchars($dept['name'], ENT_QUOTES); ?>
                                        </option>
                                    <?php endwhile; $dept_stmt->close(); ?>
                                </select>
                                <div class="form-text">Select your primary field of study for relevant internship recommendations.</div>
                            </div>
                            <button type="submit" class="btn-apply w-100">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted mb-0">Already have an account?</p>
                            <a href="login.php" class="btn-custom btn-primary-custom mt-2">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
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
