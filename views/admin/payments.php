<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/stripe.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

// Filtres
$statut_filter = $_GET['statut'] ?? 'succeeded';
$search = $_GET['search'] ?? '';
$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');

$db = Database::getInstance();

// Requête pour les paiements Stripe
$sql = "SELECT ps.*, 
        CONCAT(u.prenom, ' ', u.nom) as nom_utilisateur, 
        u.email, u.photo_profil
        FROM paiements_stripe ps
        JOIN utilisateurs u ON ps.utilisateur_id = u.id
        WHERE 1=1";
$params = [];

if ($statut_filter && $statut_filter !== 'tous') {
    $sql .= " AND ps.status = ?";
    $params[] = $statut_filter;
}

if ($search) {
    $sql .= " AND (u.prenom LIKE ? OR u.nom LIKE ? OR u.email LIKE ? OR ps.stripe_session_id LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($date_debut) {
    $sql .= " AND DATE(ps.created_at) >= ?";
    $params[] = $date_debut;
}

if ($date_fin) {
    $sql .= " AND DATE(ps.created_at) <= ?";
    $params[] = $date_fin;
}

$sql .= " ORDER BY ps.created_at DESC";

$paiements = $db->fetchAll($sql, $params);

// Statistiques
$stats = $db->fetch("
    SELECT 
        COUNT(*) as total_transactions,
        COALESCE(SUM(CASE WHEN status = 'succeeded' THEN montant ELSE 0 END), 0) as revenus_valides,
        COALESCE(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END), 0) as total_echecs,
        COALESCE(AVG(CASE WHEN status = 'succeeded' THEN montant ELSE NULL END), 0) as montant_moyen
    FROM paiements_stripe
    WHERE DATE(created_at) BETWEEN ? AND ?
", [$date_debut, $date_fin]);

