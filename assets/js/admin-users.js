/**
 * Admin - Gestion Utilisateurs
 */

let modalUserDetails, modalUserStatus, modalUserReset;

document.addEventListener('DOMContentLoaded', function () {
    const detailsEl = document.getElementById('modalUserDetails');
    const statusEl = document.getElementById('modalUserStatus');
    const resetEl = document.getElementById('modalUserReset');
    
    if (detailsEl) modalUserDetails = new bootstrap.Modal(detailsEl);
    if (statusEl) modalUserStatus = new bootstrap.Modal(statusEl);
    if (resetEl) modalUserReset = new bootstrap.Modal(resetEl);
});

// ====================== DÉTAILS UTILISATEUR ======================

async function showUserDetails(userId) {
    const content = document.getElementById('userDetailsContent');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
    modalUserDetails.show();

    try {
        const res = await fetch('/lulu/api/admin-users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'details', id: userId })
        });
        const data = await res.json();

        if (!data.success) {
            content.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            return;
        }

        const u = data.user;

        let photoUrl = '/lulu/assets/images/default-avatar.png';
        if (u.photo_profil) {
            if (u.photo_profil.startsWith('http')) {
                photoUrl = u.photo_profil;
            } else if (u.photo_profil.startsWith('uploads/')) {
                photoUrl = '/lulu/' + u.photo_profil;
            } else if (u.photo_profil.startsWith('profiles/')) {
                photoUrl = '/lulu/uploads/' + u.photo_profil;
            } else {
                photoUrl = '/lulu/uploads/profiles/' + u.photo_profil;
            }
        }

        content.innerHTML = `
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted mb-3"><i class="bi bi-person-fill"></i> Profil</h6>
                    <div class="d-flex align-items-center">
                        <img src="${photoUrl}"
                             class="rounded-circle me-3"
                             style="width:60px;height:60px;object-fit:cover;"
                             onerror="this.src='/lulu/assets/images/default-avatar.png'">
                        <div>
                            <strong>${u.prenom} ${u.nom}</strong><br>
                            <small class="text-muted">${u.email}</small><br>
                            ${u.telephone ? `<small class="text-muted"><i class="bi bi-telephone"></i> ${u.telephone}</small>` : ''}
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted mb-3"><i class="bi bi-shield-lock"></i> Compte</h6>
                    <p class="mb-1"><strong>Rôle :</strong> ${u.type_utilisateur}</p>
                    <p class="mb-1"><strong>Statut :</strong> ${u.statut}</p>
                    <p class="mb-1"><strong>Créé le :</strong> ${formatDateFull(u.date_inscription)}</p>
                    ${u.derniere_connexion ? `<p class="mb-1"><strong>Dernière connexion :</strong> ${formatDateFull(u.derniere_connexion)}</p>` : ''}
                </div>
                <div class="col-md-12 mb-3">
                    <h6 class="text-muted mb-2"><i class="bi bi-tags"></i> Catégories</h6>
                    <div class="d-flex flex-wrap gap-2">
                        ${u.categories && u.categories.length > 0 ? u.categories.map(cat => `<span class="badge" style="background-color: ${cat.couleur}; color: white;">${cat.icone} ${cat.nom}</span>`).join('') : '<small class="text-muted">Aucune catégorie</small>'}
                    </div>
                </div>
                <div class="col-md-12 mb-3">
                    <h6 class="text-muted mb-2"><i class="bi bi-activity"></i> Activité</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <span class="badge bg-primary">Abonnements : ${u.total_abonnements}</span>
                        <span class="badge bg-success">Actifs : ${u.abonnements_actifs}</span>
                        <span class="badge bg-info">Paiements valides : ${u.paiements_valides}</span>
                        <span class="badge bg-warning text-dark">Demandes : ${u.demandes_total}</span>
                    </div>
                </div>
            </div>
        `;
    } catch (e) {
        console.error(e);
        content.innerHTML = '<div class="alert alert-danger">Erreur réseau</div>';
    }
}

// ====================== CHANGEMENT STATUT ======================

function changeUserStatus(userId, newStatus) {
    document.getElementById('statusUserId').value = userId;
    document.getElementById('statusNewValue').value = newStatus;
    document.getElementById('statusReason').value = '';

    let text = '';
    if (newStatus === 'suspendu') {
        text = "Vous êtes sur le point de <strong>mettre en pause</strong> ce compte. L'utilisateur ne pourra plus se connecter tant qu'il est suspendu.";
    } else if (newStatus === 'bloque') {
        text = "Vous êtes sur le point de <strong>bloquer définitivement</strong> ce compte. L'accès sera totalement coupé.";
    } else if (newStatus === 'actif') {
        text = "Vous êtes sur le point de <strong>réactiver</strong> ce compte. L'utilisateur pourra à nouveau se connecter.";
    }

    document.getElementById('statusConfirmText').innerHTML = text;
    modalUserStatus.show();
}

async function confirmChangeUserStatus() {
    const userId = parseInt(document.getElementById('statusUserId').value, 10);
    const newStatus = document.getElementById('statusNewValue').value;
    const reason = document.getElementById('statusReason').value || null;

    try {
        const res = await fetch('/lulu/api/admin-users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'change_status',
                id: userId,
                new_status: newStatus,
                reason: reason
            })
        });
        const data = await res.json();

        if (data.success) {
            alert(data.message);
            modalUserStatus.hide();
            location.reload();
        } else {
            alert('Erreur : ' + data.message);
        }
    } catch (e) {
        console.error(e);
        alert('Erreur réseau');
    }
}

// ====================== RESET MOT DE PASSE ======================

function resetUserPassword(userId) {
    document.getElementById('resetUserId').value = userId;
    modalUserReset.show();
}

async function confirmResetUserPassword() {
    const userId = parseInt(document.getElementById('resetUserId').value, 10);

    try {
        const res = await fetch('/lulu/api/admin-users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'reset_password',
                id: userId
            })
        });
        const data = await res.json();

        if (data.success) {
            alert(data.message + (data.temp_password ? "\nMot de passe temporaire (DEV): " + data.temp_password : ''));
            modalUserReset.hide();
        } else {
            alert('Erreur : ' + data.message);
        }
    } catch (e) {
        console.error(e);
        alert('Erreur réseau');
    }
}

// ====================== NAVIGATION RAPIDE ======================

function goToUserSubscriptions(userId) {
    window.location.href = `/lulu/views/admin/subscriptions.php?user_id=${userId}`;
}

function goToUserPayments(userId) {
    window.location.href = `/lulu/views/admin/payments.php?user_id=${userId}`;
}

// ====================== EXPORT CSV ======================

function exportUsersCSV() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = '/lulu/api/admin-users-export.php?' + params.toString();
}

// ====================== HELPERS ======================

function formatDateFull(dateString) {
    if (!dateString) return '-';
    const d = new Date(dateString);
    return d.toLocaleDateString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}
