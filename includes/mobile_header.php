<?php
// Mobile-optimized header with PWA support
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="description" content="Connect students, companies, and administrators for amazing internship opportunities">
    <meta name="keywords" content="internships, jobs, students, companies, career">
    <meta name="author" content="Internship Management System">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="InternHub">
    <meta name="application-name" content="InternHub">
    <meta name="msapplication-TileColor" content="#6366f1">
    <meta name="msapplication-config" content="/intern/browserconfig.xml">
    
    <!-- Manifest -->
    <link rel="manifest" href="/intern/manifest.json">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="72x72" href="/intern/assets/icons/icon-72x72.png">
    <link rel="apple-touch-icon" sizes="96x96" href="/intern/assets/icons/icon-96x96.png">
    <link rel="apple-touch-icon" sizes="128x128" href="/intern/assets/icons/icon-128x128.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/intern/assets/icons/icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/intern/assets/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/intern/assets/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="384x384" href="/intern/assets/icons/icon-384x384.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/intern/assets/icons/icon-512x512.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/intern/assets/icons/icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/intern/assets/icons/icon-16x16.png">
    <link rel="shortcut icon" href="/intern/assets/icons/icon-192x192.png">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Enhanced Mobile Responsive CSS -->
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
    
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
        
        * {
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            padding-top: var(--safe-area-inset-top);
            padding-bottom: var(--safe-area-inset-bottom);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            margin: 10px auto;
            max-width: 100%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem;
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
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }
        
        .header-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .mobile-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .nav-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .btn-custom {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            min-height: 44px; /* Touch target size */
            touch-action: manipulation;
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
            padding: 1rem;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 0.5rem 0;
            padding-bottom: calc(0.5rem + env(safe-area-inset-bottom));
            z-index: 1000;
            display: none;
        }
        
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.5rem;
            color: #6b7280;
            text-decoration: none;
            font-size: 0.75rem;
            transition: color 0.3s ease;
            min-height: 60px;
        }
        
        .bottom-nav-item.active {
            color: var(--primary-color);
        }
        
        .bottom-nav-item:hover {
            color: var(--primary-color);
        }
        
        .bottom-nav-item i {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            .main-container {
                margin: 0;
                border-radius: 0;
                min-height: 100vh;
            }
            
            .header-section {
                padding: 1rem;
            }
            
            .header-title {
                font-size: 1.5rem;
            }
            
            .content-section {
                padding: 1rem;
                padding-bottom: 80px; /* Space for bottom nav */
            }
            
            .bottom-nav {
                display: flex;
                justify-content: space-around;
            }
            
            .nav-buttons {
                display: none; /* Hide top nav on mobile */
            }
        }
        
        /* Desktop styles */
        @media (min-width: 769px) {
            .main-container {
                margin: 20px auto;
                max-width: 1200px;
                border-radius: 20px;
            }
            
            .header-section {
                padding: 2rem;
                border-radius: 20px 20px 0 0;
            }
            
            .header-title {
                font-size: 2.5rem;
            }
            
            .content-section {
                padding: 2rem;
            }
        }
        
        /* Touch optimizations */
        @media (hover: none) and (pointer: coarse) {
            .btn-custom {
                min-height: 44px;
                min-width: 44px;
            }
            
            .btn-custom:hover {
                transform: none;
            }
            
            .btn-custom:active {
                transform: scale(0.95);
            }
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Pull to refresh indicator */
        .pull-to-refresh {
            position: fixed;
            top: -60px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            border-radius: 0 0 20px 20px;
            z-index: 1001;
            transition: top 0.3s ease;
        }
        
        .pull-to-refresh.show {
            top: 0;
        }
    </style>
</head>
<body>
    <!-- Pull to refresh indicator -->
    <div class="pull-to-refresh" id="pullToRefresh">
        <i class="fas fa-sync-alt me-2"></i>
        <span>Release to refresh</span>
    </div>

    <!-- Bottom Navigation (Mobile) -->
    <nav class="bottom-nav" id="bottomNav">
        <a href="dashboard.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="search.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'search.php' ? 'active' : ''; ?>">
            <i class="fas fa-search"></i>
            <span>Search</span>
        </a>
        <a href="../messages.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) === '../messages.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i>
            <span>Messages</span>
        </a>
        <a href="profile.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a href="gamification.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'gamification.php' ? 'active' : ''; ?>">
            <i class="fas fa-trophy"></i>
            <span>Awards</span>
        </a>
    </nav>
