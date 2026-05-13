<?php
require_once 'config/db.php';
require_once 'config/cms.php';

$cms = new CMSManager($conn);
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

$page = $cms->getPageBySlug($slug);

if (!$page) {
    // Page not found - show 404
    http_response_code(404);
    $page_title = 'Page Not Found';
    $page_content = '<h1>404 - Page Not Found</h1><p>The page you are looking for does not exist.</p>';
    $meta_description = 'Page not found';
} else {
    $page_title = $page['title'];
    $page_content = $page['content'];
    $meta_description = $page['meta_description'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Internship Hub</title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#6366f1">
    <link rel="manifest" href="/intern/manifest.json">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #f59e0b;
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
            max-width: 900px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .header-section {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2.5rem 2rem;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        
        .content-section {
            padding: 3rem 2rem;
        }
        
        .page-content {
            line-height: 1.8;
            color: #374151;
        }
        
        .page-content h1, .page-content h2, .page-content h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .page-content h1 { font-size: 2.5rem; }
        .page-content h2 { font-size: 2rem; }
        .page-content h3 { font-size: 1.5rem; }
        
        .page-content p {
            margin-bottom: 1.5rem;
        }
        
        .page-content ul, .page-content ol {
            margin-bottom: 1.5rem;
            padding-left: 2rem;
        }
        
        .page-content li {
            margin-bottom: 0.5rem;
        }
        
        .page-content a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .page-content a:hover {
            text-decoration: underline;
        }
        
        .page-content blockquote {
            border-left: 4px solid var(--primary-color);
            padding-left: 1.5rem;
            margin: 2rem 0;
            font-style: italic;
            color: #6b7280;
        }
        
        .page-content code {
            background: #f3f4f6;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        
        .page-content pre {
            background: #1f2937;
            color: #f9fafb;
            padding: 1.5rem;
            border-radius: 8px;
            overflow-x: auto;
            margin: 2rem 0;
        }
        
        .page-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
        }
        
        .page-content th, .page-content td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .page-content th {
            background: #f9fafb;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .btn-back {
            background: white;
            color: var(--primary-color);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
            color: var(--primary-dark);
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 2rem;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
            color: rgba(255, 255, 255, 0.7);
        }
        
        .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            color: white;
        }
        
        .breadcrumb-item.active {
            color: white;
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item">
                    <a href="index.php">Home</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($page_title); ?>
                </li>
            </ol>
        </nav>
        
        <h1 class="mb-3"><?php echo htmlspecialchars($page_title); ?></h1>
        <p class="mb-0 opacity-90">
            <?php echo htmlspecialchars($meta_description); ?>
        </p>
        
        <div class="mt-4">
            <a href="index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>

    <div class="content-section">
        <div class="page-content">
            <?php echo $page_content; ?>
        </div>
        
        <div class="text-center mt-5">
            <div class="d-flex justify-content-center gap-3">
                <a href="index.php" class="btn-back">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <a href="search.php" class="btn-back">
                    <i class="fas fa-search"></i> Browse Internships
                </a>
                <a href="auth/register.php" class="btn-back">
                    <i class="fas fa-user-plus"></i> Sign Up
                </a>
            </div>
        </div>
        
        <?php if ($page): ?>
            <div class="text-center mt-4 pt-4 border-top">
                <small class="text-muted">
                    Last updated: <?php echo date('F j, Y', strtotime($page['updated_at'])); ?>
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Add smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add copy functionality for code blocks
document.querySelectorAll('pre code').forEach(block => {
    const button = document.createElement('button');
    button.className = 'btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2';
    button.innerHTML = '<i class="fas fa-copy"></i>';
    button.style.cssText = 'font-size: 0.75rem;';
    button.onclick = () => {
        navigator.clipboard.writeText(block.textContent);
        button.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => {
            button.innerHTML = '<i class="fas fa-copy"></i>';
        }, 2000);
    };
    
    const pre = block.parentElement;
    pre.style.position = 'relative';
    pre.appendChild(button);
});
</script>
</body>
</html>
