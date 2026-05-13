<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}
include "../config/db.php";
include "../config/email.php";
include "../config/gamification.php";

$internship_id = $_GET['id'] ?? '';
$error = '';
$success = '';

// Get internship details
$stmt = $conn->prepare(
    'SELECT i.id, i.title, i.description, i.duration, u.name AS company_name
     FROM internships i
     JOIN users u ON i.company_id = u.id
     WHERE i.id = ?'
);
$stmt->bind_param('i', $internship_id);
$stmt->execute();
$result = $stmt->get_result();
$internship = $result->fetch_assoc();
$stmt->close();

if (!$internship) {
    $error = 'Internship not found.';
}

// Check if already applied
if ($internship && empty($error)) {
    $stmt = $conn->prepare(
        'SELECT id FROM applications WHERE internship_id = ? AND student_id = ?'
    );
    $stmt->bind_param('ii', $internship_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $error = 'You have already applied for this internship.';
    }
    $stmt->close();
}

// Handle application submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $internship && empty($error)) {
    $cover_letter = trim($_POST['cover_letter'] ?? '');
    
    if ($cover_letter === '') {
        $error = 'Please provide a cover letter.';
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert application
            $stmt = $conn->prepare(
                'INSERT INTO applications (internship_id, student_id, cover_letter) VALUES (?, ?, ?)'
            );
            $stmt->bind_param('iis', $internship_id, $_SESSION['user_id'], $cover_letter);
            $stmt->execute();
            $stmt->close();
            
            // Get student email and name
            $stmt = $conn->prepare('SELECT name, email FROM users WHERE id = ?');
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            // Send email notification
            $email = new EmailNotification($conn);
            $email_sent = $email->sendApplicationReceived(
                $student['email'],
                $student['name'],
                $internship['title'],
                $internship['company_name']
            );
            
            // Check for gamification achievements
            $gamification = new GamificationSystem($conn);
            $new_achievements = $gamification->checkAchievements($_SESSION['user_id']);
            
            // Log activity
            $stmt = $conn->prepare('INSERT INTO user_activity (user_id, activity_type, resource_type, resource_id) VALUES (?, "apply", "internship", ?)');
            $stmt->bind_param('ii', $_SESSION['user_id'], $internship_id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            $success = 'Application submitted successfully! A confirmation email has been sent to your registered email address.';
            if (!empty($new_achievements)) {
                $success .= ' 🎉 You earned new achievements!';
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Unable to submit application. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply for Internship</title>
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
            <h1 class="header-title">Apply for Internship 📝</h1>
            <p class="header-subtitle">Submit your application and take the next step in your career</p>
            
            <div class="nav-buttons">
                <a href="dashboard.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
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
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 mb-4" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error, ENT_QUOTES); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success border-0 mb-4" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success, ENT_QUOTES); ?>
                        <div class="mt-3">
                            <a href="dashboard.php" class="btn-apply">
                                <i class="fas fa-tachometer-alt me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($internship && empty($success)): ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 pt-4">
                            <h4 class="mb-2">
                                <i class="fas fa-briefcase text-primary me-2"></i>
                                <?php echo htmlspecialchars($internship['title'], ENT_QUOTES); ?>
                            </h4>
                            <div class="text-secondary">
                                <i class="fas fa-building me-1"></i> <?php echo htmlspecialchars($internship['company_name'], ENT_QUOTES); ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="mb-3"><?php echo htmlspecialchars($internship['description'], ENT_QUOTES); ?></p>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary">
                                    <i class="fas fa-clock me-1"></i> <?php echo htmlspecialchars($internship['duration'], ENT_QUOTES); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pt-4">
                            <h5 class="mb-0">
                                <i class="fas fa-paper-plane text-success me-2"></i>Submit Your Application
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="apply.php?id=<?php echo urlencode($internship_id); ?>">
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-file-alt me-2"></i>Cover Letter
                                    </label>
                                    <textarea name="cover_letter" class="form-control" rows="8" 
                                              placeholder="Tell us why you're interested in this internship and why you'd be a good fit..." required></textarea>
                                    <div class="form-text">Explain your qualifications, experience, and interest in this position.</div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn-apply">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Application
                                    </button>
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
