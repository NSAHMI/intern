<?php
// Mobile Responsiveness Testing Page
// This page demonstrates all responsive features across different devices
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Mobile Responsiveness Test - Internship Hub</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#6366f1">
    <link rel="manifest" href="manifest.json">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Enhanced Mobile Responsive CSS -->
    <link rel="stylesheet" href="assets/css/mobile-responsive.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        .test-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            margin: 20px auto;
            max-width: 100%;
            padding: var(--mobile-spacing-lg);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .device-info {
            background: #f8fafc;
            border-radius: 12px;
            padding: var(--mobile-spacing-md);
            margin-bottom: var(--mobile-spacing-lg);
            border: 1px solid #e2e8f0;
        }
        
        .responsive-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: var(--mobile-spacing-md);
            margin: var(--mobile-spacing-lg) 0;
        }
        
        .grid-item {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            padding: var(--mobile-spacing-md);
            border-radius: 12px;
            text-align: center;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .touch-demo {
            background: #f3f4f6;
            border-radius: 12px;
            padding: var(--mobile-spacing-md);
            margin: var(--mobile-spacing-lg) 0;
        }
        
        .touch-target {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: var(--mobile-spacing-md);
            margin: var(--mobile-spacing-sm) 0;
            min-height: var(--touch-target-min);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .touch-target:active {
            background: #6366f1;
            color: white;
            transform: scale(0.95);
        }
        
        .form-demo {
            background: #f8fafc;
            border-radius: 12px;
            padding: var(--mobile-spacing-md);
            margin: var(--mobile-spacing-lg) 0;
        }
        
        .safe-area-demo {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: var(--mobile-spacing-md);
            border-radius: 12px;
            margin: var(--mobile-spacing-lg) 0;
            text-align: center;
        }
        
        .breakpoint-indicator {
            position: fixed;
            top: var(--safe-area-inset-top);
            right: 0;
            background: rgba(99, 102, 241, 0.9);
            color: white;
            padding: var(--mobile-spacing-xs) var(--mobile-spacing-sm);
            font-size: var(--mobile-font-xs);
            font-weight: 600;
            border-radius: 0 0 0 8px;
            z-index: 1000;
        }
        
        @media (max-width: 320px) {
            .breakpoint-indicator::after { content: "XS (320px)"; }
        }
        @media (min-width: 321px) and (max-width: 375px) {
            .breakpoint-indicator::after { content: "SM (375px)"; }
        }
        @media (min-width: 376px) and (max-width: 414px) {
            .breakpoint-indicator::after { content: "MD (414px)"; }
        }
        @media (min-width: 415px) and (max-width: 768px) {
            .breakpoint-indicator::after { content: "LG (768px)"; }
        }
        @media (min-width: 769px) and (max-width: 1024px) {
            .breakpoint-indicator::after { content: "XL (1024px)"; }
        }
        @media (min-width: 1025px) {
            .breakpoint-indicator::after { content: "2XL (1025px+)"; }
        }
    </style>
</head>
<body>
    <!-- Breakpoint Indicator -->
    <div class="breakpoint-indicator">
        <i class="fas fa-mobile-alt me-1"></i>
        <span></span>
    </div>

    <div class="container">
        <!-- Device Information -->
        <div class="test-section">
            <h1 class="mb-4"><i class="fas fa-mobile-alt me-2"></i>Mobile Responsiveness Test</h1>
            
            <div class="device-info">
                <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Device Information</h5>
                <div class="row">
                    <div class="col-xs-6 col-sm-4">
                        <strong>Screen Width:</strong><br>
                        <span id="screenWidth"></span>px
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <strong>Screen Height:</strong><br>
                        <span id="screenHeight"></span>px
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <strong>Pixel Ratio:</strong><br>
                        <span id="pixelRatio"></span>
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <strong>Orientation:</strong><br>
                        <span id="orientation"></span>
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <strong>Touch Support:</strong><br>
                        <span id="touchSupport"></span>
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <strong>User Agent:</strong><br>
                        <small id="userAgent"></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Responsive Grid Test -->
        <div class="test-section">
            <h2 class="mb-3"><i class="fas fa-th me-2"></i>Responsive Grid</h2>
            <p class="text-muted mb-4">Grid layout that adapts to screen size</p>
            
            <div class="responsive-grid">
                <div class="grid-item">Item 1</div>
                <div class="grid-item">Item 2</div>
                <div class="grid-item">Item 3</div>
                <div class="grid-item">Item 4</div>
                <div class="grid-item">Item 5</div>
                <div class="grid-item">Item 6</div>
            </div>
        </div>

        <!-- Touch Targets Test -->
        <div class="test-section">
            <h2 class="mb-3"><i class="fas fa-hand-pointer me-2"></i>Touch Targets</h2>
            <p class="text-muted mb-4">Minimum 44px touch targets for accessibility</p>
            
            <div class="touch-demo">
                <div class="touch-target">
                    <i class="fas fa-home me-2"></i>Home
                </div>
                <div class="touch-target">
                    <i class="fas fa-search me-2"></i>Search
                </div>
                <div class="touch-target">
                    <i class="fas fa-user me-2"></i>Profile
                </div>
                <div class="touch-target">
                    <i class="fas fa-cog me-2"></i>Settings
                </div>
            </div>
        </div>

        <!-- Form Elements Test -->
        <div class="test-section">
            <h2 class="mb-3"><i class="fas fa-edit me-2"></i>Form Elements</h2>
            <p class="text-muted mb-4">Mobile-optimized form inputs</p>
            
            <div class="form-demo">
                <form>
                    <div class="mb-3">
                        <label class="form-label">Text Input</label>
                        <input type="text" class="form-control" placeholder="Enter text here">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Input</label>
                        <input type="email" class="form-control" placeholder="email@example.com">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Dropdown</label>
                        <select class="form-control">
                            <option>Option 1</option>
                            <option>Option 2</option>
                            <option>Option 3</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Textarea</label>
                        <textarea class="form-control" rows="4" placeholder="Enter message here"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit Form
                        </button>
                        <button type="button" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Typography Test -->
        <div class="test-section">
            <h2 class="mb-3"><i class="fas fa-font me-2"></i>Typography</h2>
            <p class="text-muted mb-4">Responsive text sizing</p>
            
            <h1>Heading 1 - Main Title</h1>
            <h2>Heading 2 - Section Title</h2>
            <h3>Heading 3 - Subsection Title</h3>
            <h4>Heading 4 - Component Title</h4>
            <h5>Heading 5 - Small Title</h5>
            <h6>Heading 6 - Micro Title</h6>
            
            <p>This is a paragraph of text that demonstrates how body text scales across different screen sizes. The line height and font size are optimized for readability on mobile devices.</p>
            
            <small>This is small text for secondary information.</small>
        </div>

        <!-- Safe Area Demo -->
        <div class="test-section">
            <h2 class="mb-3"><i class="fas fa-mobile-alt me-2"></i>Safe Area Support</h2>
            <p class="text-muted mb-4">Supports notched phones and safe areas</p>
            
            <div class="safe-area-demo">
                <i class="fas fa-shield-alt fa-2x mb-2"></i>
                <h5>Safe Area Aware</h5>
                <p>This content respects safe areas on notched devices like iPhone X and newer Android phones.</p>
            </div>
        </div>

        <!-- Navigation Test -->
        <div class="test-section">
            <h2 class="mb-3"><i class="fas fa-bars me-2"></i>Navigation</h2>
            <p class="text-muted mb-4">Mobile navigation patterns</p>
            
            <div class="d-grid gap-2">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Home
                </a>
                <a href="auth/login.php" class="btn btn-secondary">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
                <a href="auth/register.php" class="btn btn-success">
                    <i class="fas fa-user-plus me-2"></i>Register
                </a>
            </div>
        </div>

        <!-- Cards Test -->
        <div class="test-section">
            <h2 class="mb-3"><i class="fas fa-square me-2"></i>Cards</h2>
            <p class="text-muted mb-4">Responsive card layouts</p>
            
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Card 1</h5>
                            <p class="card-text">This card adapts to different screen sizes with proper spacing and typography.</p>
                            <button class="btn btn-primary btn-sm">Learn More</button>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Card 2</h5>
                            <p class="card-text">Cards stack vertically on mobile and side-by-side on larger screens.</p>
                            <button class="btn btn-primary btn-sm">Learn More</button>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Card 3</h5>
                            <p class="card-text">Touch-friendly buttons and proper spacing for mobile interaction.</p>
                            <button class="btn btn-primary btn-sm">Learn More</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation (Mobile Only) -->
    <nav class="bottom-nav">
        <a href="index.php" class="bottom-nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="search.php" class="bottom-nav-item">
            <i class="fas fa-search"></i>
            <span>Search</span>
        </a>
        <a href="auth/login.php" class="bottom-nav-item">
            <i class="fas fa-user"></i>
            <span>Login</span>
        </a>
        <a href="auth/register.php" class="bottom-nav-item">
            <i class="fas fa-user-plus"></i>
            <span>Register</span>
        </a>
        <a href="mobile_test.php" class="bottom-nav-item active">
            <i class="fas fa-mobile-alt"></i>
            <span>Test</span>
        </a>
    </nav>

    <script>
        // Update device information
        function updateDeviceInfo() {
            document.getElementById('screenWidth').textContent = window.innerWidth;
            document.getElementById('screenHeight').textContent = window.innerHeight;
            document.getElementById('pixelRatio').textContent = window.devicePixelRatio || 1;
            document.getElementById('orientation').textContent = window.innerHeight > window.innerWidth ? 'Portrait' : 'Landscape';
            document.getElementById('touchSupport').textContent = 'ontouchstart' in window ? 'Yes' : 'No';
            document.getElementById('userAgent').textContent = navigator.userAgent.split(' ')[0];
        }
        
        // Update on load and resize
        updateDeviceInfo();
        window.addEventListener('resize', updateDeviceInfo);
        window.addEventListener('orientationchange', updateDeviceInfo);
        
        // Add touch feedback
        document.querySelectorAll('.touch-target, .btn, .card').forEach(element => {
            element.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            
            element.addEventListener('touchend', function() {
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 100);
            });
        });
        
        // Test vibration API
        document.querySelectorAll('.btn, .touch-target').forEach(element => {
            element.addEventListener('click', function() {
                if ('vibrate' in navigator) {
                    navigator.vibrate(10);
                }
            });
        });
        
        // Test intersection observer for lazy loading simulation
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });
            
            document.querySelectorAll('.test-section').forEach(section => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                section.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(section);
            });
        }
    </script>
</body>
</html>
