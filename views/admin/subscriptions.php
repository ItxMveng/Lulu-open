<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/stripe.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

// Filtres
$statut_filter = $_GET['statut'] ?? 'Actif';
$search = $_GET['search'] ?? '';

$db = Database::getInstance();

// Requête pour TOUS les utilisateurs avec leur statut d'abonnement réel
$sql = "SELECT u.id, u.prenom, u.nom, u.email, u.photo_profil, u.type_utilisateur,
               CASE 
                   WHEN u.subscription_status = 'Actif' AND u.subscription_end_date > NOW() THEN 'Actif'
                   WHEN u.subscription_status = 'Actif' AND u.subscription_end_date <= NOW() THEN 'Expiré'
                   ELSE 'Gratuit'
               END as subscription_status,
               u.subscription_start_date, 
               u.subscription_end_date,
               CASE 
                   WHEN u.subscription_status = 'Actif' AND u.subscription_end_date > NOW() THEN DATEDIFF(u.subscription_end_date, NOW())
                   ELSE NULL 
               END as days_remaining,
               ps.plan as stripe_plan
        FROM utilisateurs u 
        LEFT JOIN paiements_stripe ps ON u.id = ps.utilisateur_id AND ps.status = 'succeeded'
        WHERE 1=1";
$params = [];

if ($statut_filter && $statut_filter !== 'tous') {
    if ($statut_filter === 'Gratuit') {
        $sql .= " AND (u.subscription_status IS NULL OR u.subscription_status != 'Actif' OR u.subscription_end_date <= NOW())";
    } elseif ($statut_filter === 'Expiré') {
        $sql .= " AND u.subscription_status = 'Actif' AND u.subscription_end_date <= NOW()";
    } else {
        $sql .= " AND u.subscription_status = ? AND u.subscription_end_date > NOW()";
        $params[] = $statut_filter;
    }
}

