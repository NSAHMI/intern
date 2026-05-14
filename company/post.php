<?php
/**
 * Post New Internship
 * Internship Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_role('company');

$errors = [];
$success = '';

// Get company profile
$profile = db_fetch("SELECT * FROM company_profiles WHERE user_id = ?", [$_SESSION['user_id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean($_POST['title'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $requirements = clean($_POST['requirements'] ?? '');
    $responsibilities = clean($_POST['responsibilities'] ?? '');
    $location = clean($_POST['location'] ?? '');
    $type = $_POST['type'] ?? 'onsite';
    $duration = clean($_POST['duration'] ?? '');
    $stipend = clean($_POST['stipend'] ?? '');
    $positions = intval($_POST['positions'] ?? 1);
    $deadline = $_POST['deadline'] ?? '';

    // Validation
    if (empty($title)) {
        $errors[] = 'Job title is required';
    }
    if (empty($description)) {
        $errors[] = 'Job description is required';
    }
    if (!in_array($type, ['remote', 'onsite', 'hybrid'])) {
        $errors[] = 'Please select a valid work type';
    }
    if ($positions < 1) {
        $positions = 1;
    }

    if (empty($errors)) {
        db_query(
            "INSERT INTO internships (company_id, title, description, requirements, responsibilities, location, type, duration, stipend, positions, deadline, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')",
            [
                $_SESSION['user_id'],
                $title,
                $description,
                $requirements,
                $responsibilities,
                $location,
                $type,
                $duration,
                $stipend,
                $positions,
                $deadline ?: null
            ]
        );

        set_flash('success', 'Internship posted successfully!');
        redirect('/company/dashboard.php');
    }
}

$page_title = 'Post New Internship';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - InternHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
            --gray: #6b7280;
            --dark: #1f2937;
            --light: #f3f4f6;
            --white: #ffffff;
            --border: #e5e7eb;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--light);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: var(--dark);
            color: var(--white);
            padding: 1.5rem;
            overflow-y: auto;
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .sidebar-logo i { color: var(--primary); }

        .sidebar-nav { list-style: none; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: var(--primary);
            color: var(--white);
        }
        .sidebar-nav a i { width: 20px; }

        .main-content {
            margin-left: 260px;
            padding: 2rem;
            max-width: 900px;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }

        /* Card */
        .card {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-title i {
            color: var(--primary);
        }

        /* Form */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .form-label .required {
            color: var(--danger);
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

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .form-hint {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 0.5rem;
        }

        /* Radio Options */
        .type-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .type-option {
            position: relative;
        }

        .type-option input {
            position: absolute;
            opacity: 0;
        }

        .type-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.25rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .type-option label i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--gray);
        }

        .type-option label span {
            font-weight: 600;
            color: var(--dark);
        }

        .type-option input:checked + label {
            border-color: var(--primary);
            background: rgba(99,102,241,0.05);
        }

        .type-option input:checked + label i {
            color: var(--primary);
        }

        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .alert-danger {
            background: rgba(239,68,68,0.1);
            color: #b91c1c;
        }

        .alert ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        /* Buttons */
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.875rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--border);
            color: var(--gray);
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .type-options { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="/index.php" class="sidebar-logo">
            <i class="fas fa-briefcase"></i> InternHub
        </a>
        <ul class="sidebar-nav">
            <li><a href="/company/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="/company/post.php" class="active"><i class="fas fa-plus-circle"></i> Post Internship</a></li>
            <li><a href="/company/internships.php"><i class="fas fa-briefcase"></i> My Internships</a></li>
            <li><a href="/company/applications.php"><i class="fas fa-users"></i> Applications</a></li>
            <li><a href="/company/profile.php"><i class="fas fa-building"></i> Company Profile</a></li>
            <li><a href="/company/messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
            <li><a href="/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Post New Internship</h1>
            <div class="breadcrumb">
                <a href="/company/dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span>Post Internship</span>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Basic Information -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-info-circle"></i> Basic Information</h3>

                <div class="form-group">
                    <label class="form-label">Job Title <span class="required">*</span></label>
                    <input type="text" name="title" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                           placeholder="e.g., Software Development Intern" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Job Description <span class="required">*</span></label>
                    <textarea name="description" class="form-control"
                              placeholder="Describe the internship role, projects, and what the intern will learn..."
                              required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Requirements</label>
                    <textarea name="requirements" class="form-control"
                              placeholder="List required skills, qualifications, and experience..."><?php echo htmlspecialchars($_POST['requirements'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Responsibilities</label>
                    <textarea name="responsibilities" class="form-control"
                              placeholder="List key responsibilities and duties..."><?php echo htmlspecialchars($_POST['responsibilities'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Work Details -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-laptop-house"></i> Work Details</h3>

                <div class="form-group">
                    <label class="form-label">Work Type <span class="required">*</span></label>
                    <div class="type-options">
                        <div class="type-option">
                            <input type="radio" name="type" value="remote" id="type_remote"
                                   <?php echo ($_POST['type'] ?? '') === 'remote' ? 'checked' : ''; ?>>
                            <label for="type_remote">
                                <i class="fas fa-home"></i>
                                <span>Remote</span>
                            </label>
                        </div>
                        <div class="type-option">
                            <input type="radio" name="type" value="onsite" id="type_onsite"
                                   <?php echo ($_POST['type'] ?? 'onsite') === 'onsite' ? 'checked' : ''; ?>>
                            <label for="type_onsite">
                                <i class="fas fa-building"></i>
                                <span>On-site</span>
                            </label>
                        </div>
                        <div class="type-option">
                            <input type="radio" name="type" value="hybrid" id="type_hybrid"
                                   <?php echo ($_POST['type'] ?? '') === 'hybrid' ? 'checked' : ''; ?>>
                            <label for="type_hybrid">
                                <i class="fas fa-exchange-alt"></i>
                                <span>Hybrid</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                               placeholder="e.g., San Francisco, CA">
                        <p class="form-hint">Leave empty for remote positions</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration</label>
                        <input type="text" name="duration" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['duration'] ?? ''); ?>"
                               placeholder="e.g., 3 months, 6 months">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Stipend</label>
                        <input type="text" name="stipend" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['stipend'] ?? ''); ?>"
                               placeholder="e.g., $1500/month, Unpaid">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Number of Positions</label>
                        <input type="number" name="positions" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['positions'] ?? '1'); ?>"
                               min="1" placeholder="1">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Application Deadline</label>
                    <input type="date" name="deadline" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>">
                    <p class="form-hint">Leave empty for no deadline</p>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Post Internship
                </button>
                <a href="/company/dashboard.php" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </main>
</body>
</html>
