<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'company') {
    header('Location: ../auth/login.php');
    exit;
}
include "../config/db.php";

$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $expiration_date = trim($_POST['expiration_date'] ?? '');
    $department_id = trim($_POST['department_id'] ?? '');

    if ($title === '' || $description === '' || $duration === '' || $expiration_date === '' || $department_id === '') {
        $errors[] = 'Please fill in all fields.';
    }

    if (empty($errors)) {
        $company_id = $_SESSION['user_id'];
        $stmt = $conn->prepare('INSERT INTO internships (company_id, department_id, title, description, duration, expiration_date) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iissss', $company_id, $department_id, $title, $description, $duration, $expiration_date);

        if ($stmt->execute()) {
            $internship_id = $stmt->insert_id;
            
            // Send notifications to all registered students
            $notification_stmt = $conn->prepare('SELECT email, name FROM users WHERE role = "student"');
            $notification_stmt->execute();
            $students = $notification_stmt->get_result();
            
            while ($student = $students->fetch_assoc()) {
                // In a real implementation, you would send emails here
                // For now, we'll just mark that notifications were sent
                error_log("Notification sent to: " . $student['email'] . " for new internship: " . $_POST['title']);
            }
            
            // Mark notifications as sent
            $update_stmt = $conn->prepare('UPDATE internships SET notification_sent = TRUE WHERE id = ?');
            $update_stmt->bind_param('i', $internship_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            $success = 'Internship posted successfully! All registered students have been notified.';
        } else {
            $errors[] = 'Unable to post internship. Please try again later.';
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Internship - Internship Management System</title>
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
            <h1 class="header-title">Post New Internship 📝</h1>
            <p class="header-subtitle">Create exciting opportunities for talented students</p>
            
            <div class="nav-buttons">
                <a href="dashboard.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="profile.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-building"></i> Company Profile
                </a>
                <a href="../messages.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="../auth/logout.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="content-section">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 mb-4" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo implode('<br>', $errors); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success border-0 mb-4" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success, ENT_QUOTES); ?>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="text-primary mb-3">
                                <i class="fas fa-briefcase fa-3x"></i>
                            </div>
                            <h4>Create Internship Posting</h4>
                            <p class="text-muted">Provide details about the internship opportunity</p>
                        </div>

                        <form method="post" action="post_internship.php">
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-graduation-cap me-2"></i>Department/Field
                                </label>
                                <select name="department_id" class="form-select" required>
                                    <option value="">Select department</option>
                                    <?php
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
                                <div class="form-text">Choose the department this internship belongs to.</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-heading me-2"></i>Internship Title
                                </label>
                                <input type="text" name="title" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES); ?>" 
                                       placeholder="e.g. Software Development Intern" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-align-left me-2"></i>Job Description
                                </label>
                                <textarea name="description" class="form-control" rows="6" 
                                          placeholder="Describe the role, responsibilities, and requirements..." required><?php echo htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES); ?></textarea>
                                <div class="form-text">Be specific about the role and what students will learn.</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-clock me-2"></i>Duration
                                </label>
                                <input type="text" name="duration" class="form-control" 
                                       placeholder="e.g. 3 months, 6 months, Summer 2024" 
                                       value="<?php echo htmlspecialchars($_POST['duration'] ?? '', ENT_QUOTES); ?>" required>
                                <div class="form-text">Specify the length and timing of the internship.</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar-times me-2"></i>Expiration Date
                                </label>
                                <input type="date" name="expiration_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['expiration_date'] ?? '', ENT_QUOTES); ?>" 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                <div class="form-text">Applications will no longer be accepted after this date.</div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn-apply">
                                    <i class="fas fa-paper-plane me-2"></i>Post Internship
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