if ($search) {
    $sql .= " AND (u.prenom LIKE ? OR u.nom LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY u.subscription_end_date DESC";

$abonnements = $db->fetchAll($sql, $params);

// Statistiques
$stats = [
    'total_users' => $db->fetch("SELECT COUNT(*) as count FROM utilisateurs")['count'],
    'total_actifs' => $db->fetch("SELECT COUNT(*) as count FROM utilisateurs WHERE subscription_status = 'Actif' AND subscription_end_date > NOW()")['count'],
    'expire_7j' => $db->fetch("SELECT COUNT(*) as count FROM utilisateurs WHERE subscription_status = 'Actif' AND subscription_end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)")['count'],
    'revenus_mois' => $db->fetch("SELECT COALESCE(SUM(montant), 0) as total FROM paiements_stripe WHERE status = 'succeeded' AND MONTH(created_at) = MONTH(NOW())")['total'],
    'gratuits' => $db->fetch("SELECT COUNT(*) as count FROM utilisateurs WHERE subscription_status IS NULL OR subscription_status != 'Actif' OR subscription_end_date <= NOW()")['count']
];

// Demandes d'upgrade en attente
$pending_requests = $db->fetchAll("
    SELECT du.*, u.prenom, u.nom, u.email, u.type_utilisateur
    FROM demandes_upgrade du
    JOIN utilisateurs u ON du.utilisateur_id = u.id
    WHERE du.statut = 'en_attente'
    ORDER BY du.date_demande DESC
");

// Paiements récents
$recent_payments = $db->fetchAll("
    SELECT ps.*, u.prenom, u.nom, u.email
    FROM paiements_stripe ps
    JOIN utilisateurs u ON ps.utilisateur_id = u.id
    ORDER BY ps.created_at DESC
    LIMIT 20
");

$page_title = "Gestion Stripe - Admin LULU-OPEN";
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
    
    .badge-active { background: #e8f5e8; color: #388e3c; }
    .badge-inactive { background: #ffebee; color: #d32f2f; }
    .badge-expired { background: #fff3e0; color: #f57c00; }
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
                    <li class="breadcrumb-item active">Gestion Stripe</li>
                </ol>
            </div>
        </nav>
        
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-2" style="color: var(--primary-dark); font-weight: 700;">
                    <i class="bi bi-credit-card-fill me-2"></i>Gestion Stripe
                </h1>
                <p class="text-muted">Abonnements, paiements et suivi automatisé</p>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon users">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?= number_format($stats['total_users']) ?></h3>
                            <small class="text-muted">Utilisateurs total</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon subscriptions">
                            <i class="bi bi-shield-check-fill"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?= number_format($stats['total_actifs']) ?></h3>
                            <small class="text-muted">Abonnements actifs</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon pending">
                            <i class="bi bi-clock-fill"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?= number_format($stats['expire_7j']) ?></h3>
                            <small class="text-muted">Expirent dans 7j</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon revenue">
                            <i class="bi bi-currency-euro"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?= number_format($stats['revenus_mois'], 2) ?>€</h3>
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
        
        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Rechercher</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Nom, email..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="Actif" <?= $statut_filter === 'Actif' ? 'selected' : '' ?>>Actifs</option>
                            <option value="Gratuit" <?= $statut_filter === 'Gratuit' ? 'selected' : '' ?>>Gratuits</option>
                            <option value="Inactif" <?= $statut_filter === 'Inactif' ? 'selected' : '' ?>>Inactifs</option>
                            <option value="Expiré" <?= $statut_filter === 'Expiré' ? 'selected' : '' ?>>Expirés</option>
                            <option value="tous" <?= $statut_filter === 'tous' ? 'selected' : '' ?>>Tous</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Abonnements et Paiements -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-shield-check-fill text-success me-2"></i>
                            Abonnements (<?= count($abonnements) ?>)
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Plan</th>
                                    <th>Statut</th>
                                    <th>Début</th>
                                    <th>Fin</th>
                                    <th>Jours restants</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($abonnements)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
                                            <p class="text-muted mt-2">Aucun abonnement trouvé</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($abonnements as $abo): ?>
                                        <tr class="<?= $abo['days_remaining'] <= 7 && $abo['subscription_status'] === 'Actif' ? 'table-warning' : '' ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= url('assets/images/default-avatar.png') ?>" 
                                                         class="rounded-circle me-2"
                                                         style="width: 32px; height: 32px; object-fit: cover;">
                                                    <div>
                                                        <strong><?= htmlspecialchars($abo['prenom'] . ' ' . $abo['nom']) ?></strong>
                                                        <br><small class="text-muted"><?= htmlspecialchars($abo['email']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $planName = 'Gratuit';
                                                $realStatus = $abo['subscription_status'];
                                                
                                                if ($realStatus === 'Actif') {
                                                    if ($abo['stripe_plan'] && getStripeConfig($abo['stripe_plan'])) {
                                                        $planName = getStripeConfig($abo['stripe_plan'])['name'];
                                                    } elseif ($abo['subscription_end_date'] && $abo['subscription_start_date']) {
                                                        $start = new DateTime($abo['subscription_start_date']);
                                                        $end = new DateTime($abo['subscription_end_date']);
                                                        $months = $start->diff($end)->m + ($start->diff($end)->y * 12);
                                                        
                                                        if ($months <= 1) $planName = 'Mensuel';
                                                        elseif ($months <= 3) $planName = 'Trimestriel';
                                                        else $planName = 'Annuel';
                                                    } else {
                                                        $planName = 'Premium';
                                                    }
                                                }
                                                
                                                $badgeColor = $planName === 'Gratuit' ? 'secondary' : 'info';
                                                ?>
                                                <span class="badge bg-<?= $badgeColor ?>">
                                                    <?= $planName ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $realStatus = $abo['subscription_status'];
                                                $badgeClass = match($realStatus) {
                                                    'Actif' => 'success',
                                                    'Gratuit' => 'secondary',
                                                    'Inactif' => 'danger',
                                                    'Expiré' => 'warning',
                                                    default => 'secondary'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $badgeClass ?>">
                                                    <?= $realStatus ?>
                                                </span>
                                            </td>
                                            <td><?= $abo['subscription_start_date'] ? date('d/m/Y', strtotime($abo['subscription_start_date'])) : '-' ?></td>
                                            <td><?= $abo['subscription_end_date'] ? date('d/m/Y', strtotime($abo['subscription_end_date'])) : '-' ?></td>
                                            <td>
                                                <?php if ($realStatus === 'Actif' && $abo['days_remaining'] !== null): ?>
                                                    <?php if ($abo['days_remaining'] <= 7): ?>
                                                        <span class="text-danger fw-bold">
                                                            <i class="bi bi-exclamation-triangle"></i> <?= $abo['days_remaining'] ?> jours
                                                        </span>
                                                    <?php else: ?>
                                                        <?= $abo['days_remaining'] ?> jours
                                                    <?php endif; ?>
                                                <?php elseif ($realStatus === 'Gratuit'): ?>
                                                    <span class="text-muted">Illimité</span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($realStatus === 'Gratuit' || $realStatus === 'Expiré'): ?>
                                                        <button class="btn btn-success btn-action" 
                                                                onclick="renouvelerGratuit(<?= $abo['id'] ?>)"
                                                                title="Renouveler gratuit (1 an)">
                                                            <i class="bi bi-arrow-clockwise"></i>
                                                        </button>
                                                        <button class="btn btn-primary btn-action" 
                                                                onclick="activerPremium(<?= $abo['id'] ?>)"
                                                                title="Activer premium">
                                                            <i class="bi bi-star-fill"></i>
                                                        </button>
                                                    <?php elseif ($realStatus === 'Actif'): ?>
                                                        <button class="btn btn-warning btn-action" 
                                                                onclick="suspendreAbonnement(<?= $abo['id'] ?>)"
                                                                title="Suspendre">
                                                            <i class="bi bi-pause-circle"></i>
                                                        </button>
                                                        <button class="btn btn-info btn-action" 
                                                                onclick="prolongerAbonnement(<?= $abo['id'] ?>)"
                                                                title="Prolonger">
                                                            <i class="bi bi-plus-circle"></i>
                                                        </button>
                                                    <?php elseif ($realStatus === 'Inactif'): ?>
                                                        <button class="btn btn-success btn-action" 
                                                                onclick="reactiverAbonnement(<?= $abo['id'] ?>)"
                                                                title="Réactiver">
                                                            <i class="bi bi-play-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                        <?php if (empty($recent_payments)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
                                <p class="text-muted mt-2">Aucun paiement</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_payments as $payment): ?>
                                <div class="d-flex align-items-center p-3 border-bottom">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?= htmlspecialchars($payment['prenom'] . ' ' . $payment['nom']) ?></div>
                                        <small class="text-muted">
                                            <?= date('d/m H:i', strtotime($payment['created_at'])) ?>
                                            <?php if ($payment['plan']): ?>
                                                - <?= getStripeConfig($payment['plan'])['name'] ?? ucfirst($payment['plan']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success"><?= number_format($payment['montant'], 2) ?>€</div>
                                        <span class="badge bg-<?= $payment['status'] === 'succeeded' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($payment['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function approveRequest(requestId) {
            if (confirm('Approuver cette demande d\'abonnement ?')) {
                fetch('<?= url('api/admin-subscriptions.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'approuver', id: requestId })
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
        }
        
        function rejectRequest(requestId) {
            const motif = prompt('Motif du refus (optionnel):');
            if (motif !== null) {
                fetch('<?= url('api/admin-subscriptions.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'refuser', id: requestId, motif: motif || 'Demande refusée par l\'administrateur' })
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
        }
        
        function renouvelerGratuit(userId) {
            if (confirm('Renouveler l\'abonnement gratuit pour 1 an ?')) {
                actionAbonnement('renouveler_gratuit', { user_id: userId });
            }
        }
        
        function activerPremium(userId) {
            const plan = prompt('Plan premium (monthly/quarterly/yearly):', 'monthly');
            const duree = prompt('Durée en mois:', '12');
            
            if (plan && duree) {
                actionAbonnement('activer_premium', { 
                    user_id: userId, 
                    plan: plan, 
                    duree: parseInt(duree) 
                });
            }
        }
        
        function suspendreAbonnement(userId) {
            const motif = prompt('Motif de la suspension:');
            if (motif) {
                actionAbonnement('suspendre_abonnement', { 
                    user_id: userId, 
                    motif: motif 
                });
            }
        }
        
        function reactiverAbonnement(userId) {
            if (confirm('Réactiver cet abonnement ?')) {
                actionAbonnement('reactiver_abonnement', { user_id: userId });
            }
        }
        
        function prolongerAbonnement(userId) {
            const duree = prompt('Prolonger de combien de mois ?', '1');
            if (duree) {
                actionAbonnement('prolonger_abonnement', { 
                    user_id: userId, 
                    duree: parseInt(duree) 
                });
            }
        }
        
        function actionAbonnement(action, data) {
            fetch('<?= url('api/admin-subscription-actions.php') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: action, ...data })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
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
    </script>
</body>
</html>
