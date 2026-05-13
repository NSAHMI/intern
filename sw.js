const CACHE_NAME = 'internship-v1';
const urlsToCache = [
  '/',
  '/intern/',
  '/intern/index.php',
  '/intern/auth/login.php',
  '/intern/auth/register.php',
  '/intern/student/dashboard.php',
  '/intern/company/dashboard.php',
  '/intern/admin/dashboard.php',
  '/intern/search.php',
  '/intern/messages.php',
  '/intern/student/profile.php',
  '/intern/student/gamification.php',
  '/intern/company/profile.php',
  '/intern/admin/analytics.php',
  '/intern/admin/email_management.php',
  '/intern/manifest.json',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap'
];

// Install Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

// Fetch Service Worker
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Cache hit - return response
        if (response) {
          return response;
        }

        // Clone the request
        const fetchRequest = event.request.clone();

        return fetch(fetchRequest).then(response => {
          // Check if valid response
          if(!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }

          // Clone the response
          const responseToCache = response.clone();

          caches.open(CACHE_NAME)
            .then(cache => {
              // Don't cache API calls or dynamic content
              if (!event.request.url.includes('api/') && 
                  !event.request.url.includes('.php') ||
                  event.request.url.includes('dashboard.php') ||
                  event.request.url.includes('search.php') ||
                  event.request.url.includes('messages.php')) {
                cache.put(event.request, responseToCache);
              }
            });

          return response;
        }).catch(() => {
          // Offline fallback
          if (event.request.destination === 'document') {
            return caches.match('/intern/index.php');
          }
        });
      })
  );
});

// Activate Service Worker
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Background Sync for offline actions
self.addEventListener('sync', event => {
  if (event.tag === 'background-sync') {
    event.waitUntil(doBackgroundSync());
  }
});

function doBackgroundSync() {
  // Handle offline actions when back online
  return self.registration.showNotification('Internship Hub', {
    body: 'Your offline actions have been synced!',
    icon: '/intern/assets/icons/icon-192x192.png',
    badge: '/intern/assets/icons/icon-72x72.png',
    tag: 'sync-complete'
  });
}

// Push Notifications
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'New notification from Internship Hub',
    icon: '/intern/assets/icons/icon-192x192.png',
    badge: '/intern/assets/icons/icon-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'View Details',
        icon: '/intern/assets/icons/icon-96x96.png'
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/intern/assets/icons/icon-96x96.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('Internship Hub', options)
  );
});

// Notification Click Handler
self.addEventListener('notificationclick', event => {
  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/intern/student/dashboard.php')
    );
  } else if (event.action === 'close') {
    // Just close the notification
  } else {
    // Default action - open the app
    event.waitUntil(
      clients.matchAll().then(clientList => {
        for (const client of clientList) {
          if (client.url === '/intern/' && 'focus' in client) {
            return client.focus();
          }
        }
        if (clients.openWindow) {
          return clients.openWindow('/intern/');
        }
      })
    );
  }
});

// Periodic Background Sync (for checking new internships)
self.addEventListener('periodicsync', event => {
  if (event.tag === 'check-new-internships') {
    event.waitUntil(checkForNewInternships());
  }
});

function checkForNewInternships() {
  // This would typically make an API call to check for new internships
  // For now, we'll just show a notification periodically
  return self.registration.showNotification('New Internships Available!', {
    body: 'Check out the latest internship opportunities',
    icon: '/intern/assets/icons/icon-192x192.png',
    badge: '/intern/assets/icons/icon-72x72.png',
    tag: 'new-internships'
  });
}
