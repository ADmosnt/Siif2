// Service Worker para SIIF2 PWA - Push Notifications

// Evento: recibir push notification
self.addEventListener('push', function (event) {
    if (!event.data) return

    let data
    try {
        data = event.data.json()
    } catch (e) {
        data = {
            title: 'SIIF2',
            body: event.data.text(),
        }
    }

    const options = {
        body: data.body || data.message || '',
        icon: data.icon || '/pwa-192x192.png',
        badge: data.badge || '/pwa-192x192.png',
        tag: data.tag || 'siif-notification',
        data: data.data || {},
        vibrate: [200, 100, 200],
        requireInteraction: true,
    }

    event.waitUntil(
        self.registration.showNotification(data.title || 'SIIF2', options)
    )
})

// Evento: click en la notificación
self.addEventListener('notificationclick', function (event) {
    event.notification.close()

    const url = event.notification.data?.url || '/'

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            // Si ya hay una ventana abierta, enfocarla y navegar
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i]
                if ('focus' in client) {
                    client.focus()
                    client.navigate(url)
                    return
                }
            }
            // Si no hay ventana, abrir una nueva
            if (clients.openWindow) {
                return clients.openWindow(url)
            }
        })
    )
})

// Evento: cerrar notificación (opcional, para analytics)
self.addEventListener('notificationclose', function (event) {
    // Se puede usar para tracking si se necesita
})
