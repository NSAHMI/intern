</div>
</div>

<!-- PWA JavaScript -->
<script src="/intern/assets/js/pwa.js"></script>

<!-- Mobile Optimizations -->
<script>
// Pull to refresh functionality
let startY = 0;
let isPulling = false;
const pullThreshold = 80;

document.addEventListener('touchstart', (e) => {
    if (window.scrollY === 0) {
        startY = e.touches[0].clientY;
        isPulling = true;
    }
});

document.addEventListener('touchmove', (e) => {
    if (!isPulling) return;
    
    const currentY = e.touches[0].clientY;
    const diff = currentY - startY;
    
    if (diff > 0 && window.scrollY === 0) {
        e.preventDefault();
        
        if (diff > pullThreshold) {
            document.getElementById('pullToRefresh').classList.add('show');
        }
    }
});

document.addEventListener('touchend', () => {
    if (!isPulling) return;
    
    const pullToRefresh = document.getElementById('pullToRefresh');
    
    if (pullToRefresh.classList.contains('show')) {
        pullToRefresh.classList.remove('show');
        location.reload();
    }
    
    isPulling = false;
});

// Offline detection
window.addEventListener('online', () => {
    if (window.pwaManager) {
        window.pwaManager.showNotification('Back Online', 'Your connection has been restored', 'success');
    }
});

window.addEventListener('offline', () => {
    if (window.pwaManager) {
        window.pwaManager.showNotification('Offline Mode', 'You are currently offline. Some features may be limited.', 'warning');
    }
});

// Add share functionality
function shareInternship(title, url) {
    if (window.pwaManager) {
        window.pwaManager.shareContent(
            title,
            'Check out this internship opportunity on Internship Hub!',
            url
        );
    }
}

// Add to home screen reminder
setTimeout(() => {
    if (!window.matchMedia('(display-mode: standalone)').matches && 
        !localStorage.getItem('pwa-install-dismissed')) {
        
        const reminder = document.createElement('div');
        reminder.className = 'alert alert-info position-fixed';
        reminder.style.cssText = `
            bottom: 80px;
            left: 20px;
            right: 20px;
            z-index: 1000;
            animation: slideInUp 0.3s ease-out;
        `;
        reminder.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-download me-2"></i>
                    <strong>Install Internship Hub</strong>
                    <div class="small">Add to home screen for the best experience</div>
                </div>
                <div>
                    <button class="btn btn-sm btn-primary me-2" onclick="installPWA()">
                        Install
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="dismissInstallReminder()">
                        Later
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(reminder);
        
        setTimeout(() => {
            if (reminder.parentNode) {
                reminder.style.animation = 'slideOutDown 0.3s ease-out';
                setTimeout(() => reminder.remove(), 300);
            }
        }, 15000);
    }
}, 5000);

function installPWA() {
    if (window.pwaManager && window.pwaManager.deferredPrompt) {
        window.pwaManager.installApp();
        dismissInstallReminder();
    }
}

function dismissInstallReminder() {
    localStorage.setItem('pwa-install-dismissed', 'true');
    const reminder = document.querySelector('.alert:has(button[onclick="dismissInstallReminder()"])');
    if (reminder) {
        reminder.style.animation = 'slideOutDown 0.3s ease-out';
        setTimeout(() => reminder.remove(), 300);
    }
}

// Performance monitoring
if ('performance' in window) {
    window.addEventListener('load', () => {
        setTimeout(() => {
            const perfData = performance.getEntriesByType('navigation')[0];
            const loadTime = perfData.loadEventEnd - perfData.loadEventStart;
            
            if (loadTime > 3000) {
                console.log('Slow load detected:', loadTime + 'ms');
            }
        }, 0);
    });
}

// Vibration feedback for mobile
function vibrate(pattern = [50]) {
    if ('vibrate' in navigator) {
        navigator.vibrate(pattern);
    }
}

// Add vibration to button clicks
document.addEventListener('click', (e) => {
    if (e.target.closest('.btn-custom, .btn-apply, .bottom-nav-item')) {
        vibrate(10);
    }
});

// Handle back button on mobile
if (window.matchMedia('(max-width: 768px)').matches) {
    let backButtonPressed = false;
    
    window.addEventListener('popstate', (e) => {
        if (backButtonPressed) {
            // User pressed back again, exit app if standalone
            if (window.matchMedia('(display-mode: standalone)').matches) {
                e.preventDefault();
                if (confirm('Exit Internship Hub?')) {
                    window.close();
                }
            }
        } else {
            backButtonPressed = true;
            setTimeout(() => {
                backButtonPressed = false;
            }, 2000);
        }
    });
}

// Intersection Observer for lazy loading
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            }
        });
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// Service Worker messaging
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', event => {
        if (event.data && event.data.type === 'CACHE_UPDATED') {
            const updateBtn = document.createElement('div');
            updateBtn.className = 'alert alert-success position-fixed';
            updateBtn.style.cssText = `
                top: 20px;
                right: 20px;
                left: 20px;
                z-index: 1000;
                animation: slideInDown 0.3s ease-out;
            `;
            updateBtn.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Content Updated</strong>
                        <div class="small">Latest content is now available offline</div>
                    </div>
                    <button class="btn btn-sm btn-success" onclick="this.closest('.alert').remove();">
                        OK
                    </button>
                </div>
            `;
            
            document.body.appendChild(updateBtn);
            
            setTimeout(() => {
                if (updateBtn.parentNode) {
                    updateBtn.remove();
                }
            }, 5000);
        }
    });
}

// Analytics for PWA usage
function trackPWAEvent(eventName, data = {}) {
    // Here you would send data to your analytics service
    console.log('PWA Event:', eventName, data);
    
    // Example: Track when app is used standalone
    if (window.matchMedia('(display-mode: standalone)').matches) {
        data.isStandalone = true;
    }
    
    // Example: Track connection type
    if (navigator.connection) {
        data.connectionType = navigator.connection.effectiveType;
    }
}

// Track page load
trackPWAEvent('page_load', {
    page: window.location.pathname,
    timestamp: Date.now()
});

// Track user engagement
let engagementTime = 0;
setInterval(() => {
    if (document.visibilityState === 'visible') {
        engagementTime++;
        
        // Track engagement milestones
        if (engagementTime === 60) { // 1 minute
            trackPWAEvent('engagement_1_min');
        } else if (engagementTime === 300) { // 5 minutes
            trackPWAEvent('engagement_5_min');
        }
    }
}, 1000);
</script>

</body>
</html>
