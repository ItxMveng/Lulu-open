<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

// Filtres
$statut_filter = $_GET['statut'] ?? 'valide';
$methode_filter = $_GET['methode'] ?? '';
$search = $_GET['search'] ?? '';
$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');

// Requête avec filtres
$db = Database::getInstance()->getConnection();
$sql = "SELECT p.*, 
        CONCAT(u.prenom, ' ', u.nom) as nom_utilisateur, 
        u.email, u.photo_profil,
        pl.nom as plan_nom, 
        a.id as abonnement_id
        FROM paiements p
        JOIN utilisateurs u ON p.utilisateur_id = u.id
        LEFT JOIN abonnements a ON p.abonnement_id = a.id
        LEFT JOIN plans_abonnement pl ON a.plan_id = pl.id
        WHERE 1=1";
$params = [];

if ($statut_filter && $statut_filter !== 'tous') {
    $sql .= " AND p.statut = ?";
    $params[] = $statut_filter;
}
if ($methode_filter) {
    $sql .= " AND p.methode_paiement = ?";
    $params[] = $methode_filter;
}
if ($search) {
    $sql .= " AND (u.prenom LIKE ? OR u.nom LIKE ? OR u.email LIKE ? OR p.transaction_id LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}
if ($date_debut) {
    $sql .= " AND DATE(p.date_paiement) >= ?";
    $params[] = $date_debut;
}
if ($date_fin) {
    $sql .= " AND DATE(p.date_paiement) <= ?";
    $params[] = $date_fin;
}

$sql .= " ORDER BY p.date_paiement DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_transactions,
        COALESCE(SUM(CASE WHEN statut = 'valide' THEN montant ELSE 0 END), 0) as revenus_valides,
        COALESCE(SUM(CASE WHEN statut = 'rembourse' THEN montant ELSE 0 END), 0) as total_rembourses,
        COALESCE(SUM(CASE WHEN statut = 'echoue' THEN 1 ELSE 0 END), 0) as total_echecs
    FROM paiements
    WHERE DATE(date_paiement) BETWEEN ? AND ?
");
$stmt->execute([$date_debut, $date_fin]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = "Gestion Paiements - Admin LULU-OPEN";
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
        <div class="row mb-4" data-aos="fade-down">
            <div class="col-md-6">
                <h1 class="mb-2" style="color: var(--primary-dark); font-weight: 700;">
                    <i class="bi bi-credit-card-fill me-2"></i>Gestion des Paiements
                </h1>
                <p class="text-muted">Transactions et historique financier</p>
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
                            <small class="text-muted">Remboursés</small>
                            <h4 class="mb-0 text-warning"><?= number_format($stats['total_rembourses'], 2) ?>€</h4>
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
        <div class="card-custom mb-4" data-aos="fade-up">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Rechercher</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Nom, email, transaction ID..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="valide" <?= $statut_filter === 'valide' ? 'selected' : '' ?>>Validés</option>
                            <option value="en_attente" <?= $statut_filter === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="echoue" <?= $statut_filter === 'echoue' ? 'selected' : '' ?>>Échoués</option>
                            <option value="rembourse" <?= $statut_filter === 'rembourse' ? 'selected' : '' ?>>Remboursés</option>
                            <option value="annule" <?= $statut_filter === 'annule' ? 'selected' : '' ?>>Annulés</option>
                            <option value="tous" <?= $statut_filter === 'tous' ? 'selected' : '' ?>>Tous</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Méthode</label>
                        <select name="methode" class="form-select">
                            <option value="">Toutes</option>
                            <option value="stripe" <?= $methode_filter === 'stripe' ? 'selected' : '' ?>>Stripe</option>
                            <option value="paypal" <?= $methode_filter === 'paypal' ? 'selected' : '' ?>>PayPal</option>
                            <option value="virement" <?= $methode_filter === 'virement' ? 'selected' : '' ?>>Virement</option>
                            <option value="autre" <?= $methode_filter === 'autre' ? 'selected' : '' ?>>Autre</option>
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
                    
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
                
                <div class="d-flex gap-2 mt-3">
                    <a href="<?= url('views/admin/payments.php') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Réinitialiser
                    </a>
                    <button onclick="exportCSV()" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-download"></i> Export CSV
                    </button>
                    <button onclick="printReport()" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Tableau paiements -->
        <div class="card-custom" data-aos="fade-up" data-aos-delay="100">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: var(--gradient-primary); color: white;">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Utilisateur</th>
                            <th>Plan</th>
                            <th>Montant</th>
                            <th>Méthode</th>
                            <th>Statut</th>
                            <th>Transaction</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($paiements)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: var(--text-muted);"></i>
                                    <p class="text-muted mt-3">Aucun paiement trouvé</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($paiements as $paiement): ?>
                                <tr>
                                    <td class="fw-bold">#<?= $paiement['id'] ?></td>
                                    
                                    <td>
                                        <?= date('d/m/Y', strtotime($paiement['date_paiement'])) ?>
                                        <br><small class="text-muted"><?= date('H:i', strtotime($paiement['date_paiement'])) ?></small>
                                    </td>
                                    
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php
                                            $photo = $paiement['photo_profil'] ?? null;
                                            if ($photo) {
                                                if (strpos($photo, 'http') === 0) {
                                                    $photoUrl = $photo;
                                                } elseif (strpos($photo, 'uploads/') === 0) {
                                                    $photoUrl = url($photo);
                                                } elseif (strpos($photo, 'profiles/') === 0) {
                                                    $photoUrl = url('uploads/' . $photo);
                                                } else {
                                                    $photoUrl = url('uploads/profiles/' . $photo);
                                                }
                                            } else {
                                                $photoUrl = url('assets/images/default-avatar.png');
                                            }
                                            ?>
                                            <img src="<?= $photoUrl ?>" 
                                                 class="rounded-circle me-2"
                                                 style="width: 35px; height: 35px; object-fit: cover;"
                                                 onerror="this.src='<?= url('assets/images/default-avatar.png') ?>'">
                                            <div>
                                                <strong><?= htmlspecialchars($paiement['nom_utilisateur']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($paiement['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td><?= htmlspecialchars($paiement['plan_nom'] ?? 'N/A') ?></td>
                                    
                                    <td class="fw-bold" style="color: <?= $paiement['statut'] === 'rembourse' ? '#dc3545' : '#28a745' ?>;">
                                        <?= $paiement['statut'] === 'rembourse' ? '-' : '' ?><?= number_format($paiement['montant'], 2) ?>€
                                    </td>
                                    
                                    <td>
                                        <?php
                                        $methodeIcon = match($paiement['methode_paiement']) {
                                            'stripe' => 'bi-credit-card',
                                            'paypal' => 'bi-paypal',
                                            'virement' => 'bi-bank',
                                            default => 'bi-cash'
                                        };
                                        ?>
                                        <i class="bi <?= $methodeIcon ?> me-1"></i>
                                        <?= ucfirst($paiement['methode_paiement']) ?>
                                    </td>
                                    
                                    <td>
                                        <?php
                                        $badgeClass = match($paiement['statut']) {
                                            'valide' => 'success',
                                            'en_attente' => 'warning',
                                            'echoue' => 'danger',
                                            'rembourse' => 'info',
                                            'annule' => 'dark',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>">
                                            <?= ucfirst($paiement['statut']) ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <small class="text-muted font-monospace">
                                            <?= $paiement['transaction_id'] ? substr($paiement['transaction_id'], 0, 15) . '...' : '-' ?>
                                        </small>
                                    </td>
                                    
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-action" 
                                                    onclick="showDetails(<?= $paiement['id'] ?>)"
                                                    title="Détails">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            
                                            <button class="btn btn-outline-secondary btn-action" 
                                                    onclick="downloadInvoice(<?= $paiement['id'] ?>)"
                                                    title="Facture PDF">
                                                <i class="bi bi-file-pdf"></i>
                                            </button>
                                            
                                            <?php if ($paiement['statut'] === 'en_attente'): ?>
                                                <button class="btn btn-outline-success btn-action" 
                                                        onclick="validatePayment(<?= $paiement['id'] ?>)"
                                                        title="Valider">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($paiement['statut'] === 'valide' && empty($paiement['date_remboursement'])): ?>
                                                <button class="btn btn-outline-danger btn-action" 
                                                        onclick="openRefundModal(<?= $paiement['id'] ?>, <?= $paiement['montant'] ?>)"
                                                        title="Rembourser">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
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
            
            <?php if (count($paiements) > 0): ?>
                <div class="card-footer bg-white text-center">
                    <small class="text-muted">
                        Affichage de <?= count($paiements) ?> transaction(s)
                    </small>
                </div>
            <?php endif; ?>
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
