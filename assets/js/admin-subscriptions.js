/**
 * Gestion Abonnements - Interface Admin
 */

let modalDetails, modalProlong, modalCancel;

document.addEventListener('DOMContentLoaded', function() {
    modalDetails = new bootstrap.Modal(document.getElementById('modalDetails'));
    modalProlong = new bootstrap.Modal(document.getElementById('modalProlong'));
    modalCancel = new bootstrap.Modal(document.getElementById('modalCancel'));
    
    document.getElementById('formProlong').addEventListener('submit', handleProlongSubmit);
    document.getElementById('formCancel').addEventListener('submit', handleCancelSubmit);
    
    document.getElementById('dureeProlongation').addEventListener('change', function() {
        const customDiv = document.getElementById('customDaysDiv');
        customDiv.style.display = this.value === 'custom' ? 'block' : 'none';
    });
});

// Afficher détails abonnement
async function showDetails(abonnementId) {
    const content = document.getElementById('detailsContent');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
    modalDetails.show();
    
    try {
        const response = await fetch('/lulu/api/admin-subscriptions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'details', id: abonnementId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            content.innerHTML = renderDetailsHTML(data);
        } else {
            content.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
    } catch (error) {
        console.error('Erreur:', error);
        content.innerHTML = '<div class="alert alert-danger">Erreur réseau</div>';
    }
}

function renderDetailsHTML(data) {
    const abo = data.abonnement;
    
    // Gérer le chemin de la photo
    let photoUrl = '/lulu/assets/images/default-avatar.png';
    if (abo.photo_profil) {
        if (abo.photo_profil.startsWith('http')) {
            photoUrl = abo.photo_profil;
        } else if (abo.photo_profil.startsWith('uploads/')) {
            photoUrl = '/lulu/' + abo.photo_profil;
        } else if (abo.photo_profil.startsWith('profiles/')) {
            photoUrl = '/lulu/uploads/' + abo.photo_profil;
        } else {
            photoUrl = '/lulu/uploads/profiles/' + abo.photo_profil;
        }
    }
    
    let html = `
        <div class="row">
            <div class="col-md-6 mb-3">
                <h6 class="text-muted">Utilisateur</h6>
                <div class="d-flex align-items-center mb-2">
                    <img src="${photoUrl}" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;" onerror="this.src='/lulu/assets/images/default-avatar.png'">
                    <div>
                        <strong>${abo.nom_utilisateur}</strong><br>
                        <small class="text-muted">${abo.email}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <h6 class="text-muted">Abonnement</h6>
                <p class="mb-1"><strong>Plan :</strong> ${abo.plan_nom}</p>
                <p class="mb-1"><strong>Statut :</strong> <span class="badge bg-primary">${abo.statut}</span></p>
                <p class="mb-1"><strong>Mode :</strong> ${abo.type_abonnement}</p>
            </div>
            <div class="col-md-6 mb-3">
                <h6 class="text-muted">Période</h6>
                <p class="mb-1"><strong>Début :</strong> ${formatDate(abo.date_debut)}</p>
                <p class="mb-1"><strong>Fin :</strong> ${formatDate(abo.date_fin)}</p>
                ${abo.date_prochaine_facturation ? `<p class="mb-1"><strong>Prochaine facture :</strong> ${formatDate(abo.date_prochaine_facturation)}</p>` : ''}
            </div>
            <div class="col-md-6 mb-3">
                <h6 class="text-muted">Finances</h6>
                <p class="mb-1"><strong>Montant :</strong> ${abo.montant}€</p>
                <p class="mb-1"><strong>Renouvellement auto :</strong> ${abo.renouvellement_auto ? 'Oui' : 'Non'}</p>
            </div>
        </div>
        <hr>
        <h6 class="text-muted mb-3">Historique des paiements</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Transaction</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    if (data.paiements && data.paiements.length > 0) {
        data.paiements.forEach(p => {
            html += `
                <tr>
                    <td>${formatDate(p.date_paiement)}</td>
                    <td class="fw-bold">${p.montant}€</td>
                    <td><span class="badge bg-${p.statut === 'valide' ? 'success' : 'warning'}">${p.statut}</span></td>
                    <td><small>${p.transaction_id || '-'}</small></td>
                </tr>
            `;
        });
    } else {
        html += '<tr><td colspan="4" class="text-center text-muted">Aucun paiement</td></tr>';
    }
    
    html += `
                </tbody>
            </table>
        </div>
        <hr>
        <h6 class="text-muted mb-3">Historique des actions admin</h6>
        <div class="logs-container" style="max-height: 200px; overflow-y: auto;">
    `;
    
    if (data.logs && data.logs.length > 0) {
        data.logs.forEach(log => {
            html += `
                <div class="mb-2 p-2 bg-light rounded">
                    <small>
                        <strong>${formatDate(log.created_at)}</strong> - ${log.admin_nom}<br>
                        Action : <span class="badge bg-info">${log.action}</span>
                    </small>
                </div>
            `;
        });
    } else {
        html += '<p class="text-muted text-center">Aucune action enregistrée</p>';
    }
    
    html += '</div>';
    
    if (abo.notes_admin) {
        html += `
            <hr>
            <h6 class="text-muted mb-3">Notes admin</h6>
            <pre class="bg-light p-3 rounded" style="max-height: 150px; overflow-y: auto;">${abo.notes_admin}</pre>
        `;
    }
    
    return html;
}

// Ouvrir modal prolongation
function openProlongModal(abonnementId) {
    document.getElementById('abonnementIdProlong').value = abonnementId;
    document.getElementById('dureeProlongation').value = '30';
    document.getElementById('motifProlongation').value = '';
    document.getElementById('customDaysDiv').style.display = 'none';
    modalProlong.show();
}

async function handleProlongSubmit(e) {
    e.preventDefault();
    
    const abonnementId = document.getElementById('abonnementIdProlong').value;
    const duree = document.getElementById('dureeProlongation').value;
    const motif = document.getElementById('motifProlongation').value;
    
    let jours;
    if (duree === 'custom') {
        jours = parseInt(document.getElementById('customDays').value);
        if (!jours || jours < 1) {
            alert('Veuillez entrer un nombre de jours valide');
            return;
        }
    } else {
        jours = parseInt(duree);
    }
    
    if (!confirm(`Prolonger cet abonnement de ${jours} jours ?`)) return;
    
    try {
        const response = await fetch('/lulu/api/admin-subscriptions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'prolonger',
                id: abonnementId,
                jours: jours,
                motif: motif
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            modalProlong.hide();
            location.reload();
        } else {
            alert('Erreur : ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur réseau');
    }
}

// Suspendre abonnement
async function suspendAbonnement(abonnementId) {
    const motif = prompt('Motif de suspension :');
    if (!motif) return;
    
    if (!confirm('Suspendre cet abonnement ?')) return;
    
    try {
        const response = await fetch('/lulu/api/admin-subscriptions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'suspendre',
                id: abonnementId,
                motif: motif
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erreur : ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur réseau');
    }
}

// Réactiver abonnement
async function reactiveAbonnement(abonnementId) {
    if (!confirm('Réactiver cet abonnement ?')) return;
    
    try {
        const response = await fetch('/lulu/api/admin-subscriptions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'reactiver',
                id: abonnementId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erreur : ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur réseau');
    }
}

// Ouvrir modal résiliation
function openCancelModal(abonnementId) {
    document.getElementById('abonnementIdCancel').value = abonnementId;
    document.getElementById('motifCancel').value = '';
    document.getElementById('commentaireCancel').value = '';
    document.getElementById('cancelImmediate').checked = true;
    modalCancel.show();
}

async function handleCancelSubmit(e) {
    e.preventDefault();
    
    const abonnementId = document.getElementById('abonnementIdCancel').value;
    const motif = document.getElementById('motifCancel').value;
    const commentaire = document.getElementById('commentaireCancel').value;
    const type = document.querySelector('input[name="typeCancel"]:checked').value;
    
    if (!motif) {
        alert('Le motif est obligatoire');
        return;
    }
    
    const confirmMsg = type === 'immediate' 
        ? 'ATTENTION : Résiliation immédiate. Le compte sera bloqué maintenant. Confirmer ?' 
        : 'Résiliation à la fin de la période payée. Confirmer ?';
    
    if (!confirm(confirmMsg)) return;
    
    try {
        const response = await fetch('/lulu/api/admin-subscriptions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'resilier',
                id: abonnementId,
                motif: motif,
                type: type,
                commentaire: commentaire
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            modalCancel.hide();
            location.reload();
        } else {
            alert('Erreur : ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur réseau');
    }
}

// Export CSV
function exportCSV() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = '/lulu/api/admin-subscriptions-export.php?' + params.toString();
}

// Helper
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
