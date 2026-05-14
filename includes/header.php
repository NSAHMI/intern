<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'InternHub'; ?> - InternHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1f2937;
            --gray: #6b7280;
            --light: #f3f4f6;
            --white: #ffffff;
            --border: #e5e7eb;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
            --radius: 12px;
            --radius-lg: 16px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--light);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Navbar */
        .navbar {
            background: var(--white);
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-link {
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-user-info {
            text-align: right;
        }

        .nav-user-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .nav-user-role {
            font-size: 0.75rem;
            color: var(--gray);
            text-transform: capitalize;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--light);
            color: var(--dark);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        .btn-success { background: var(--success); color: var(--white); }
        .btn-danger { background: var(--danger); color: var(--white); }
        .btn-warning { background: var(--warning); color: var(--dark); }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Cards */
        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-primary { background: rgba(99,102,241,0.1); color: var(--primary); }
        .badge-success { background: rgba(34,197,94,0.1); color: var(--success); }
        .badge-warning { background: rgba(245,158,11,0.1); color: var(--warning); }
        .badge-danger { background: rgba(239,68,68,0.1); color: var(--danger); }
        .badge-info { background: rgba(59,130,246,0.1); color: var(--info); }
        .badge-secondary { background: var(--light); color: var(--gray); }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success { background: rgba(34,197,94,0.1); color: #15803d; border: 1px solid rgba(34,197,94,0.2); }
        .alert-danger { background: rgba(239,68,68,0.1); color: #b91c1c; border: 1px solid rgba(239,68,68,0.2); }
        .alert-warning { background: rgba(245,158,11,0.1); color: #b45309; border: 1px solid rgba(245,158,11,0.2); }
        .alert-info { background: rgba(59,130,246,0.1); color: #1d4ed8; border: 1px solid rgba(59,130,246,0.2); }

        /* Tables */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .table th {
            background: var(--light);
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--gray);
            text-transform: uppercase;
        }

        .table tbody tr:hover {
            background: var(--light);
        }

        /* Grid */
        .grid {
            display: grid;
            gap: 1.5rem;
        }

        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }

        @media (max-width: 1024px) {
            .grid-4 { grid-template-columns: repeat(2, 1fr); }
            .grid-3 { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .grid-4, .grid-3, .grid-2 { grid-template-columns: 1fr; }
            .nav-links { display: none; }
            .container { padding: 1rem; }
        }

        /* Stats Cards */
        .stat-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .stat-icon-primary { background: rgba(99,102,241,0.1); color: var(--primary); }
        .stat-icon-success { background: rgba(34,197,94,0.1); color: var(--success); }
        .stat-icon-warning { background: rgba(245,158,11,0.1); color: var(--warning); }
        .stat-icon-info { background: rgba(59,130,246,0.1); color: var(--info); }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--border);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 1.5rem;
        }

        /* Utilities */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .mt-1 { margin-top: 0.5rem; }
        .mt-2 { margin-top: 1rem; }
        .mt-3 { margin-top: 1.5rem; }
        .mb-1 { margin-bottom: 0.5rem; }
        .mb-2 { margin-bottom: 1rem; }
        .mb-3 { margin-bottom: 1.5rem; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .gap-1 { gap: 0.5rem; }
        .gap-2 { gap: 1rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/index.php" class="logo">
                <i class="fas fa-briefcase"></i> InternHub
            </a>

            <?php if (is_logged_in()): ?>
                <div class="nav-links">
                    <?php if (get_user_role() === 'student'): ?>
                        <a href="/student/dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                        <a href="/student/browse.php" class="nav-link"><i class="fas fa-search"></i> Browse</a>
                        <a href="/student/applications.php" class="nav-link"><i class="fas fa-file-alt"></i> My Applications</a>
                    <?php elseif (get_user_role() === 'company'): ?>
                        <a href="/company/dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                        <a href="/company/internships.php" class="nav-link"><i class="fas fa-briefcase"></i> Internships</a>
                        <a href="/company/applicants.php" class="nav-link"><i class="fas fa-users"></i> Applicants</a>
                    <?php elseif (get_user_role() === 'admin'): ?>
                        <a href="/admin/dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                        <a href="/admin/users.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
                        <a href="/admin/companies.php" class="nav-link"><i class="fas fa-building"></i> Companies</a>
                        <a href="/admin/internships.php" class="nav-link"><i class="fas fa-briefcase"></i> Internships</a>
                    <?php endif; ?>
                </div>

                <div class="nav-user">
                    <div class="nav-user-info">
                        <div class="nav-user-name"><?php echo clean(get_user_name()); ?></div>
                        <div class="nav-user-role"><?php echo get_user_role(); ?></div>
                    </div>
                    <a href="/auth/logout.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            <?php else: ?>
                <div class="nav-links">
                    <a href="/index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
                    <a href="/browse.php" class="nav-link"><i class="fas fa-search"></i> Browse Internships</a>
                </div>
                <div class="nav-user">
                    <a href="/auth/login.php" class="btn btn-secondary">Sign In</a>
                    <a href="/auth/register.php" class="btn btn-primary">Get Started</a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <main>
        <?php
        $flash = get_flash();
        if ($flash):
        ?>
            <div class="container" style="padding-bottom: 0;">
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                    <?php echo $flash['message']; ?>
                </div>
            </div>
        <?php endif; ?>
