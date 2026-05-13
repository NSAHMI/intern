<?php
// Internship Management System - Landing Page
session_start();

// Redirect logged-in users to their dashboard
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'student') {
        header('Location: student/dashboard.php');
        exit;
    } elseif ($role === 'company') {
        header('Location: company/dashboard.php');
        exit;
    } elseif ($role === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Connect students with amazing internship opportunities. A world-class platform for students, companies, and administrators.">
    <title>Internship Management System - Connect Students with Opportunities</title>
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
            <h1 class="header-title">Internship Management System 🚀</h1>
            <p class="header-subtitle">Connect students with amazing internship opportunities</p>
            
            <div class="nav-buttons">
                <a href="auth/login.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="auth/register.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-user-plus"></i> Register
                </a>
                <a href="public_internships.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-briefcase"></i> Browse Internships
                </a>
                <a href="student/dashboard.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-graduation-cap"></i> Student Portal
                </a>
                <a href="company/dashboard.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-building"></i> Company Portal
                </a>
                <a href="admin/dashboard.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-shield-alt"></i> Admin Portal
                </a>
            </div>
        </div>
    </div>

    <div class="content-section">
        <div class="row text-center mb-4">
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="text-primary mb-3">
                            <i class="fas fa-graduation-cap fa-3x"></i>
                        </div>
                        <h5 class="fw-bold">For Students</h5>
                        <p class="text-muted mb-3">Discover and apply for exciting internship opportunities. Build your career with real-world experience.</p>
                        <ul class="list-unstyled text-start small text-muted">
                            <li><i class="fas fa-check text-success me-2"></i>Browse internships by field</li>
                            <li><i class="fas fa-check text-success me-2"></i>Track your applications</li>
                            <li><i class="fas fa-check text-success me-2"></i>Earn achievements &amp; points</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="text-success mb-3">
                            <i class="fas fa-building fa-3x"></i>
                        </div>
                        <h5 class="fw-bold">For Companies</h5>
                        <p class="text-muted mb-3">Post internships and connect with talented students. Find the perfect candidates for your team.</p>
                        <ul class="list-unstyled text-start small text-muted">
                            <li><i class="fas fa-check text-success me-2"></i>Post unlimited internships</li>
                            <li><i class="fas fa-check text-success me-2"></i>Review applications easily</li>
                            <li><i class="fas fa-check text-success me-2"></i>Message candidates directly</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="text-warning mb-3">
                            <i class="fas fa-shield-alt fa-3x"></i>
                        </div>
                        <h5 class="fw-bold">For Administrators</h5>
                        <p class="text-muted mb-3">Manage users and oversee the platform. Keep everything running smoothly.</p>
                        <ul class="list-unstyled text-start small text-muted">
                            <li><i class="fas fa-check text-success me-2"></i>User management tools</li>
                            <li><i class="fas fa-check text-success me-2"></i>Analytics dashboard</li>
                            <li><i class="fas fa-check text-success me-2"></i>Content management</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="text-center mt-4 pt-4 border-top">
            <h5 class="mb-4"><i class="fas fa-star text-warning me-2"></i>Platform Features</h5>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded">
                        <i class="fas fa-search fa-2x text-primary mb-2"></i>
                        <div class="small fw-semibold">Advanced Search</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded">
                        <i class="fas fa-envelope fa-2x text-info mb-2"></i>
                        <div class="small fw-semibold">In-App Messaging</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded">
                        <i class="fas fa-trophy fa-2x text-warning mb-2"></i>
                        <div class="small fw-semibold">Gamification</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded">
                        <i class="fas fa-mobile-alt fa-2x text-success mb-2"></i>
                        <div class="small fw-semibold">Mobile Ready</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
