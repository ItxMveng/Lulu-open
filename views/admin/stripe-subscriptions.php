<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

// Filtres
$statut_filter = $_GET['statut'] ?? 'Actif';
$search = $_GET['search'] ?? '';

$db = Database::getInstance();

// Requête pour les abonnements Stripe
$sql = "SELECT u.id, u.prenom, u.nom, u.email, u.photo_profil, u.type_utilisateur,
               u.subscription_status, u.subscription_start_date, u.subscription_end_date,
               DATEDIFF(u.subscription_end_date, NOW()) as days_remaining
        FROM utilisateurs u 
        WHERE u.subscription_status IS NOT NULL";
$params = [];

if ($statut_filter && $statut_filter !== 'tous') {
    $sql .= " AND u.subscription_status = ?";
    $params[] = $statut_filter;
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
    'total_actifs' => $db->fetch("SELECT COUNT(*) as count FROM utilisateurs WHERE subscription_status = 'Actif' AND subscription_end_date > NOW()")['count'],
    'expire_7j' => $db->fetch("SELECT COUNT(*) as count FROM utilisateurs WHERE subscription_status = 'Actif' AND subscription_end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)")['count'],
    'revenus_mois' => $db->fetch("SELECT COALESCE(SUM(montant), 0) as total FROM paiements_stripe WHERE status = 'succeeded' AND MONTH(created_at) = MONTH(NOW())")['total']
];

// Paiements récents
$recent_payments = $db->fetchAll("
    SELECT ps.*, u.prenom, u.nom, u.email 
    FROM paiements_stripe ps 
    JOIN utilisateurs u ON ps.utilisateur_id = u.id 
    ORDER BY ps.created_at DESC 
    LIMIT 10
");

$page_title = "Abonnements Stripe - Admin LULU-OPEN";
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
    }
    
    .badge-active { background: #e8f5e8; color: #388e3c; }
    .badge-cancelled { background: #ffebee; color: #d32f2f; }
    .badge-expired { background: #fff3e0; color: #f57c00; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>
    
    <div class="admin-content">
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-2" style="color: var(--primary-dark); font-weight: 700;">
                    <i class="bi bi-credit-card-fill me-2"></i>Abonnements Stripe
                </h1>
                <p class="text-muted">Gestion des abonnements automatisés</p>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-shield-check-fill text-success" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= number_format($stats['total_actifs']) ?></h3>
                            <small class="text-muted">Abonnements actifs</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= number_format($stats['expire_7j']) ?></h3>
                            <small class="text-muted">Expirent dans 7j</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-currency-euro text-primary" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= number_format($stats['revenus_mois'], 2) ?>€</h3>
                            <small class="text-muted">Revenus ce mois</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
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
        
        <!-- Liste des abonnements -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul me-2"></i>
                            Abonnements (<?= count($abonnements) ?>)
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Statut</th>
                                    <th>Début</th>
                                    <th>Fin</th>
                                    <th>Jours restants</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($abonnements)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
                                            <p class="text-muted mt-2">Aucun abonnement trouvé</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($abonnements as $abo): ?>
                                        <tr class="<?= $abo['days_remaining'] <= 7 && $abo['subscription_status'] === 'active' ? 'table-warning' : '' ?>">
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
                                                $badgeClass = match($abo['subscription_status']) {
                                                    'active' => 'success',
                                                    'cancelled' => 'danger',
                                                    'expired' => 'warning',
                                                    default => 'secondary'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $badgeClass ?>">
                                                    <?= ucfirst($abo['subscription_status']) ?>
                                                </span>
                                            </td>
                                            <td><?= $abo['subscription_start_date'] ? date('d/m/Y', strtotime($abo['subscription_start_date'])) : '-' ?></td>
                                            <td><?= $abo['subscription_end_date'] ? date('d/m/Y', strtotime($abo['subscription_end_date'])) : '-' ?></td>
                                            <td>
                                                <?php if ($abo['subscription_status'] === 'active' && $abo['days_remaining'] !== null): ?>
                                                    <?php if ($abo['days_remaining'] <= 7): ?>
                                                        <span class="text-danger fw-bold">
                                                            <i class="bi bi-exclamation-triangle"></i> <?= $abo['days_remaining'] ?> jours
                                                        </span>
                                                    <?php else: ?>
                                                        <?= $abo['days_remaining'] ?> jours
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
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
                            <i class="bi bi-credit-card me-2"></i>
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>