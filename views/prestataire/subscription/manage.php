<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Gestion de l'abonnement</h1>
                    <p class="text-muted mb-0">Gérez votre abonnement et consultez l'historique</p>
                </div>
                <?php if (!$currentSubscription || $currentSubscription['statut'] !== 'actif'): ?>
                    <a href="/prestataire/subscription/checkout" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Souscrire un abonnement
                    </a>
                <?php endif; ?>
            </div>

            <!-- Statut actuel -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statut de l'abonnement</h5>
                </div>
                <div class="card-body">
                    <?php if ($currentSubscription && $currentSubscription['statut'] === 'actif'): ?>
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="status-icon bg-success me-3">
                                        <i class="bi bi-check-circle text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Abonnement <?= ucfirst($currentSubscription['type_abonnement']) ?> Actif</h6>
                                        <p class="text-muted mb-0">
                                            Expire le <?= date('d/m/Y', strtotime($currentSubscription['date_fin'])) ?>
                                            (<?= $daysRemaining ?> jours restants)
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="subscription-progress mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">Période d'abonnement</small>
                                        <small class="text-muted"><?= $daysRemaining ?> / <?= $totalDays ?> jours</small>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar <?= $daysRemaining <= 7 ? 'bg-warning' : 'bg-success' ?>" 
                                             style="width: <?= ($daysRemaining / $totalDays) * 100 ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="subscription-details">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Prix</small>
                                            <strong><?= formatPrice($currentSubscription['prix']) ?></strong>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Renouvellement</small>
                                            <strong><?= $currentSubscription['auto_renouvellement'] ? 'Automatique' : 'Manuel' ?></strong>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Prochaine facturation</small>
                                            <strong><?= date('d/m/Y', strtotime($currentSubscription['date_fin'])) ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="btn-group-vertical d-grid gap-2">
                                    <button class="btn btn-outline-primary btn-sm" onclick="toggleAutoRenewal()">
                                        <i class="bi bi-arrow-repeat"></i> 
                                        <?= $currentSubscription['auto_renouvellement'] ? 'Désactiver' : 'Activer' ?> le renouvellement
                                    </button>
                                    <button class="btn btn-outline-warning btn-sm" onclick="upgradeSubscription()">
                                        <i class="bi bi-arrow-up-circle"></i> Changer d'abonnement
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="cancelSubscription()">
                                        <i class="bi bi-x-circle"></i> Annuler l'abonnement
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div class="status-icon bg-warning mx-auto mb-3">
                                <i class="bi bi-exclamation-triangle text-white"></i>
                            </div>
                            <h6 class="mb-2">Aucun abonnement actif</h6>
                            <p class="text-muted mb-3">Souscrivez à un abonnement pour accéder à toutes les fonctionnalités</p>
                            <a href="/prestataire/subscription/checkout" class="btn btn-primary">
                                Choisir un abonnement
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historique des abonnements -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Historique des abonnements</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="downloadInvoices()">
                        <i class="bi bi-download"></i> Télécharger les factures
                    </button>
                </div>
                <div class="card-body">
                    <?php if (!empty($subscriptionHistory)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Période</th>
                                        <th>Type</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th>Paiement</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscriptionHistory as $subscription): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold"><?= date('d/m/Y', strtotime($subscription['date_debut'])) ?></span>
                                                    <small class="text-muted">au <?= date('d/m/Y', strtotime($subscription['date_fin'])) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= ucfirst($subscription['type_abonnement']) ?></span>
                                            </td>
                                            <td class="fw-bold"><?= formatPrice($subscription['prix']) ?></td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'actif' => 'success',
                                                    'expire' => 'secondary',
                                                    'annule' => 'danger',
                                                    'suspendu' => 'warning'
                                                ];
                                                $statusLabel = [
                                                    'actif' => 'Actif',
                                                    'expire' => 'Expiré',
                                                    'annule' => 'Annulé',
                                                    'suspendu' => 'Suspendu'
                                                ];
                                                ?>
                                                <span class="badge bg-<?= $statusClass[$subscription['statut']] ?? 'secondary' ?>">
                                                    <?= $statusLabel[$subscription['statut']] ?? 'Inconnu' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($subscription['statut_paiement']): ?>
                                                    <div class="d-flex flex-column">
                                                        <span class="badge bg-<?= $subscription['statut_paiement'] === 'valide' ? 'success' : 'danger' ?> mb-1">
                                                            <?= ucfirst($subscription['statut_paiement']) ?>
                                                        </span>
                                                        <small class="text-muted"><?= ucfirst($subscription['methode_paiement'] ?? '') ?></small>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="viewInvoice(<?= $subscription['id'] ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <?php if ($subscription['statut_paiement'] === 'valide'): ?>
                                                        <button class="btn btn-outline-secondary" onclick="downloadInvoice(<?= $subscription['id'] ?>)">
                                                            <i class="bi bi-download"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-receipt" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">Aucun historique d'abonnement</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="stat-icon bg-primary mb-3 mx-auto">
                                <i class="bi bi-calendar-check text-white"></i>
                            </div>
                            <h4 class="mb-1"><?= $subscriptionStats['total_months'] ?? 0 ?></h4>
                            <p class="text-muted mb-0">Mois d'abonnement</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="stat-icon bg-success mb-3 mx-auto">
                                <i class="bi bi-currency-euro text-white"></i>
                            </div>
                            <h4 class="mb-1"><?= formatPrice($subscriptionStats['total_spent'] ?? 0) ?></h4>
                            <p class="text-muted mb-0">Total dépensé</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="stat-icon bg-info mb-3 mx-auto">
                                <i class="bi bi-graph-up text-white"></i>
                            </div>
                            <h4 class="mb-1"><?= $subscriptionStats['avg_monthly'] ?? 0 ?>%</h4>
                            <p class="text-muted mb-0">Taux de renouvellement</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.status-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.subscription-progress .progress {
    border-radius: 10px;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.btn-group-vertical .btn {
    border-radius: 6px !important;
    margin-bottom: 0.25rem;
}

.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<script>
async function toggleAutoRenewal() {
    if (!confirm('Voulez-vous modifier le renouvellement automatique ?')) return;
    
    try {
        const response = await fetch('/api/subscription/toggle-renewal', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                csrf_token: '<?= $csrf_token ?>'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        alert('Erreur de connexion');
    }
}

async function cancelSubscription() {
    if (!confirm('Êtes-vous sûr de vouloir annuler votre abonnement ? Cette action est irréversible.')) return;
    
    try {
        const response = await fetch('/api/subscription/cancel', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                csrf_token: '<?= $csrf_token ?>'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Abonnement annulé avec succès');
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        alert('Erreur de connexion');
    }
}

function upgradeSubscription() {
    window.location.href = '/prestataire/subscription/checkout?upgrade=1';
}

function viewInvoice(subscriptionId) {
    window.open('/api/subscription/invoice/' + subscriptionId, '_blank');
}

function downloadInvoice(subscriptionId) {
    window.location.href = '/api/subscription/invoice/' + subscriptionId + '/download';
}

function downloadInvoices() {
    window.location.href = '/api/subscription/invoices/download';
}
</script>