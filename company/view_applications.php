<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'company') {
    header('Location: ../auth/login.php');
    exit;
}
include "../config/db.php";

$internship_id = $_GET['id'] ?? '';
$error = '';
$success = '';

// Verify internship belongs to this company
$stmt = $conn->prepare(
    'SELECT id, title, description, duration FROM internships WHERE id = ? AND company_id = ?'
);
$stmt->bind_param('ii', $internship_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$internship = $result->fetch_assoc();
$stmt->close();

if (!$internship) {
    $error = 'Internship not found or access denied.';
}

// Get applications for this internship
$applications = [];
if ($internship) {
    $stmt = $conn->prepare(
        'SELECT a.id, a.status, a.applied_at, u.name, u.email
         FROM applications a
         JOIN users u ON a.student_id = u.id
         WHERE a.internship_id = ?
         ORDER BY a.applied_at DESC'
    );
    $stmt->bind_param('i', $internship_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $applications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Handle application status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['action'])) {
    $application_id = $_POST['application_id'];
    $action = $_POST['action'];
    
    if (in_array($action, ['accept', 'reject'])) {
        $status = $action === 'accept' ? 'accepted' : 'rejected';
        $stmt = $conn->prepare(
            'UPDATE applications SET status = ? WHERE id = ? AND internship_id = ?'
        );
        $stmt->bind_param('sii', $status, $application_id, $internship_id);
        
        if ($stmt->execute()) {
            $success = 'Application ' . $status . ' successfully.';
            // Refresh applications list
            header('Location: view_applications.php?id=' . urlencode($internship_id) . '&success=' . urlencode($success));
            exit;
        } else {
            $error = 'Unable to update application status.';
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
    <title>View Applications - Internship Management System</title>
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
            margin-bottom: 1rem;
        }
        
        .application-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-hover-shadow);
        }
        
        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }
        
        .btn-accept {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
        }
        
        .btn-accept:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .btn-reject {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
        }
        
        .btn-reject:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">Applications Management 📋</h1>
            <p class="header-subtitle">Review and manage applications for your internship postings</p>
            
            <div class="nav-buttons">
                <a href="dashboard.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
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
                <a href="../auth/logout.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="content-section">
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 mb-4" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error, ENT_QUOTES); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success border-0 mb-4" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success, ENT_QUOTES); ?>
            </div>
        <?php endif; ?>

        <?php if ($internship): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-4">
                    <h4 class="mb-2">
                        <i class="fas fa-briefcase text-primary me-2"></i>
                        <?php echo htmlspecialchars($internship['title'], ENT_QUOTES); ?>
                    </h4>
                    <div class="text-secondary">
                        <i class="fas fa-clock me-1"></i> Duration: <?php echo htmlspecialchars($internship['duration'], ENT_QUOTES); ?>
                    </div>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo htmlspecialchars($internship['description'], ENT_QUOTES); ?></p>
                </div>
            </div>

            <?php if (empty($applications)): ?>
                <div class="text-center py-5">
                    <div class="text-muted mb-4">
                        <i class="fas fa-user-slash fa-3x"></i>
                    </div>
                    <h4>No Applications Yet</h4>
                    <p class="text-muted">No students have applied to this internship yet.</p>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4">
                        <h5 class="mb-0">
                            <i class="fas fa-users text-success me-2"></i>Applicants (<?php echo count($applications); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($applications as $application): ?>
                            <div class="application-card">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">
                                            <div class="text-primary me-3">
                                                <i class="fas fa-user-circle fa-2x"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($application['name'], ENT_QUOTES); ?></h6>
                                                <div class="text-muted small">
                                                    <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($application['email'], ENT_QUOTES); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-muted small">
                                            <i class="fas fa-calendar me-1"></i> Applied: <?php echo date('M j, Y', strtotime($application['applied_at'])); ?>
                                        </div>
                                        <div class="mt-2">
                                            <?php if ($application['status'] === 'pending'): ?>
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-hourglass-half me-1"></i> Pending
                                                </span>
                                            <?php elseif ($application['status'] === 'accepted'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle me-1"></i> Accepted
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times-circle me-1"></i> Rejected
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-5 text-end">
                                        <?php if ($application['status'] === 'pending'): ?>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                <input type="hidden" name="action" value="accept">
                                                <button type="submit" class="btn-action btn-accept" 
                                                        onclick="return confirm('Accept this application?')">
                                                    <i class="fas fa-check me-1"></i> Accept
                                                </button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn-action btn-reject" 
                                                        onclick="return confirm('Reject this application?')">
                                                    <i class="fas fa-times me-1"></i> Reject
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-check me-1"></i> Processed
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
