// Progressive Web App functionality
class PWAManager {
    constructor() {
        this.deferredPrompt = null;
        this.installButton = null;
        this.init();
    }

    init() {
        // Listen for beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallButton();
        });

        // Listen for app installed event
        window.addEventListener('appinstalled', () => {
            this.hideInstallButton();
            this.showInstallSuccess();
        });

        // Check if app is already installed
        if (window.matchMedia('(display-mode: standalone)').matches) {
            this.isInstalled = true;
        }

        // Initialize service worker
        this.initServiceWorker();
        
        // Initialize mobile optimizations
        this.initMobileOptimizations();
        
        // Initialize push notifications
        this.initPushNotifications();
    }

    initServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/intern/sw.js')
                .then(registration => {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    
                    // Check for updates
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                this.showUpdateAvailable();
                            }
                        });
                    });
                })
                .catch(error => {
                    console.log('ServiceWorker registration failed: ', error);
                });
        }
    }

    initMobileOptimizations() {
        // Add touch-friendly interactions
        this.addTouchOptimizations();
        
        // Optimize for mobile viewport
        this.optimizeViewport();
        
        // Add mobile-specific CSS classes
        this.addMobileClasses();
        
        // Handle orientation changes
        this.handleOrientationChange();
    }

    addTouchOptimizations() {
        // Add ripple effect to buttons
        document.addEventListener('touchstart', function(e) {
            if (e.target.closest('.btn-custom, .btn-apply, .stat-box, .achievement-card')) {
                const ripple = document.createElement('span');
                ripple.classList.add('ripple');
                e.target.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            }
        });

        // Prevent double-tap zoom on buttons
        document.addEventListener('touchend', function(e) {
            if (e.target.closest('button, a')) {
                e.preventDefault();
                e.target.click();
            }
        });
    }

    optimizeViewport() {
        // Set proper viewport height for mobile browsers
        const setViewportHeight = () => {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        };

        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);
        window.addEventListener('orientationchange', setViewportHeight);
    }

    addMobileClasses() {
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        if (isMobile) {
            document.body.classList.add('mobile-device');
            
            // Add mobile-specific CSS
            const mobileCSS = `
                .mobile-device .main-container {
                    margin: 10px auto;
                    max-width: 100%;
                    border-radius: 15px;
                }
                
                .mobile-device .header-section {
                    padding: 1.5rem;
                }
                
                .mobile-device .header-title {
                    font-size: 1.8rem;
                }
                
                .mobile-device .content-section {
                    padding: 1rem;
                }
                
                .mobile-device .nav-buttons {
                    flex-direction: column;
                    gap: 0.5rem;
                }
                
                .mobile-device .btn-custom {
                    width: 100%;
                    justify-content: center;
                }
                
                .mobile-device .internship-card {
                    margin-bottom: 1rem;
                }
                
                .mobile-device .achievement-card {
                    margin-bottom: 0.75rem;
                }
                
                .mobile-device .leaderboard-item {
                    padding: 0.75rem;
                }
                
                .mobile-device .stat-box {
                    margin-bottom: 0.75rem;
                }
                
                /* Ripple effect */
                .ripple {
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    transform: scale(0);
                    animation: ripple-animation 0.6s ease-out;
                    pointer-events: none;
                }
                
                @keyframes ripple-animation {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
                
                /* Safe area insets for notched phones */
                .mobile-device {
                    padding-top: env(safe-area-inset-top);
                    padding-bottom: env(safe-area-inset-bottom);
                }
                
                /* Better scrolling on mobile */
                .mobile-device {
                    -webkit-overflow-scrolling: touch;
                    scroll-behavior: smooth;
                }
            `;
            
            const style = document.createElement('style');
            style.textContent = mobileCSS;
            document.head.appendChild(style);
        }
    }

    handleOrientationChange() {
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                // Recalculate layouts after orientation change
                window.dispatchEvent(new Event('resize'));
            }, 100);
        });
    }

    showInstallButton() {
        // Create install button
        if (document.getElementById('pwa-install-btn')) return;

        const installBtn = document.createElement('button');
        installBtn.id = 'pwa-install-btn';
        installBtn.className = 'btn btn-primary position-fixed';
        installBtn.style.cssText = `
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            border-radius: 50px;
            padding: 12px 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInUp 0.3s ease-out;
        `;
        installBtn.innerHTML = '<i class="fas fa-download me-2"></i>Install App';
        
        installBtn.addEventListener('click', () => this.installApp());
        
        document.body.appendChild(installBtn);
        this.installButton = installBtn;
    }

    hideInstallButton() {
        if (this.installButton) {
            this.installButton.style.animation = 'slideOutDown 0.3s ease-out';
            setTimeout(() => {
                if (this.installButton) {
                    this.installButton.remove();
                    this.installButton = null;
                }
            }, 300);
        }
    }

    async installApp() {
        if (!this.deferredPrompt) return;

        this.deferredPrompt.prompt();
        const { outcome } = await this.deferredPrompt.userChoice;
        
        if (outcome === 'accepted') {
            console.log('User accepted the install prompt');
        } else {
            console.log('User dismissed the install prompt');
        }
        
        this.deferredPrompt = null;
        this.hideInstallButton();
    }

    showInstallSuccess() {
        this.showNotification('App Installed Successfully!', 'You can now access Internship Hub from your home screen.', 'success');
    }

    showUpdateAvailable() {
        const updateBtn = document.createElement('div');
        updateBtn.className = 'alert alert-info position-fixed';
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
                    <i class="fas fa-sync-alt me-2"></i>
                    <strong>Update Available</strong>
                    <div class="small">A new version of the app is ready.</div>
                </div>
                <button class="btn btn-sm btn-primary" onclick="this.closest('.alert').remove(); location.reload();">
                    Update Now
                </button>
            </div>
        `;
        
        document.body.appendChild(updateBtn);
        
        setTimeout(() => {
            if (updateBtn.parentNode) {
                updateBtn.remove();
            }
        }, 10000);
    }

    initPushNotifications() {
        if ('Notification' in window && 'serviceWorker' in navigator) {
            // Request notification permission
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    console.log('Notification permission granted');
                    this.subscribeToPushNotifications();
                }
            });
        }
    }

    async subscribeToPushNotifications() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array('your-vapid-public-key-here')
            });
            
            console.log('Push notification subscription:', subscription);
            // Here you would send the subscription to your server
        } catch (error) {
            console.error('Push subscription failed:', error);
        }
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        
        return outputArray;
    }

    showNotification(title, message, type = 'info') {
        if ('Notification' in window && Notification.permission === 'granted') {
            const options = {
                body: message,
                icon: '/intern/assets/icons/icon-192x192.png',
                badge: '/intern/assets/icons/icon-72x72.png',
                tag: 'internship-notification',
                requireInteraction: type === 'success'
            };
            
            new Notification(title, options);
        }
    }

    // Share API integration
    async shareContent(title, text, url) {
        if (navigator.share) {
            try {
                await navigator.share({
                    title: title,
                    text: text,
                    url: url
                });
            } catch (error) {
                console.log('Error sharing:', error);
            }
        } else {
            // Fallback - copy to clipboard
            this.copyToClipboard(url);
            this.showNotification('Link Copied', 'The link has been copied to your clipboard', 'success');
        }
    }

    copyToClipboard(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }
}

// Initialize PWA when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.pwaManager = new PWAManager();
});

// Add CSS animations
const pwaAnimations = `
    @keyframes slideInUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutDown {
        from {
            transform: translateY(0);
            opacity: 1;
        }
        to {
            transform: translateY(100%);
            opacity: 0;
        }
    }
    
    @keyframes slideInDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
`;

const style = document.createElement('style');
style.textContent = pwaAnimations;
document.head.appendChild(style);
