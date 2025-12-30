/**
 * Gestion des notifications CLIENT - LULU-OPEN
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Marquer comme lu
    document.querySelectorAll('.mark-read').forEach(btn => {
        btn.addEventListener('click', function() {
            const notifId = this.dataset.id;
            markAsRead(notifId);
        });
    });
    
    // Supprimer notification
    document.querySelectorAll('.delete-notif').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Supprimer cette notification ?')) return;
            
            const notifId = this.dataset.id;
            deleteNotification(notifId);
        });
    });
    
    // Polling nouvelles notifications (toutes les 30s)
    setInterval(checkNewNotifications, 30000);
});

/**
 * Marquer notification comme lue
 */
function markAsRead(notificationId) {
    fetch('/lulu/api/notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'mark_read',
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (item) {
                item.classList.remove('unread');
                item.querySelector('.mark-read')?.remove();
            }
            updateNotificationBadge(-1);
        }
    })
    .catch(error => console.error('Erreur:', error));
}

/**
 * Supprimer notification
 */
function deleteNotification(notificationId) {
    fetch('/lulu/api/notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'delete',
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (item) {
                item.style.transition = 'all 0.3s';
                item.style.opacity = '0';
                item.style.transform = 'translateX(-100%)';
                setTimeout(() => item.remove(), 300);
            }
        }
    })
    .catch(error => console.error('Erreur:', error));
}

/**
 * Vérifier nouvelles notifications
 */
function checkNewNotifications() {
    fetch('/lulu/api/notifications.php?action=count')
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                updateNotificationBadge(data.count);
            }
        })
        .catch(error => console.error('Erreur:', error));
}

/**
 * Mettre à jour badge notifications
 */
function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    }
}
