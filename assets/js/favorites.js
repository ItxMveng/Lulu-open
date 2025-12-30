/**
 * Gestion des favoris côté client - LULU-OPEN
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Retirer des favoris (AJAX)
    document.querySelectorAll('.remove-favorite').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const cibleId = this.dataset.id;
            const cibleType = this.dataset.type;
            const card = this.closest('.favorite-card');
            
            if (!confirm('Retirer ce profil de vos favoris ?')) {
                return;
            }
            
            // Requête AJAX
            fetch('/lulu/api/favorites?action=remove&cible_id=' + cibleId + '&cible_type=' + cibleType, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'removed') {
                    // Animation suppression
                    card.style.transition = 'all 0.3s';
                    card.style.transform = 'scale(0)';
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                        // Recharger si plus de favoris
                        if (document.querySelectorAll('.favorite-card').length === 0) {
                            location.reload();
                        }
                    }, 300);
                    
                    showToast('✅ Retiré des favoris', 'success');
                } else {
                    showToast('❌ Erreur lors de la suppression', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showToast('❌ Erreur réseau', 'error');
            });
        });
    });
});

/**
 * Afficher notification toast
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = '9999';
    toast.style.minWidth = '250px';
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Ajouter aux favoris (utilisé sur pages profils)
 */
function addToFavorites(cibleId, cibleType) {
    fetch('/lulu/api/favorites?action=add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            cible_id: cibleId,
            type_cible: cibleType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'added') {
            showToast('✅ Ajouté aux favoris', 'success');
            // Mettre à jour l'icône
            const btn = document.querySelector(`[data-favorite-id="${cibleId}"]`);
            if (btn) {
                btn.classList.add('active');
                btn.innerHTML = '<i class="bi bi-heart-fill"></i>';
            }
        } else if (data.status === 'already_added') {
            showToast('ℹ️ Déjà dans vos favoris', 'info');
        } else {
            showToast('❌ ' + (data.message || 'Erreur'), 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showToast('❌ Erreur réseau', 'error');
    });
}
