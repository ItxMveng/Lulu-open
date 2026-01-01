<?php require_once __DIR__ . '/../../config/stripe.php'; ?>
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

// Récupération des données
$db = Database::getInstance();

// Statistiques générales
$stats = [
    'total_users' => $db->fetch("SELECT COUNT(*) as count FROM utilisateurs")['count'],
    'active_subscriptions' => $db->fetch("SELECT COUNT(*) as count FROM utilisateurs WHERE subscription_status = 'Actif' AND subscription_end_date > NOW()")['count'],
    'pending_requests' => $db->fetch("SELECT COUNT(*) as count FROM demandes_upgrade WHERE statut = 'en_attente'")['count'],
    'monthly_revenue' => $db->fetch("SELECT COALESCE(SUM(montant), 0) as total FROM paiements_stripe WHERE status = 'succeeded' AND MONTH(created_at) = MONTH(NOW())")['total']
];

// Demandes d'upgrade en attente
$pending_requests = $db->fetchAll("
    SELECT du.*, u.prenom, u.nom, u.email, u.type_utilisateur
    FROM demandes_upgrade du
    JOIN utilisateurs u ON du.utilisateur_id = u.id
    WHERE du.statut = 'en_attente'
    ORDER BY du.date_demande DESC
");

// Abonnements actifs avec Stripe
$active_subscriptions = $db->fetchAll("
    SELECT u.id, u.prenom, u.nom, u.email, u.subscription_status, 
           u.subscription_start_date, u.subscription_end_date,
           DATEDIFF(u.subscription_end_date, NOW()) as days_remaining
    FROM utilisateurs u
    WHERE u.subscription_status = 'Actif' 
    AND u.subscription_end_date > NOW()
    ORDER BY u.subscription_end_date ASC
    LIMIT 20
");

// Paiements récents
$recent_payments = $db->fetchAll("
    SELECT ps.*, u.prenom, u.nom, u.email
    FROM paiements_stripe ps
    JOIN utilisateurs u ON ps.utilisateur_id = u.id
    ORDER BY ps.created_at DESC
    LIMIT 20
");

$page_title = "Dashboard Stripe - Admin LULU-OPEN";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/admin-global.css') ?>" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
    .admin-content {
        margin-left: 260px;
        padding: 2rem;
        min-height: 100vh;
        background: #f8f9fa;
    }
    
    @media (max-width: 991.98px) {
        .admin-content {
            margin-left: 0;
            padding-top: calc(60px + 2rem);
        }
    }
    
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: none;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }
    
    .stat-icon.users { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .stat-icon.subscriptions { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .stat-icon.pending { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .stat-icon.revenue { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333 !important; }
    
    .btn-action {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(0,123,255,0.05);
    }
    
    .badge-plan {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .badge-monthly { background: #e3f2fd; color: #1976d2; }
    .badge-quarterly { background: #e8f5e8; color: #388e3c; }
    .badge-yearly { background: #fff3e0; color: #f57c00; }
    .badge-active { background: #e8f5e8; color: #388e3c; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>
    
    <div class="admin-content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <div class="breadcrumb-custom">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= url('views/admin/dashboard.php') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Stripe Dashboard</li>
                </ol>
            </div>
        </nav>
        
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-down">
            <div>
                <h1 class="mb-2" style="color: var(--primary-dark); font-weight: 700;">
                    <i class="bi bi-credit-card-fill me-2"></i>Dashboard Stripe
                </h1>
                <p class="text-muted">Gestion des paiements et abonnements automatisés</p>
            </div>
            <div>
                <a href="<?= url('views/admin/subscriptions.php') ?>" class="btn btn-primary">
                    <i class="bi bi-list-ul me-2"></i>Tous les abonnements
                </a>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3" data-aos="fade-up" data-aos-delay="0">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon users">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0" id="stat-total-users"><?= number_format($stats['total_users']) ?></h3>
                            <small class="text-muted">Utilisateurs total</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon subscriptions">
                            <i class="bi bi-shield-check-fill"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0" id="stat-active-subs"><?= number_format($stats['active_subscriptions']) ?></h3>
                            <small class="text-muted">Abonnements actifs</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon pending">
                            <i class="bi bi-clock-fill"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0" id="stat-pending"><?= number_format($stats['pending_requests']) ?></h3>
                            <small class="text-muted">Demandes en attente</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon revenue">
                            <i class="bi bi-currency-euro"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0" id="stat-revenue"><?= number_format($stats['monthly_revenue'], 2) ?>€</h3>
                            <small class="text-muted">Revenus ce mois</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Demandes en attente -->
        <?php if (!empty($pending_requests)): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    Demandes d'upgrade en attente (<?= count($pending_requests) ?>)
                </h5>
                <span class="badge bg-warning"><?= count($pending_requests) ?> à traiter</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Plan demandé</th>
                            <th>Montant</th>
                            <th>Date demande</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_requests as $request): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($request['prenom'] . ' ' . $request['nom']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($request['email']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $planConfig = getStripeConfig($request['plan_demande']);
                                    ?>
                                    <span class="badge bg-primary">
                                        <?= $planConfig ? $planConfig['name'] : ucfirst($request['plan_demande']) ?>
                                    </span>
                                    <?php if ($planConfig): ?>
                                    <br><small class="text-muted"><?= $planConfig['description'] ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-success"><?= number_format($request['montant'], 2) ?>€</td>
                                <td><?= date('d/m/Y H:i', strtotime($request['date_demande'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-success btn-action" 
                                                onclick="approveRequest(<?= $request['id'] ?>)"
                                                title="Approuver">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button class="btn btn-danger btn-action" 
                                                onclick="rejectRequest(<?= $request['id'] ?>)"
                                                title="Refuser">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Abonnements actifs -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-shield-check-fill text-success me-2"></i>
                            Abonnements actifs récents
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Plan</th>
                                    <th>Expire dans</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_subscriptions as $sub): ?>
                                    <tr class="<?= $sub['days_remaining'] <= 7 ? 'table-warning' : '' ?>">
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($sub['prenom'] . ' ' . $sub['nom']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($sub['email']) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-plan badge-active">
                                                Premium
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($sub['days_remaining'] <= 7): ?>
                                                <span class="text-danger fw-bold">
                                                    <i class="bi bi-exclamation-triangle"></i> <?= $sub['days_remaining'] ?> jours
                                                </span>
                                            <?php else: ?>
                                                <?= $sub['days_remaining'] ?> jours
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline-primary btn-action" 
                                                    onclick="viewUser(<?= $sub['id'] ?>)"
                                                    title="Voir détails">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-credit-card-fill text-primary me-2"></i>
                            Paiements récents
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach (array_slice($recent_payments, 0, 10) as $payment): ?>
                            <div class="d-flex align-items-center p-3 border-bottom">
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?= htmlspecialchars($payment['prenom'] . ' ' . $payment['nom']) ?></div>
                                    <small class="text-muted"><?= date('d/m H:i', strtotime($payment['created_at'])) ?></small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-success"><?= number_format($payment['montant'], 2) ?>€</div>
                                    <span class="badge bg-<?= $payment['status'] === 'succeeded' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($payment['status']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalTitle">Confirmer l'action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="confirmModalBody">
                    <!-- Contenu dynamique -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="confirmModalAction">Confirmer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 600, once: true });
        
        // Mise à jour temps réel des statistiques
        function updateStats() {
            fetch('<?= url('api/admin-stripe-stats.php') ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('stat-active-subs').textContent = data.stats.abonnements_actifs;
                        document.getElementById('stat-revenue').textContent = data.stats.revenus_aujourd_hui.toFixed(2) + '€';
                        
                        // Afficher notification si nouveaux abonnements
                        if (data.stats.nouveaux_aujourd_hui > 0) {
                            console.log(`🎉 ${data.stats.nouveaux_aujourd_hui} nouveaux abonnements aujourd'hui`);
                        }
                    }
                })
                .catch(console.error);
        }
        
        // Mise à jour toutes les 30 secondes
        setInterval(updateStats, 30000);
        
        function approveRequest(requestId) {
            showConfirmModal(
                'Approuver la demande',
                'Êtes-vous sûr de vouloir approuver cette demande d\'abonnement ? L\'utilisateur sera automatiquement facturé et son abonnement sera activé.',
                () => processRequest(requestId, 'approuver')
            );
        }
        
        function rejectRequest(requestId) {
            showConfirmModal(
                'Refuser la demande',
                'Êtes-vous sûr de vouloir refuser cette demande d\'abonnement ?',
                () => {
                    const motif = prompt('Motif du refus (optionnel):');
                    processRequest(requestId, 'refuser', motif || 'Demande refusée par l\'administrateur');
                }
            );
        }
        
        function processRequest(requestId, action, motif = null) {
            const data = { action, id: requestId };
            if (motif) data.motif = motif;
            
            fetch('<?= url('api/admin-subscriptions.php') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de communication avec le serveur');
            });
        }
        
        function viewUser(userId) {
            window.open(`<?= url('views/admin/user-detail.php') ?>?id=${userId}`, '_blank');
        }
        
        function showConfirmModal(title, body, callback) {
            document.getElementById('confirmModalTitle').textContent = title;
            document.getElementById('confirmModalBody').textContent = body;
            
            const actionBtn = document.getElementById('confirmModalAction');
            actionBtn.onclick = () => {
                callback();
                bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
            };
            
            new bootstrap.Modal(document.getElementById('confirmModal')).show();
        }
    </script>
</body>
</html>