<?php
/**
 * Apply for Internship
 * Internship Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_role('student');

$internship_id = $_GET['id'] ?? '';
$error = '';
$success = '';

// Get internship details
$internship = db_fetch("
    SELECT i.*, cp.company_name, cp.industry, cp.location as company_location
    FROM internships i
    JOIN company_profiles cp ON i.company_id = cp.user_id
    WHERE i.id = ? AND i.status = 'open'
", [$internship_id]);

if (!$internship) {
    $error = 'Internship not found or is no longer available.';
}

// Check if already applied
if ($internship && empty($error)) {
    $existing = db_fetch(
        "SELECT id FROM applications WHERE internship_id = ? AND student_id = ?",
        [$internship_id, $_SESSION['user_id']]
    );
    if ($existing) {
        $error = 'You have already applied for this internship.';
    }
}

// Handle application submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $internship && empty($error)) {
    $cover_letter = trim($_POST['cover_letter'] ?? '');

    if (empty($cover_letter)) {
        $error = 'Please provide a cover letter.';
    } else {
        // Insert application
        db_query(
            "INSERT INTO applications (student_id, internship_id, cover_letter, status) VALUES (?, ?, ?, 'pending')",
            [$_SESSION['user_id'], $internship_id, $cover_letter]
        );

        // Create notification for company
        $notification_msg = $_SESSION['user_name'] . ' has applied for ' . $internship['title'];
        db_query(
            "INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, 'application', ?)",
            [$internship['company_id'], 'New Application', $notification_msg, '/company/applications.php']
        );

        set_flash('success', 'Your application has been submitted successfully!');
        redirect('/student/applications.php');
    }
}

$page_title = 'Apply for Internship';
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

        /* Sidebar */
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

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 2rem;
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

        /* Cards */
        .card {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .card-company {
            color: var(--gray);
            font-size: 0.95rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-description {
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--light);
            border-radius: 8px;
            font-size: 0.9rem;
            color: var(--dark);
        }

        .meta-item i { color: var(--primary); }

        /* Form */
        .form-group { margin-bottom: 1.5rem; }
        .form-label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
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
        textarea.form-control { resize: vertical; min-height: 200px; }
        .form-hint {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 0.5rem;
        }

        /* Buttons */
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
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        /* Alert */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-danger {
            background: rgba(239,68,68,0.1);
            color: #b91c1c;
        }
        .alert-success {
            background: rgba(34,197,94,0.1);
            color: #15803d;
        }

        /* Badge */
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-open { background: rgba(34,197,94,0.1); color: var(--success); }
        .badge-remote { background: rgba(99,102,241,0.1); color: var(--primary); }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="/index.php" class="sidebar-logo">
            <i class="fas fa-briefcase"></i> InternHub
        </a>
        <ul class="sidebar-nav">
            <li><a href="/student/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="/student/browse.php"><i class="fas fa-search"></i> Browse Internships</a></li>
            <li><a href="/student/applications.php"><i class="fas fa-clipboard-list"></i> My Applications</a></li>
            <li><a href="/student/profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="/student/messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
            <li><a href="/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Apply for Internship</h1>
            <div class="breadcrumb">
                <a href="/student/dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <a href="/student/browse.php">Browse</a>
                <i class="fas fa-chevron-right"></i>
                <span>Apply</span>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <a href="/student/browse.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Browse
            </a>
        <?php elseif ($internship): ?>
            <!-- Internship Details -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2 class="card-title"><?php echo htmlspecialchars($internship['title']); ?></h2>
                        <p class="card-company">
                            <i class="fas fa-building"></i>
                            <?php echo htmlspecialchars($internship['company_name']); ?>
                            <?php if ($internship['industry']): ?>
                                <span style="color: var(--border);">|</span>
                                <?php echo htmlspecialchars($internship['industry']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <span class="badge badge-open"><i class="fas fa-check-circle"></i> Open</span>
                </div>

                <div class="card-meta">
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($internship['location'] ?? 'Remote'); ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <?php echo htmlspecialchars($internship['duration'] ?? 'Flexible'); ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-laptop-house"></i>
                        <?php echo ucfirst($internship['type']); ?>
                    </div>
                    <?php if ($internship['stipend']): ?>
                        <div class="meta-item">
                            <i class="fas fa-dollar-sign"></i>
                            <?php echo htmlspecialchars($internship['stipend']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <p class="card-description"><?php echo nl2br(htmlspecialchars($internship['description'])); ?></p>

                <?php if ($internship['requirements']): ?>
                    <h4 style="font-weight: 600; margin-bottom: 0.5rem;">Requirements</h4>
                    <p class="card-description"><?php echo nl2br(htmlspecialchars($internship['requirements'])); ?></p>
                <?php endif; ?>

                <?php if ($internship['responsibilities']): ?>
                    <h4 style="font-weight: 600; margin-bottom: 0.5rem;">Responsibilities</h4>
                    <p class="card-description"><?php echo nl2br(htmlspecialchars($internship['responsibilities'])); ?></p>
                <?php endif; ?>
            </div>

            <!-- Application Form -->
            <div class="card">
                <h3 style="font-weight: 700; margin-bottom: 1.5rem;">
                    <i class="fas fa-paper-plane" style="color: var(--primary);"></i> Submit Your Application
                </h3>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Cover Letter</label>
                        <textarea name="cover_letter" class="form-control"
                                  placeholder="Tell us why you're interested in this internship and why you'd be a good fit..."
                                  required><?php echo htmlspecialchars($_POST['cover_letter'] ?? ''); ?></textarea>
                        <p class="form-hint">
                            Explain your qualifications, relevant experience, and interest in this position.
                            A good cover letter can significantly increase your chances.
                        </p>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Application
                        </button>
                        <a href="/student/browse.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
