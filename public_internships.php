<?php
include "config/db.php";

// Get all active internships for public viewing
$stmt = $conn->prepare(
    'SELECT i.*, u.name as company_name FROM internships i JOIN users u ON i.company_id = u.id WHERE i.expiration_date >= CURDATE() ORDER BY i.created_at DESC'
);
$stmt->execute();
$result = $stmt->get_result();
$internships = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Internship Opportunities - Public View</title>
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
        
        .internship-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            height: 100%;
        }
        
        .internship-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-hover-shadow);
            border-color: var(--primary-color);
        }
        
        .card-title {
            color: var(--dark-color);
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .card-company {
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .card-description {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .card-duration {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
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
        
        .alert-custom {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: none;
            border-radius: 12px;
            color: #92400e;
            padding: 1.5rem;
        }
        
        .no-internships-icon {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        
        .stats-row {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #6b7280;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">Internship Opportunities 🚀</h1>
            <p class="header-subtitle">Discover exciting internship opportunities from top companies</p>
            
            <div class="nav-buttons">
                <a href="index.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="auth/login.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-sign-in-alt"></i> Student Login
                </a>
                <a href="auth/register.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            </div>
        </div>
    </div>

    <div class="content-section">
        <!-- Statistics -->
        <div class="stats-row">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($internships); ?></div>
                        <div class="stat-label">Active Opportunities</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">
                            <?php 
                            $companies = array_unique(array_column($internships, 'company_name'));
                            echo count($companies);
                            ?>
                        </div>
                        <div class="stat-label">Participating Companies</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">
                            <?php 
                            $avg_duration = 0;
                            if (!empty($internships)) {
                                foreach ($internships as $internship) {
                                    if (preg_match('/(\d+)/', $internship['duration'], $matches)) {
                                        $avg_duration += $matches[1];
                                    }
                                }
                                echo round($avg_duration / count($internships), 1);
                            } else {
                                echo '0';
                            }
                            ?>
                        </div>
                        <div class="stat-label">Avg. Duration (Months)</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">
                            <?php 
                            $expiring_soon = 0;
                            foreach ($internships as $internship) {
                                $days_left = (new DateTime($internship['expiration_date']))->diff(new DateTime())->days;
                                if ($days_left <= 7) {
                                    $expiring_soon++;
                                }
                            }
                            echo $expiring_soon;
                            ?>
                        </div>
                        <div class="stat-label">Expiring Soon (≤7 days)</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($internships)): ?>
            <div class="alert-custom text-center">
                <div class="no-internships-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h4>No Active Internships Available</h4>
                <p class="mb-3">Check back later for new opportunities from top companies!</p>
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn-apply" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($internships as $internship): ?>
                    <div class="col-md-6">
                        <div class="internship-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($internship['title'], ENT_QUOTES); ?></h5>
                                    <div class="card-company">
                                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($internship['company_name'], ENT_QUOTES); ?>
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-2">
                                    <div class="badge bg-<?php 
                                        echo (new DateTime($internship['expiration_date']) < new DateTime()) ? 'danger' : 'success'; 
                                    ?>">
                                        <i class="fas fa-<?php 
                                            echo (new DateTime($internship['expiration_date']) < new DateTime()) ? 'times-circle' : 'check-circle'; 
                                        ?>"></i> 
                                        <?php 
                                            if (new DateTime($internship['expiration_date']) < new DateTime()) {
                                                echo 'Expired';
                                            } else {
                                                $days_left = (new DateTime($internship['expiration_date']))->diff(new DateTime())->days;
                                                echo $days_left . ' days left';
                                            }
                                        ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt"></i> 
                                        Expires: <?php echo date('M j, Y', strtotime($internship['expiration_date'])); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <p class="card-description"><?php echo htmlspecialchars($internship['description'], ENT_QUOTES); ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="card-duration">
                                    <i class="fas fa-clock"></i> <?php echo htmlspecialchars($internship['duration'], ENT_QUOTES); ?>
                                </div>
                                <a href="auth/login.php" class="btn-apply">
                                    <i class="fas fa-sign-in-alt"></i> Login to Apply
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Call to Action -->
        <div class="text-center mt-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h4 class="mb-3">Ready to Apply?</h4>
                    <p class="text-muted mb-4">Create an account to track your applications and get notified about new opportunities.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="auth/register.php" class="btn-apply">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </a>
                        <a href="auth/login.php" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
