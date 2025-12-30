<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

// Filtres
$statut_filter = $_GET['statut'] ?? 'actif';
$plan_filter = $_GET['plan'] ?? '';
$search = $_GET['search'] ?? '';

// Requête avec filtres
$db = Database::getInstance()->getConnection();
$sql = "SELECT a.*, 
        CONCAT(u.prenom, ' ', u.nom) as nom_utilisateur, 
        u.email, u.photo_profil, u.type_utilisateur,
        p.nom as plan_nom, p.prix_mensuel, p.prix_annuel,
        a.prix as montant,
        a.auto_renouvellement as renouvellement_auto,
        NULL as date_prochaine_facturation
        FROM abonnements a
        JOIN utilisateurs u ON a.utilisateur_id = u.id
        JOIN plans_abonnement p ON a.plan_id = p.id
        WHERE 1=1";
$params = [];

if ($statut_filter && $statut_filter !== 'tous') {
    $sql .= " AND a.statut = ?";
    $params[] = $statut_filter;
}
if ($plan_filter) {
    $sql .= " AND a.plan_id = ?";
    $params[] = $plan_filter;
}
if ($search) {
    $sql .= " AND (u.prenom LIKE ? OR u.nom LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY a.date_fin ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$abonnements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stmt = $db->query("SELECT COUNT(*) FROM abonnements WHERE statut = 'actif'");
$total_actifs = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM abonnements WHERE statut = 'actif' AND date_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
$expirant_7j = $stmt->fetchColumn();

$stmt = $db->query("SELECT COALESCE(SUM(montant), 0) FROM paiements WHERE statut = 'valide' AND MONTH(date_paiement) = MONTH(CURDATE())");
$revenus_mois = $stmt->fetchColumn();

// Liste plans pour filtre
$stmt = $db->query("SELECT id, nom FROM plans_abonnement WHERE actif = 1 ORDER BY ordre_affichage");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Gestion Abonnements - Admin LULU-OPEN";
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
    
    .table-warning {
        background-color: rgba(255, 193, 7, 0.1) !important;
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
                    <li class="breadcrumb-item active">Abonnements</li>
                </ol>
            </div>
        </nav>
        
        <!-- En-tête + Stats -->
        <div class="row mb-4" data-aos="fade-down">
            <div class="col-md-6">
                <h1 class="mb-2" style="color: var(--primary-dark); font-weight: 700;">
                    <i class="bi bi-award-fill me-2"></i>Gestion des Abonnements
                </h1>
                <p class="text-muted">Gérer les abonnements et renouvellements</p>
            </div>
            <div class="col-md-6">
                <div class="row g-2">
                    <div class="col-md-4">
                        <div class="stat-mini-card">
                            <small class="text-muted">Actifs</small>
                            <h4 class="mb-0 text-success"><?= $total_actifs ?></h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-mini-card">
                            <small class="text-muted">Expirent 7j</small>
                            <h4 class="mb-0 text-warning"><?= $expirant_7j ?></h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-mini-card">
                            <small class="text-muted">Revenus mois</small>
                            <h4 class="mb-0 text-primary"><?= number_format($revenus_mois, 2) ?>€</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filtres + Actions -->
        <div class="card-custom mb-4" data-aos="fade-up">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <!-- Recherche -->
                    <div class="col-md-3">
                        <label class="form-label">Rechercher</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Nom, email..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <!-- Statut -->
                    <div class="col-md-3">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="actif" <?= $statut_filter === 'actif' ? 'selected' : '' ?>>Actifs</option>
                            <option value="essai" <?= $statut_filter === 'essai' ? 'selected' : '' ?>>Essai gratuit</option>
                            <option value="suspendu" <?= $statut_filter === 'suspendu' ? 'selected' : '' ?>>Suspendus</option>
                            <option value="expire" <?= $statut_filter === 'expire' ? 'selected' : '' ?>>Expirés</option>
                            <option value="annule" <?= $statut_filter === 'annule' ? 'selected' : '' ?>>Annulés</option>
                            <option value="tous" <?= $statut_filter === 'tous' ? 'selected' : '' ?>>Tous</option>
                        </select>
                    </div>
                    
                    <!-- Plan -->
                    <div class="col-md-3">
                        <label class="form-label">Plan</label>
                        <select name="plan" class="form-select">
                            <option value="">Tous les plans</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?= $plan['id'] ?>" <?= $plan_filter == $plan['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($plan['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Actions -->
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary-custom flex-grow-1">
                            <i class="bi bi-search"></i> Filtrer
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="exportCSV()">
                            <i class="bi bi-download"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Liste abonnements -->
        <div class="card-custom" data-aos="fade-up" data-aos-delay="100">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: var(--gradient-primary); color: white;">
                        <tr>
                            <th>Utilisateur</th>
                            <th>Plan</th>
                            <th>Statut</th>
                            <th>Date début</th>
                            <th>Date fin</th>
                            <th>Montant</th>
                            <th>Renouvellement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($abonnements)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: var(--text-muted);"></i>
                                    <p class="text-muted mt-3">Aucun abonnement trouvé</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($abonnements as $abo): ?>
                                <?php
                                // Calcul jours restants
                                $today = new DateTime();
                                $dateFin = new DateTime($abo['date_fin']);
                                $joursRestants = $today->diff($dateFin)->days;
                                $isExpireSoon = $joursRestants <= 7 && $abo['statut'] === 'actif';
                                ?>
                                <tr class="<?= $isExpireSoon ? 'table-warning' : '' ?>">
                                    <!-- Utilisateur -->
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php
                                            $photo = $abo['photo_profil'] ?? null;
                                            if ($photo) {
                                                if (strpos($photo, 'http') === 0) {
                                                    // URL absolue
                                                    $photoUrl = $photo;
                                                } elseif (strpos($photo, 'uploads/') === 0) {
                                                    // Chemin déjà avec uploads/
                                                    $photoUrl = url($photo);
                                                } elseif (strpos($photo, 'profiles/') === 0) {
                                                    // Format: profiles/xxx.png -> uploads/profiles/xxx.png
                                                    $photoUrl = url('uploads/' . $photo);
                                                } else {
                                                    // Format: user_xxx.jpg -> uploads/profiles/user_xxx.jpg
                                                    $photoUrl = url('uploads/profiles/' . $photo);
                                                }
                                            } else {
                                                $photoUrl = url('assets/images/default-avatar.png');
                                            }
                                            ?>
                                            <img src="<?= $photoUrl ?>" 
                                                 class="rounded-circle me-2"
                                                 style="width: 40px; height: 40px; object-fit: cover;"
                                                 onerror="this.src='<?= url('assets/images/default-avatar.png') ?>'">
                                            <div>
                                                <strong><?= htmlspecialchars($abo['nom_utilisateur']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($abo['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- Plan -->
                                    <td>
                                        <strong><?= htmlspecialchars($abo['plan_nom']) ?></strong>
                                        <br><small class="text-muted">
                                            <?= ucfirst($abo['type_abonnement']) ?> - 
                                            <?= $abo['type_abonnement'] === 'mensuel' ? $abo['prix_mensuel'] : $abo['prix_annuel'] ?>€
                                        </small>
                                    </td>
                                    
                                    <!-- Statut -->
                                    <td>
                                        <?php
                                        $badgeClass = match($abo['statut']) {
                                            'actif' => 'success',
                                            'essai' => 'info',
                                            'suspendu' => 'warning',
                                            'expire' => 'danger',
                                            'annule' => 'dark',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>">
                                            <?= ucfirst($abo['statut']) ?>
                                        </span>
                                        <?php if ($isExpireSoon): ?>
                                            <br><small class="text-danger">
                                                <i class="bi bi-exclamation-triangle"></i> <?= $joursRestants ?>j restants
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Dates -->
                                    <td><?= date('d/m/Y', strtotime($abo['date_debut'])) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($abo['date_fin'])) ?>
                                        <?php if (isset($abo['date_prochaine_facturation']) && $abo['date_prochaine_facturation']): ?>
                                            <br><small class="text-muted">
                                                Fact: <?= date('d/m', strtotime($abo['date_prochaine_facturation'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Montant -->
                                    <td class="fw-bold text-success">
                                        <?= number_format($abo['montant'] ?? 0, 2) ?>€
                                    </td>
                                    
                                    <!-- Renouvellement auto -->
                                    <td>
                                        <?php if (isset($abo['renouvellement_auto']) && $abo['renouvellement_auto']): ?>
                                            <i class="bi bi-arrow-repeat text-success" title="Auto"></i> Oui
                                        <?php else: ?>
                                            <i class="bi bi-x-circle text-danger" title="Manuel"></i> Non
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Actions -->
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-action" 
                                                    onclick="showDetails(<?= $abo['id'] ?>)"
                                                    title="Détails">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            
                                            <?php if ($abo['statut'] === 'actif' || $abo['statut'] === 'essai'): ?>
                                                <button class="btn btn-outline-success btn-action" 
                                                        onclick="openProlongModal(<?= $abo['id'] ?>)"
                                                        title="Prolonger">
                                                    <i class="bi bi-plus-circle"></i>
                                                </button>
                                                <button class="btn btn-outline-warning btn-action" 
                                                        onclick="suspendAbonnement(<?= $abo['id'] ?>)"
                                                        title="Suspendre">
                                                    <i class="bi bi-pause-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($abo['statut'] === 'suspendu'): ?>
                                                <button class="btn btn-outline-success btn-action" 
                                                        onclick="reactiveAbonnement(<?= $abo['id'] ?>)"
                                                        title="Réactiver">
                                                    <i class="bi bi-play-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-outline-danger btn-action" 
                                                    onclick="openCancelModal(<?= $abo['id'] ?>)"
                                                    title="Résilier">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
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

    <!-- Modal Détails Abonnement -->
    <div class="modal fade" id="modalDetails" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--gradient-primary); color: white;">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle-fill me-2"></i>Détails de l'abonnement
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

    <!-- Modal Prolonger -->
    <div class="modal fade" id="modalProlong" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--gradient-primary); color: white;">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle-fill me-2"></i>Prolonger l'abonnement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formProlong">
                    <div class="modal-body">
                        <input type="hidden" id="abonnementIdProlong">
                        
                        <div class="mb-3">
                            <label class="form-label">Durée de prolongation</label>
                            <select class="form-select" id="dureeProlongation" required>
                                <option value="7">7 jours</option>
                                <option value="15">15 jours</option>
                                <option value="30" selected>1 mois (30 jours)</option>
                                <option value="90">3 mois (90 jours)</option>
                                <option value="365">1 an (365 jours)</option>
                                <option value="custom">Personnalisé</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="customDaysDiv" style="display:none;">
                            <label class="form-label">Nombre de jours</label>
                            <input type="number" class="form-control" id="customDays" min="1" max="3650">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Motif (optionnel)</label>
                            <textarea class="form-control" id="motifProlongation" rows="2"
                                      placeholder="Ex: Geste commercial, compensation panne..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> La date de fin sera automatiquement prolongée.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Prolonger
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Résilier -->
    <div class="modal fade" id="modalCancel" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-x-circle-fill me-2"></i>Résilier l'abonnement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formCancel">
                    <div class="modal-body">
                        <input type="hidden" id="abonnementIdCancel">
                        
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>Attention :</strong> Cette action est irréversible.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Motif de résiliation <span class="text-danger">*</span></label>
                            <select class="form-select" id="motifCancel" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="demande_client">Demande du client</option>
                                <option value="impaye">Impayé prolongé</option>
                                <option value="fraude">Fraude détectée</option>
                                <option value="violation_cgu">Violation CGU</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Commentaire interne</label>
                            <textarea class="form-control" id="commentaireCancel" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Type de résiliation</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="typeCancel" 
                                       id="cancelImmediate" value="immediate" checked>
                                <label class="form-check-label" for="cancelImmediate">
                                    Immédiate (bloque accès maintenant)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="typeCancel" 
                                       id="cancelEndPeriod" value="end_period">
                                <label class="form-check-label" for="cancelEndPeriod">
                                    À la fin de la période payée
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle"></i> Confirmer la résiliation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="<?= url('assets/js/admin-subscriptions.js') ?>"></script>
    <script>
        AOS.init({ duration: 600, once: true });
    </script>
</body>
</html>
