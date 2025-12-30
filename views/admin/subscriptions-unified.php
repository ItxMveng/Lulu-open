<?php
require_once '../../config/config.php';
requireRole('admin');

require_once '../../models/Subscription.php';
$subscriptionModel = new Subscription($database);

// Gérer les filtres
$activeTab = $_GET['tab'] ?? 'pending';
$filters = [];

switch ($activeTab) {
    case 'active':
        $filters['status'] = 'actif';
        break;
    case 'expired':
        $filters['status'] = 'expire';
        break;
    case 'suspended':
        $filters['status'] = 'suspendu';
        break;
    case 'expiring':
        $filters['status'] = 'actif';
        $filters['expiring_soon'] = true;
        break;
}

// Récupérer les données
$pendingRequests = $subscriptionModel->getPendingRequests();
$allSubscriptions = $subscriptionModel->getAllSubscriptions($filters);
$stats = $subscriptionModel->getSubscriptionStats();
$pendingCount = count($pendingRequests);

// Messages flash
$successMessage = $_SESSION['success_message'] ?? null;
$errorMessage = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$currencySymbols = [
    'EUR' => '€', 'USD' => '$', 'CHF' => 'CHF', 'MAD' => 'DH',
    'XOF' => 'CFA', 'XAF' => 'FCFA', 'CAD' => 'CAD', 'GBP' => '£'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Abonnements - Admin LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #007bff;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8f9fa;
        }
        
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 0 1.5rem;
            margin-bottom: 2rem;
        }
        
        .sidebar-header h4 {
            font-weight: 700;
            margin: 0;
        }
        
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: white;
        }
        
        .sidebar-nav .badge {
            margin-left: auto;
        }
        
        .admin-content {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .tab-navigation {
            background: white;
            border-radius: 12px;
            padding: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .tab-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            background: transparent;
            color: #6c757d;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        
        .tab-btn:hover {
            background: #f8f9fa;
            color: var(--primary);
        }
        
        .tab-btn.active {
            background: var(--primary);
            color: white;
        }
        
        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .card-header-custom {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
        }
        
        .table-custom {
            margin: 0;
        }
        
        .table-custom th {
            background: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6c757d;
            padding: 1rem;
            border: none;
        }
        
        .table-custom td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .table-custom tr {
            border-bottom: 1px solid #f1f1f1;
            transition: background 0.2s;
        }
        
        .table-custom tr:hover {
            background: #f8f9fa;
        }
        
        .badge-custom {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        
        .btn-action {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        
        .modal-custom .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .modal-custom .modal-header {
            border-bottom: 1px solid #e9ecef;
            padding: 1.5rem;
        }
        
        .modal-custom .modal-body {
            padding: 1.5rem;
        }
        
        .alert-custom {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h4><span style="color: #ffd700;">LULU</span>-OPEN</h4>
            <small>Administration</small>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <span class="me-2">📊</span> Dashboard
            </a>
            <a href="categories/index.php" class="nav-link">
                <span class="me-2">📁</span> Catégories
            </a>
            <a href="users.php" class="nav-link">
                <span class="me-2">👥</span> Utilisateurs
            </a>
            <a href="subscriptions-unified.php" class="nav-link active">
                <span class="me-2">💳</span> Abonnements
                <?php if ($pendingCount > 0): ?>
                    <span class="badge bg-danger"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
            <a href="../../logout.php" class="nav-link text-danger">
                <span class="me-2">🚪</span> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-1">Gestion des Abonnements</h1>
                <p class="text-muted mb-0">Vue complète des abonnements et demandes</p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e3f2fd; color: #1976d2;">📊</div>
                    <div class="stat-value"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total Abonnements</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e8f5e9; color: #388e3c;">✅</div>
                    <div class="stat-value"><?= $stats['actifs'] ?></div>
                    <div class="stat-label">Actifs</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff3e0; color: #f57c00;">⚠️</div>
                    <div class="stat-value"><?= $stats['expiring_soon'] ?></div>
                    <div class="stat-label">Expirent Bientôt</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fce4ec; color: #c2185b;">💰</div>
                    <div class="stat-value"><?= number_format($stats['mrr'], 0) ?>€</div>
                    <div class="stat-label">MRR (Revenu Mensuel)</div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <a href="?tab=pending" class="tab-btn <?= $activeTab === 'pending' ? 'active' : '' ?>">
                🔔 En Attente <?php if ($pendingCount > 0): ?><span class="badge bg-danger ms-1"><?= $pendingCount ?></span><?php endif; ?>
            </a>
            <a href="?tab=active" class="tab-btn <?= $activeTab === 'active' ? 'active' : '' ?>">
                ✅ Actifs (<?= $stats['actifs'] ?>)
            </a>
            <a href="?tab=expiring" class="tab-btn <?= $activeTab === 'expiring' ? 'active' : '' ?>">
                ⚠️ Expirent Bientôt (<?= $stats['expiring_soon'] ?>)
            </a>
            <a href="?tab=expired" class="tab-btn <?= $activeTab === 'expired' ? 'active' : '' ?>">
                📅 Expirés (<?= $stats['expires'] ?>)
            </a>
            <a href="?tab=suspended" class="tab-btn <?= $activeTab === 'suspended' ? 'active' : '' ?>">
                🚫 Suspendus (<?= $stats['suspendus'] ?>)
            </a>
            <a href="?tab=all" class="tab-btn <?= $activeTab === 'all' ? 'active' : '' ?>">
                📋 Tous
            </a>
        </div>

        <!-- Content -->
        <?php if ($activeTab === 'pending'): ?>
            <!-- Demandes en attente -->
            <div class="content-card">
                <div class="card-header-custom">
                    <h5 class="mb-0">Demandes d'Abonnement en Attente</h5>
                </div>
                <div class="table-responsive">
                    <?php if (empty($pendingRequests)): ?>
                        <div class="p-4 text-center text-muted">
                            <p class="mb-0">Aucune demande en attente</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Utilisateur</th>
                                    <th>Type</th>
                                    <th>Durée</th>
                                    <th>Montant</th>
                                    <th>Méthode</th>
                                    <th>Date</th>
                                    <th>Preuve</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingRequests as $req): ?>
                                    <tr>
                                        <td><strong>#<?= $req['id'] ?></strong></td>
                                        <td>
                                            <div><strong><?= htmlspecialchars($req['prenom'] . ' ' . $req['nom']) ?></strong></div>
                                            <small class="text-muted"><?= htmlspecialchars($req['email']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge-custom bg-info text-white">
                                                <?= ucfirst($req['type_utilisateur']) ?>
                                            </span>
                                        </td>
                                        <td><?= $req['duration_months'] ?> mois</td>
                                        <td> <!-- Montant du plan -->
                                            <strong><?= number_format($req['plan_price'] ?? 0, 2) ?> <?= $currencySymbols[$req['plan_currency']] ?? $req['plan_currency'] ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($req['payment_method']) ?></td>
                                        <td><small><?= date('d/m/Y H:i', strtotime($req['submitted_at'])) ?></small></td>
                                        <td><a href="/lulu/<?= htmlspecialchars($req['proof_document_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">👁️ Voir</a></td>
                                        <td>
                                            <button class="btn btn-sm btn-success" onclick="verifyRequest(<?= $req['id'] ?>)">
                                                ✓ Vérifier
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="rejectRequest(<?= $req['id'] ?>)">
                                                ✗ Rejeter
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Liste des abonnements -->
            <div class="content-card">
                <div class="card-header-custom">
                    <h5 class="mb-0">Abonnements - <?= ucfirst($activeTab) ?></h5>
                </div>
                <div class="table-responsive">
                    <?php if (empty($allSubscriptions)): ?>
                        <div class="p-4 text-center text-muted">
                            <p class="mb-0">Aucun abonnement trouvé</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Utilisateur</th>
                                    <th>Type</th>
                                    <th>Durée</th>
                                    <th>Prix</th>
                                    <th>Période</th>
                                    <th>Statut</th>
                                    <th>Expire dans</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allSubscriptions as $sub): ?>
                                    <tr>
                                        <td><strong>#<?= $sub['id'] ?></strong></td>
                                        <td>
                                            <div><strong><?= htmlspecialchars($sub['prenom'] . ' ' . $sub['nom']) ?></strong></div>
                                            <small class="text-muted"><?= htmlspecialchars($sub['email']) ?></small>
                                            <br><span class="badge-custom bg-secondary"><?= ucfirst($sub['type_utilisateur']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge-custom bg-primary text-white">
                                                <?= ucfirst($sub['type_abonnement']) ?>
                                            </span>
                                        </td>
                                        <td><?= $sub['duree_mois'] ?? 3 ?> mois</td>
                                        <td><strong><?= number_format($sub['prix'], 2) ?> <?= $currencySymbols[$sub['devise'] ?? 'EUR'] ?? ($sub['devise'] ?? 'EUR') ?></strong></td>
                                        <td>
                                            <small>
                                                <?= date('d/m/Y', strtotime($sub['date_debut'])) ?><br>
                                                au <?= date('d/m/Y', strtotime($sub['date_fin'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeClass = match($sub['statut']) {
                                                'actif' => 'bg-success',
                                                'expire' => 'bg-warning',
                                                'suspendu' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge-custom <?= $badgeClass ?> text-white">
                                                <?= ucfirst($sub['statut']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($sub['statut'] === 'actif'): ?>
                                                <?php if ($sub['days_remaining'] <= 7 && $sub['days_remaining'] >= 0): ?>
                                                    <span class="text-danger fw-bold"><?= $sub['days_remaining'] ?> jour(s)</span>
                                                <?php elseif ($sub['days_remaining'] > 0): ?>
                                                    <span class="text-success"><?= $sub['days_remaining'] ?> jour(s)</span>
                                                <?php else: ?>
                                                    <span class="text-muted">Expiré</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="viewSubscription(<?= $sub['id'] ?>)" title="Voir détails">
                                                    👁️
                                                </button>
                                                <?php if ($sub['statut'] === 'actif'): ?>
                                                    <button class="btn btn-outline-warning" onclick="extendSubscription(<?= $sub['id'] ?>)" title="Prolonger">
                                                        ⏰
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="suspendSubscription(<?= $sub['id'] ?>)" title="Suspendre">
                                                        🚫
                                                    </button>
                                                <?php elseif ($sub['statut'] === 'suspendu'): ?>
                                                    <button class="btn btn-outline-success" onclick="reactivateSubscription(<?= $sub['id'] ?>)" title="Réactiver">
                                                        ✅
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Vérification -->
    <div class="modal fade modal-custom" id="verifyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Vérifier le Paiement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="/lulu/controllers/AdminSubscriptionController.php?action=verifyPayment">
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="verify_request_id">
                        <div class="mb-3">
                            <label class="form-label">Notes administratives (optionnel)</label>
                            <textarea name="admin_notes" class="form-control" rows="3" placeholder="Commentaires..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">✓ Activer l'Abonnement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Rejet -->
    <div class="modal fade modal-custom" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rejeter la Demande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="/lulu/controllers/AdminSubscriptionController.php?action=rejectPayment">
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="reject_request_id">
                        <div class="mb-3">
                            <label class="form-label">Raison du rejet *</label>
                            <textarea name="admin_notes" class="form-control" rows="3" placeholder="Expliquez la raison..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">✗ Confirmer le Rejet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verifyRequest(id) {
            document.getElementById('verify_request_id').value = id;
            new bootstrap.Modal(document.getElementById('verifyModal')).show();
        }

        function rejectRequest(id) {
            document.getElementById('reject_request_id').value = id;
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        }

        function viewSubscription(id) {
            alert('Fonctionnalité "Voir détails" - À implémenter avec modal détaillé');
            // TODO: Afficher modal avec historique complet, paiements, etc.
        }

        function suspendSubscription(id) {
            if (confirm('Êtes-vous sûr de vouloir suspendre cet abonnement ?')) {
                window.location.href = `/lulu/api/admin-subscription-actions.php?action=suspend&id=${id}`;
            }
        }

        function reactivateSubscription(id) {
            if (confirm('Êtes-vous sûr de vouloir réactiver cet abonnement ?')) {
                window.location.href = `/lulu/api/admin-subscription-actions.php?action=reactivate&id=${id}`;
            }
        }

        function extendSubscription(id) {
            const months = prompt('Combien de mois souhaitez-vous ajouter ?', '3');
            if (months && !isNaN(months) && months > 0) {
                window.location.href = `/lulu/api/admin-subscription-actions.php?action=extend&id=${id}&months=${months}`;
            }
        }
    </script>
</body>
</html>
