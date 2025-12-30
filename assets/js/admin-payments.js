/**
 * Gestion Paiements - Interface Admin
 */

let modalDetails, modalRefund;

document.addEventListener('DOMContentLoaded', function() {
    modalDetails = new bootstrap.Modal(document.getElementById('modalDetails'));
    modalRefund = new bootstrap.Modal(document.getElementById('modalRefund'));
    
    document.getElementById('formRefund').addEventListener('submit', handleRefundSubmit);
    
    document.getElementById('typeRefund').addEventListener('change', function() {
        const montantDiv = document.getElementById('montantPartielDiv');
        montantDiv.style.display = this.value === 'partiel' ? 'block' : 'none';
    });
});

// Afficher détails paiement
async function showDetails(paiementId) {
    const content = document.getElementById('detailsContent');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
    
    modalDetails.show();
    
    try {
        const response = await fetch('/lulu/api/admin-payments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'details', id: paiementId })
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
    const p = data.paiement;
    
    // Gérer photo profil
    let photoUrl = '/lulu/assets/images/default-avatar.png';
    if (p.photo_profil) {
        if (p.photo_profil.startsWith('http')) {
            photoUrl = p.photo_profil;
        } else if (p.photo_profil.startsWith('uploads/')) {
            photoUrl = '/lulu/' + p.photo_profil;
        } else if (p.photo_profil.startsWith('profiles/')) {
            photoUrl = '/lulu/uploads/' + p.photo_profil;
        } else {
            photoUrl = '/lulu/uploads/profiles/' + p.photo_profil;
        }
    }
    
    const statusBadge = {
        'valide': 'success',
        'en_attente': 'warning',
        'echoue': 'danger',
        'rembourse': 'info',
        'annule': 'dark'
    };
    
    let html = `
        <div class="row">
            <div class="col-md-6 mb-3">
                <h6 class="text-muted mb-3"><i class="bi bi-person-fill"></i> Utilisateur</h6>
                <div class="d-flex align-items-center mb-3">
                    <img src="${photoUrl}" 
                         class="rounded-circle me-3"
                         style="width: 60px; height: 60px; object-fit: cover;"
                         onerror="this.src='/lulu/assets/images/default-avatar.png'">
                    <div>
                        <strong>${p.nom_utilisateur}</strong><br>
                        <small class="text-muted">${p.email}</small><br>
                        ${p.telephone ? `<small class="text-muted"><i class="bi bi-telephone"></i> ${p.telephone}</small>` : ''}
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-3">
                <h6 class="text-muted mb-3"><i class="bi bi-credit-card"></i> Paiement</h6>
                <p class="mb-2"><strong>ID :</strong> #${p.id}</p>
                <p class="mb-2"><strong>Montant :</strong> <span class="fs-4 fw-bold text-success">${parseFloat(p.montant).toFixed(2)}€</span></p>
                <p class="mb-2"><strong>Statut :</strong> <span class="badge bg-${statusBadge[p.statut] || 'secondary'}">${p.statut.toUpperCase()}</span></p>
                <p class="mb-2"><strong>Méthode :</strong> ${p.methode_paiement.toUpperCase()}</p>
            </div>
            
            <div class="col-md-6 mb-3">
                <h6 class="text-muted mb-3"><i class="bi bi-calendar"></i> Dates</h6>
                <p class="mb-2"><strong>Date paiement :</strong> ${formatDateFull(p.date_paiement)}</p>
            </div>
            
            <div class="col-md-6 mb-3">
                <h6 class="text-muted mb-3"><i class="bi bi-receipt"></i> Transaction</h6>
                ${p.transaction_id ? `<p class="mb-2"><strong>ID Transaction :</strong><br><code class="bg-light p-2 d-block">${p.transaction_id}</code></p>` : '<p class="text-muted">Aucun ID de transaction</p>'}
            </div>
            
            ${p.plan_nom ? `
            <div class="col-12 mb-3">
                <h6 class="text-muted mb-3"><i class="bi bi-award"></i> Abonnement associé</h6>
                <div class="alert alert-info mb-0">
                    <strong>Plan :</strong> ${p.plan_nom}<br>
                    ${p.abo_date_debut ? `<strong>Période :</strong> ${formatDate(p.abo_date_debut)} au ${formatDate(p.abo_date_fin)}` : ''}
                </div>
            </div>
            ` : ''}
            
            ${p.statut === 'rembourse' ? `
            <div class="col-12 mb-3">
                <div class="alert alert-danger">
                    <h6 class="alert-heading"><i class="bi bi-arrow-counterclockwise"></i> Remboursement</h6>
                    <p class="mb-0">Ce paiement a été remboursé</p>
                </div>
            </div>
            ` : ''}
        </div>
        
        ${data.logs && data.logs.length > 0 ? `
        <hr>
        <h6 class="text-muted mb-3"><i class="bi bi-clock-history"></i> Historique des actions admin</h6>
        <div class="logs-container" style="max-height: 200px; overflow-y: auto;">
            ${data.logs.map(log => `
                <div class="mb-2 p-2 bg-light rounded">
                    <small>
                        <strong>${formatDateFull(log.created_at)}</strong> - ${log.admin_nom}<br>
                        <span class="badge bg-info">${log.action}</span>
                    </small>
                </div>
            `).join('')}
        </div>
        ` : ''}
    `;
    
    return html;
}

// Valider paiement manuel
async function validatePayment(paiementId) {
    if (!confirm('Valider ce paiement manuellement ?\n\nCela confirmera la réception du paiement et activera l\'abonnement.')) {
        return;
    }
    
    try {
        const response = await fetch('/lulu/api/admin-payments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'valider',
                id: paiementId
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

// Ouvrir modal remboursement
function openRefundModal(paiementId, montant) {
    document.getElementById('paiementIdRefund').value = paiementId;
    document.getElementById('montantMaxRefund').value = montant;
    document.getElementById('montantMaxDisplay').textContent = parseFloat(montant).toFixed(2);
    document.getElementById('typeRefund').value = 'total';
    document.getElementById('montantPartielDiv').style.display = 'none';
    document.getElementById('montantPartiel').value = '';
    document.getElementById('motifRefundSelect').value = '';
    document.getElementById('motifRefundTexte').value = '';
    document.getElementById('notifierUtilisateur').checked = true;
    modalRefund.show();
}

async function handleRefundSubmit(e) {
    e.preventDefault();
    
    const paiementId = document.getElementById('paiementIdRefund').value;
    const type = document.getElementById('typeRefund').value;
    const motifSelect = document.getElementById('motifRefundSelect').value;
    const motifTexte = document.getElementById('motifRefundTexte').value;
    const notifier = document.getElementById('notifierUtilisateur').checked;
    
    if (!motifSelect) {
        alert('Veuillez sélectionner un motif');
        return;
    }
    
    let montant = null;
    if (type === 'partiel') {
        montant = parseFloat(document.getElementById('montantPartiel').value);
        const montantMax = parseFloat(document.getElementById('montantMaxRefund').value);
        
        if (!montant || montant <= 0 || montant > montantMax) {
            alert(`Montant invalide. Doit être entre 0.01 et ${montantMax.toFixed(2)}€`);
            return;
        }
    }
    
    const confirmMsg = type === 'total' 
        ? 'ATTENTION : Remboursement TOTAL. L\'abonnement sera suspendu. Confirmer ?' 
        : `Rembourser ${montant.toFixed(2)}€ ? L'abonnement restera actif.`;
    
    if (!confirm(confirmMsg)) return;
    
    try {
        const response = await fetch('/lulu/api/admin-payments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'rembourser',
                id: paiementId,
                type: type,
                montant: montant,
                motif: motifSelect,
                motif_texte: motifTexte,
                notifier: notifier
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            modalRefund.hide();
            location.reload();
        } else {
            alert('Erreur : ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur réseau');
    }
}

// Télécharger facture PDF
function downloadInvoice(paiementId) {
    window.open(`/lulu/api/generate-invoice.php?id=${paiementId}`, '_blank');
}

// Export CSV
function exportCSV() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = '/lulu/api/admin-payments-export.php?' + params.toString();
}

// Imprimer rapport
function printReport() {
    window.print();
}

// Helpers
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR');
}

function formatDateFull(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
