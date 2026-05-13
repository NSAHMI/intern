<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}
include "../config/db.php";

// Get student's applications
$stmt = $conn->prepare(
    'SELECT a.id, a.status, a.applied_at, i.title, i.duration, u.name AS company_name
     FROM applications a
     JOIN internships i ON a.internship_id = i.id
     JOIN users u ON i.company_id = u.id
     WHERE a.student_id = ?
     ORDER BY a.applied_at DESC'
);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$applications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Applications</title>
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
        
        .application-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            height: 100%;
        }
        
        .application-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-hover-shadow);
            border-color: var(--primary-color);
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
            <h1 class="header-title">My Applications 📋</h1>
            <p class="header-subtitle">Track your internship application journey</p>
            
            <div class="nav-buttons">
                <a href="dashboard.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-search"></i> Browse Internships
                </a>
                <a href="../auth/logout.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="content-section">
        <?php if (empty($applications)): ?>
            <div class="text-center py-5">
                <div class="text-muted mb-4">
                    <i class="fas fa-clipboard-list fa-4x"></i>
                </div>
                <h4>No Applications Yet</h4>
                <p class="text-muted mb-4">Start applying to internships to track your progress here!</p>
                <a href="dashboard.php" class="btn-apply">
                    <i class="fas fa-search me-2"></i>Browse Internships
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4 mb-4">
                <?php foreach ($applications as $application): ?>
                    <div class="col-md-6">
                        <div class="application-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold text-dark"><?php echo htmlspecialchars($application['title'], ENT_QUOTES); ?></h5>
                                    <div class="text-secondary mb-2">
                                        <i class="fas fa-building me-1"></i> <?php echo htmlspecialchars($application['company_name'], ENT_QUOTES); ?>
                                    </div>
                                </div>
                                <div class="badge bg-<?php 
                                    echo $application['status'] === 'pending' ? 'warning' : 
                                         ($application['status'] === 'accepted' ? 'success' : 'danger'); 
                                ?>">
                                    <i class="fas fa-<?php 
                                        echo $application['status'] === 'pending' ? 'hourglass-half' : 
                                             ($application['status'] === 'accepted' ? 'check-circle' : 'times-circle'); 
                                    ?> me-1"></i>
                                    <?php echo ucfirst(htmlspecialchars($application['status'], ENT_QUOTES)); ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <span class="text-primary">
                                    <i class="fas fa-clock me-1"></i> <?php echo htmlspecialchars($application['duration'], ENT_QUOTES); ?>
                                </span>
                            </div>
                            
                            <div class="text-muted small mb-3">
                                <i class="fas fa-calendar me-1"></i> Applied: <?php echo date('M j, Y', strtotime($application['applied_at'])); ?>
                            </div>
                            
                            <div class="text-<?php 
                                echo $application['status'] === 'pending' ? 'warning' : 
                                     ($application['status'] === 'accepted' ? 'success' : 'danger'); 
                            ?>">
                                <?php 
                                if ($application['status'] === 'pending') {
                                    echo '<i class="fas fa-hourglass-half me-1"></i> Application under review';
                                } elseif ($application['status'] === 'accepted') {
                                    echo '<i class="fas fa-check-circle me-1"></i> Congratulations! You were accepted.';
                                } else {
                                    echo '<i class="fas fa-times-circle me-1"></i> Application was not selected.';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-chart-pie text-primary me-2"></i>Application Summary
                    </h5>
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="border-end">
                                <h4 class="text-warning"><?php 
                                    echo count(array_filter($applications, fn($a) => $a['status'] === 'pending')); 
                                ?></h4>
                                <p class="text-muted mb-0">Pending</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border-end">
                                <h4 class="text-success"><?php 
                                    echo count(array_filter($applications, fn($a) => $a['status'] === 'accepted')); 
                                ?></h4>
                                <p class="text-muted mb-0">Accepted</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h4 class="text-danger"><?php 
                                echo count(array_filter($applications, fn($a) => $a['status'] === 'rejected')); 
                            ?></h4>
                            <p class="text-muted mb-0">Rejected</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
