/**
 * Dashboard CLIENT - Interactions et animations
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Rafraîchir compteurs toutes les 30 secondes
    setInterval(refreshCounters, 30000);
    
    // Marquer notifications comme lues au clic
    document.querySelectorAll('.list-group-item').forEach(item => {
        item.addEventListener('click', function() {
            if (this.classList.contains('bg-light')) {
                this.classList.remove('bg-light');
                // Décrémenter badge
                updateNotificationBadge(-1);
            }
        });
    });
});

/**
 * Rafraîchir les compteurs de statistiques
 */
function refreshCounters() {
    fetch('/lulu/api/client/stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour les compteurs avec animation
                updateCounter('favoris', data.stats.favoris);
                updateCounter('messages_non_lus', data.stats.messages_non_lus);
                updateCounter('notifications_non_lues', data.stats.notifications_non_lues);
                updateCounter('consultations_7j', data.stats.consultations_7j);
            }
        })
        .catch(error => console.error('Erreur refresh:', error));
}

/**
 * Mettre à jour un compteur avec animation
 */
function updateCounter(id, newValue) {
    const element = document.querySelector(`[data-counter="${id}"]`);
    if (element) {
        const oldValue = parseInt(element.textContent);
        if (oldValue !== newValue) {
            element.style.transform = 'scale(1.2)';
            element.textContent = newValue;
            setTimeout(() => {
                element.style.transform = 'scale(1)';
            }, 200);
        }
    }
}

/**
 * Mettre à jour badge notifications
 */
function updateNotificationBadge(delta) {
    const badge = document.querySelector('.badge.bg-danger');
    if (badge) {
        const current = parseInt(badge.textContent);
        const newValue = Math.max(0, current + delta);
        if (newValue === 0) {
            badge.remove();
        } else {
            badge.textContent = newValue;
        }
    }
}
