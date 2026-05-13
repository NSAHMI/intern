<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'company') {
    header('Location: ../auth/login.php');
    exit;
}
include "../config/db.php";

// Get company profile to check verification/allocation status
$profile_stmt = $conn->prepare('SELECT * FROM company_profiles WHERE user_id = ?');
$profile_stmt->bind_param('i', $_SESSION['user_id']);
$profile_stmt->execute();
$company_profile = $profile_stmt->get_result()->fetch_assoc();
$profile_stmt->close();

// Check if company profile is complete (indicates proper allocation)
$is_profile_complete = $company_profile && !empty($company_profile['company_name']) && !empty($company_profile['description']);
$is_verified = $company_profile['is_verified'] ?? false;

// Get company's internships
$stmt = $conn->prepare(
    'SELECT id, title, description, duration, created_at, status
     FROM internships
     WHERE company_id = ?
     ORDER BY created_at DESC'
);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$internships = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get application counts for each internship
foreach ($internships as &$internship) {
    $stmt = $conn->prepare(
        'SELECT COUNT(*) as count, status
         FROM applications
         WHERE internship_id = ?
         GROUP BY status'
    );
    $stmt->bind_param('i', $internship['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $internship['applications'] = [];
    while ($row = $result->fetch_assoc()) {
        $internship['applications'][$row['status']] = $row['count'];
    }
    $stmt->close();
}

// Get total statistics
$total_applications = 0;
$pending_applications = 0;
$accepted_applications = 0;
foreach ($internships as $internship) {
    $total_applications += array_sum($internship['applications']);
    $pending_applications += $internship['applications']['pending'] ?? 0;
    $accepted_applications += $internship['applications']['accepted'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Dashboard - Internship Management System</title>
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
            color: white;
        }

        .content-section {
            padding: 2rem;
        }

        .status-banner {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-banner.pending {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 1px solid #f59e0b;
            color: #92400e;
        }

        .status-banner.verified {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border: 1px solid #10b981;
            color: #065f46;
        }

        .status-banner.incomplete {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border: 1px solid #ef4444;
            color: #991b1b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--card-shadow);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-hover-shadow);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            font-weight: 500;
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

        .btn-apply {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            color: white;
        }

        .btn-apply.btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .header-title {
                font-size: 1.75rem;
            }

            .nav-buttons {
                gap: 0.5rem;
            }

            .btn-custom {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-number {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">Company Portal 🏢</h1>
            <p class="header-subtitle">Manage your internship postings and connect with talented students</p>

            <div class="nav-buttons">
                <a href="dashboard.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="post_internship.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-plus-circle"></i> Post Internship
                </a>
                <a href="profile.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-building"></i> Company Profile
                </a>
                <a href="../messages.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="../auth/setup_email_2fa.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-shield-alt"></i> 2FA Settings
                </a>
                <a href="../auth/logout.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="content-section">
        <!-- Company Status Banner -->
        <?php if (!$is_profile_complete): ?>
            <div class="status-banner incomplete">
                <i class="fas fa-exclamation-circle fa-2x"></i>
                <div>
                    <strong>Profile Incomplete</strong>
                    <p class="mb-0">Please complete your company profile to be fully connected to the platform. This helps students learn about your organisation.</p>
                    <a href="profile.php" class="btn btn-sm btn-danger mt-2">
                        <i class="fas fa-edit me-1"></i> Complete Profile
                    </a>
                </div>
            </div>
        <?php elseif (!$is_verified): ?>
            <div class="status-banner pending">
                <i class="fas fa-clock fa-2x"></i>
                <div>
                    <strong>Pending Verification</strong>
                    <p class="mb-0">Your company profile is under review. Once verified, you'll have full access to all features and a verification badge will be displayed to students.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="status-banner verified">
                <i class="fas fa-check-circle fa-2x"></i>
                <div>
                    <strong>Verified Company</strong>
                    <p class="mb-0">Your company is verified and fully connected to the platform. Students can trust your internship postings.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-briefcase fa-2x text-primary mb-2"></i>
                <div class="stat-number"><?php echo count($internships); ?></div>
                <div class="stat-label">Active Postings</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users fa-2x text-info mb-2"></i>
                <div class="stat-number"><?php echo $total_applications; ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-hourglass-half fa-2x text-warning mb-2"></i>
                <div class="stat-number"><?php echo $pending_applications; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-check fa-2x text-success mb-2"></i>
                <div class="stat-number"><?php echo $accepted_applications; ?></div>
                <div class="stat-label">Accepted</div>
            </div>
        </div>

        <!-- Internship Listings -->
        <h5 class="mb-3"><i class="fas fa-briefcase me-2"></i>Your Internship Postings</h5>

        <?php if (empty($internships)): ?>
            <div class="text-center py-5">
                <div class="text-muted mb-4">
                    <i class="fas fa-briefcase fa-4x"></i>
                </div>
                <h4>No Internships Posted Yet</h4>
                <p class="text-muted mb-4">Start by posting your first internship to connect with talented students!</p>
                <a href="post_internship.php" class="btn-apply">
                    <i class="fas fa-plus-circle"></i> Post Your First Internship
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($internships as $internship): ?>
                    <div class="col-md-6">
                        <div class="internship-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold text-dark"><?php echo htmlspecialchars($internship['title'], ENT_QUOTES); ?></h5>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Posted: <?php echo date('M j, Y', strtotime($internship['created_at'])); ?>
                                    </small>
                                </div>
                                <?php
                                $status = $internship['status'] ?? 'active';
                                $status_class = $status === 'active' ? 'bg-success' : ($status === 'inactive' ? 'bg-secondary' : 'bg-danger');
                                $status_icon = $status === 'active' ? 'fa-check-circle' : ($status === 'inactive' ? 'fa-pause-circle' : 'fa-times-circle');
                                ?>
                                <div class="badge <?php echo $status_class; ?>">
                                    <i class="fas <?php echo $status_icon; ?>"></i> <?php echo ucfirst($status); ?>
                                </div>
                            </div>

                            <p class="text-muted mb-3">
                                <?php echo htmlspecialchars(substr($internship['description'], 0, 120), ENT_QUOTES); ?>...
                            </p>

                            <div class="mb-3">
                                <span class="badge bg-primary me-2">
                                    <i class="fas fa-clock"></i> <?php echo htmlspecialchars($internship['duration'], ENT_QUOTES); ?>
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-warning text-dark me-1">
                                        <i class="fas fa-hourglass-half"></i> <?php echo $internship['applications']['pending'] ?? 0; ?> Pending
                                    </span>
                                    <span class="badge bg-success">
                                        <i class="fas fa-user-check"></i> <?php echo $internship['applications']['accepted'] ?? 0; ?> Accepted
                                    </span>
                                </div>
                                <a href="view_applications.php?id=<?php echo $internship['id']; ?>" class="btn-apply btn-sm">
                                    <i class="fas fa-eye"></i> View Applications
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