$page_title = "Paiements Stripe - Admin LULU-OPEN";
?>
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
    
    .stat-mini-card {
        background: white;
        padding: 1rem;
        border-radius: 10px;
        box-shadow: var(--shadow-sm);
        text-align: center;
    }
    
    .btn-action {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .font-monospace {
        font-family: 'Courier New', monospace;
    }
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
                    <li class="breadcrumb-item active">Paiements</li>
                </ol>
            </div>
        </nav>
        
        <!-- En-tête + Stats -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h1 class="mb-2" style="color: var(--primary-dark); font-weight: 700;">
                    <i class="bi bi-credit-card-fill me-2"></i>Paiements Stripe
                </h1>
                <p class="text-muted">Transactions et historique Stripe</p>
            </div>
            <div class="col-md-6">
                <div class="row g-2">
                    <div class="col-md-3">
                        <div class="stat-mini-card">
                            <small class="text-muted">Transactions</small>
                            <h4 class="mb-0 text-primary"><?= $stats['total_transactions'] ?></h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-mini-card">
                            <small class="text-muted">Revenus</small>
                            <h4 class="mb-0 text-success"><?= number_format($stats['revenus_valides'], 2) ?>€</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-mini-card">
                            <small class="text-muted">Montant moyen</small>
                            <h4 class="mb-0 text-info"><?= number_format($stats['montant_moyen'], 2) ?>€</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-mini-card">
                            <small class="text-muted">Échecs</small>
                            <h4 class="mb-0 text-danger"><?= $stats['total_echecs'] ?></h4>
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
                               placeholder="Nom, email, session ID..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="succeeded" <?= $statut_filter === 'succeeded' ? 'selected' : '' ?>>Réussis</option>
                            <option value="pending" <?= $statut_filter === 'pending' ? 'selected' : '' ?>>En attente</option>
                            <option value="failed" <?= $statut_filter === 'failed' ? 'selected' : '' ?>>Échoués</option>
                            <option value="tous" <?= $statut_filter === 'tous' ? 'selected' : '' ?>>Tous</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Du</label>
                        <input type="date" name="date_debut" class="form-control" value="<?= $date_debut ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Au</label>
                        <input type="date" name="date_fin" class="form-control" value="<?= $date_fin ?>">
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tableau paiements -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Utilisateur</th>
                            <th>Plan</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Session Stripe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($paiements)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                    <p class="text-muted mt-3">Aucun paiement trouvé</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($paiements as $paiement): ?>
                                <tr>
                                    <td class="fw-bold">#<?= $paiement['id'] ?></td>
                                    
                                    <td>
                                        <?= date('d/m/Y', strtotime($paiement['created_at'])) ?>
                                        <br><small class="text-muted"><?= date('H:i', strtotime($paiement['created_at'])) ?></small>
                                    </td>
                                    
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= url('assets/images/default-avatar.png') ?>" 
                                                 class="rounded-circle me-2"
                                                 style="width: 32px; height: 32px; object-fit: cover;">
                                            <div>
                                                <strong><?= htmlspecialchars($paiement['nom_utilisateur']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($paiement['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <?php 
                                        $planConfig = getStripeConfig($paiement['plan']);
                                        ?>
                                        <span class="badge bg-info">
                                            <?= $planConfig ? $planConfig['name'] : ucfirst($paiement['plan']) ?>
                                        </span>
                                        <?php if ($planConfig): ?>
                                        <br><small class="text-muted"><?= $planConfig['description'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="fw-bold text-success">
                                        <?= number_format($paiement['montant'], 2) ?>€
                                    </td>
                                    
                                    <td>
                                        <?php
                                        $badgeClass = match($paiement['status']) {
                                            'succeeded' => 'success',
                                            'pending' => 'warning',
                                            'failed' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>">
                                            <?= ucfirst($paiement['status']) ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <small class="text-muted font-monospace">
                                            <?= $paiement['stripe_session_id'] ? substr($paiement['stripe_session_id'], 0, 20) . '...' : '-' ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Détails Paiement -->
    <div class="modal fade" id="modalDetails" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--gradient-primary); color: white;">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle-fill me-2"></i>Détails du paiement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Remboursement -->
    <div class="modal fade" id="modalRefund" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Rembourser le paiement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRefund">
                    <div class="modal-body">
                        <input type="hidden" id="paiementIdRefund">
                        <input type="hidden" id="montantMaxRefund">
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Attention :</strong> Le remboursement sera traité via Stripe/PayPal et l'utilisateur sera notifié.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Type de remboursement</label>
                            <select class="form-select" id="typeRefund" required>
                                <option value="total">Remboursement total</option>
                                <option value="partiel">Remboursement partiel</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="montantPartielDiv" style="display: none;">
                            <label class="form-label">Montant à rembourser (€)</label>
                            <input type="number" class="form-control" id="montantPartiel" 
                                   min="0.01" step="0.01" placeholder="0.00">
                            <small class="text-muted">Maximum : <span id="montantMaxDisplay"></span>€</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Motif du remboursement <span class="text-danger">*</span></label>
                            <select class="form-select mb-2" id="motifRefundSelect" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="demande_client">Demande du client</option>
                                <option value="erreur_facturation">Erreur de facturation</option>
                                <option value="service_non_rendu">Service non rendu</option>
                                <option value="annulation_abonnement">Annulation d'abonnement</option>
                                <option value="geste_commercial">Geste commercial</option>
                                <option value="autre">Autre (préciser ci-dessous)</option>
                            </select>
                            <textarea class="form-control" id="motifRefundTexte" rows="3"
                                      placeholder="Détails supplémentaires (optionnel)..."></textarea>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="notifierUtilisateur" checked>
                            <label class="form-check-label" for="notifierUtilisateur">
                                Notifier l'utilisateur par email
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-arrow-counterclockwise"></i> Confirmer le remboursement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="<?= url('assets/js/admin-payments.js') ?>"></script>
    <script>
        AOS.init({ duration: 600, once: true });
    </script>
</body>
</html>
